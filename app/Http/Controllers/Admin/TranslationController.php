<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DeepLTranslationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TranslationController extends Controller
{
    public function __construct(private readonly DeepLTranslationService $deepL) {}

    /**
     * Translate an array of text/html items via DeepL.
     *
     * Request body:
     *   source_lang  string           e.g. "en"
     *   target_lang  string           e.g. "de"
     *   items        array of objects { key: string, text: string, isHtml: bool }
     *
     * Response:
     *   translations  object  { [key]: translatedText }
     */
    public function translate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'source_lang'        => ['required', 'string', 'size:2'],
            'target_lang'        => ['required', 'string', 'size:2'],
            'items'              => ['required', 'array', 'min:1', 'max:200'],
            'items.*.key'        => ['required', 'string'],
            'items.*.text'       => ['present', 'nullable', 'string'],
            'items.*.isHtml'     => ['required', 'boolean'],
        ]);

        if (!$this->deepL->isConfigured()) {
            return response()->json(['error' => 'DeepL API not configured.'], 503);
        }

        $items = array_map(fn ($i) => [
            'text'   => (string) ($i['text'] ?? ''),
            'isHtml' => (bool) $i['isHtml'],
        ], $validated['items']);

        $keys        = array_column($validated['items'], 'key');
        $translated  = $this->deepL->translate($items, $validated['source_lang'], $validated['target_lang']);
        $result      = array_combine($keys, $translated);

        return response()->json(['translations' => $result]);
    }
}
