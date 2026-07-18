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
        'reference_code',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $prefix = 'EQ-';
            $paddingLength = 2; 

            // Scope the query to THIS specific organization
            $lastRecord = static::where('organization_id', $model->organization_id)
                                ->latest('created_at')
                                ->first();

            if (! $lastRecord || ! $lastRecord->reference_code) {
                // It is this organization's very first team
                $model->reference_code = $prefix . str_pad(1, $paddingLength, '0', STR_PAD_LEFT);
            } else {
                // Extract the number from this organization's last team
                $lastNumber = (int) substr($lastRecord->reference_code, strlen($prefix));
                $newNumber = $lastNumber + 1;
                
                // Assign the new reference code
                $model->reference_code = $prefix . str_pad($newNumber, $paddingLength, '0', STR_PAD_LEFT);
            }
        });
    }
    
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
