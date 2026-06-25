<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Commentaires extends Model
{
    /** @use HasFactory<\Database\Factories\CommentairesFactory> */
    use HasFactory;
    

    protected $fillable = [
        'user_id',
        'tache_id',
        'content',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tache()
    {
        return $this->belongsTo(Tache::class);
    }
}
