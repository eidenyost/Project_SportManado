<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\OrganizerParticipantController;
use App\Http\Controllers\EventRegistrationController;
use App\Http\Controllers\OrganizerController;
use App\Http\Controllers\OrganizerPaymentController;
use App\Http\Controllers\OrganizerFinanceController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\NotificationController;
use App\Models\GalleryPhoto;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\CheckOrganizerStatus;
use App\Http\Middleware\RequireApprovedOrganizer;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
| No authentication required.
*/

Route::get('/', function () {
    $eventsActiveCount = \App\Models\Event::where('status', 'active')->count();
    $ticketsSold = \App\Models\Ticket::whereIn('status', ['active', 'used'])->count();
    $organizersCount = \App\Models\Organizer::where('status', 'approved')->count();

    $events = \App\Models\Event::with('organizer')
        ->withCount(['tickets' => function ($query) {
            $query->whereIn('status', ['pending', 'active', 'used']);
        }])
        ->where('status', 'active')
        ->latest()
        ->take(4)
        ->get();

    $galleryPhotos = GalleryPhoto::latest()->take(6)->get();

    return view('welcome', compact('events', 'eventsActiveCount', 'ticketsSold', 'organizersCount', 'galleryPhotos'));
})->name('home');

Route::middleware('auth')->prefix('notifications')->name('notifications.')->group(function () {
    Route::get('/', [NotificationController::class, 'index'])->name('index');
    Route::get('/unread-count', [NotificationController::class, 'unreadCount'])->name('unread-count');
    Route::post('/read-all', [NotificationController::class, 'markAllAsRead'])->name('read-all');
    Route::post('/{notification}/read', [NotificationController::class, 'markAsRead'])->name('read');
});

Route::get('/withdrawals/{withdrawal}/proof',[AdminDashboardController::class,'downloadWithdrawalProof'])->middleware('auth')->name('withdrawals.proof');

Route::middleware('auth')->group(function () {
    Route::get('/organizer/events/{event}/documents/{type}', [\App\Http\Controllers\PrivateDocumentController::class, 'event'])
        ->name('organizer.events.documents');
    Route::get('/event-registration/{eventRequest}/documents/{type}', [\App\Http\Controllers\PrivateDocumentController::class, 'registration'])
        ->name('event-registration.documents');
});

Route::post('/payments/notification', [OrganizerPaymentController::class, 'handleNotification'])
    ->name('payments.notification');

