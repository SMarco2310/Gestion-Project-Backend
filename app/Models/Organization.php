<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    
    protected $fillable = [
        'user_id',
        'name',
        'description',
        // 'website',
        'logo',
    ];

    //This will help accessing the users in the organization
    public function users(){
        $this->hasMany(User::class);
    }
    // This will help in accessing the projets of the organization
    public function projets(){
        $this->hasMany(Projets::class);
    }
    public 
}
