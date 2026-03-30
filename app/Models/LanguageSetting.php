<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

class LanguageSetting extends Model
{
    protected $primaryKey = 'locale';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['locale', 'is_published'];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    /**
     * Get published status for all supported locales (excluding source locale).
     * Missing rows default to published = true.
     */
    public static function allWithStatus(string $sourceLang = 'en'): array
    {
        $all = LaravelLocalization::getSupportedLanguagesKeys();
        $stored = static::all()->keyBy('locale');

        return collect($all)
            ->filter(fn($l) => $l !== $sourceLang)
            ->mapWithKeys(fn($l) => [
                $l => $stored[$l]?->is_published ?? true,
            ])
            ->toArray();
    }

    /**
     * Get all published locale keys (used in frontend).
     */
    public static function publishedKeys(): array
    {
        return Cache::remember('language_settings.published', 60, function () {
            $all = LaravelLocalization::getSupportedLanguagesKeys();
            $stored = static::all()->keyBy('locale');

            return collect($all)
                ->filter(fn($l) => $stored[$l]?->is_published ?? true)
                ->values()
                ->toArray();
        });
    }

    /**
     * Toggle published status for a locale and bust cache.
     */
    public static function toggle(string $locale): bool
    {
        $setting = static::firstOrNew(['locale' => $locale]);
        $setting->is_published = !($setting->is_published ?? true);
        $setting->save();

        Cache::forget('language_settings.published');

        return $setting->is_published;
    }
}
