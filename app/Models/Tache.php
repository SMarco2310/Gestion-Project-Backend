<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Projet;

class Tache extends Model
{
    /** @use HasFactory<\Database\Factories\TacheFactory> */
    use HasFactory;

    protected $fillable=[
        'title',
        'description',
        'priority',
        'status',
        'due_date',
        'projet_id'
    ]

    public function projet(){
        return $this->belongsTo(Projet::class);
    }

    public function commentaires(){
        return $this->hasMany(Commentaires::class);
    }
}
