<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Invitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'token',
        'organization_id',
        'team_id',
        'projet_id',
        'role',
        'status',
        'expires_at',
        'invited_by',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function projet()
    {
        return $this->belongsTo(Projet::class);
    }



    public function inviter()
    {
        return $this->belongsTo(User::class, 'invited_by');
    }
}
