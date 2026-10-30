<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $query = Notification::with('notifiable')->where('user_id', Auth::id());

        if ($request->get('filter') === 'unread') {
            $query->where('is_read', false);
        }

        $totalUnread = Notification::where('user_id',Auth::id())->where('is_read',false)->count();
        $search = \App\Support\Search::term($request);
        if ($search !== '') $query->where(fn ($q) => $q->where('title','like','%'.$search.'%')->orWhere('message','like','%'.$search.'%'));
        $notifications = $query->latest()->paginate(15)->withQueryString();

        $navigation=Auth::user()->isAdmin() ? [
            'activeTab'=>'notifications',
            'totalPendingOrganizers'=>\App\Models\Organizer::where('status','pending')->count(),
            'totalPendingEvents'=>\App\Models\Event::where('status','pending')->count(),
            'totalPendingRequests'=>\App\Models\EventRegistrationRequest::where('status','pending')->count(),
        ] : [];
        return view('organizer.notifications.index', array_merge(compact('notifications','totalUnread'),$navigation));
    }

    public function markAsRead(Notification $notification)
    {
        abort_if($notification->user_id !== Auth::id(), 403);

        $notification->update(['is_read' => true]);

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'count'=>Notification::where('user_id',Auth::id())->where('is_read',false)->count()]);
        }

        return redirect()->back();
    }

    public function markAllAsRead()
    {
        Notification::where('user_id', Auth::id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'count'=>Notification::where('user_id',Auth::id())->where('is_read',false)->count()]);
        }

        return redirect()->back();
    }

    public function unreadCount()
    {
        $count = Notification::where('user_id', Auth::id())
            ->where('is_read', false)
            ->count();

        $latest = Notification::where('user_id', Auth::id())
            ->latest()
            ->take(5)
            ->get();

        return response()->json([
            'count' => $count,
            'notifications' => $latest,
        ]);
    }
}
