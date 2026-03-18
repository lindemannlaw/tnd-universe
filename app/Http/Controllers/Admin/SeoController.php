<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SeoGenerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SeoController extends Controller
{
    public function __construct(private readonly SeoGenerationService $seo) {}

    /**
     * Generate SEO metadata from content context via OpenAI.
     *
     * Request body:
     *   locale   string   Target locale for generated copy, e.g. "en"
     *   context  object   { title, short_description, location, property_type, area, content }
     *
     * Response:
     *   { seo_title, seo_description, seo_keywords }
     */
    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'locale'                    => ['required', 'string', 'size:2'],
            'context'                   => ['required', 'array'],
            'context.title'             => ['nullable', 'string', 'max:500'],
            'context.short_description' => ['nullable', 'string', 'max:2000'],
            'context.location'          => ['nullable', 'string', 'max:200'],
            'context.property_type'     => ['nullable', 'string', 'max:200'],
            'context.area'              => ['nullable', 'string', 'max:20'],
            'context.content'           => ['nullable', 'string', 'max:3000'],
        ]);

        if (!$this->seo->isConfigured()) {
            return response()->json(['error' => 'OpenAI API not configured.'], 503);
        }

        $result = $this->seo->generate($validated['context'], $validated['locale']);

        return response()->json($result);
    }
}
