<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GalleryProxyController extends Controller
{
    public function show(Request $request)
    {
        $url = $request->query('u');
        if (!$url) {
            return response('Missing image URL', 400);
        }

        // Optional: allowlist hosts for safety
        $allowedHosts = [
            'express.connoisseur-suite.com',
            'express.connoisseur-suite.co.uk',
            'express2.splitsecondsurveys.co.uk',
            'express.splitsecondsurveys.co.uk',
            'app.connoisseur-suite.com',
            'app.connoisseur-suite.co.uk',
            'connoisseur-suite.co.uk',
            'connoisseur-suite.com',
        ];
        $host = parse_url($url, PHP_URL_HOST);
        if (!in_array($host, $allowedHosts, true)) {
            return response('Host not allowed', 400);
        }

        try {
            $resp = Http::timeout(10)
                ->withHeaders([
                    'Accept' => 'image/*,*/*',
                    'User-Agent' => 'Mozilla/5.0 (compatible; GalleryProxy/1.0)'
                ])
                ->withoutRedirecting()
                ->get($url);

            if (!$resp->ok()) {
                \Log::warning("Gallery proxy failed to fetch image: {$url}, Status: {$resp->status()}, Body: " . substr($resp->body(), 0, 200));
                return response('Image not found', 404);
            }

            $bytes = $resp->body();
            $mime = $resp->header('Content-Type', 'image/png');
            
            // Validate that we actually got image data
            if (empty($bytes) || strlen($bytes) < 100) {
                \Log::warning("Gallery proxy received empty or suspiciously small image: {$url}, Size: " . strlen($bytes));
                return response('Invalid image data', 404);
            }
            
            $now  = gmdate('D, d M Y H:i:s') . ' GMT';
            $etag = '"' . md5($bytes) . '"';

            if ($request->headers->get('If-None-Match') === $etag) {
                return response('', 304)
                    ->header('Cache-Control', 'public, max-age=31536000, immutable')
                    ->header('ETag', $etag)
                    ->header('Last-Modified', $now)
                    ->header('Content-Type', $mime);
            }

            return response($bytes, 200)
                ->header('Cache-Control', 'public, max-age=31536000, immutable')
                ->header('ETag', $etag)
                ->header('Last-Modified', $now)
                ->header('Content-Type', $mime);
        } catch (\Throwable $e) {
            \Log::error("Gallery proxy error: {$e->getMessage()}", [
                'url' => $url,
                'trace' => $e->getTraceAsString()
            ]);
            return response('Proxy error', 500);
        }
    }
}


