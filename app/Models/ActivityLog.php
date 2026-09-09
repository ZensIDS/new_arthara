<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    public $timestamps = false; // kita isi created_at manual, tidak butuh updated_at

    protected $fillable = [
        'user_id',
        'subject_type',
        'subject_id',
        'module',
        'action',
        'description',
        'old_data',
        'new_data',
        'ip_address',
        'created_at',
    ];

    protected $casts = [
        'old_data'   => 'array',
        'new_data'   => 'array',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subject()
    {
        return $this->morphTo();
    }
}
