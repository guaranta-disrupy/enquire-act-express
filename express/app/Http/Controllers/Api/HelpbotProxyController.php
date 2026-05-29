<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Proxies chat stream requests to the centralized ai-helpbot API (server-to-server).
 * Same-origin for the browser (no CORS); aligned with Fluent's setup.
 * Streams the SSE response back so the UI gets word-by-word streaming.
 */
class HelpbotProxyController extends Controller
{
    /**
     * Proxy POST to ai-helpbot /api/chat/message/stream and stream the response back.
     */
    public function proxyStream(Request $request)
    {
        $helpbotUrl = config('services.ai_helpbot.url');
        if (empty($helpbotUrl)) {
            Log::warning('[HelpbotProxyController] ai_helpbot.url not configured');
            return response()->json(['message' => 'Helpbot service not configured.'], 503);
        }

        $url = rtrim($helpbotUrl, '/') . '/api/chat/message/stream';
        $body = $request->getContent();
        $payload = [];
        if ($request->header('Content-Type') && str_contains($request->header('Content-Type'), 'application/json') && $body !== '') {
            $decoded = json_decode($body, true);
            if (is_array($decoded)) {
                $payload = [
                    'prompt' => isset($decoded['prompt']) ? mb_substr((string) $decoded['prompt'], 0, 200) : null,
                    'websitePosition' => $decoded['websitePosition'] ?? null,
                    'platform' => $decoded['platform'] ?? null,
                    'intent' => $decoded['intent'] ?? null,
                    'research_mode' => $decoded['research_mode'] ?? null,
                ];
            }
        }
        Log::info('[HelpbotProxyController] 5 passing to ai helpbot', $payload ?: ['body' => 'non-JSON or empty']);

        $headers = [
            'Accept' => 'text/event-stream',
            'X-Requested-With' => 'XMLHttpRequest',
        ];
        if ($request->user()) {
            $headers['X-User-Id'] = (string) $request->user()->id;
        }
        // Forward Bearer token so ai-helpbot can authenticate (same as frontend direct call)
        $authHeader = $request->header('Authorization');
        if ($authHeader) {
            $headers['Authorization'] = $authHeader;
        }

        // Forward Content-Type and body (supports JSON or multipart/form-data)
        $contentType = $request->header('Content-Type');
        if ($contentType) {
            $headers['Content-Type'] = $contentType;
        }

        try {
            $client = new GuzzleClient(['timeout' => 120, 'connect_timeout' => 10]);
            $response = $client->request('POST', $url, [
                'headers' => $headers,
                'body' => $body,
                'stream' => true,
            ]);

            $statusCode = $response->getStatusCode();
            $responseContentType = $response->getHeaderLine('Content-Type');

            if ($statusCode !== 200) {
                Log::warning('[HelpbotProxyController] ai-helpbot returned non-200', ['status' => $statusCode]);
                return response()->json(['message' => 'Helpbot returned an error.'], 502);
            }

            // When helpbot returns JSON (e.g. simple greeting), convert to SSE so the frontend can display it
            if (str_contains($responseContentType, 'application/json')) {
                $bodyString = $response->getBody()->getContents();
                $data = json_decode($bodyString, true);
                $message = is_array($data) ? ($data['message'] ?? $data['text'] ?? '') : '';
                $ssePayload = json_encode(['text' => $message, 'done' => true]);
                return response()->stream(function () use ($ssePayload) {
                    echo 'data: ' . $ssePayload . "\n\n";
                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();
                }, 200, [
                    'Content-Type' => 'text/event-stream',
                    'Cache-Control' => 'no-cache',
                    'Connection' => 'keep-alive',
                    'X-Accel-Buffering' => 'no',
                ]);
            }

            // Stream SSE response back (same headers as ai-helpbot for proper streaming in UI)
            $stream = $response->getBody();
            return response()->stream(function () use ($stream) {
                while (! $stream->eof()) {
                    echo $stream->read(8192);
                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();
                }
            }, 200, [
                'Content-Type' => $responseContentType ?: 'text/event-stream',
                'Cache-Control' => 'no-cache',
                'Connection' => 'keep-alive',
                'X-Accel-Buffering' => 'no',
            ]);
        } catch (RequestException $e) {
            Log::warning('[HelpbotProxyController] ai-helpbot request failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Unable to reach the help service. Please try again.',
            ], 502);
        } catch (\Throwable $e) {
            Log::warning('[HelpbotProxyController] ai-helpbot error', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'An error occurred. Please try again.',
            ], 502);
        }
    }
}
