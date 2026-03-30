<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LanguageSetting;
use Illuminate\Http\JsonResponse;

class LanguageSettingController extends Controller
{
    public function toggle(string $locale): JsonResponse
    {
        $supported = \LaravelLocalization::getSupportedLanguagesKeys();

        if (!in_array($locale, $supported)) {
            return response()->json(['error' => 'Unsupported locale'], 422);
        }

        $isPublished = LanguageSetting::toggle($locale);

        return response()->json([
            'locale'       => $locale,
            'is_published' => $isPublished,
        ]);
    }
}
