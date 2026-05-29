<?php

namespace App\Octane\Listeners;

use Laravel\Octane\Events\RequestHandled;

class SetStaticAssetHeaders
{
    public function handle(RequestHandled $event): void
    {
        $request = $event->request;
        $response = $event->response;
        
        // Only apply headers to successful responses
        if (!$response->isSuccessful()) {
            return;
        }
        
        // Check if it's a Vite asset request
        $path = $request->getPathInfo();
        
        if (str_starts_with($path, '/build/assets/') && 
            (str_ends_with($path, '.js') || str_ends_with($path, '.css'))) {
            
            // Set cache headers only for successful responses
            $response->headers->set('Cache-Control', 'public, max-age=31536000, immutable');
            $response->headers->set('Expires', gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
        }
    }
}