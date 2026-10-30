<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Organizer;
use Illuminate\Support\Facades\Auth;

abstract class Controller
{
    protected function authOrganizer(): Organizer
    {
        return Organizer::where('user_id', Auth::id())->firstOrFail();
    }

    protected function authorizeEventOwnership(Event $event): Organizer
    {
        $organizer = $this->authOrganizer();
        abort_if($event->organizer_id !== $organizer->id, 403, 'Anda tidak memiliki hak akses untuk mengelola event ini.');
        return $organizer;
    }
}
