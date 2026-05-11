<?php

namespace Tests\Feature;

use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PublicImprintPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_imprint_page_renders_when_page_exists(): void
    {
        Cache::forget('language_settings.published');

        // View-Composer `ContactsServiceProvider` injiziert `contacts` für alle `public.*`-Views.
        Page::forceCreate([
            'slug' => 'contacts',
            'title' => ['en' => 'Contacts', 'de' => 'Kontakt'],
            'description' => ['en' => '<p></p>', 'de' => '<p></p>'],
            'content_data' => [
                'en' => [
                    'phones' => ['+41 00 000 00 00'],
                    'emails' => ['info@example.test'],
                    'whatsapp' => '',
                    'address' => 'Test',
                ],
                'de' => [
                    'phones' => ['+41 00 000 00 00'],
                    'emails' => ['info@example.test'],
                    'whatsapp' => '',
                    'address' => 'Test',
                ],
            ],
        ]);

        Page::forceCreate([
            'slug' => 'imprint',
            'title' => ['en' => 'Imprint', 'de' => 'Impressum'],
            'description' => ['en' => '<p>Legal</p>', 'de' => '<p>Rechtliches</p>'],
        ]);

        // Mit hideDefaultLocaleInURL entspricht die kanonische Default-Locale-URL `/imprint` (ohne `/en/`).
        $response = $this->get('/imprint');
        $response->assertOk()->assertViewIs('public.pages.imprint');

        $response->assertHeader('Cache-Control', 'public, max-age=0, must-revalidate');
        $this->assertNotNull($response->headers->get('Last-Modified'), 'Last-Modified header must be set on public pages');
    }

    public function test_imprint_page_returns_304_when_if_modified_since_matches(): void
    {
        Cache::forget('language_settings.published');

        Page::forceCreate([
            'slug' => 'contacts',
            'title' => ['en' => 'Contacts'],
            'description' => ['en' => '<p></p>'],
            'content_data' => [
                'en' => [
                    'phones' => ['+41 00 000 00 00'],
                    'emails' => ['info@example.test'],
                    'whatsapp' => '',
                    'address' => 'Test',
                ],
            ],
        ]);

        $imprint = Page::forceCreate([
            'slug' => 'imprint',
            'title' => ['en' => 'Imprint'],
            'description' => ['en' => '<p>Legal</p>'],
        ]);

        // Symfony's isNotModified compares HTTP-date precision (seconds), so use the page's exact updated_at.
        $ifModifiedSince = $imprint->updated_at->toRfc7231String();

        $response = $this->withHeaders(['If-Modified-Since' => $ifModifiedSince])->get('/imprint');

        $response->assertStatus(304);
    }
}
