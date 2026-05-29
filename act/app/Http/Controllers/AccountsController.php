<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class AccountsController extends Controller
{
    public function index(Request $request)
    {
        $keyword = $request->keyword ?? false;
        $users = User::when($keyword, function($query, $keyword) {
            $query->where('name', 'like', "%{$keyword}%");
        })->orderBy('name', 'asc')->paginate();
        return Inertia::render('Accounts/Account', [
            'users' => $users,
            'keyw' => $request->keyword ?? null
        ]);
    }

    public function dashboard(User $user)
    {
        return Inertia::render('Accounts/Dashboard', [
            'current_user' => $user,
            'teams' => $user->teams
        ]);
    }

    public function create(): \Inertia\Response
    {
        return Inertia::render('Accounts/Create');
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'name' => 'required',
            'password' => 'required|confirmed',
            'email' => 'required|email|unique:users,email|min:8|max:255',
            'valid_until' => 'required|date',
            'role' => 'required'
        ]);

        $user = User::create([
            'name' => strip_tags($request->name),
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'role' => $request->role,
            'expired_at' => Carbon::parse($request->valid_until . " 00:00")
        ]);

        $user->ownedTeams()->save(Team::forceCreate([
            'user_id' => $user->id,
            'name' => explode(' ', $user->name, 2)[0] . "'s Team",
            'personal_team' => true,
        ]));

        if ($user)
            return Redirect::back();
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required',
            'valid_until' => 'required|date',
            'role' => 'required',
            // 'email' => 'required|email|unique:users,email|min:8|max:255'
        ]);

        $user->update([
            // 'email' => $request->input('email'),
            'name' => $request->input('name'),
            'expired_at' => Carbon::parse($request->input('valid_until') . ' 00:00'),
            'role' => $request->input('role')
        ]);

        return Redirect::back();
    }

    public function leave_team(User $user, Team $team)
    {
        if ($user->belongsToTeam($team)) {
            $user->update([
                'current_team_id' => $user->personalTeam()->id
            ]);
            DB::table('team_user')->where([
                'user_id' => $user->id,
                'team_id' => $team->id
            ])->delete();
        }
        return Redirect::back();
    }

    public function change_status(User $user)
    {
        $user->update([
            'is_active' => !$user->is_active
        ]);
        return Redirect::back();
    }

    public function logout(Request $request)
    {
        if (Auth::check()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        if (!$request->expectsJson()) {
            return \redirect('/');
        } else {
            return response()->json(['message' => 'ok']);
        }
    }
}
