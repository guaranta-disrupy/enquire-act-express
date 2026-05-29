<?php

namespace App\Actions\App\Folder;

use App\Models\Folder;

class CreateFolder {

    public static function create($user, $input): Folder
    {
        return Folder::create([
            'name' => $input['name'],
            'user_id' => $user->id,
            'team_id' => $user->current_team_id,
            'config' => [],
            'parent_id' => $input['parent_id'] ?? null
        ]);
    }
}