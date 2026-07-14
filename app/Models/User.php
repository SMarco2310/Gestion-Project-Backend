<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['first_name', 'last_name', 'phone', 'provider_id', 'email','bio', 'password', 'profile_picture'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasUuids, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's full name.
     */
    public function getNameAttribute()
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Send the password reset notification using our custom branded template.
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }
    public function projets()
    {
        return $this->hasMany(Projet::class);
    }

    public function commentaires()
{
    return $this->hasManyThrough(
        Commentaires::class,
        Tache::class,
        'projet_id',
        'tache_id',
        'id',
        'id'
    );
}

    public function organizations()
        {
            return $this->belongsToMany(Organization::class, 'organization_user')
                        ->withPivot(['role', 'joined_at'])
                        ->withCasts([
                            'joined_at' => 'datetime',
                        ]);
        }


    public function teams()
    {
        return $this->belongsToMany(Team::class, 'team_user')
                    ->withPivot(['role', 'joined_at'])
                    ->withCasts([
                        'joined_at' => 'datetime',
                    ]);
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user')
                    ->withPivot('assigned_at')
                    ->withCasts(['assigned_at' => 'datetime']);
    }

    public function projets_collaborated()
    {
        return $this->belongsToMany(Projet::class, 'projet_user')
                    ->withPivot('joined_at')
                    ->withCasts(['joined_at' => 'datetime']);
    }
}
