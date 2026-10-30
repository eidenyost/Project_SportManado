<?php

namespace App\Http\Controllers;

use App\Models\GalleryPhoto;

class PageController extends Controller
{
    public function panduan()
    {
        return view('pages.panduan');
    }

    public function galeri()
    {
        $photos = GalleryPhoto::latest()->get();
        return view('pages.galeri', compact('photos'));
    }
}
