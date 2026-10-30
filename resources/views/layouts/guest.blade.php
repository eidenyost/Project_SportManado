<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'SportManado') }}</title>

        <!-- Local application assets -->
        @include('layouts._assets')

        <style>
            body { font-family: 'Inter', sans-serif; }
            .sports-gradient {
                background: radial-gradient(circle at top right, rgba(255, 107, 0, 0.08), transparent),
                            radial-gradient(circle at bottom left, rgba(13, 21, 39, 0.8), transparent),
                            #080c14;
            }
            /* Custom dark inputs inside guest layout */
            .guest-card input[type="text"],
            .guest-card input[type="email"],
            .guest-card input[type="password"],
            .guest-card input[type="number"],
            .guest-card input[type="tel"] {
                background-color: #080c14 !important;
                border-color: rgba(30, 41, 59, 0.8) !important;
                color: #ffffff !important;
            }
            .guest-card input::placeholder {
                color: #475569 !important;
            }
            .guest-card input:focus {
                border-color: #ff6b00 !important;
                box-shadow: 0 0 0 1px rgba(255, 107, 0, 0.2) !important;
            }
            .guest-card label {
                color: #cbd5e1 !important;
                font-family: 'Archivo', sans-serif !important;
                font-weight: 700 !important;
                text-transform: uppercase !important;
                letter-spacing: 0.05em !important;
                font-size: 11px !important;
            }
            .guest-card button[type="submit"] {
                background-color: #ff6b00 !important;
                color: #ffffff !important;
                border: none !important;
                font-family: 'Archivo', sans-serif !important;
                font-weight: 700 !important;
                border-radius: 0.75rem !important;
                box-shadow: 0 0 20px rgba(255, 107, 0, 0.25) !important;
            }
            .guest-card button[type="submit"]:hover {
                background-color: #e05e00 !important;
            }
            .guest-card a {
                color: #ff6b00 !important;
            }
            .guest-card a:hover {
                color: #f97316 !important;
                text-decoration: underline !important;
            }
        </style>
    </head>
    <body class="font-sans antialiased text-white sports-gradient min-h-screen relative flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="relative z-10 w-full sm:max-w-md flex flex-col items-center guest-card">
            <div class="mb-8">
                <a href="{{ url('/') }}" class="flex flex-col items-center gap-2">
                    <span class="text-4xl font-extrabold tracking-tight text-white drop-shadow-md" style="font-family:'Archivo',sans-serif"><span class="text-[#ff6b00]">Sport</span>Manado</span>
                    <span class="text-xs font-semibold text-slate-400 tracking-widest uppercase">E-Ticketing Portal</span>
                </a>
            </div>

            <!-- Solid brand card -->
            <div class="w-full bg-[#0d1527] border border-slate-800/80 shadow-2xl rounded-3xl p-8 sm:p-10">
                {{ $slot }}
            </div>
        </div>
        @stack('scripts')
    </body>
</html>
