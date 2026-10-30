<?php

namespace App\Support;

use Illuminate\Http\Request;

class Search
{
    public static function term(?Request $request = null): string
    {
        $data = ($request ?? request())->validate(['search'=>['nullable','string','max:120']]);
        return trim($data['search'] ?? '');
    }
}
