<x-app-layout>
    @push('styles')
    <style>
        .profile-card input[type="text"],
        .profile-card input[type="email"],
        .profile-card input[type="password"] {
            background-color: #080c14 !important;
            border-color: rgba(30, 41, 59, 0.8) !important;
            color: #ffffff !important;
        }
        .profile-card input:focus {
            border-color: #ff6b00 !important;
            box-shadow: 0 0 0 1px rgba(255, 107, 0, 0.2) !important;
        }
        .profile-card label {
            color: #cbd5e1 !important;
        }
    </style>
    @endpush

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            <!-- Header -->
            <div class="mb-6">
                <h1 class="text-3xl font-extrabold text-white font-display">
                    Pengaturan <span class="text-[#ff6b00]">Profil Saya</span>
                </h1>
                <p class="text-sm text-slate-400 mt-1">Kelola informasi profil, password, dan keamanan akun Anda.</p>
            </div>

            <!-- Profile Info Form Card -->
            <div class="p-6 sm:p-8 bg-[#0d1527] border border-slate-800 rounded-2xl shadow-sm profile-card">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <!-- Password Form Card -->
            <div class="p-6 sm:p-8 bg-[#0d1527] border border-slate-800 rounded-2xl shadow-sm profile-card">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <!-- Delete Form Card -->
            <div class="p-6 sm:p-8 bg-[#0d1527] border border-slate-800 rounded-2xl shadow-sm profile-card">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
