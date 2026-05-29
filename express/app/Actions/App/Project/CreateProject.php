<?php

namespace App\Actions\App\Project;

use App\Models\Team;

class CreateProject {

    public static function create($user, $input): void
    {
        // $user->switchTeam($team = $user->ownedTeams()->create([
        //     'name' => $input['name'],
        //     'personal_team' => false,
        //     'config' => ['status' => 'active']
        // ]));
        // return $user->currentTeam;
        foreach ($input['names'] as $name) {
            $user->ownedTeams()->create([
                'name' => $name,
                'personal_team' => false,
                'config' => ['status' => 'active']
            ]);
        }
    }
}