<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Projet;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tache extends Model
{
    /** @use HasFactory<\Database\Factories\TacheFactory> */
    use HasFactory;

    protected $fillable=[
        'title',
        'reference_code',
        'description',
        'priority',
        'status',
        'tag',
        'due_date',
        'projet_id',
        'parent_task_id',
        'banner_image'
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'datetime',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $prefix = 'TSK-';
            $paddingLength = 3; 

            $projet = $model->projet ?? Projet::find($model->projet_id);
            
            if ($projet) {
                $userId = $projet->user_id;

                $lastRecord = static::whereHas('projet', function($query) use ($userId) {
                                    $query->where('user_id', $userId);
                                })
                                ->latest('id')
                                ->first();

                if (! $lastRecord || ! $lastRecord->reference_code) {
                    $model->reference_code = $prefix . str_pad(1, $paddingLength, '0', STR_PAD_LEFT);
                } else {
                    $lastNumber = (int) substr($lastRecord->reference_code, strlen($prefix));
                    $newNumber = $lastNumber + 1;
                    $model->reference_code = $prefix . str_pad($newNumber, $paddingLength, '0', STR_PAD_LEFT);
                }
            }
        });
    }

    public function projet(): BelongsTo
    {
        return $this->belongsTo(Projet::class);
    }

    public function parentTask(): BelongsTo
    {
        return $this->belongsTo(Tache::class, 'parent_task_id');
    }

    public function subTasks(): HasMany
    {
        return $this->hasMany(Tache::class, 'parent_task_id');
    }

    public function commentaires(): HasMany
    {
        return $this->hasMany(Commentaires::class);
    }
}
