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
        'reference_code',
        'status',
        'start_date',
        'end_date',
        'user_id',
        'team_id',
        'organization_id'
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'datetime',
            'end_date' => 'datetime',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $prefix = 'PRJ-';
            $paddingLength = 3; 

            // IMPORTANT: Scope the query to THIS specific user
            $lastRecord = static::where('user_id', $model->user_id)
                                ->latest('id')
                                ->first();

            if (! $lastRecord || ! $lastRecord->reference_code) {
                // It is this user's very first project
                $model->reference_code = $prefix . str_pad(1, $paddingLength, '0', STR_PAD_LEFT);
            } else {
                // Extract the number from this user's last project
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

    public function user(){
        return $this->belongsTo(User::class);
    }
    
    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function taches(){
        return $this->hasMany(Tache::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'projet_user')
                    ->withPivot('joined_at')
                    ->withTimestamps();
    }
}
