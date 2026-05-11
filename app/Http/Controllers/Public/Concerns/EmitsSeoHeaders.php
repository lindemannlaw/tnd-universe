<?php

namespace App\Http\Controllers\Public\Concerns;

use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

trait EmitsSeoHeaders
{
    /**
     * Render a view as a Response with Last-Modified + Cache-Control headers
     * so search engines can use If-Modified-Since for efficient re-crawling.
     *
     * Returns 304 Not Modified when the request's If-Modified-Since matches.
     *
     * @param  array<string,mixed>  $data
     */
    protected function seoResponse(string $view, array $data, ?Carbon $lastModified = null): Response
    {
        $response = response()->view($view, $data);

        if ($lastModified === null) {
            return $response;
        }

        $response->setLastModified($lastModified);
        $response->headers->set('Cache-Control', 'public, max-age=0, must-revalidate');

        if ($response->isNotModified(request())) {
            return $response;
        }

        return $response;
    }
}
