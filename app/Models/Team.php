<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Team extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'organization_id', 
        'name',
        'description',
    ];
    
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'team_user')
                    ->withPivot(['role', 'joined_at'])
                    ->withCasts([
                        'joined_at' => 'datetime',
                    ]);
    }
    public function projets()
    {
        return $this->belongsToMany(Projet::class, 'projet_team')->withTimestamps();
    }

    public function workspaces()
    {
        return $this->belongsToMany(Workspace::class, 'team_workspace')->withTimestamps();
    }
}
