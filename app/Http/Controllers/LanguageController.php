<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LanguageController extends Controller
{
    public function switch(Request $request, string $locale): mixed
    {
        $supportedLocales = ['en', 'kz', 'ru'];

        if (! in_array($locale, $supportedLocales)) {
            abort(404);
        }

        session(['locale' => $locale]);

        return redirect()->back();
    }
}
