<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\SitemapGeneratorService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    private const CACHE_TTL = 86400;

    public function __construct(private readonly SitemapGeneratorService $generator)
    {
    }

    public function index(): Response
    {
        $xml = Cache::remember('sitemap:index', self::CACHE_TTL, fn () => $this->generator->index());

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function type(string $type): Response
    {
        if (!in_array($type, SitemapGeneratorService::TYPES, true)) {
            abort(404);
        }

        $xml = Cache::remember(
            "sitemap:{$type}",
            self::CACHE_TTL,
            fn () => $this->generator->sitemap($type),
        );

        if ($xml === null) {
            abort(404);
        }

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