/*
|--------------------------------------------------------------------------
| Profile & Event Routes  (any authenticated user)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', CheckOrganizerStatus::class])->group(function () {
    // Dynamic Dashboard Router (all roles redirect here first)
    Route::get('/dashboard', [DashboardController::class, 'userDashboard'])->name('dashboard');

    // Event listing & detail (now requires login)
    Route::get('/events',          [EventController::class, 'index'])->name('events.index');
    Route::get('/events/{event}',  [EventController::class, 'show'])->name('events.show');

    Route::get('/profile',    [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile',  [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Organizer profile setup (accessible by regular users to become EO)
    Route::get('/organizer/setup',  [OrganizerController::class, 'setup'])->name('organizer.setup');
    Route::post('/organizer/setup', [OrganizerController::class, 'storeSetup'])->name('organizer.store-setup');
    Route::get('/organizer/pending', function () {
        $organizer = \App\Models\Organizer::where('user_id', auth()->id())->first();
        abort_if(!$organizer, 404);
        return view('organizer.pending', compact('organizer'));
    })->name('organizer.pending');

    // Halaman Panduan & Galeri
    Route::get('/panduan', [PageController::class, 'panduan'])->name('pages.panduan');
    Route::get('/galeri', [PageController::class, 'galeri'])->name('pages.galeri');
});

/*
|--------------------------------------------------------------------------
| User Routes  (role: user)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified', 'role:user'])->group(function () {
    // Tickets
    Route::get('/tickets',                         [TicketController::class, 'myTickets'])->name('tickets.index');
    Route::get('/events/{event}/register',         [TicketController::class, 'register'])->name('tickets.register');
    Route::get('/events/{event}/registration-state', [TicketController::class, 'registrationState'])->middleware('throttle:30,1')->name('tickets.registration-state');
    Route::post('/events/{event}/register',        [TicketController::class, 'submitPayment'])->name('tickets.submit-payment');
    Route::get('/tickets/{ticket}',                   [TicketController::class, 'show'])->name('tickets.show');
    Route::get('/tickets/{ticket}/check-payment',     [TicketController::class, 'checkPaymentStatus'])->name('tickets.check-payment');
    Route::get('/tickets/{ticket}/status', [TicketController::class, 'paymentStatus'])->middleware('throttle:60,1')->name('tickets.status');
    Route::post('/tickets/{ticket}/sync-payment', [TicketController::class, 'syncPaymentStatus'])->middleware('throttle:12,1')->name('tickets.sync-payment');
    Route::post('/tickets/{ticket}/demo-pay',         [TicketController::class, 'demoPay'])->name('tickets.demo-pay');

    // Event Registration Request (Participant → awaiting admin approval)
    Route::get('/event-registration',         [EventRegistrationController::class, 'create'])->name('event-registration.create');
    Route::post('/event-registration',        [EventRegistrationController::class, 'store'])->name('event-registration.store');
    Route::get('/event-registration/status',  [EventRegistrationController::class, 'status'])->name('event-registration.status');
});

/*
|--------------------------------------------------------------------------
| Organizer Routes  (role: organizer)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified', 'role:organizer', RequireApprovedOrganizer::class])
    ->prefix('organizer')
    ->name('organizer.')
    ->group(function () {
        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'organizerDashboard'])->name('dashboard');



        // Event CRUD (resource routes, minus the public index/show)
        Route::prefix('events')->name('events.')->group(function () {
            Route::get('/',              [EventController::class, 'organizerIndex'])->name('index');
            Route::get('/create',        [EventController::class, 'create'])->name('create');
            Route::post('/',             [EventController::class, 'store'])->name('store');
            Route::get('/{event}/edit',  [EventController::class, 'edit'])->name('edit');
            Route::patch('/{event}',     [EventController::class, 'update'])->name('update');
            Route::delete('/{event}',    [EventController::class, 'destroy'])->name('destroy');

            // Participants list and actions
            Route::get('/{event}/participants', [EventController::class, 'participants'])->name('participants');
            Route::get('/{event}/participants/export', [EventController::class, 'exportParticipants'])->name('participants.export');
            Route::get('/{event}/participants/{ticket}/edit', [OrganizerParticipantController::class, 'edit'])->name('participants.edit');
            Route::patch('/{event}/participants/{ticket}', [OrganizerParticipantController::class, 'update'])->name('participants.update');
            Route::post('/{event}/participants/{ticket}/cancel', [OrganizerParticipantController::class, 'cancel'])->name('participants.cancel');
            Route::post('/{event}/participants/{ticket}/approve', [EventController::class, 'approveFreeRegistration'])->name('participants.approve');
            Route::post('/{event}/participants/{ticket}/reject', [EventController::class, 'rejectRegistration'])->name('participants.reject');

            // Redirect to participants page (legacy)
            Route::get('/{event}/manage', [EventController::class, 'manage'])->name('manage');

            // QR scanning
            Route::get('/{event}/scan',         [TicketController::class, 'scanPage'])->name('scan');
            Route::post('/{event}/validate-qr', [TicketController::class, 'validateQr'])->name('validate-qr');
        });



        // Finance management
        Route::prefix('finances')->name('finances.')->group(function () {
            Route::get('/{event}',             [OrganizerFinanceController::class, 'financePage'])->name('finance');
            Route::get('/{event}/export',      [OrganizerFinanceController::class, 'exportFinance'])->name('export');
            Route::get('/{event}/withdraw',    [OrganizerFinanceController::class, 'withdrawPage'])->name('withdraw.page');
            Route::post('/{event}/withdraw',   [OrganizerFinanceController::class, 'withdraw'])->name('withdraw');
        });

        // Notifications
        Route::prefix('notifications')->name('notifications.')->group(function () {
            Route::get('/', [NotificationController::class, 'index'])->name('index');
            Route::post('/{notification}/read', [NotificationController::class, 'markAsRead'])->name('read');
            Route::post('/read-all', [NotificationController::class, 'markAllAsRead'])->name('read-all');
            Route::get('/unread-count', [NotificationController::class, 'unreadCount'])->name('unread-count');
        });
    });

/*
|--------------------------------------------------------------------------
| Admin Routes  (role: admin)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::post('/events/{event}/approve', [AdminDashboardController::class, 'approveEvent'])->name('events.approve');
        Route::post('/events/{event}/reject', [AdminDashboardController::class, 'rejectEvent'])->name('events.reject');
        Route::post('/events/{event}/complete', [AdminDashboardController::class, 'markAsCompleted'])->name('events.complete');
        Route::delete('/events/{event}', [AdminDashboardController::class, 'deleteEvent'])->name('events.delete');
        Route::delete('/users/{user}', [AdminDashboardController::class, 'deleteUser'])->name('users.delete');
        Route::patch('/users/{user}/role', [AdminDashboardController::class, 'updateRole'])->name('users.update-role');

        // Event Registration Requests from Participants
        Route::post('/event-requests/{eventRequest}/approve', [AdminDashboardController::class, 'approveEventRequest'])->name('event-requests.approve');
        Route::post('/event-requests/{eventRequest}/reject',  [AdminDashboardController::class, 'rejectEventRequest'])->name('event-requests.reject');

        // Organizer (EO) Registration Reviews
        Route::post('/organizers/{organizer}/approve', [AdminDashboardController::class, 'approveOrganizer'])->name('organizers.approve');
        Route::post('/organizers/{organizer}/reject', [AdminDashboardController::class, 'rejectOrganizer'])->name('organizers.reject');

        // Secure Document Downloads (Anti-Fraud Proposals/Permits/KTPs)
        Route::get('/download-document', [AdminDashboardController::class, 'downloadDocument'])->name('download-document');

        // Admin withdrawal actions
        Route::post('/withdrawals/{withdrawal}/approve', [AdminDashboardController::class, 'approveWithdrawal'])->name('withdrawals.approve');
        Route::post('/withdrawals/{withdrawal}/reject', [AdminDashboardController::class, 'rejectWithdrawal'])->name('withdrawals.reject');

        // Gallery management
        Route::post('/gallery', [AdminDashboardController::class, 'storeGalleryPhoto'])->name('gallery.store')->middleware('throttle:10,1');
        Route::delete('/gallery/{galleryPhoto}', [AdminDashboardController::class, 'destroyGalleryPhoto'])->name('gallery.destroy');

        // Real-time badge count polling
        Route::get('/pending-counts', [AdminDashboardController::class, 'pendingCounts'])->name('pending-counts');
    });

/*
|--------------------------------------------------------------------------
| Auth Scaffolding (Breeze / Jetstream generated)
|--------------------------------------------------------------------------
*/

require __DIR__.'/auth.php';
