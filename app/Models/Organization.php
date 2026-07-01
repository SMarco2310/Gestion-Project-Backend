<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    //
    protected $fillable = [
        'user_id',
        'name',
        'description',
        // 'website',
        'logo',
    ];
}
