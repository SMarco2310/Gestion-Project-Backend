<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    
    protected $fillable = [
        'name',
        'description',
        'logo',
        'reminder_days_before_start',
        'reminder_days_before_end',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'organization_user')
                    ->withPivot(['role', 'joined_at'])
                    ->withCasts([
                        'joined_at' => 'datetime',
                    ]);
    }

    public function teams()
    {
        return $this->hasMany(Team::class);
    }

    
    public function notifications()
    {
        return $this->hasMany(Notifications::class);
    }

    public function projets()
    {
        return $this->hasMany(Projet::class);
    }
}


