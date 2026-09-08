<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdmissionApplication extends Model
{
    use SoftDeletes;

    public const STATUSES = ['submitted', 'under_review', 'contacted', 'accepted', 'rejected', 'withdrawn'];

    protected $fillable = [
        'user_id', 'application_number', 'full_name', 'email', 'phone', 'date_of_birth',
        'program_id', 'previous_qualification', 'message', 'status',
        'reviewed_by', 'reviewed_at', 'admin_notes',
    ];

    protected function casts(): array
    {
        return ['date_of_birth' => 'date', 'reviewed_at' => 'datetime'];
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
