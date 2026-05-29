<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Team;
use Laravel\Jetstream\Jetstream;
use Illuminate\Support\Facades\Validator;

class TeamsController extends Controller
{
    public function index(Request $request): \Inertia\Response
    {
        $keyword = $request->keyword ?? false;
        $teams = Team::with('owner:id,name')->orderBy('personal_team', 'asc')->when($keyword, function($query, $keyword) {
            $query->where('name', 'like', "%{$keyword}%");
        })->orderBy('name', 'asc')->paginate();
        return Inertia::render('Teams2/Index', [
            'teams' => $teams,
            'keyw' => $request->keyword ?? null
        ]);
    }

    public function show($team_id): \Inertia\Response
    {
        $team = Team::find($team_id)->load('owner', 'users', 'projects');
        return Inertia::render('Teams2/Dashboard', [
            'current_team' => $team
        ]);
    }

    public function add_member(Request $request, Team $team)
    {
        $email = $request->input('email');
        $validator = Validator::make([
            'email' => $email
        ], $this->rules(), [
            'email.exists' => __('We were unable to find a registered user with this email address.'),
        ]);
        $validator->validateWithBag('addTeamMember');
        $validator->after(
            $this->ensureUserIsNotAlreadyOnTeam($team, $email)
        )->validateWithBag('addTeamMember');

        $team->users()->attach(
            $newTeamMember = Jetstream::findUserByEmailOrFail($email),
            ['role' => 'admin']
        );
        return Redirect::back();
    }

    /**
     * Get the validation rules for adding a team member.
     *
     * @return array
     */
    protected function rules()
    {
        return array_filter([
            'email' => ['required', 'email', 'exists:users']
        ]);
    }

    public function remove_member(Team $team, User $user): \Illuminate\Http\RedirectResponse
    {
        if ($user->belongsToTeam($team)) {
            if ($user->currentTeam->id === $team->id) {
                $user->update([
                    'current_team_id' => $user->personalTeam()->id
                ]);
            }
            DB::table('team_user')->where([
                'user_id' => $user->id,
                'team_id' => $team->id
            ])->delete();
        }
        return Redirect::back();
    }

    public function update(Request $request, Team $team): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'name' => 'required|string'
        ]);
        $team->update([
            'name' => strip_tags($request->input('name'))
        ]);
        return Redirect::back();
    }

    /**
     * Ensure that the user is not already on the team.
     *
     * @param  mixed  $team
     * @param  string  $email
     * @return \Closure
     */
    protected function ensureUserIsNotAlreadyOnTeam($team, string $email)
    {
        return function ($validator) use ($team, $email) {
            $validator->errors()->addIf(
                $team->hasUserWithEmail($email),
                'email',
                __('This user already belongs to the team.')
            );
        };
    }

    public function teams(Request $request)
    {
        return $request->user()->allTeams();
    }
}
