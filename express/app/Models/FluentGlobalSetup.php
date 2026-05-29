<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FluentGlobalSetup extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $connection = 'mysql_auth';

    protected $table = 'global_setups';

    protected $casts = [
        'display_config' => 'array',
        'attribute_config' => 'array',
        'express_messages' => 'array',
        'act_messages' => 'array',
        'fast_messages' => 'array',
        'impress_messages' => 'array',
        'imprint_messages' => 'array',
        'impact_messages' => 'array',
        'impulse_messages' => 'array',
        'recent_changes' => 'array',
    ];
}
