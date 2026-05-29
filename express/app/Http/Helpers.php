<?php

use App\Models\Project;

use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

if (!function_exists('generateCode')) {
    function generateCode($length = 5): string
    {
        do {
            $code = Str::random($length);
            $exists = Project::where('code', $code)->exists();
        }while($exists);

        return $code;
    }
}

if (!function_exists('generator')) {
    function generator($array): Generator
    {
        foreach ($array as $key => $value) {
            yield $key => $value;
        }
    }
}

if (!function_exists('collectHeaders')) {
    function collectHeaders($data): \Illuminate\Support\Collection
    {
        $result = [];
        foreach (generator($data) as $keys) {
            foreach (generator(collect($keys)->keys()) as $key) {
                $result[] = $key;
            }
        }
        natsort($result);
        return collect($result);
    }
}

if (!function_exists('copyQuestionnaire')) {
    function copyQuestionnaire($data, $questionnaire)
    {
        $fill = Arr::add($data, 'code', generateCode(10));
        $new_questionnaire = $questionnaire->replicate()->fill($fill);
        $new_questionnaire->save();

        return $new_questionnaire;
    }
}

if (!function_exists('copyQuestions')) {
    function copyQuestions($fill, $question)
    {
        $new_question = $question->replicate()->fill($fill);
        $new_question->save();

        return $new_question;
    }
}

if (!function_exists('cleanString')) {
    function cleanString($content, $equivalent = null)
    {
        //$string = htmlentities($content, null, 'utf-8');
        $content = str_replace("&nbsp;", " ", $content);
        $content = str_replace("&amp;", "&", $content);
        //$content = html_entity_decode($content);
        $stripped = strip_tags($content);
        return trim(empty($stripped) ? $equivalent : $stripped);
    }
}

if (!function_exists('get_secret')) {
    function get_secret($env, $default = '')
    {
        // Check if the environment variable is set
        if ($envValue = env($env)) {
            return $envValue;
        }
        
        // Construct the path to the secret file
        $secretFile = '/run/secrets/express_' . strtolower($env);
        
        // Check if the secret file exists and return its contents if it does
        if (file_exists($secretFile)) {
            return trim(file_get_contents($secretFile));
        }

        // Return the default value if no secret is found
        return $default;
    }
}

if (!function_exists('parse_user_agent_info')) {
    function parse_user_agent_info(\Illuminate\Http\Request $request): array
    {
        $ua = (string) ($request->header('User-Agent') ?? '');

        $platform = 'Unknown';
        $browserName = 'Unknown';
        $device = 'Unknown';

        // Basic platform detection
        if (stripos($ua, 'Windows') !== false) { $platform = 'Windows'; }
        elseif (stripos($ua, 'Mac OS X') !== false || stripos($ua, 'Macintosh') !== false) { $platform = 'macOS'; }
        elseif (stripos($ua, 'Android') !== false) { $platform = 'Android'; }
        elseif (stripos($ua, 'iPhone') !== false || stripos($ua, 'iPad') !== false) { $platform = 'iOS'; }
        elseif (stripos($ua, 'Linux') !== false) { $platform = 'Linux'; }

        // Basic browser detection
        if (stripos($ua, 'Edg/') !== false) { $browserName = 'Edge'; }
        elseif (stripos($ua, 'OPR/') !== false || stripos($ua, 'Opera') !== false) { $browserName = 'Opera'; }
        elseif (stripos($ua, 'Chrome/') !== false && stripos($ua, 'Chromium') === false) { $browserName = 'Chrome'; }
        elseif (stripos($ua, 'Safari/') !== false && stripos($ua, 'Chrome/') === false) { $browserName = 'Safari'; }
        elseif (stripos($ua, 'Firefox/') !== false) { $browserName = 'Firefox'; }
        elseif (stripos($ua, 'MSIE') !== false || stripos($ua, 'Trident/') !== false) { $browserName = 'IE'; }

        // Version extraction
        $version = null;
        $versionPatterns = [
            'Edge' => '/Edg\/([\d\.]+)/',
            'Opera' => '/(?:OPR|Opera)\/([\d\.]+)/',
            'Chrome' => '/Chrome\/([\d\.]+)/',
            'Safari' => '/Version\/([\d\.]+)/',
            'Firefox' => '/Firefox\/([\d\.]+)/',
            'IE' => '/(?:MSIE\s|rv:)([\d\.]+)/',
        ];
        if (isset($versionPatterns[$browserName]) && preg_match($versionPatterns[$browserName], $ua, $m)) {
            $version = $m[1];
        }

        // Device type
        if (stripos($ua, 'Mobile') !== false || stripos($ua, 'Android') !== false || stripos($ua, 'iPhone') !== false) {
            $device = 'Mobile';
        } elseif (stripos($ua, 'iPad') !== false || stripos($ua, 'Tablet') !== false) {
            $device = 'Tablet';
        } else {
            $device = 'Desktop';
        }

        $ip = $request->ip();

        return [
            'ip' => $ip,
            'browser' => trim($browserName . ' ' . ($version ?? '')),
            'device' => $device,
            'platform' => $platform,
        ];
    }
}