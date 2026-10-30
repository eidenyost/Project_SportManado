<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Organizer;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class EventController extends Controller
{
    // -------------------------------------------------------------------------
    // PUBLIC FACING
    // -------------------------------------------------------------------------

    /**
     * List all published / active events (public).
     */
    public function index(Request $request)
    {
        $query = Event::with('organizer')
            ->withCount(['tickets' => function ($q) {
                $q->whereIn('status', ['pending', 'active', 'used']);
            }])
            ->where('status', 'active');

        // Optional keyword search
        if (($search = \App\Support\Search::term($request)) !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%");
            });
        }

        // Optional category filter
        if ($category = $request->get('category')) {
            $query->where('category', $category);
        }

        // Optional price filter
        if ($price = $request->get('price')) {
            if ($price === 'gratis') {
                $query->where('ticket_price', 0);
            } elseif ($price === 'berbayar') {
                $query->where('ticket_price', '>', 0);
            }
        }

        // Optional date filter
        if ($date = $request->get('date')) {
            $query->whereDate('event_date', $date);
        }

        // Optional sorting
        $sort = $request->get('sort', 'terbaru');
        if ($sort === 'terdekat') {
            $query->orderBy('event_date', 'asc');
        } elseif ($sort === 'harga_terendah') {
            $query->orderBy('ticket_price', 'asc');
        } else {
            $query->latest('event_date');
        }

        $categories = ['Lari & Trail', 'Padel / Tenis', 'E-Sports', 'Bersepeda', 'Futsal', 'Basket', 'Bulutangkis', 'Olahraga Lainnya'];

        $events = $query->paginate(12);

        return view('events.index', compact('events', 'categories'));
    }

    /**
     * Show a single event detail page (public).
     */
    public function show(Event $event)
    {
        if ($event->status !== 'active') {
            return redirect()->route('events.index')
                ->with('error', 'Event tidak ditemukan atau belum disetujui.');
        }

        $event->load(['organizer', 'tickets']);

        $userTicket = null;
        if (Auth::check()) {
            $userTicket = Ticket::where('event_id', $event->id)
                ->where('user_id', Auth::id())
                ->with('payment')
                ->first();
        }

        $isFull             = $event->isFull();
        $participantCount   = $event->allocatedTicketCount();

        return view('events.show', compact('event', 'userTicket', 'isFull', 'participantCount'));
    }

    // -------------------------------------------------------------------------
    // ORGANIZER CRUD
    // -------------------------------------------------------------------------

    public function organizerIndex(Request $request)
    {
        $user = auth()->user();
        $organizer = Organizer::where('user_id', $user->id)->first();

        abort_if(!$organizer, 403, 'Anda bukan penyelenggara resmi.');

        // Status counts untuk filter chips (sebelum filter)
        $statusCounts = Event::where('organizer_id', $organizer->id)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        // Query utama dengan filter
        $query = Event::withCount(['tickets' => function ($q) {
                $q->whereIn('status', ['pending', 'active', 'used']);
            }])
            ->where('organizer_id', $organizer->id);

        if (($search = \App\Support\Search::term($request)) !== '') {
            $query->where('title', 'like', "%{$search}%");
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $events = $query->latest('event_date')->paginate(10)->withQueryString();

        return view('organizer.events.index', compact('events', 'statusCounts'));
    }

    /**
     * Show the form to create a new event (organizer only).
     */
    public function create()
    {
        $organizer = Organizer::where('user_id', Auth::id())->firstOrFail();

        return view('organizer.events.create', compact('organizer'));
    }

    /**
     * Persist a new event to the database (organizer only).
     */
    public function store(Request $request)
    {
        $organizer = Organizer::where('user_id', Auth::id())->firstOrFail();

        $validated = $request->validate([
            'title'                 => ['required', 'string', 'max:255'],
            'description'           => ['required', 'string'],
            'location'              => ['required', 'string', 'max:255'],
            'event_date'            => ['required', 'date', 'after:now'],
            'event_end_at'          => ['nullable', 'date', 'after:event_date'],
            'registration_deadline' => ['required', 'date', 'before:event_date'],
            'max_participants'      => ['required', 'integer', 'min:1', 'max:100000'],
            'ticket_price'          => $this->ticketPriceRules(),
            'category'              => ['required', 'string', 'max:100', Rule::in(['Lari & Trail', 'Padel / Tenis', 'E-Sports', 'Bersepeda', 'Futsal', 'Basket', 'Bulutangkis', 'Olahraga Lainnya'])],
            'banner'                => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'proposal'              => ['required', 'file', 'mimetypes:application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/msword', 'max:10240'],
            'permit'                => ['required', 'file', 'mimetypes:application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/msword', 'max:10240'],
            'ktp_image'             => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:4096'],
            'auto_approve'          => ['nullable', 'boolean'],
            'custom_fields'         => ['nullable', 'array', 'max:20'],
            'custom_fields.*'       => ['array'],
            'custom_fields.*.label' => ['required', 'string', 'max:100'],
            'custom_fields.*.type'  => ['required', Rule::in(['text', 'textarea', 'radio'])],
            'custom_fields.*.options' => ['nullable', 'string', 'max:500'],
            'custom_fields.*.required' => ['nullable', 'boolean'],
            'custom_fields.*.image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ], $this->ticketPriceMessages());

        $bannerPath = null;
        if ($request->hasFile('banner')) {
            $bannerPath = $request->file('banner')->store('event-banners', 'public');
        }

        $proposalPath = null;
        if ($request->hasFile('proposal')) {
            $proposalPath = $request->file('proposal')->store('event-proposals', 'local');
        }

        $permitPath = null;
        if ($request->hasFile('permit')) {
            $permitPath = $request->file('permit')->store('event-permits', 'local');
        }

        $ktpPath = null;
        if ($request->hasFile('ktp_image')) {
            $ktpPath = $request->file('ktp_image')->store('organizer-ktps', 'local');
        }

        $customFieldsData = $this->normalizeCustomFields($request, $validated['custom_fields'] ?? []);

        Event::create([
            'organizer_id'          => $organizer->id,
            'title'                 => $validated['title'],
            'description'           => $validated['description'],
            'location'              => $validated['location'],
            'event_date'            => $validated['event_date'],
            'event_end_at'          => $validated['event_end_at'] ?? null,
            'registration_deadline' => $validated['registration_deadline'],
            'max_participants'      => $validated['max_participants'],
            'ticket_price'          => $validated['ticket_price'],
            'category'              => $validated['category'],
            'banner'                => $bannerPath,
            'proposal'              => $proposalPath,
            'permit'                => $permitPath,
            'ktp_image'             => $ktpPath,
            'status'                => 'pending', // Starts as pending admin approval
            'auto_approve'          => $request->boolean('auto_approve'),
            'custom_fields'         => $customFieldsData,
        ]);

        return redirect()->route('organizer.dashboard')
            ->with('success', 'Event berhasil didaftarkan! Tunggu proses evaluasi & persetujuan Admin dalam 1x24 jam.');
    }

    /**
     * Show the edit form for an existing event (organizer only, owns event).
     */
    public function edit(Event $event)
    {
        $this->authorizeOrganizer($event);

        return view('organizer.events.edit', compact('event'));
    }

    /**
     * Update an existing event (organizer only, owns event).
     */
    public function update(Request $request, Event $event)
    {
        $this->authorizeOrganizer($event);

        $allowedStatuses = $event->status === 'active'
            ? 'pending,active,draft,cancelled,completed'
            : 'pending,draft,cancelled,completed';

        $validated = $request->validate([
            'title'                 => ['required', 'string', 'max:255'],
            'description'           => ['required', 'string'],
            'location'              => ['required', 'string', 'max:255'],
            'event_date'            => ['required', 'date'],
            'event_end_at'          => ['nullable', 'date', 'after:event_date'],
            'registration_deadline' => ['required', 'date', 'before:event_date'],
            'max_participants'      => ['required', 'integer', 'min:1', 'max:100000'],
            'ticket_price'          => $this->ticketPriceRules(),
            'category'              => ['required', 'string', 'max:100', Rule::in(['Lari & Trail', 'Padel / Tenis', 'E-Sports', 'Bersepeda', 'Futsal', 'Basket', 'Bulutangkis', 'Olahraga Lainnya'])],
            'banner'                => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'status'                => ['required', 'in:' . $allowedStatuses],
            'auto_approve'          => ['sometimes', 'boolean'],
            'custom_fields'         => ['sometimes', 'array', 'max:20'],
            'custom_fields.*'       => ['array'],
            'custom_fields.*.label' => ['required', 'string', 'max:100'],
            'custom_fields.*.type'  => ['required', Rule::in(['text', 'textarea', 'radio'])],
            'custom_fields.*.options' => ['nullable', 'string', 'max:500'],
            'custom_fields.*.required' => ['nullable', 'boolean'],
            'custom_fields.*.image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ], $this->ticketPriceMessages());

        if ($event->status === 'completed' || $event->status === 'cancelled') {
            throw \Illuminate\Validation\ValidationException::withMessages(['status'=>'Event telah diarsipkan dan tidak dapat diubah.']);
        }
        if ($validated['status'] === 'completed' && now()->lt($event->endsAt())) {
            throw \Illuminate\Validation\ValidationException::withMessages(['status'=>'Event belum melewati waktu selesai.']);
        }
        if ($request->has('auto_approve')) {
            $validated['auto_approve'] = $request->boolean('auto_approve');
        }
        if ($request->has('custom_fields')) {
            $validated['custom_fields'] = $this->normalizeCustomFields(
                $request,
                $validated['custom_fields'],
                $event->custom_fields ?? [],
            );
        }

        $participantCount = $event->allocatedTicketCount();

        if ($participantCount > 0) {
            if ($validated['ticket_price'] != $event->ticket_price) {
                return redirect()->back()->withInput()->with('error', 'Harga tiket tidak dapat diubah setelah ada peserta terdaftar.');
            }
            if ($validated['max_participants'] < $participantCount) {
                return redirect()->back()->withInput()->with('error', "Kapasitas maksimum tidak boleh kurang dari jumlah peserta saat ini ({$participantCount}).");
            }
        }

        // Keep the previous banner until its replacement and database update succeed.
        $previousBanner = $event->banner;
        $newBanner = null;
        if ($request->hasFile('banner')) {
            $newBanner = $request->file('banner')->store('event-banners', 'public');
            if (!$newBanner) {
                throw new \RuntimeException('Banner gagal disimpan. Berkas sebelumnya tetap tersedia.');
            }
            $validated['banner'] = $newBanner;
        }

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($event, $validated) {
                $locked = Event::whereKey($event->id)->lockForUpdate()->firstOrFail();
                if ($locked->status === 'completed' || $locked->status === 'cancelled') {
                    throw \Illuminate\Validation\ValidationException::withMessages(['status'=>'Event telah diarsipkan dan tidak dapat diubah.']);
                }
                $count = $locked->allocatedTicketCount();
                if ($count > 0 && ($validated['ticket_price'] != $locked->ticket_price || $validated['max_participants'] < $count)) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['max_participants'=>'Harga atau kuota tidak dapat diubah karena sudah ada peserta terdaftar.']);
                }
                if (in_array($validated['status'], ['cancelled','draft','pending'], true)) {
                    app(\App\Services\EventCancellationPolicy::class)->ensureAllowed($locked);
                }
                $locked->update($validated);
            });
        } catch (\Throwable $exception) {
            if ($newBanner) {
                Storage::disk('public')->delete($newBanner);
            }
            throw $exception;
        }
        if ($newBanner && $previousBanner && $previousBanner !== $newBanner) {
            Storage::disk('public')->delete($previousBanner);
        }

        return redirect()->route('organizer.dashboard')
            ->with('success', 'Event berhasil diperbarui!');
    }

    /**
     * Soft-delete / cancel an event (organizer only, owns event).
     */
    public function destroy(Event $event)
    {
        $this->authorizeOrganizer($event);

        // Mark as cancelled rather than hard-deleting to preserve ticket records
        \Illuminate\Support\Facades\DB::transaction(function () use ($event) {
            $locked = Event::whereKey($event->id)->lockForUpdate()->firstOrFail();
            abort_if($locked->status === 'completed', 422, 'Event selesai merupakan arsip.');
            app(\App\Services\EventCancellationPolicy::class)->ensureAllowed($locked);
            $locked->update(['status'=>'cancelled']);
        });

        return redirect()->route('organizer.dashboard')
            ->with('success', 'Event telah dibatalkan.');
    }

    // -------------------------------------------------------------------------
    // PARTICIPANTS
    // -------------------------------------------------------------------------

    /**
     * List all participants (ticket holders) for a given event (organizer only).
     */
    public function participants(Event $event)
    {
        $this->authorizeOrganizer($event);

        // Allow access only if the event is approved
        if (!in_array($event->status, ['active', 'completed', 'cancelled'], true)) {
            return redirect()->route('organizer.dashboard')
                ->with('error', 'Menu Manajemen Event dinonaktifkan hingga event disetujui Admin.');
        }

        $ticketsQuery = Ticket::with(['user', 'payment'])
            ->where('event_id', $event->id);
        $search = \App\Support\Search::term();
        $ticketsQuery->search($search);
        $filter = request()->query('filter', '');
        if (in_array($filter, ['pending','active','used','cancelled'], true)) {
            $ticketsQuery->where('status', $filter);
        }
        $tickets = $ticketsQuery
            ->latest()
            ->paginate(30)->withQueryString();

        $statusCounts = Ticket::where('event_id',$event->id)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total','status');
        $totalAll   = Ticket::where('event_id', $event->id)->count();
        $validAll   = Ticket::where('event_id', $event->id)->whereIn('status', ['active', 'used'])->count();
        $pendingAll = Ticket::where('event_id', $event->id)->where('status', 'pending')->count();
        $usedAll    = Ticket::where('event_id', $event->id)->where('status', 'used')->count();

        return view('organizer.events.participants', compact('event', 'tickets', 'totalAll', 'validAll', 'pendingAll', 'usedAll', 'statusCounts'));
    }

    public function exportParticipants(Event $event)
    {
        $this->authorizeOrganizer($event);
        abort_unless(in_array($event->status, ['active', 'completed', 'cancelled'], true), 403);

        $tickets = Ticket::with(['user', 'payment'])
            ->where('event_id', $event->id)
            ->search(\App\Support\Search::term())
            ->when(in_array(request('filter'),['pending','active','used','cancelled'],true),fn ($q)=>$q->where('status',request('filter')))
            ->orderBy('created_at')
            ->get();

        $filename = 'peserta-' . \Illuminate\Support\Str::slug($event->title) . '-' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $customFields = collect($event->custom_fields)->pluck('label')->toArray();

        $callback = function () use ($tickets, $customFields) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF");

            $headers = array_merge(
                ['No', 'Nama', 'Email'],
                $customFields,
                ['Kode Tiket', 'Status', 'Pembayaran', 'Daftar', 'Catatan Pendaftaran', 'Waktu Validasi (WITA)', 'Sumber Kehadiran']
            );
            fputcsv($handle, \App\Support\Csv::safeRow($headers));

            foreach ($tickets as $i => $ticket) {
                $answers = $ticket->custom_answers ?? [];
                $row = [
                    $i + 1,
                    $ticket->participant_name ?? '-',
                    $ticket->participant_email ?? '-',
                ];
                foreach ($customFields as $field) {
                    $row[] = $answers[$field] ?? '-';
                }
                $row[] = $ticket->ticket_code;
                $row[] = $ticket->status;
                $row[] = $ticket->payment?->status ?? '-';
                $row[] = $ticket->created_at->format('d/m/Y H:i');
                $row[] = $ticket->notes ?? '';
                $row[] = $ticket->used_at?->timezone('Asia/Makassar')->format('d/m/Y H:i:s') ?? '';
                $row[] = match ($ticket->check_in_method) { 'qr'=>'QR', 'manual'=>'Manual Penyelenggara/EO', default=>'' };

                fputcsv($handle, \App\Support\Csv::safeRow($row));
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Approve a free registration (pending → active).
     * Only for free events with auto_approve=false.
     */
    public function approveFreeRegistration(Event $event, Ticket $ticket)
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($event, $ticket) {
            $lockedEvent = Event::whereKey($event->id)->lockForUpdate()->firstOrFail();
            $lockedTicket = Ticket::whereKey($ticket->id)->lockForUpdate()->firstOrFail();
            $this->authorizeOrganizer($lockedEvent);
            abort_if($lockedTicket->event_id !== $lockedEvent->id, 404);
            abort_unless($lockedEvent->acceptsCheckIn(), 403, 'Event tidak aktif atau sudah selesai.');
            abort_if($lockedEvent->ticket_price > 0, 403, 'Gunakan sistem pembayaran untuk event berbayar.');
            abort_if($lockedTicket->status !== 'pending', 422, 'Hanya pendaftaran pending yang dapat disetujui.');
            $lockedTicket->update(['status' => 'active']);
        });

        return redirect()->route('organizer.events.participants', $event)
            ->with('success', "Pendaftaran {$ticket->participant_name} telah disetujui.");
    }

    /**
     * Reject a pending registration (pending → cancelled).
     */
    public function rejectRegistration(Event $event, Ticket $ticket, Request $request)
    {
        $validated = $request->validate(['notes' => 'nullable|string|max:500']);

        \Illuminate\Support\Facades\DB::transaction(function () use ($event, $ticket, $validated) {
            $lockedEvent = Event::whereKey($event->id)->lockForUpdate()->firstOrFail();
            $lockedTicket = Ticket::whereKey($ticket->id)->lockForUpdate()->firstOrFail();
            $this->authorizeOrganizer($lockedEvent);
            abort_if($lockedTicket->event_id !== $lockedEvent->id, 404);
            abort_unless($lockedEvent->acceptsCheckIn(), 403, 'Event tidak aktif atau sudah selesai.');
            abort_if($lockedTicket->status !== 'pending', 422, 'Hanya pendaftaran pending yang dapat ditolak.');

            $payments = \App\Models\Payment::where('ticket_id',$lockedTicket->id)->orderBy('id')->lockForUpdate()->get();
            if ($lockedEvent->ticket_price > 0 || $payments->contains(fn ($payment) => $payment->amount > 0)) {
                throw \Illuminate\Validation\ValidationException::withMessages(['notes'=>'Kepesertaan berbayar tidak dapat dibatalkan atau ditolak karena pengembalian dana belum tersedia.']);
            }

            $lockedTicket->update([
                'status' => 'cancelled',
                'cancellation_reason' => $validated['notes'] ?? null,
                'cancelled_at' => now(),
                'cancelled_by' => Auth::id(),
            ]);
        });

        return redirect()->route('organizer.events.participants', $event)
            ->with('success', "Pendaftaran {$ticket->participant_name} telah ditolak.");
    }

    /**
     * Show single event management dashboard.
     */
    public function manage(Event $event)
    {
        $this->authorizeOrganizer($event);
        abort_unless(in_array($event->status, ['active', 'completed', 'cancelled'], true), 403, 'Event belum disetujui.');
        return redirect()->route('organizer.events.participants', $event);
    }

    // -------------------------------------------------------------------------
    // PRIVATE HELPERS
    // -------------------------------------------------------------------------

    /**
     * Abort with 403 if the authenticated user does not own this event.
     */
    private function authorizeOrganizer(Event $event): void
    {
        $organizer = Organizer::where('user_id', Auth::id())->first();

        if (! $organizer || $event->organizer_id !== $organizer->id) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengelola event ini.');
        }
    }

    private function ticketPriceRules(): array
    {
        return ['required', 'integer', 'min:0', 'max:99999999'];
    }

    private function ticketPriceMessages(): array
    {
        return [
            'ticket_price.integer' => 'Harga tiket harus berupa rupiah utuh tanpa pecahan.',
            'ticket_price.min' => 'Harga tiket tidak boleh negatif.',
            'ticket_price.max' => 'Harga tiket terlalu besar untuk diproses.',
        ];
    }

    private function normalizeCustomFields(Request $request, array $fields, array $existingFields = []): array
    {
        $normalizedFields = [];
        $labels = [];

        foreach ($fields as $index => $field) {
            $label = trim($field['label']);
            $labelKey = mb_strtolower($label);
            if (isset($labels[$labelKey])) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    "custom_fields.{$index}.label" => 'Judul pertanyaan tidak boleh duplikat.',
                ]);
            }
            $labels[$labelKey] = true;

            $type = $field['type'];
            $options = [];
            if ($type === 'radio') {
                $options = collect(explode(',', (string) ($field['options'] ?? '')))
                    ->map(static fn (string $option) => trim($option))
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                if ($options === []) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "custom_fields.{$index}.options" => 'Pilihan radio harus memiliki setidaknya satu opsi.',
                    ]);
                }
            }

            $image = $existingFields[$index]['image'] ?? null;
            if ($request->hasFile("custom_fields.{$index}.image")) {
                $image = $request->file("custom_fields.{$index}.image")->store('event-custom-fields', 'public');
            }

            $normalizedFields[] = [
                'label' => $label,
                'type' => $type,
                'options' => $options,
                'required' => !array_key_exists('required', $field) || filter_var($field['required'], FILTER_VALIDATE_BOOLEAN),
                'image' => $image,
            ];
        }

        return $normalizedFields;
    }
}
