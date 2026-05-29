<?php

namespace App\Models;

use App\Observers\GlobalGalleryObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// #[ObservedBy(GlobalGalleryObserver::class)]
class FluentGallery extends Model
{
    use HasFactory;

    protected $connection = 'mysql_auth';

    protected $table = 'global_galleries';

    protected $guarded = [];

    protected $casts = [
        'tags' => 'array',
        'config' => 'array',
    ];

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
