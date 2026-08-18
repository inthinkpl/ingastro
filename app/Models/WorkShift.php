<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkShift extends Model
{
    protected $fillable = [
        'user_id',
        'clock_in',
        'clock_out',
        'break_minutes',
        'break_start_at',
        'status',
        'hourly_rate',
        'notes',
    ];

    protected $casts = [
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
        'break_start_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}