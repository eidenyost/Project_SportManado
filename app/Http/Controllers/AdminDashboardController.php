<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventRegistrationRequest;
use App\Models\User;
use App\Models\Organizer;
use App\Models\Payment;
use App\Models\WithdrawalRequest;
use App\Models\GalleryPhoto;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AdminDashboardController extends Controller
{
    /**
     * Show the Admin Dashboard.
     */
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'overview');
        $activeTab = $tab;

        $totalActiveEvents = Event::where('status', 'active')->count();
        $totalPendingEvents = Event::where('status', 'pending')->count();
        $totalOrganizers = User::where('role', 'organizer')->count();
        $totalPendingRequests = EventRegistrationRequest::where('status', 'pending')->count();
        $totalPendingOrganizers = Organizer::where('status', 'pending')->count();
        $totalUsersCount = User::count();
        $totalGalleryPhotos = GalleryPhoto::count();

        $tabData = match ($tab) {
            'organizer_verifications' => $this->loadOrganizerVerificationsTab(),
            'pending_validation' => $this->loadPendingValidationTab(),
            'all_events' => $this->loadAllEventsTab(),
            'manage_users' => $this->loadManageUsersTab(),
            'payment_monitoring' => $this->loadPaymentMonitoringTab(),
            'withdrawal_requests' => $this->loadWithdrawalRequestsTab(),
            'gallery' => $this->loadGalleryTab(),
            default => $this->loadOverviewTab(),
        };

        $defaults = [
            'pendingEvents' => collect(),
            'allEvents' => collect(),
            'allUsers' => collect(),
            'payments' => collect(),
            'pendingEventRequests' => collect(),
            'pendingOrganizers' => collect(),
            'withdrawalRequests' => collect(),
            'galleryPhotos' => collect(),
            'statusCounts' => collect(),
            'roleCounts' => collect(),
            'paymentCounts' => collect(),
            'wdCounts' => collect(),
        ];

        return view('admin.dashboard', array_merge(
            compact(
                'tab', 'activeTab',
                'totalActiveEvents', 'totalPendingEvents',
                'totalOrganizers', 'totalPendingRequests',
                'totalPendingOrganizers', 'totalUsersCount',
                'totalGalleryPhotos',
            ),
            $defaults,
            $tabData
        ));
    }

    /**
     * Return pending counts as JSON for real-time badge polling.
     */
    public function pendingCounts()
    {
        return response()->json([
            'totalPendingOrganizers' => Organizer::where('status', 'pending')->count(),
            'totalPendingRequests' => EventRegistrationRequest::where('status', 'pending')->count(),
            'totalPendingEvents' => Event::where('status', 'pending')->count(),
        ]);
    }

    private function loadOverviewTab(): array
    {
        return [
            'allEvents' => Event::with(['organizer'])
                ->withCount(['tickets as active_used_tickets_count' => fn($q) => $q->whereIn('status', ['active', 'used'])])
                ->latest()
                ->limit(10)
                ->get(),
        ];
    }

    private function loadOrganizerVerificationsTab(): array
    {
        return [
            'pendingOrganizers' => Organizer::with('user')
                ->where('status', 'pending')
                ->when(\App\Support\Search::term() !== '',fn ($q)=>$q->where(fn ($s)=>$s->where('organization_name','like','%'.\App\Support\Search::term().'%')->orWhereHas('user',fn ($u)=>$u->where('name','like','%'.\App\Support\Search::term().'%')->orWhere('email','like','%'.\App\Support\Search::term().'%'))))
                ->latest()
                ->paginate(12)
                ->withQueryString(),
        ];
    }

    private function loadPendingValidationTab(): array
    {
        return [
            'pendingEvents' => Event::with('organizer')
                ->where('status', 'pending')
                ->when(\App\Support\Search::term() !== '',fn ($q)=>$q->where('title','like','%'.\App\Support\Search::term().'%'))
                ->latest()
                ->get(),
            'pendingEventRequests' => EventRegistrationRequest::with('user')
                ->where('status', 'pending')
                ->when(\App\Support\Search::term() !== '',fn ($q)=>$q->where('event_title','like','%'.\App\Support\Search::term().'%'))
                ->latest()
                ->get(),
        ];
    }

    private function loadAllEventsTab(): array
    {
        return [
            'statusCounts' => Event::selectRaw('status, count(*) as total')
                ->whereNotIn('status', ['cancelled', 'rejected'])
                ->groupBy('status')
                ->pluck('total', 'status'),
            'allEvents' => Event::with(['organizer', 'tickets'])
                ->whereNotIn('status', ['cancelled', 'rejected'])
                ->when(request('search'), fn($q, $s) => $q->where(function($q) use ($s) {
                    $q->where('title', 'like', "%{$s}%")
                      ->orWhere('location', 'like', "%{$s}%")
                      ->orWhere('category', 'like', "%{$s}%");
                }))
                ->when(request('status'), fn($q, $s) => $q->where('status', $s))
                ->latest()
                ->paginate(12)
                ->withQueryString(),
        ];
    }

    private function loadManageUsersTab(): array
    {
        return [
            'roleCounts' => User::selectRaw('role, count(*) as total')
                ->groupBy('role')
                ->pluck('total', 'role'),
            'allUsers' => User::with('organizer')
                ->when(request('search'), fn($q, $s) => $q->where(function($q) use ($s) {
                    $q->where('name', 'like', "%{$s}%")
                      ->orWhere('email', 'like', "%{$s}%");
                }))
                ->when(request('role'), fn($q, $r) => $q->where('role', $r))
                ->latest()
                ->paginate(15)
                ->withQueryString(),
        ];
    }

    private function loadPaymentMonitoringTab(): array
    {
        $search=\App\Support\Search::term();
        return [
            'paymentCounts'=>Payment::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total','status'),
            'payments'=>Payment::with(['user','ticket.event'])->search($search)
                ->when(in_array(request('status'),['pending','confirmed','rejected'],true),fn ($q)=>$q->where('status',request('status')))
                ->latest()->paginate(20)->withQueryString(),
        ];
    }

    private function loadWithdrawalRequestsTab(): array
    {
        $search=\App\Support\Search::term();
        return [
            'wdCounts'=>WithdrawalRequest::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total','status'),
            'withdrawalRequests'=>WithdrawalRequest::with(['event','organizer'])
                ->when($search !== '',fn ($q)=>$q->where(function ($query) use ($search) {
                    $query->where('account_holder','like','%'.$search.'%')->orWhere('bank_name','like','%'.$search.'%')
                        ->orWhere('iris_reference_no','like','%'.$search.'%')
                        ->orWhereHas('event',fn ($event)=>$event->where('title','like','%'.$search.'%'))
                        ->orWhereHas('organizer',fn ($eo)=>$eo->where('organization_name','like','%'.$search.'%'));
                }))
                ->when(in_array(request('status'),['pending','approved','rejected'],true),fn ($q)=>$q->where('status',request('status')))
                ->latest()->paginate(20)->withQueryString(),
        ];
    }

    private function loadGalleryTab(): array
    {
        return [
            'galleryPhotos' => GalleryPhoto::latest()->paginate(20)->withQueryString(),
        ];
    }

    /**
     * Approve a pending event.
     */
    public function approveEvent(Event $event)
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($event) {
        $event=Event::whereKey($event->id)->lockForUpdate()->firstOrFail();
        abort_unless($event->status==='pending',400,'Pengajuan ini tidak sedang menunggu pemeriksaan.');

        $event->update(['status' => 'active']);

        Notification::create([
            'user_id' => $event->organizer->user_id,
            'type' => 'event_approved',
            'title' => 'Event Disetujui',
            'message' => "Event '{$event->title}' telah disetujui dan aktif secara publik!",
            'notifiable_type' => Event::class,
            'notifiable_id' => $event->id,
        ]);

        return redirect()->route('admin.dashboard')
            ->with('success', "Event '{$event->title}' telah berhasil disetujui dan aktif secara publik!");
        });
    }

    /**
     * Reject and delete a pending event.
     */
    public function rejectEvent(Request $request, Event $event)
    {
        // Preserve participant access, financial history, and associated files.
        if ($event->tickets()->exists()) {
            return back()->with('error', 'Event dengan riwayat peserta tidak dapat dihapus atau ditolak. Gunakan arsip event selesai.');
        }
        $adminNotes = $request->input('admin_notes', '');
        $organizerUserId = $event->organizer->user_id;
        $eventTitle = $event->title;

        // Soft delete to preserve audit trail, do not clean up files
        $this->archiveEmptyEvent($event);

        Notification::create([
            'user_id' => $organizerUserId,
            'type' => 'event_rejected',
            'title' => 'Event Ditolak',
            'message' => $adminNotes
                ? "Event '{$eventTitle}' ditolak: {$adminNotes}"
                : "Event '{$eventTitle}' ditolak.",
            'admin_notes' => $adminNotes,
            'notifiable_type' => Event::class,
            'notifiable_id' => $event->id,
        ]);

        return redirect()->route('admin.dashboard')
            ->with('success', "Pendaftaran event '{$eventTitle}' telah ditolak dan diarsipkan.");
    }

    /**
     * Delete any event from the system.
     */
    public function deleteEvent(Event $event)
    {
        // Preserve participant access, financial history, and associated files.
        if ($event->tickets()->exists()) {
            return back()->with('error', 'Event dengan riwayat peserta tidak dapat dihapus atau ditolak. Gunakan arsip event selesai.');
        }
        $this->archiveEmptyEvent($event);
        $this->cleanupEventFiles($event);

        return redirect()->route('admin.dashboard')
            ->with('success', "Event '{$event->title}' telah berhasil dihapus dari sistem.");
    }

    /**
     * Mark an active event as completed.
     */
    private function archiveEmptyEvent(Event $event): void
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($event) {
            $locked = Event::whereKey($event->id)->lockForUpdate()->firstOrFail();
            if ($locked->tickets()->exists() || \App\Models\WithdrawalRequest::where('event_id', $event->id)->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages(['status'=>'Event dengan riwayat peserta atau keuangan harus tetap tersimpan.']);
            }
            $locked->delete();
        });
    }

    public function markAsCompleted(Event $event)
    {
        abort_if($event->status !== 'active', 400, 'Hanya event dengan status aktif yang bisa ditandai selesai.');

        $event->update(['status' => 'completed']);



        return redirect()->route('admin.dashboard', ['tab' => 'all_events'])
            ->with('success', "Event '{$event->title}' telah ditandai sebagai selesai.");
    }

    /**
     * Delete a user (organizer or athlete).
     */
    public function deleteUser(User $user)
    {
        if ($user->id === Auth::id()) {
            return redirect()->route('admin.dashboard', ['tab' => 'manage_users'])
                ->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        if ($user->hasProtectedHistory()) {
            return back()->with('error', 'Akun memiliki riwayat tiket, pembayaran, atau Penyelenggara/EO dan tidak dapat dihapus.');
        }

        // Loop through tickets and delete QR codes
        foreach ($user->tickets as $ticket) {
            if ($ticket->qr_code_path && Storage::disk('public')->exists($ticket->qr_code_path)) {
                Storage::disk('public')->delete($ticket->qr_code_path);
            }
        }

        // Clean up organizer files if user is an organizer
        if ($user->isOrganizer()) {
            $organizer = $user->organizer;
            if ($organizer) {
                foreach ($organizer->events as $event) {
                    $this->cleanupEventFiles($event);
                }

                if ($organizer->logo && Storage::disk('public')->exists($organizer->logo)) {
                    Storage::disk('public')->delete($organizer->logo);
                }

                if ($organizer->ktp_path && Storage::disk('local')->exists($organizer->ktp_path)) {
                    Storage::disk('local')->delete($organizer->ktp_path);
                }

                if ($organizer->selfie_path && Storage::disk('local')->exists($organizer->selfie_path)) {
                    Storage::disk('local')->delete($organizer->selfie_path);
                }
            }
        }

        // Delete user (associated events, tickets, and payments will cascade)
        $user->delete();

        return redirect()->route('admin.dashboard', ['tab' => 'manage_users'])
            ->with('success', "Akun pengguna '{$user->name}' telah berhasil dihapus dari sistem.");
    }

    /**
     * Change a user's role (Peserta, Penyelenggara/EO, Admin).
     */
    public function updateRole(Request $request, User $user)
    {
        if ($user->id === Auth::id()) {
            return redirect()->route('admin.dashboard', ['tab' => 'manage_users'])
                ->with('error', 'Anda tidak dapat mengubah peran Anda sendiri.');
        }

        $validated = $request->validate([
            'role' => ['required', 'in:user,organizer,admin'],
        ]);

        if ($validated['role'] === 'organizer' && !$user->organizer) {
            return redirect()->route('admin.dashboard', ['tab' => 'manage_users'])
                ->with('error', "Tidak dapat mengubah peran '{$user->name}' menjadi Penyelenggara/EO. Pengguna belum memiliki profil Penyelenggara/EO.");
        }

        $user->update(['role' => $validated['role']]);

        return redirect()->route('admin.dashboard', ['tab' => 'manage_users'])
            ->with('success', "Peran pengguna '{$user->name}' berhasil diubah menjadi '" . (['user' => 'Peserta', 'organizer' => 'Penyelenggara/EO', 'admin' => 'Admin'][$validated['role']]) . "'.");
    }

    /**
     * Approve a participant's event registration request.
     * This upgrades the user's role to organizer and creates the event.
     */
    public function approveEventRequest(EventRegistrationRequest $eventRequest)
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($eventRequest) {
        $eventRequest=EventRegistrationRequest::whereKey($eventRequest->id)->lockForUpdate()->firstOrFail();
        abort_unless($eventRequest->status==='pending',400,'Pengajuan ini tidak sedang menunggu pemeriksaan.');

        $price = (string) $eventRequest->ticket_price;
        if (!preg_match('/^\d+(?:\.0{1,2})?$/', $price) || (int) $price > 99999999) {
            return redirect()->route('admin.dashboard')
                ->with('error', 'Pengajuan memiliki harga tiket pecahan atau di luar batas. Minta pemohon mengajukan ulang dengan rupiah utuh.');
        }

        $user = $eventRequest->user;

        // 1. Upgrade user role to organizer (if not already)
        if ($user->role !== 'organizer') {
            $user->update(['role' => 'organizer']);
        }

        // 2. Create or retrieve the Organizer profile
        $organizer = Organizer::firstOrCreate(
            ['user_id' => $user->id],
            [
                'organization_name' => $eventRequest->organization_name,
                'phone'             => $eventRequest->phone,
                'description'       => 'Komunitas Olahraga yang terdaftar di SportManado.',
            ]
        );

        // 3. Create the Event record from request data
        Event::create([
            'organizer_id'          => $organizer->id,
            'title'                 => $eventRequest->event_title,
            'description'           => $eventRequest->event_description,
            'location'              => $eventRequest->event_location,
            'event_date'            => $eventRequest->event_date,
            'registration_deadline' => $eventRequest->registration_deadline,
            'max_participants'      => $eventRequest->max_participants,
            'ticket_price'          => $eventRequest->ticket_price,
            'category'              => $eventRequest->category,
            'banner'                => $eventRequest->banner,
            'proposal'              => $eventRequest->proposal_file,
            'permit'                => $eventRequest->permit_file,
            'status'                => 'active',
            'custom_fields'         => [],
        ]);

        // 4. Mark the request as approved
        $eventRequest->update(['status' => 'approved']);

        return redirect()->route('admin.dashboard')
            ->with('success', "Pengajuan event '{$eventRequest->event_title}' dari {$user->name} telah disetujui! Akun mereka kini menjadi Penyelenggara/EO.");
        });
    }

    /**
     * Reject a participant's event registration request.
     */
    public function rejectEventRequest(Request $request, EventRegistrationRequest $eventRequest)
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($eventRequest, $request) {
        $eventRequest=EventRegistrationRequest::whereKey($eventRequest->id)->lockForUpdate()->firstOrFail();
        abort_unless($eventRequest->status==='pending',400,'Pengajuan ini tidak sedang menunggu pemeriksaan.');

        $validated = $request->validate([
            'admin_notes' => ['nullable', 'string', 'max:500'],
        ]);

        // Delete uploaded files
        if ($eventRequest->proposal_file) {
            Storage::disk('local')->delete($eventRequest->proposal_file);
        }
        if ($eventRequest->permit_file) {
            Storage::disk('local')->delete($eventRequest->permit_file);
        }
        if ($eventRequest->banner) {
            Storage::disk('public')->delete($eventRequest->banner);
        }

        // Mark as rejected with optional notes
        $eventRequest->update([
            'status'      => 'rejected',
            'admin_notes' => $validated['admin_notes'] ?? null,
        ]);

        return redirect()->route('admin.dashboard')
            ->with('success', "Pengajuan event '{$eventRequest->event_title}' telah ditolak.");
        });
    }

    /**
     * Helper to clean up all physical files associated with an event.
     */
    private function cleanupEventFiles(Event $event): void
    {
        if ($event->banner) {
            Storage::disk('public')->delete($event->banner);
        }
        if ($event->proposal) {
            Storage::disk('local')->delete($event->proposal);
        }
        if ($event->permit) {
            Storage::disk('local')->delete($event->permit);
        }
        if ($event->ktp_image) {
            Storage::disk('local')->delete($event->ktp_image);
        }

        // Clean up helper images inside custom fields
        if (is_array($event->custom_fields)) {
            foreach ($event->custom_fields as $field) {
                if (!empty($field['image'])) {
                    Storage::disk('public')->delete($field['image']);
                }
            }
        }
    }

    /**
     * Approve a pending organizer profile.
     */
    public function approveOrganizer(Organizer $organizer)
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($organizer) {
        $organizer=Organizer::whereKey($organizer->id)->lockForUpdate()->firstOrFail();
        abort_unless($organizer->status==='pending',400,'Pengajuan ini tidak sedang menunggu pemeriksaan.');

        $organizer->update(['status' => 'approved']);
        $organizer->user->update(['role' => 'organizer']);

        Notification::create([
            'user_id' => $organizer->user_id,
            'type' => 'organizer_approved',
            'title' => 'Akun Penyelenggara/EO Disetujui',
            'message' => "Akun Penyelenggara/EO '{$organizer->organization_name}' telah disetujui! Silakan masuk.",
            'notifiable_type' => Organizer::class,
            'notifiable_id' => $organizer->id,
        ]);

        return redirect()->route('admin.dashboard')
            ->with('success', "Akun Penyelenggara/EO '{$organizer->organization_name}' telah disetujui!");
        });
    }

    /**
     * Reject a pending organizer profile.
     */
    public function rejectOrganizer(Request $request, Organizer $organizer)
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($organizer, $request) {
        $organizer=Organizer::whereKey($organizer->id)->lockForUpdate()->firstOrFail();
        abort_unless($organizer->status==='pending',400,'Pengajuan ini tidak sedang menunggu pemeriksaan.');

        $validated = $request->validate([
            'admin_notes' => ['required', 'string', 'max:500'],
        ]);

        $organizer->update([
            'status' => 'rejected',
            'admin_notes' => $validated['admin_notes']
        ]);

        Notification::create([
            'user_id' => $organizer->user_id,
            'type' => 'organizer_rejected',
            'title' => 'Akun Penyelenggara/EO Ditolak',
            'message' => "Akun Penyelenggara/EO '{$organizer->organization_name}' ditolak: {$validated['admin_notes']}",
            'admin_notes' => $validated['admin_notes'],
            'notifiable_type' => Organizer::class,
            'notifiable_id' => $organizer->id,
        ]);

        return redirect()->route('admin.dashboard')
            ->with('success', "Akun Penyelenggara/EO '{$organizer->organization_name}' telah ditolak.");
        });
    }

    /**
     * Download a private event document securely.
     */
    public function downloadDocument(Request $request)
    {
        $path = $request->query('path');

        if (!$path) {
            abort(400, 'Parameter path diperlukan.');
        }
        abort_unless(is_string($path), 400, 'Parameter path tidak valid.');

        // Check if the path is in allowed directories to prevent directory traversal
        $allowedPrefixes = [
            'event-proposals/',
            'event-permits/',
            'organizer-ktps/',
            'event-reg-proposals/',
            'event-reg-permits/',
        ];

        return \App\Support\PrivateFiles::respond($path, $allowedPrefixes);
    }

    /**
     * Approve a withdrawal request.
     */
    public function approveWithdrawal(Request $request, WithdrawalRequest $withdrawal)
    {
        $data=$request->validate([
            'transfer_proof'=>['required','file','mimes:jpg,jpeg,png,webp,pdf','max:5120'],
            'transfer_reference'=>['nullable','string','max:100'],
            'admin_notes'=>['nullable','string','max:500'],
        ],['transfer_proof.required'=>'Bukti transfer wajib diunggah sebelum pencairan disetujui.']);
        app(\App\Services\ManualWithdrawalService::class)->approve($withdrawal,$request->user()->id,
            $request->file('transfer_proof'),$data['transfer_reference']??null,$data['admin_notes']??null);
        return redirect()->route('admin.dashboard',['tab'=>'withdrawal_requests'])
            ->with('success','Penarikan disetujui. Pengiriman dana dan bukti transfer telah dicatat.');
    }

    public function downloadWithdrawalProof(WithdrawalRequest $withdrawal)
    {
        $isOwner=$withdrawal->organizer?->user_id===Auth::id();
        abort_unless(Auth::user()->isAdmin() || $isOwner,403,'Bukti transfer hanya dapat dilihat Admin dan Penyelenggara/EO pemilik pengajuan.');
        $path=$withdrawal->transfer_proof_path;
        return \App\Support\PrivateFiles::respond($path, ['withdrawal-proofs/']);
    }

    /**
     * Reject a withdrawal request.
     */
    public function rejectWithdrawal(Request $request, WithdrawalRequest $withdrawal)
    {


        $request->validate([
            'admin_notes' => 'required|string|max:500',
        ]);

        return \Illuminate\Support\Facades\DB::transaction(function () use ($request,$withdrawal) {
        Organizer::whereKey($withdrawal->organizer_id)->lockForUpdate()->firstOrFail();
        Event::whereKey($withdrawal->event_id)->lockForUpdate()->firstOrFail();
        $withdrawal=WithdrawalRequest::whereKey($withdrawal->id)->lockForUpdate()->firstOrFail();
        abort_unless($withdrawal->status === 'pending',400,'Pengajuan ini tidak sedang dalam status pending.');
        abort_if($withdrawal->payout_requested_at || $withdrawal->iris_reference_no,422,'Pencairan telah dikirim ke provider. Dana tidak dapat dilepas melalui penolakan manual; lakukan rekonsiliasi terlebih dahulu.');
        $withdrawal->update([
            'status' => 'rejected',
            'admin_notes' => $request->admin_notes,
        ]);

        Notification::create([
            'user_id' => $withdrawal->organizer->user_id,
            'type' => 'withdrawal_rejected',
            'title' => 'Penarikan Dana Ditolak',
            'message' => 'Penarikan dana Rp ' . number_format($withdrawal->amount, 0, ',', '.') . ' ditolak: ' . $request->admin_notes,
            'admin_notes' => $request->admin_notes,
            'notifiable_type' => WithdrawalRequest::class,
            'notifiable_id' => $withdrawal->id,
        ]);

        return redirect()->route('admin.dashboard', ['tab' => 'withdrawal_requests'])
            ->with('success', 'Pengajuan penarikan dana telah ditolak.');
        });
    }

    /**
     * Store a new gallery photo.
     */
    public function storeGalleryPhoto(Request $request)
    {
        $validated = $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ]);

        $path = $request->file('image')->store('gallery-photos', 'public');

        GalleryPhoto::create(['image_path' => $path]);

        return redirect()->route('admin.dashboard', ['tab' => 'gallery'])
            ->with('success', 'Foto berhasil ditambahkan ke galeri.');
    }

    /**
     * Delete a gallery photo.
     */
    public function destroyGalleryPhoto(GalleryPhoto $galleryPhoto)
    {
        if ($galleryPhoto->image_path && Storage::disk('public')->exists($galleryPhoto->image_path)) {
            Storage::disk('public')->delete($galleryPhoto->image_path);
        }

        $galleryPhoto->delete();

        return redirect()->route('admin.dashboard', ['tab' => 'gallery'])
            ->with('success', 'Foto berhasil dihapus dari galeri.');
    }
}
