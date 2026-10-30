<?php

namespace App\Http\Controllers;

use App\Models\EventRegistrationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class EventRegistrationController extends Controller
{
    /**
     * Show the event registration form for participants (role: user).
     */
    public function create()
    {
        $user = Auth::user();

        // If already an organizer, redirect to organizer dashboard
        if ($user->isOrganizer()) {
            return redirect()->route('organizer.dashboard')
                ->with('info', 'Anda sudah terdaftar sebagai penyelenggara event.');
        }

        // Check for an existing pending or approved request
        $existingRequest = EventRegistrationRequest::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'approved'])
            ->first();

        if ($existingRequest) {
            return redirect()->route('event-registration.status')
                ->with('info', 'Anda sudah memiliki pengajuan pendaftaran event yang sedang diproses.');
        }

        return view('events.register-form');
    }

    /**
     * Store a new event registration request from a participant.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        // Guard: organizer already
        if ($user->isOrganizer()) {
            return redirect()->route('organizer.dashboard');
        }

        // Guard: already has active request
        $existingRequest = EventRegistrationRequest::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'approved'])
            ->first();

        if ($existingRequest) {
            return redirect()->route('event-registration.status')
                ->with('info', 'Anda sudah memiliki pengajuan yang sedang diproses.');
        }

        $validated = $request->validate([
            'organization_name'     => ['required', 'string', 'max:255'],
            'phone'                 => ['required', 'string', 'max:30'],
            'event_title'           => ['required', 'string', 'max:255'],
            'event_description'     => ['required', 'string'],
            'event_location'        => ['required', 'string', 'max:255'],
            'event_date'            => ['required', 'date', 'after:now'],
            'registration_deadline' => ['required', 'date', 'before:event_date'],
            'max_participants'      => ['required', 'integer', 'min:1', 'max:100000'],
            'ticket_price'          => ['required', 'integer', 'min:0', 'max:99999999'],
            'category'              => ['required', 'string', 'max:100', Rule::in(['Lari & Trail', 'Padel / Tenis', 'E-Sports', 'Bersepeda', 'Futsal', 'Basket', 'Bulutangkis', 'Olahraga Lainnya'])],
            'proposal_file'         => ['required', 'file', 'mimetypes:application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/msword', 'max:10240'],
            'permit_file'           => ['required', 'file', 'mimetypes:application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/msword', 'max:10240'],
            'banner'                => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ], [
            'proposal_file.required' => 'Proposal Resmi Acara wajib diunggah.',
            'proposal_file.mimes'    => 'Proposal harus berformat PDF atau DOCX.',
            'permit_file.required'   => 'Surat Izin Penyelenggaraan Kegiatan wajib diunggah.',
            'permit_file.mimes'      => 'Surat Izin harus berformat PDF atau DOCX.',
            'event_date.after'       => 'Tanggal event harus setelah hari ini.',
            'registration_deadline.before' => 'Batas registrasi harus sebelum tanggal event.',
            'ticket_price.integer' => 'Harga tiket harus berupa rupiah utuh tanpa pecahan.',
            'ticket_price.min' => 'Harga tiket tidak boleh negatif.',
            'ticket_price.max' => 'Harga tiket terlalu besar untuk diproses.',
        ]);

        // Upload mandatory files
        $proposalPath = $request->file('proposal_file')->store('event-reg-proposals', 'local');
        $permitPath   = $request->file('permit_file')->store('event-reg-permits', 'local');

        // Upload optional banner
        $bannerPath = null;
        if ($request->hasFile('banner')) {
            $bannerPath = $request->file('banner')->store('event-reg-banners', 'public');
        }

        EventRegistrationRequest::create([
            'user_id'               => $user->id,
            'organization_name'     => $validated['organization_name'],
            'phone'                 => $validated['phone'],
            'event_title'           => $validated['event_title'],
            'event_description'     => $validated['event_description'],
            'event_location'        => $validated['event_location'],
            'event_date'            => $validated['event_date'],
            'registration_deadline' => $validated['registration_deadline'],
            'max_participants'      => $validated['max_participants'],
            'ticket_price'          => $validated['ticket_price'],
            'category'              => $validated['category'],
            'proposal_file'         => $proposalPath,
            'permit_file'           => $permitPath,
            'banner'                => $bannerPath,
            'status'                => 'pending',
        ]);

        return redirect()->route('event-registration.status')
            ->with('success', 'Pengajuan pendaftaran event Anda telah berhasil dikirim! Tunggu verifikasi Admin dalam 1×24 jam.');
    }

    /**
     * Show the registration request status page for the logged-in participant.
     */
    public function status()
    {
        $user = Auth::user();

        // If already an organizer, redirect to organizer dashboard
        if ($user->isOrganizer()) {
            return redirect()->route('organizer.dashboard');
        }

        // Get the latest request (any status)
        $registrationRequest = EventRegistrationRequest::where('user_id', $user->id)
            ->latest()
            ->first();

        return view('events.registration-status', compact('registrationRequest'));
    }
}
