<?php

namespace App\Traits;

trait Roles {
    protected $available_roles = [
        [
            'key' => 'admin',
            'name' => 'Administrator',
            'description' => 'Administrator users can perform any action.',
            'permissions' => ['create', 'read', 'update', 'delete', 'manage']
        ],
        [
            'key' => 'editor',
            'name' => 'Editor',
            'description' => 'Editor users have the ability to read and update.',
            'permissions' => ['read', 'update']
        ],
        [
            'key' => 'viewer',
            'name' => 'Viewer',
            'description' => 'Viewer users have the ability to read.',
            'permissions' => ['read']
        ]
    ];
}