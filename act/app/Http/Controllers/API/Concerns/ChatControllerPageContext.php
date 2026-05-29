<?php

namespace App\Http\Controllers\API\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

trait ChatControllerPageContext
{
    private function detectCurrentPage(Request $request): string
    {
        $websitePosition = $request->websitePosition;
        $platform = $request->platform;
        $currentUrl = $request->input('current_url');
        $activeSidebar = $request->input('active_sidebar');
        $sidebarState = $request->input('sidebar_state');
        $mainPage = $request->input('main_page');
        
        Log::info('Current Page Detection Input', [
            'websitePosition' => $websitePosition,
            'platform' => $platform,
            'currentUrl' => $currentUrl,
            'activeSidebar' => $activeSidebar,
            'sidebarState' => $sidebarState,
            'mainPage' => $mainPage
        ]);
        
        $urlSegments = $this->parseUrlSegments($currentUrl ?: $websitePosition);
        
        if ($activeSidebar) {
            $sidebarPage = $this->mapSidebarToPage($activeSidebar);
            if ($sidebarPage) {
                Log::info('Active sidebar detected - using Neo4j context for page', [
                    'activeSidebar' => $activeSidebar, 
                    'mappedPage' => $sidebarPage,
                    'mainPage' => $mainPage,
                    'user_id' => optional($request->user())->id
                ]);
                return $sidebarPage;
            }
        }
        
        if ($sidebarState && is_array($sidebarState)) {
            foreach ($sidebarState as $expandedItem) {
                $expandedPage = $this->mapSidebarToPage($expandedItem);
                if ($expandedPage) {
                    Log::info('Expanded sidebar detected', ['expandedItem' => $expandedItem, 'mappedPage' => $expandedPage, 'mainPage' => $mainPage]);
                    return $expandedPage;
                }
            }
        }
        
        if ($mainPage) {
            Log::info('Using main page from frontend', ['mainPage' => $mainPage]);
            return $mainPage;
        }
        
        $urlPage = $this->detectPageFromUrl($urlSegments);
        if ($urlPage) {
            Log::info('URL-based page detection', ['urlSegments' => $urlSegments, 'detectedPage' => $urlPage]);
            return $urlPage;
        }
        
        $legacyPage = $this->mapLegacyPosition($websitePosition);
        if ($legacyPage) {
            Log::info('Legacy position mapping', ['websitePosition' => $websitePosition, 'mappedPage' => $legacyPage]);
            return $legacyPage;
        }
        
        Log::info('Using default fallback', [
            'websitePosition' => $websitePosition,
            'platform' => $platform,
            'currentUrl' => $currentUrl,
            'activeSidebar' => $activeSidebar
        ]);
        return 'ACT';
    }
    
    private function getPageContext(Request $request): array
    {
        $activeSidebar = $request->input('active_sidebar');
        $mainPage = $request->input('main_page') ?: $request->websitePosition;
        
        return [
            'main_page' => $mainPage,
            'active_sidebar' => $activeSidebar ? $this->mapSidebarToPage($activeSidebar) : null,
        ];
    }
    
    private function parseUrlSegments(string $url): array
    {
        if (empty($url)) {
            return [];
        }
        
        $path = parse_url($url, PHP_URL_PATH);
        if (!$path) {
            return [];
        }
        
        $segments = array_filter(explode('/', trim($path, '/')));
        
        $filteredSegments = array_filter($segments, function ($segment) {
            return !in_array($segment, ['www', 'act', 'connoisseur-suite', 'co', 'uk']);
        });
        
        return array_values($filteredSegments);
    }
    
    private function mapSidebarToPage(string $sidebarItem): ?string
    {
        $sidebarMapping = config('ai_act.sidebar_to_page', []);
        $normalizedItem = strtolower(str_replace(['_', ' '], '-', $sidebarItem));
        return $sidebarMapping[$normalizedItem] ?? null;
    }
    
    private function detectPageFromUrl(array $segments): ?string
    {
        if (empty($segments)) return null;
        
        if (in_array('survey', $segments) || in_array('questionnaire', $segments)) {
            $subPages = ['questions' => 'questionnaire', 'settings' => 'settings', 'themes' => 'themes', 
                         'gallery' => 'gallery', 'advanced' => 'advanced', 'preview' => 'preview'];
            foreach ($subPages as $page => $result) {
                if (in_array($page, $segments)) return $result;
            }
            return 'questionnaire';
        }
        
        $urlMapping = config('ai_act.url_to_page', []);
        foreach ($segments as $segment) {
            if (isset($urlMapping[$segment])) {
                return $urlMapping[$segment];
            }
        }
        
        return null;
    }
    
    private function mapLegacyPosition(string $websitePosition): ?string
    {
        $legacyMapping = config('ai_act.legacy_position_to_page', []);
        return $legacyMapping[$websitePosition] ?? null;
    }
}
