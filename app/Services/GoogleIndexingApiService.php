<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Submits URL change notifications to Google's Indexing API.
 *
 * NOTE: Google's Indexing API is officially documented for JobPosting and
 * BroadcastEvent (live-stream) structured data. Using it for general URL
 * re-indexing works in practice but is outside Google's documented scope —
 * Google may rate-limit or stop honouring such requests at any time. We use
 * it as an *additional* signal alongside IndexNow (Bing/Yandex) and rely
 * primarily on Last-Modified headers + Search Console for Google.
 *
 * Service account JWT is signed locally (RS256 via openssl_sign) to avoid
 * pulling in the heavyweight google/apiclient package for one endpoint.
 */
class GoogleIndexingApiService
{
    private const OAUTH_TOKEN_URL = 'https://oauth2.googleapis.com/token';

    private const PUBLISH_URL = 'https://indexing.googleapis.com/v3/urlNotifications:publish';

    private const SCOPE = 'https://www.googleapis.com/auth/indexing';

    private const TOKEN_CACHE_TTL = 3000; // 50 min (Google tokens valid 1h)

    private const TOKEN_CACHE_KEY = 'google-indexing-api:access-token';

    public function isEnabled(): bool
    {
        return (bool) config('services.google.indexing_api.enabled')
            && is_readable((string) config('services.google.indexing_api.credentials_path'));
    }

    /**
     * Submit URL update/delete notifications. Failures are logged but never thrown.
     *
     * @param  array<int, string>  $urls
     * @param  string  $type  URL_UPDATED|URL_DELETED
     * @return array{status: string, submitted?: int, skipped?: int, message: string}
     */
    public function submit(array $urls, string $type = 'URL_UPDATED'): array
    {
        if (! $this->isEnabled()) {
            return ['status' => 'not_configured', 'message' => 'Google Indexing API not enabled.'];
        }

        $urls = array_values(array_unique(array_filter($urls)));
        if (empty($urls)) {
            return ['status' => 'success', 'submitted' => 0, 'message' => 'No URLs.'];
        }

        $token = $this->getAccessToken();
        if ($token === null) {
            return ['status' => 'error', 'message' => 'Failed to obtain access token.'];
        }

        $submitted = 0;
        $skipped = 0;

        foreach ($urls as $url) {
            if (! $this->reserveQuotaSlot()) {
                $skipped++;
                Log::warning('[GoogleIndexingApiService] Daily quota cap reached', ['skipped_url' => $url]);

                continue;
            }

            try {
                $response = Http::timeout(15)
                    ->withToken($token)
                    ->acceptJson()
                    ->post(self::PUBLISH_URL, ['url' => $url, 'type' => $type]);

                if ($response->successful()) {
                    $submitted++;
                } else {
                    Log::warning('[GoogleIndexingApiService] Publish non-success', [
                        'url' => $url,
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('[GoogleIndexingApiService] Publish threw', [
                    'url' => $url,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return [
            'status' => 'success',
            'submitted' => $submitted,
            'skipped' => $skipped,
            'message' => "Google Indexing API: {$submitted} submitted, {$skipped} skipped (quota).",
        ];
    }

    private function getAccessToken(): ?string
    {
        return Cache::remember(self::TOKEN_CACHE_KEY, self::TOKEN_CACHE_TTL, function (): ?string {
            try {
                $credentials = $this->loadCredentials();
                $jwt = $this->signJwt($credentials);

                $response = Http::asForm()->timeout(10)->post(self::OAUTH_TOKEN_URL, [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $jwt,
                ]);

                if (! $response->successful()) {
                    Log::warning('[GoogleIndexingApiService] Token exchange failed', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);

                    return null;
                }

                return (string) $response->json('access_token') ?: null;
            } catch (\Throwable $e) {
                Log::warning('[GoogleIndexingApiService] Token exchange threw', ['message' => $e->getMessage()]);

                return null;
            }
        });
    }

    /**
     * @return array{client_email: string, private_key: string}
     */
    private function loadCredentials(): array
    {
        $path = (string) config('services.google.indexing_api.credentials_path');
        $raw = file_get_contents($path);
        if ($raw === false) {
            throw new \RuntimeException("Cannot read credentials file: {$path}");
        }
        $json = json_decode($raw, true);
        if (! is_array($json) || ! isset($json['client_email'], $json['private_key'])) {
            throw new \RuntimeException('Service account JSON missing client_email or private_key.');
        }

        return ['client_email' => (string) $json['client_email'], 'private_key' => (string) $json['private_key']];
    }

    /**
     * Build and sign a JWT bearer assertion for OAuth2 token exchange.
     *
     * @param  array{client_email: string, private_key: string}  $credentials
     */
    private function signJwt(array $credentials): string
    {
        $now = time();
        $header = ['alg' => 'RS256', 'typ' => 'JWT'];
        $claims = [
            'iss' => $credentials['client_email'],
            'scope' => self::SCOPE,
            'aud' => self::OAUTH_TOKEN_URL,
            'iat' => $now,
            'exp' => $now + 3600,
        ];

        $segments = [
            $this->base64UrlEncode((string) json_encode($header, JSON_UNESCAPED_SLASHES)),
            $this->base64UrlEncode((string) json_encode($claims, JSON_UNESCAPED_SLASHES)),
        ];
        $signingInput = implode('.', $segments);

        $signature = '';
        $ok = openssl_sign($signingInput, $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256);
        if (! $ok) {
            throw new \RuntimeException('openssl_sign failed: '.openssl_error_string());
        }

        return $signingInput.'.'.$this->base64UrlEncode($signature);
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Increment daily counter atomically and return whether we may proceed.
     */
    private function reserveQuotaSlot(): bool
    {
        $cap = (int) config('services.google.indexing_api.daily_quota', 180);
        $key = 'google-indexing-api:daily-'.date('Y-m-d');

        $current = (int) Cache::get($key, 0);
        if ($current >= $cap) {
            return false;
        }

        Cache::put($key, $current + 1, now()->endOfDay());

        return true;
    }
}
