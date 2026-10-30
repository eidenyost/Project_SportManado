<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckOrganizerStatus
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Onboarding routes must remain reachable even without an organizer profile.
        if ($request->routeIs('logout', 'organizer.pending', 'organizer.setup', 'organizer.store-setup')) {
            return $next($request);
        }

        if ($user && $user->organizer) {
            $status = $user->organizer->status;

            if ($status === 'pending') {
                return redirect()->route('organizer.pending');
            }

            if ($status === 'rejected') {
                return redirect()->route('organizer.setup')
                    ->with('error', 'Profil Penyelenggara Anda ditolak: ' . $user->organizer->admin_notes);
            }
        } elseif ($user && $user->isOrganizer() && !$user->organizer) {
            return redirect()->route('organizer.setup')
                ->with('error', 'Profil Penyelenggara tidak ditemukan. Silakan lengkapi data.');
        }

        return $next($request);
    }
}
