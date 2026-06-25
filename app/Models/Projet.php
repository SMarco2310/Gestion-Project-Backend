<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Projet extends Model
{
    /** @use HasFactory<\Database\Factories\ProjetFactory> */
    use HasFactory;


    protected $fillable=[
        'name',
        'description',
        'user_id'
    ];

    public function user(){

        return $this->belongsTo(User::class);
    }
    
    // public function users(){
    //     return $this->hasMany(User::class)
    // }

    public function taches(){
        return $this->hasMany(Tache::class);
    }
}
