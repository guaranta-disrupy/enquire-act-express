<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    //

    private function createUser($data, $team = false)
    {
        $user = null;
        DB::transaction(function() use($data, &$user, $team) {
            $user = User::forceCreate([
                'email' => $data['email'],
                'password' => Hash::make(Str::random(50, 100)),
                'name' => $data['name'],
                'system_user' => $data['role'],
                // 'is_active' => true,
                // 'expired_at' => Carbon::parse($data['expired_at'])
            ]);
            if ($team) {
                $user->ownedTeams()->save(Team::forceCreate([
                    'user_id' => $user->id,
                    'name' => explode(' ', $user->name, 2)[0]."'s Team",
                    'personal_team' => true,
                ]));
            }
            
        });
        return $user;
    }

    public function login(Request $request)
    {
        if ($request->has('email')) {
            $response = Http::acceptJson()->get(env('FLUENT_URL') . 'api/email_check?' . http_build_query(collect($request->all())->merge(['platform' => 'express'])->toArray()));
            $json_response = json_decode($response->body(), true);
            if (isset($json_response['legit']) && $json_response['legit'] === true) {
                $email = $json_response['email'];
                $user = User::where('email', $email)->first();
                if (!$user) {
                    //signup the user if email is not found in ACT `users` table
                    $user = $this->createUser($json_response, true);
                }
                // Check if owner of the team exists if not then create owner account to retain team's ownership
                $owner = User::where('email', $json_response['owner']['email'])->first();
                if (!$owner) {
                    $owner = $this->createUser($json_response['owner'], false);
                }

                $team = Team::where('fluent_id', $json_response['current_team_id'])->first();
                if (!$team) {
                    // Create the team if not exists
                    $team = Team::forceCreate([
                        'user_id' => $owner->id,
                        'name' => $json_response['team_name'],
                        'personal_team' => $json_response['is_personal_team'],
                        'fluent_id' => $json_response['current_team_id']
                    ]);
                }
                if ($team) {
                    // Add the team members to selected team to sync with fluent
                    DB::transaction(function() use($json_response, $team) {
                        $team_users = [];
                        foreach ($json_response['team_user'] as $team_user) {
                            $user = DB::table('users')->select('id')->where('email', $team_user['user'])->first();
                            if ($user && !DB::table('team_user')->where('user_id', $user->id)->where('team_id', $team->id)->exists()) {
                                $team_users[] = [
                                    'team_id' => $team->id,
                                    'user_id' => $user->id,
                                    'role' => $team_user['role'],
                                    'created_at' => now(),
                                    'updated_at' => now()
                                ];
                            }
                        }
                        if (count($team_users) > 0) {
                            DB::table('team_user')->insert($team_users);
                        } 
                    });
                    
                }
                // Login the user and switch to current team workspace
                Auth::loginUsingId($user->id);
                $user->switchTeam($team);

                // Send the ACT access token to fluent to access API endpoints
                if (!$json_response['hasToken']) {
                    Http::withToken($json_response['__token'])->get(env('FLUENT_URL') . 'api/store_token?platform=express&__token=' . $user->createToken('enquire')->plainTextToken);
                }
                
                // Create the project if the not exists
                if (isset($json_response['project_code']) && !empty($json_response['project_code'])) {
                    $project = Project::firstOrCreate(
                        ['code' => $json_response['project_code']],
                        [
                            'name' => $json_response['project_name'],
                            'team_id' => $team->id,
                            'user_id' => Auth::user()->id
                        ]
                    );
                    if (strlen($json_response['project_code']) > 3) {
                        if (!$project) {
                            return Redirect::route('projects.store', [
                                'source' => 'fluent',
                                'name' => $json_response['project_name'],
                                'code' => $json_response['project_code'],
                                'team_id' => $team->id
                            ]);
                        } else {
                            return redirect()->route('questionnaires.index', $project->code);
                        }
                    } else {
                        return redirect()->route('projects.index');
                    }
                } else {
                    return redirect()->route('projects.index');
                }
            } else {
                return redirect('https://splitsecondresearch.co.uk');
            }
        }
    }
}
