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
        'parent_task_id'
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'datetime',
        ];
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
