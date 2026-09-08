<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Enquiry extends Model
{
    use SoftDeletes;

    public const STATUSES = ['new', 'in_progress', 'contacted', 'closed', 'spam'];

    protected $fillable = [
        'name', 'email', 'phone', 'subject', 'message', 'source',
        'status', 'assigned_to', 'admin_notes', 'ip_hash', 'user_agent', 'contacted_at',
    ];

    protected function casts(): array
    {
        return ['contacted_at' => 'datetime'];
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
