<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Team extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id', 
        'name',
    ];
    
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'team_user')
                    ->withPivot('joined_at')
                    ->withCasts([
                        'joined_at' => 'datetime',
                    ]);
    }
    public function projets()
    {
        return $this->hasMany(Projet::class);
    }
}
