<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Generic DeepL translation service.
 * Usable from any module – just inject or resolve via app().
 */
class DeepLTranslationService
{
    private string $apiKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.deepl.api_key') ?? '';
        // Free keys end with :fx and use the free API endpoint
        $this->baseUrl = str_ends_with($this->apiKey, ':fx')
            ? 'https://api-free.deepl.com/v2'
            : 'https://api.deepl.com/v2';
    }

    public function isConfigured(): bool
    {
        return filled($this->apiKey);
    }

    /**
     * Translate an array of items.
     *
     * Each item must be: ['text' => string, 'isHtml' => bool]
     * Returns an array of translated strings in the same order.
     *
     * @param  array<int, array{text: string, isHtml: bool}>  $items
     * @return array<int, string>
     */
    public function translate(array $items, string $sourceLang, string $targetLang): array
    {
        $originals = array_column($items, 'text');

        if (empty($items) || !$this->isConfigured()) {
            return $originals;
        }

        $results = $originals;

        // Separate HTML vs plain text items to use appropriate tag_handling
        foreach ([false, true] as $isHtml) {
            $batch = [];
            foreach ($items as $idx => $item) {
                if ((bool) $item['isHtml'] === $isHtml) {
                    $batch[$idx] = $item['text'];
                }
            }

            $translated = $this->callApi($batch, $sourceLang, $targetLang, $isHtml);

            foreach ($translated as $idx => $text) {
                $results[$idx] = $text;
            }
        }

        // Swiss German: replace ß → ss for DE target
        if (strtolower($targetLang) === 'de') {
            $results = array_map(fn ($t) => str_replace('ß', 'ss', $t), $results);
        }

        return $results;
    }

    /**
     * @param  array<int, string>  $texts  Keyed by original index
     * @return array<int, string>
     */
    private function callApi(array $texts, string $sourceLang, string $targetLang, bool $isHtml): array
    {
        if (empty($texts)) {
            return [];
        }

        // Skip empty strings, preserve them in output
        $toTranslate = array_filter($texts, fn ($t) => filled(trim(strip_tags((string) $t))));
        $results     = $texts;

        if (empty($toTranslate)) {
            return $results;
        }

        $indices = array_keys($toTranslate);
        $values  = array_values($toTranslate);

        try {
            $payload = [
                'text'        => $values,
                'target_lang' => strtoupper($targetLang),
                // Omit source_lang to let DeepL auto-detect (better for short terms)
            ];

            if ($isHtml) {
                $payload['tag_handling'] = 'html';
            }

            $response = Http::withHeaders([
                'Authorization' => 'DeepL-Auth-Key ' . $this->apiKey,
                'Content-Type'  => 'application/json',
            ])->timeout(30)->post($this->baseUrl . '/translate', $payload);

            if (!$response->successful()) {
                Log::warning('[DeepL] API request failed', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);

                return $results;
            }

            $translations = $response->json('translations', []);

            foreach ($indices as $i => $originalIdx) {
                $results[$originalIdx] = $translations[$i]['text'] ?? $results[$originalIdx];
            }
        } catch (\Throwable $e) {
            Log::error('[DeepL] Exception during translation', [
                'message' => $e->getMessage(),
            ]);
        }

        return $results;
    }
}
