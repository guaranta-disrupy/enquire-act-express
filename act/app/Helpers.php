<?php

use App\Models\MainSurvey;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Alsaloul\Agent\Agent;
use League\Uri\Parser;
use League\Uri\Parser\QueryString;
use Illuminate\Support\Facades\Gate;


if (! function_exists('unique_code')) {
    function unique_code($length = 4) : string
    {
        $codes = DB::table('projects')->select('code')->get()->toArray();
        do {
            $code = Str::random($length);

        } while (in_array($code, $codes));
        return $code;
    }
}

if (! function_exists('projectsList')) {
    function projectsList(): array
    {
        $user = Auth::user();
        if (Cache::has($user->currentTeam->id)) {
            $projects = Cache::get($user->currentTeam->id);
            if ($user->currentTeam->projects()->count() !== count($projects)) {
                $projects = DB::table('projects')
                    ->where('team_id', $user->currentTeam->id)
                    ->whereNull('deleted_at')
                    ->select('uuid', 'name')
                    ->orderBy('id', 'desc')
                    ->get()
                    ->toArray();
                try {
                    cache([$user->currentTeam->id => $projects]);
                } catch (Exception $e){}
            }
            return $projects;
        } else {
            $projects = DB::table('projects')
                ->where('team_id', $user->currentTeam->id)
                ->whereNull('deleted_at')
                ->select('uuid', 'name')
                ->orderBy('id', 'desc')
                ->get()
                ->toArray();
            Cache::put($user->currentTeam->id, $projects);
            return $projects;
        }
    }
}

if (!function_exists('generator')) {
    function generator($data)
    {
        foreach ($data as $key => $value) {
            yield $key => $value;
        }
    }
}

if (!function_exists('generateToken')) {
    function generateToken()
    {
        $user = User::find(1);
        return $user->createToken('engine', ['record:create'])->plainTextToken;
    }
}

if (!function_exists('getClientInfo')) {
    function getClientInfo(Request $request): Collection
    {
        $agent = new Agent();
        $browser = $agent->browser();
        $platform = $agent->platform();
        return collect([
            'ip' => $request->ip(),
            'browser' => $browser . " " . $agent->version($browser),
            'device' => ($agent->isDesktop() ? 'Desktop ' : 'Mobile ') . $agent->device(),
            'platform' => $platform . " " . $agent->version($platform),
            'url' => $request->input('url')
        ])->merge($request->variables ?? []);
    }
}

if (!function_exists('generateTemporaryTable')) {
    function generateTemporaryTable()
    {
        $table_name = Str::random(10);
        Schema::create($table_name, function(Blueprint $table) {
            $table->temporary();
            $table->string('id');
        });
        return $table_name;
    }
}

if (!function_exists('cleanString')) {
    function cleanString($content, $equivalent = null)
    {
        $content = str_replace("&nbsp;", " ", $content);
        $content = str_replace("&amp;", "&", $content);
        $stripped = strip_tags($content);
        return trim(empty($stripped) ? $equivalent : $stripped);
    }
}

// Convert URL query string into associative array
if (!function_exists("query_parser")) {
    function query_parser($url)
    {
        $parser = new Parser();
        $query_string = $parser($url)['query'];
        $pairs = QueryString::extract($query_string, "&", PHP_QUERY_RFC1738);
        
        return $pairs;
    }
}

if (!function_exists('percentStat')) {
    /**
     * @param $val
     * @return string|null
     */
    function percentStat($val)
    {
        $result = '';
        if (in_array($val, range(70, 100))) {
            $result = '***';
        } else if (in_array($val, range(60, 69))) {
            $result = '**';
        } else if (in_array($val, range(55, 59))) {
            $result = '*';
        } else if (in_array($val, range(46, 54))) {
            $result = 'ns';
        } else if (in_array($val, range(41, 45))) {
            $result = '*';
        } else if (in_array($val, range(31, 40))) {
            $result = '**';
        } else if (in_array($val, range(0, 30))) {
            $result = '***';
        }
        return !is_null($val) ? $result : null;
    }
}

// Don't update or it will messed up the engine display!!!
if (!function_exists('textReplacer')) {
    function textReplacer($variables, $text, $use_attributes_as_placeholder = 'true')
    {
        foreach (generator($variables) as $variable) {
            $text = Str::replace("{{" . $variable['variable'] . "}}", ($use_attributes_as_placeholder == 'true' ? $variable['value'] : ($variable['equivalent'] ?? $variable['value'])) ?? "", $text);
        }
        return trim($text);
    }
}

if (! function_exists('explode2')) {
    function explode2($text): array|bool
    {
        return preg_split("/\\r\\n|\\r|\\n/", $text);
    }
}

