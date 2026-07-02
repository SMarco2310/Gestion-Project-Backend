<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\oOganization;

class Notifications extends Model
{
    /** @use HasFactory<\Database\Factories\NotificationsFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'message',
        'is_read',
        'oranization_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function organization()
    {
        return $this-> belongsTo(Organization::class);
    }
    
    
}
