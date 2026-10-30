<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Display the specific registration form.
     */
    public function createForm(Request $request): View
    {
        $role = $request->query('role', 'user');
        return view('auth.register-form', compact('role'));
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ];

        $isOrganizer = $request->input('role') === 'organizer';

        if ($isOrganizer) {
            $rules['organization_name'] = ['required', 'string', 'max:255'];
            $rules['phone']             = ['required', 'string', 'regex:/^(08|628)[0-9]{8,13}$/', 'max:20'];
            $rules['ktp']               = ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'];
            $rules['selfie']            = ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'];
            $rules['logo']              = ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'];
            $rules['description']       = ['nullable', 'string', 'max:1000'];
        }

        $validated = $request->validate($rules);

        $user = \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $request, $isOrganizer) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            if ($isOrganizer) {
                // Store files
                $ktpPath = $request->file('ktp')->store('organizer-ktps', 'local');
                $selfiePath = $request->file('selfie')->store('organizer-ktps', 'local');
            
                $logoPath = null;
                if ($request->hasFile('logo')) {
                    $logoPath = $request->file('logo')->store('organizer-logos', 'public');
                }

                \App\Models\Organizer::create([
                    'user_id'           => $user->id,
                    'organization_name' => $validated['organization_name'],
                    'phone'             => $validated['phone'],
                    'description'       => $validated['description'] ?? null,
                    'logo'              => $logoPath,
                    'ktp_path'          => $ktpPath,
                    'selfie_path'       => $selfiePath,
                    'status'            => 'pending',
                ]);
            }

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        if ($isOrganizer) {
            return redirect()->route('organizer.pending')
                ->with('success', 'Pendaftaran Akun Penyelenggara/EO berhasil dikirim! Menunggu persetujuan Admin.');
        }

        return redirect(route('dashboard', absolute: false));
    }
}