function criteria_iterator($conditions, $url_array)
{
    $status = false;
    if (count($conditions) == 0) {
        return true;
    }
    $process = processCriteria($url_array, $conditions);
    do {
        $process = verifyCriteria($process);
    } while (count($process) > 1);
    if ($status === false) {
        $status = $process[0]['status'];
    }
    return $status;
}

function processCriteria($request, $conditions): array
{
    $result = [];

    foreach (generator($conditions) as $key => $condition) {
        if ($condition['method'] == 'segment') {
            $result[$key]['conditions'] = processCriteria($request, $condition['conditions']);
        } else {
            $result[$key]['status'] = processOperator($request, $condition);
        }
        $result[$key]['left_operator'] = $condition['left_operator'];
    }

    return $result;
}

function processOperator($request, $condition): bool
{
    $status = false;
    if (!isset($request[$condition['var']])) {
        return $status;
    }
    switch ($condition['operator']) {
        case '==':
            if ($request[$condition['var']] == $condition['val']) {
                $status = true;
            }
            break;
        case '!=':
            if ($request[$condition['var']] <> $condition['val']) {
                $status = true;
            }
            break;
        case '>':
            if ($request[$condition['var']] > $condition['val']) {
                $status = true;
            }
            break;
        case '<':
            if ($request[$condition['var']] < $condition['val']) {
                $status = true;
            }
            break;
        case '>=':
            if ($request[$condition['var']] >= $condition['val']) {
                $status = true;
            }
            break;
        case '<=':
            if ($request[$condition['var']] <= $condition['val']) {
                $status = true;
            }
            break;
        default:
            dd("something went wrong");
            break;
    }

    return $status;

}

function verifyCriteria($results): array
{
    $j = 1;
    $new_result = [];
    for ($i = 0; $i < count($results); $i += $j) {
        if (isset($results[$i]['conditions'])) {
            if (count($results[$i]['conditions']) > 1) {
                $new_result[] = ['conditions' => verifyCriteria($results[$i]['conditions']), 'left_operator' => $results[$i]['left_operator']];
            } else {
                $new_result[] = ['left_operator' => $results[$i]['left_operator'], 'status' => $results[$i]['conditions'][0]['status']];
            }
            $j = 1;
        } else {
            if (isset($results[$i + 1])) {
                if (isset($results[$i + 1]['conditions'])) {
                    $new_result[] = $results[$i];
                    $j = 1;
                } else {
                    $status = processLeftOperator([$results[$i], $results[$i + 1]]);
                    $new_result[] = ['status' => $status, 'left_operator' => $results[$i]['left_operator']];
                    $j = 2;
                }
            } else {
                $new_result[] = $results[$i];
                $j = 1;
            }
        }
    }
    return $new_result;
}

function processLeftOperator($results): bool
{
    $status = false;
    switch ($results[1]['left_operator']) {
        case 'and':
            $status = $results[0]['status'] == true && $results[1]['status'] == true;
            break;
        case 'or':
            $status = $results[0]['status'] == true || $results[1]['status'] == true;
            break;
        default:
            dd("process operator wrong");
            break;
    }
    return $status;
}

if (!function_exists('remove_special_chars')) {
    function remove_special_chars($string)
    {
        //return preg_replace("/[^a-zA-Z0-9\s]/", " ", $string);
        return preg_replace("/[^A-Za-z0-9\_\-]/", " ", $string);

    }
}

if (!function_exists('strposa')) {
    function strposa(string $haystack, array $needles, int $offset = 0) : bool|string
    {
        foreach (generator($needles) as $needle) {
            if (strpos($haystack, $needle, $offset) !== false) {
                return $needle;
            }
        }
        return false;
    }
}

if (!function_exists('evaluate_eval')) {
    function evaluate_eval($string)
    {
        if (Str::contains($string, ['insert', 'delete', 'truncate', 'create', 'save', 'update', 'select'], true)) {
            return 'forbidden-code';
        }
        try {
            return eval('return ' . $string . ';');
        } catch (ParseError $err) {
            return null;
        }
         
    }
}

if (!function_exists('getUserPermissions')) {
    function getUserPermissions(MainSurvey $main_survey = null)
    {
        $permissions = [
            'canCreateSurvey' => Gate::check('create', MainSurvey::class)
        ];
        if ($main_survey) {
            $other_permissions = [
                'canReadSurvey' => Gate::check('view', $main_survey),
                'canUpdateSurvey' => Gate::check('update', $main_survey),
                'canDeleteSurvey' => Gate::check('delete', $main_survey),
            ];
            $permissions = array_merge($permissions, $other_permissions);
        }
        return $permissions;
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
        $secretFile = '/run/secrets/act_' . strtolower($env);
        
        // Check if the secret file exists and return its contents if it does
        if (file_exists($secretFile)) {
            return trim(file_get_contents($secretFile));
        }

        // Return the default value if no secret is found
        return $default;
    }
}