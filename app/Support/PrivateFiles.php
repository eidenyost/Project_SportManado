<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PrivateFiles
{
    public static function respond(?string $path, array $prefixes): StreamedResponse
    {
        abort_unless(is_string($path) && $path !== '', 404, 'Berkas dokumen tidak ditemukan.');
        $segments = explode('/', $path);
        abort_if(str_starts_with($path, '/') || str_contains($path, '\\')
            || preg_match('/[\x00-\x1f\x7f]/', $path)
            || in_array('..', $segments, true) || in_array('.', $segments, true)
            || !Str::startsWith($path, $prefixes), 403, 'Akses tidak sah ke berkas dokumen.');

        $disk = Storage::disk('local');
        abort_unless($disk->exists($path), 404, 'Berkas dokumen tidak ditemukan.');
        return $disk->response($path, null, [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
            'Content-Security-Policy' => "sandbox; default-src 'none'",
        ]);
    }
}
