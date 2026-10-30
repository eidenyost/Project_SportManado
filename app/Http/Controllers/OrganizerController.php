<?php

namespace App\Http\Controllers;

use App\Models\Organizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class OrganizerController extends Controller
{
    /**
     * Show the organizer profile setup form.
     *
     * If the profile already exists and is approved, redirect to dashboard.
     * If it is pending, redirect to pending.
     * If it is rejected, pass the profile for resubmission.
     */
    public function setup()
    {
        $user = Auth::user();

        $organizer = Organizer::where('user_id', $user->id)->first();

        if ($organizer) {
            if ($organizer->status === 'approved') {
                return redirect()->route('organizer.dashboard')
                    ->with('info', 'Your organizer profile is already set up.');
            } elseif ($organizer->status === 'pending') {
                return redirect()->route('organizer.pending');
            }
            // If rejected, fall through and render form with existing data for resubmit
        }

        return view('organizer.setup', compact('organizer'));
    }

    /**
     * Persist or update the organizer profile.
     */
    public function storeSetup(Request $request)
    {
        $user = Auth::user();

        // Get existing profile to handle resubmit/update
        $existing = Organizer::where('user_id', $user->id)->first();
        if ($existing && $existing->status === 'approved') {
            return redirect()->route('organizer.dashboard');
        } elseif ($existing && $existing->status === 'pending') {
            return redirect()->route('organizer.pending');
        }

        // Validate inputs (Indonesian WhatsApp format, required KTP/Selfie if not uploaded yet)
        $rules = [
            'organization_name' => ['required', 'string', 'max:255'],
            'phone'             => ['required', 'string', 'regex:/^(08|628)[0-9]{8,13}$/', 'max:20'],
            'description'       => ['nullable', 'string', 'max:1000'],
            'logo'              => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];

        // Files are required on first setup, but optional on resubmission if already uploaded
        if (!$existing || !$existing->ktp_path) {
            $rules['ktp'] = ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'];
        } else {
            $rules['ktp'] = ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:5120'];
        }

        if (!$existing || !$existing->selfie_path) {
            $rules['selfie'] = ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'];
        } else {
            $rules['selfie'] = ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:5120'];
        }

        $validated = $request->validate($rules);

        $uploads = [
            'logo' => ['directory'=>'organizer-logos','disk'=>'public','previous'=>$existing?->logo],
            'ktp' => ['directory'=>'organizer-ktps','disk'=>'local','previous'=>$existing?->ktp_path],
            'selfie' => ['directory'=>'organizer-ktps','disk'=>'local','previous'=>$existing?->selfie_path],
        ];
        $stored = [];
        try {
            foreach ($uploads as $field => $upload) {
                if ($request->hasFile($field)) {
                    $path = $request->file($field)->store($upload['directory'], $upload['disk']);
                    if (!$path) throw new \RuntimeException('Dokumen gagal disimpan.');
                    $stored[$field] = $path;
                }
            }
            $data = [
                'organization_name'=>$validated['organization_name'], 'phone'=>$validated['phone'],
                'description'=>$validated['description'] ?? null,
                'logo'=>$stored['logo'] ?? $uploads['logo']['previous'],
                'ktp_path'=>$stored['ktp'] ?? $uploads['ktp']['previous'],
                'selfie_path'=>$stored['selfie'] ?? $uploads['selfie']['previous'],
                'status'=>'pending', 'admin_notes'=>null,
            ];
            if ($existing) $existing->update($data);
            else Organizer::create(['user_id'=>$user->id] + $data);
        } catch (\Throwable $exception) {
            foreach ($stored as $field => $path) {
                try { Storage::disk($uploads[$field]['disk'])->delete($path); }
                catch (\Throwable $cleanupError) { report($cleanupError); }
            }
            throw $exception;
        }
        foreach ($stored as $field => $path) {
            $previous = $uploads[$field]['previous'];
            if ($previous && $previous !== $path) {
                try { Storage::disk($uploads[$field]['disk'])->delete($previous); }
                catch (\Throwable $cleanupError) { report($cleanupError); }
            }
        }

        return redirect()->route('organizer.pending')
            ->with('success', 'Profil Penyelenggara/EO berhasil dikirim! Menunggu persetujuan Admin.');
    }
}
