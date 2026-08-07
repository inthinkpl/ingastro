<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationSetting extends Model
{
    protected $fillable = [
        'status_key',
        'status_label',
        'title_template',
        'body_template',
    ];
}