<?php

namespace Tests\Support;

use Illuminate\Http\UploadedFile;

final class TransferProof
{
    public static function image(string $name='transfer.png'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name,base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+yTioAAAAASUVORK5CYII='
        ));
    }
}
