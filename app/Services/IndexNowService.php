<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IndexNowService
{
    private const ENDPOINT  = 'https://api.indexnow.org/indexnow';
    private const MAX_BATCH = 10000;

    public function isConfigured(): bool
    {
        return filled(config('services.indexnow.key')) && filled(config('services.indexnow.host'));
    }

    /**
     * Submits a list of URLs to IndexNow (Bing/Yandex).
     *
     * @param  array<int, string>  $urls
     * @return array{status: string, http_code?: int, submitted?: int, message: string}
     */
    public function submit(array $urls): array
    {
        if (!$this->isConfigured()) {
            return [
                'status'  => 'not_configured',
                'message' => 'IndexNow key/host are not configured.',
            ];
        }

        $urls = array_values(array_unique(array_filter($urls)));

        if (empty($urls)) {
            return [
                'status'    => 'success',
                'submitted' => 0,
                'message'   => 'No URLs to submit.',
            ];
        }

        $host        = (string) config('services.indexnow.host');
        $key         = (string) config('services.indexnow.key');
        $keyLocation = rtrim((string) config('app.url'), '/') . '/' . $key . '.txt';

        $totalSubmitted = 0;
        $lastStatus     = null;
        $lastBody       = '';

        foreach (array_chunk($urls, self::MAX_BATCH) as $chunk) {
            try {
                $response = Http::timeout(20)
                    ->acceptJson()
                    ->post(self::ENDPOINT, [
                        'host'        => $host,
                        'key'         => $key,
                        'keyLocation' => $keyLocation,
                        'urlList'     => $chunk,
                    ]);
            } catch (\Throwable $e) {
                Log::error('[IndexNowService] Request failed', ['message' => $e->getMessage()]);
                return [
                    'status'  => 'error',
                    'message' => 'Request failed: ' . $e->getMessage(),
                ];
            }

            $lastStatus = $response->status();
            $lastBody   = $response->body();

            if (!$response->successful() && $lastStatus !== 202) {
                Log::warning('[IndexNowService] API returned non-success', [
                    'status' => $lastStatus,
                    'body'   => $lastBody,
                ]);
                return [
                    'status'    => 'error',
                    'http_code' => $lastStatus,
                    'message'   => 'API returned HTTP ' . $lastStatus . ': ' . trim($lastBody),
                ];
            }

            $totalSubmitted += count($chunk);
        }

        return [
            'status'    => 'success',
            'http_code' => $lastStatus,
            'submitted' => $totalSubmitted,
            'message'   => "Submitted {$totalSubmitted} URL(s) to IndexNow.",
        ];
    }

    /**
     * Convenience: submit a single URL.
     *
     * @return array{status: string, http_code?: int, submitted?: int, message: string}
     */
    public function submitSingle(string $url): array
    {
        return $this->submit([$url]);
    }
}
