<?php
namespace App\Http\Controllers;
// namespace Laravel\Jetstream\Http\Controllers\Inertia;


use App\Models\Activity_Log;
use App\Models\TeamUser;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Models\Team;
use GuzzleHttp\Psr7\Response;
use Inertia\Inertia;
use Laravel\Jetstream\Jetstream;
use Laravel\Jetstream\Contracts\RemovesTeamMembers;
use Laravel\Jetstream\RedirectsActions;
use Illuminate\Support\Facades\Validator;

class TeamController extends Controller
{
    use RedirectsActions;

      /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $teams = Team::with('user:id,name')->orderBy('id', 'desc')->paginate(10);
        return Inertia::render('Teams2/Index', [
            'teams' => $teams,
        ]);

    }

    /**
     * Show the team management screen.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $teamId
     * @return \Inertia\Response
     */
    public function show(Request $request, $teamId)
    {
        $team = Jetstream::newTeamModel()->findOrFail($teamId);

        // Gate::authorize('view', $team);

        return Jetstream::inertia()->render($request, 'Teams2/Show', [
            'team' => $team->load('owner', 'users', 'teamInvitations'),
            'availableRoles' => array_values(Jetstream::$roles),
            'availablePermissions' => Jetstream::$permissions,
            'defaultPermissions' => Jetstream::$defaultPermissions,
            'permissions' => [
                'canAddTeamMembers' => true,
                'canDeleteTeam' =>  true,
                'canRemoveTeamMembers' => true,
                'canUpdateTeam' => true,
            ],
            'message' => 'Added'
        ]);
    }

    /**
     * Show the team creation screen.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Inertia\Response
     */
    public function create(Request $request)
    {
        //

        TeamUser::firstOrCreate([
            "team_id" => $request->id,
            "user_id" => $request->user_id,
            "role" => $request->role
        ]);
       
        $user = Auth::user();
        Activity_Log::create([
            'user_id' => $user->id,
            'action' => "store",
            'details' => "User $user->name added email $request->email under team name $request->name",
        ]);

        return Redirect::back();
        // Gate::authorize('create', Jetstream::newTeamModel());
        Jetstream::newTeamModel();

        return Inertia::render('Teams/Create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //

        TeamUser::firstOrCreate([
            "team_id" => $request->id,
            "user_id" => $request->user_id,
            "role" => $request->role
        ]);
       
        $user = Auth::user();
        Activity_Log::create([
            'user_id' => $user->id,
            'action' => "store",
            'details' => "User $user->name added email $request->email under team name $request->name",
        ]);

        return Redirect::back();
    }

  /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
        $name = $request->input('name');
        Validator::make(['name' => $name], ['name' => 'required|string'])->validateWithBag('updateTeamName');
        
        $data = Team::find($id);
        Activity_Log::create([
            'user_id' => Auth::user()->id,
            'action' => "update",
            'details' => "User ".Auth::user()->name." rename team ".$data->name." as ".$request->name,
        ]);

        $data->update([
            "name" => $request->name
        ]);

        return Redirect::back();
    }   
    

   


     /**
     * Remove the given user from the given team.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $teamId
     * @param  int  $userId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($user_id, $team_id)
    {
        
        $team = Team::find($team_id);
        $data = TeamUser::where(['team_id' => $team_id, 'user_id' => $user_id])->first();
        
        $user = User::find($user_id);
        dd($team_id);
        Activity_Log::create([
            'user_id' => Auth::user()->id,
            'action' => "delete",
            'details' => "User ".Auth::user()->name." remove ".$user->name." from project ".$team->name,
        ]);

        $data->delete();

        return Redirect::back();
    }

    public function member_update(Request $request)
    {
        
        $data = TeamUser::where(['team_id' => $request->team_id, 'user_id' => $request->user_id])->first();
        $user = User::find($request->user_id);
        Activity_Log::create([
            'user_id' => Auth::user()->id,
            'action' => "update",
            'details' => "User ".Auth::user()->name." update ".$user->name." role as ".$request->role,
        ]);

        $data->update([
            "role" => $request->role
        ]);

        return Redirect::back();
    }

    public function add_members($id){
        $team = Jetstream::newTeamModel()->findOrFail($id);
        $team = $team->load('owner', 'users', 'teamInvitations');
       
        $users = User::where('default_team_member',1)->get();
        $users->each(function($item) use($team,){
            $email = $item->email;
            if($team->hasUserWithEmail($email))
                return true;
                
            $team->users()->attach(
                $newTeamMember = Jetstream::findUserByEmailOrFail($item->email),
                ['role' => 'admin',  "team_id" => $team->id]
            );
        });
        return Redirect::back();
    }

    public function add_member(Request $request, Team $team): \Illuminate\Http\RedirectResponse
    {
        
        $email = $request->input('email');
        
        $validate = Validator::make(['email' => $email], ['email' => 'required|email|exists:users']);
        $validate->validateWithBag('addTeamMember');
       
        $validate->after(function ($validator) use ($team, $email) {
            $validator->errors()->addIf($team->hasUserWithEmail($email),'email',__('This user already belongs to the team.'));
        })->validateWithBag('addTeamMember');
       
       
        $team->users()->attach(
            $newTeamMember = Jetstream::findUserByEmailOrFail($email),
            ['role' => 'admin',  "team_id" => $team->id]
        );
        return Redirect::back();
        
    }

     /**
     * Remove the given user from the given team.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $teamId
     * @param  int  $userId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy_member(Request $request, $teamId, $userId)
    {
        $team = Jetstream::newTeamModel()->findOrFail($teamId);
        app(RemovesTeamMembers::class)->remove(
            $user = Jetstream::findUserByIdOrFail($userId),
            $team,
            $user = Jetstream::findUserByIdOrFail($userId)
        );
        if ($request->user()->id === $user->id) {
            return redirect(config('fortify.home'));
        }

        return back(303);
    }
}
