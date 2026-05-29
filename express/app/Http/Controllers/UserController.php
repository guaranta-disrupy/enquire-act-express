<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Activity_Log;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class UserController extends Controller
{

    /**
     * Instantiate a new controller instance
     * 
     * @return void
    */
    public function __construct()
    {
       
        // $this->middleware('admin.only');
    }

    /**
     * Display a listing of the resource.
     *
     * @return Inertia\Inertia
     */
    public function index()
    {
        
        $users = User::when(Auth::user()->system_user !== "root_admin",function($query){ 
                // checks when user it not root admin
                return $query->where('system_user','!=','root_admin');
            }, function ($query){
                // user is root admin
                // exclude root user on the list
                return $query->where('id','!=', Auth::user()->id);
            })
            ->orderBy('name', 'asc')->withTrashed()->paginate(10);
            $users->filter(function ($user) {
                // add isActive property to User object
                $user['isActive'] = $user->deleted_at == null ? 1:0;
                $user['default_team_member'] = $user->default_team_member == null ? 0:$user->default_team_member;
            });
        
        return Inertia::render('Users/Index', [
            'users' => $users,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
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
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Support\Facades\Redirect
     */
    public function update(Request $request)
    {
        //
        $data = User::withTrashed()->whereId($request->id)->first();
        
        // activate user status
        if($data->trashed()){
            $data->restore();
            // record log for activated user
            Activity_Log::create([
                'user_id' => Auth::user()->id,
                'action' => "update",
                'details' => "User ".Auth::user()->name." activate user ".$data->name,
            ]);
        }else{
            // update user role
            // dd($request->except(['id']));
            $data->update($request->except(['id']));
            // record log for user role updated
            Activity_Log::create([
                'user_id' => Auth::user()->id,
                'action' => "update",
                'details' => "User ".Auth::user()->name." update user ".$data->name,
            ]);
        }
            
        return Redirect::back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        // deactivate user status    
        $data = User::whereId($id)->first();
        if($data !== null){
            $data->delete();
            // record log for deactivated user
            Activity_Log::create([
                'user_id' => Auth::user()->id,
                'action' => "delete",
                'details' => "User ".Auth::user()->name." deactivate user ".$data->name,
            ]);
        }
            
        return Redirect::back();
    }


    
}
