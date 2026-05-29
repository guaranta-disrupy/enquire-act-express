<?php

namespace App\Actions\App\Folder;

class DeleteFolder
{
    public static function delete($folder)
    {
        $folder->delete();
    }
}