<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Organizer;
use App\Models\Ticket;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Dynamic dashboard router based on user role.
     */
    public function userDashboard()
    {
        $user = Auth::user();

        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        if ($user->isOrganizer()) {
            return redirect()->route('organizer.dashboard');
        }

        // Fetch user's tickets
        $tickets = Ticket::where('user_id', $user->id)
            ->with(['event', 'payment'])
            ->latest()
            ->get();

        // Calculate stats
        $stats = [
            'events_joined'   => $tickets->count(),
            'active_tickets'  => $tickets->where('status', 'active')->count(),
            'pending_payment' => $tickets->filter(function($t) {
                return $t->status === 'pending' && $t->payment && $t->payment->status === 'pending';
            })->count(),
        ];

        // Fetch upcoming events from tickets where status is active/pending and event is in the future
        $upcomingEvents = $tickets->filter(function($t) {
            return $t->event && $t->event->event_date->isFuture() && in_array($t->status, ['active', 'pending']);
        })->take(3);

        $recentTickets = $tickets->take(5);

        $latestEvents = Event::withCount(['tickets'=>fn ($q) => $q->whereIn('status',['pending','active','used'])])->where('status', 'active')
            ->latest()
            ->take(4)
            ->get();

        return view('dashboard', compact('stats', 'upcomingEvents', 'recentTickets', 'latestEvents'));
    }

    /**
     * Organizer dashboard.
     *
     * Shows the organizer's events, participant counts, and revenue stats.
     */
    public function organizerDashboard()
    {
        $user = Auth::user();

        // Retrieve or handle the case where the organizer profile hasn't been set up yet
        $organizer = Organizer::where('user_id', $user->id)->first();

        if (! $organizer) {
            return redirect()->route('organizer.setup')
                ->with('info', 'Please complete your organizer profile before accessing the dashboard.');
        }

        if ($organizer->status === 'pending') {
            return redirect()->route('organizer.pending');
        }

        if ($organizer->status === 'rejected') {
            return redirect()->route('organizer.setup')
                ->with('error', 'Profil Penyelenggara Anda ditolak: ' . $organizer->admin_notes);
        }

        $organizerId = $organizer->id;

        $totalEvents = Event::where('organizer_id', $organizerId)->count();
        $activeEventsCount = Event::where('organizer_id', $organizerId)->where('status', 'active')->count();
        $pendingEventsCount = Event::where('organizer_id', $organizerId)->where('status', 'pending')->count();

        $totalParticipants = Ticket::whereIn('event_id', function ($q) use ($organizerId) {
            $q->select('id')->from('events')->where('organizer_id', $organizerId)->whereNull('deleted_at');
        })->whereIn('status', ['active', 'used'])->count();

        $financeData=app(\App\Services\FinanceService::class)->organizerFinanceData($organizer);
        $totalRevenue=$financeData['grossRevenue'];
        $availableBalance=$financeData['availableBalance'];
        $withdrawn=$financeData['withdrawn'];
        $pendingWithdrawal=$financeData['pendingWithdrawal'];

        $recentTickets = Ticket::whereIn('event_id', function ($q) use ($organizerId) {
            $q->select('id')->from('events')->where('organizer_id', $organizerId)->whereNull('deleted_at');
        })->with(['user', 'event', 'payment'])->latest()->take(5)->get();

        return view('organizer.dashboard', compact(
            'organizer',
            'totalEvents',
            'totalParticipants',
            'totalRevenue', 'availableBalance', 'withdrawn', 'pendingWithdrawal',
            'activeEventsCount',
            'pendingEventsCount',
            'recentTickets'
        ));
    }
}
