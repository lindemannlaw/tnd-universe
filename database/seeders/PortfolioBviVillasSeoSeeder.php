<?php

namespace Database\Seeders;

use App\Models\Project;
use Illuminate\Database\Seeder;

class PortfolioBviVillasSeoSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->villas() as $config) {
            $project = Project::query()
                ->where('slug', 'like', $config['slug_like'])
                ->first();

            if (! $project) {
                $this->command?->warn("Skipped: no project matches slug LIKE {$config['slug_like']}");
                continue;
            }

            $project->setTranslation('seo_title', 'en', $config['seo_title']['en']);
            $project->setTranslation('seo_title', 'de', $config['seo_title']['de']);
            $project->setTranslation('seo_description', 'en', $config['seo_description']['en']);
            $project->setTranslation('seo_description', 'de', $config['seo_description']['de']);
            $project->setTranslation('seo_keywords', 'en', $config['seo_keywords']['en']);
            $project->setTranslation('seo_keywords', 'de', $config['seo_keywords']['de']);
            $project->setTranslation('geo_text', 'en', $config['geo_text']);
            $project->setTranslation('geo_text', 'de', $config['geo_text']);

            $project->lat = $config['lat'];
            $project->lon = $config['lon'];
            $project->geo_region = $config['geo_region'];

            $project->save();

            $this->command?->info("Seeded SEO/GEO: {$project->slug} ({$project->id})");
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function villas(): array
    {
        return [
            [
                'slug_like' => 'salt-spring%',
                'seo_title' => [
                    'en' => 'Salt Spring Villa Virgin Gorda — Luxury Villa for Sale BVI',
                    'de' => 'Salt Spring Villa Virgin Gorda — Luxus-Villa zum Verkauf BVI',
                ],
                'seo_description' => [
                    'en' => 'Exclusive luxury villa for sale on Virgin Gorda, BVI. Salt Spring Villa offers a private spa, pool and panoramic Caribbean views — a rare BVI real estate opportunity.',
                    'de' => 'Exklusive Luxus-Villa zum Verkauf auf Virgin Gorda, BVI. Salt Spring Villa mit privatem Spa, Pool und Panoramablick auf die Karibik — eine seltene BVI-Immobilien-Gelegenheit.',
                ],
                'seo_keywords' => [
                    'en' => 'salt spring villa virgin gorda, virgin gorda villa for sale, bvi villa for sale, luxury villa for sale bvi, villa with spa for sale bvi, bvi luxury real estate, caribbean luxury real estate, luxury home virgin gorda, exclusive caribbean property, luxury caribbean property, buy luxury villa caribbean, bvi investment property',
                    'de' => 'salt spring villa virgin gorda, virgin gorda villa kaufen, bvi villa kaufen, luxusvilla bvi kaufen, villa mit spa bvi kaufen, bvi luxusimmobilie, karibik luxusimmobilie, luxushaus virgin gorda, exklusive karibikimmobilie, karibik luxus immobilie, luxusvilla karibik kaufen, bvi investmentimmobilie',
                ],
                'geo_text' => 'Virgin Gorda, British Virgin Islands, Caribbean',
                'lat' => 18.4524,
                'lon' => -64.4407,
                'geo_region' => 'VG',
            ],
            [
                'slug_like' => 'red-rock%',
                'seo_title' => [
                    'en' => 'Red Rock Villa Virgin Gorda — Luxury Family Villa for Sale BVI',
                    'de' => 'Red Rock Villa Virgin Gorda — Familienluxus-Villa zum Verkauf BVI',
                ],
                'seo_description' => [
                    'en' => 'Red Rock Villa & Spa — a private luxury estate for sale on Virgin Gorda, BVI. Three bedroom suites, dedicated spa area and pool. A unique Caribbean island home.',
                    'de' => 'Red Rock Villa & Spa — privates Luxusanwesen zum Verkauf auf Virgin Gorda, BVI. Drei Schlafzimmer-Suiten, eigener Spa-Bereich und Pool. Ein einzigartiges karibisches Inselhaus.',
                ],
                'seo_keywords' => [
                    'en' => 'red rock villa virgin gorda, virgin gorda villa for sale, bvi villa for sale, luxury villa for sale caribbean, caribbean villa with pool for sale, bvi luxury real estate, private island property bvi, exclusive caribbean property, caribbean island home, buy luxury villa caribbean, private villa caribbean ownership, luxury home virgin gorda',
                    'de' => 'red rock villa virgin gorda, virgin gorda villa kaufen, bvi villa kaufen, luxusvilla karibik kaufen, karibik villa mit pool kaufen, bvi luxusimmobilie, privatinsel-immobilie bvi, exklusive karibikimmobilie, karibik inselhaus, luxusvilla karibik kaufen, privates karibikanwesen, luxushaus virgin gorda',
                ],
                'geo_text' => 'Virgin Gorda, British Virgin Islands, Caribbean',
                'lat' => 18.4524,
                'lon' => -64.4407,
                'geo_region' => 'VG',
            ],
        ];
    }
}
