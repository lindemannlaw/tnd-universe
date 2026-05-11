<?php

namespace Tests\Feature\Services;

use App\Services\GoogleIndexingApiService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleIndexingApiServiceTest extends TestCase
{
    private string $credentialsPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->credentialsPath = tempnam(sys_get_temp_dir(), 'gia-creds-').'.json';
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($key, $privatePem);

        file_put_contents($this->credentialsPath, json_encode([
            'client_email' => 'tnd-indexing-bot@test-project.iam.gserviceaccount.com',
            'private_key' => $privatePem,
        ]));

        config()->set('services.google.indexing_api.enabled', true);
        config()->set('services.google.indexing_api.credentials_path', $this->credentialsPath);
        config()->set('services.google.indexing_api.daily_quota', 5);
    }

    protected function tearDown(): void
    {
        @unlink($this->credentialsPath);
        Cache::flush();
        parent::tearDown();
    }

    public function test_returns_not_configured_when_disabled(): void
    {
        config()->set('services.google.indexing_api.enabled', false);

        $result = (new GoogleIndexingApiService)->submit(['https://example.com/']);

        $this->assertSame('not_configured', $result['status']);
    }

    public function test_returns_success_with_zero_when_no_urls(): void
    {
        $result = (new GoogleIndexingApiService)->submit([]);

        $this->assertSame('success', $result['status']);
        $this->assertSame(0, $result['submitted']);
    }

    public function test_submits_urls_and_uses_bearer_token_from_oauth_exchange(): void
    {
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 'fake-token-123', 'expires_in' => 3600]),
            'indexing.googleapis.com/*' => Http::response(['urlNotificationMetadata' => []], 200),
        ]);

        $result = (new GoogleIndexingApiService)->submit([
            'https://tnduniverse.com/about',
            'https://tnduniverse.com/de/about',
        ]);

        $this->assertSame('success', $result['status']);
        $this->assertSame(2, $result['submitted']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'indexing.googleapis.com')
                && $request['type'] === 'URL_UPDATED'
                && $request->hasHeader('Authorization', 'Bearer fake-token-123');
        });
    }

    public function test_returns_error_when_token_exchange_fails(): void
    {
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['error' => 'invalid_grant'], 400),
        ]);

        $result = (new GoogleIndexingApiService)->submit(['https://tnduniverse.com/about']);

        $this->assertSame('error', $result['status']);
    }

    public function test_skips_urls_when_daily_quota_is_exceeded(): void
    {
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 't', 'expires_in' => 3600]),
            'indexing.googleapis.com/*' => Http::response([], 200),
        ]);

        // Quota is 5; pre-fill counter to 4 so only 1 of 3 URLs goes through.
        Cache::put('google-indexing-api:daily-'.date('Y-m-d'), 4, now()->endOfDay());

        $result = (new GoogleIndexingApiService)->submit([
            'https://tnduniverse.com/a',
            'https://tnduniverse.com/b',
            'https://tnduniverse.com/c',
        ]);

        $this->assertSame('success', $result['status']);
        $this->assertSame(1, $result['submitted']);
        $this->assertSame(2, $result['skipped']);
    }
}
