<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationRead extends Model
{
    protected $table = 'notification_reads';

    protected $fillable = [
        'notification_id',
        'user_id',
        'is_read',
        'is_archived',
        'is_favorite',
    ];

    protected $casts = [
        'is_read'     => 'boolean',
        'is_archived' => 'boolean',
        'is_favorite' => 'boolean',
    ];
}
