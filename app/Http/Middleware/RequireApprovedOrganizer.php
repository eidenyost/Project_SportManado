<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireApprovedOrganizer
{
    public function handle(Request $request, Closure $next): Response
    {
        $organizer = $request->user()?->organizer()->first();

        if ($organizer && $organizer->status === 'approved') {
            return $next($request);
        }

        // Keep onboarding redirects for the dashboard only, never operational requests.
        if ($request->routeIs('organizer.dashboard')) {
            if ($organizer && $organizer->status === 'pending') {
                return redirect()->route('organizer.pending');
            }

            if ($organizer && $organizer->status === 'rejected') {
                return redirect()->route('organizer.setup')
                    ->with('error', 'Profil Penyelenggara Anda ditolak: ' . $organizer->admin_notes);
            }

            return redirect()->route('organizer.setup')
                ->with('error', 'Profil Penyelenggara tidak ditemukan. Silakan lengkapi data.');
        }

        abort(403, 'Profil Penyelenggara harus disetujui sebelum mengakses fitur ini.');
    }
}
