<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Team extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 
        'organization_id', 
        'name',
    ];
    
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // public function members()
    // {
    //     return $this->belongsToMany(User::class, 'team_members');
    // }
}
