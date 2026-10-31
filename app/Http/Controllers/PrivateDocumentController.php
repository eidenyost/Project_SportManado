<?php

namespace App\Http\Controllers;

use App\Models\{Event, EventRegistrationRequest};
use App\Support\PrivateFiles;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PrivateDocumentController extends Controller
{
    public function event(Request $request, Event $event, string $type): StreamedResponse
    {
        abort_unless($request->user()->isAdmin() || $event->organizer?->user_id === $request->user()->id, 403);
        $fields = ['proposal' => 'event-proposals/', 'permit' => 'event-permits/', 'ktp_image' => 'organizer-ktps/'];
        abort_unless(isset($fields[$type]), 404);
        return PrivateFiles::respond($event->{$type}, [$fields[$type]]);
    }

    public function registration(Request $request, EventRegistrationRequest $eventRequest, string $type): StreamedResponse
    {
        abort_unless($request->user()->isAdmin() || $eventRequest->user_id === $request->user()->id, 403);
        $fields = ['proposal' => ['proposal_file', 'event-reg-proposals/'], 'permit' => ['permit_file', 'event-reg-permits/']];
        abort_unless(isset($fields[$type]), 404);
        [$field, $prefix] = $fields[$type];
        return PrivateFiles::respond($eventRequest->{$field}, [$prefix]);
    }
}
