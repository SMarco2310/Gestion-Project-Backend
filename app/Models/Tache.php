<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Projet;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Tache extends Model
{
    /** @use HasFactory<\Database\Factories\TacheFactory> */
    use HasFactory, HasUuids;

    protected $fillable=[
        'title',
        'reference_code',
        'description',
        'priority',
        'status',
        'due_date',
        'projet_id',
        'parent_task_id',
        'banner_image',
        'assignee_id',
        'workspace_id',
        'board_column'
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
                                ->latest('created_at')
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

        static::saved(function ($model) {
            if ($model->assignee_id && $model->projet_id) {
                $projet = $model->projet ?? Projet::find($model->projet_id);
                if ($projet) {
                    $projet->users()->syncWithoutDetaching([$model->assignee_id]);
                }
            }
        });
    }

    public function projet(): BelongsTo
    {
        return $this->belongsTo(Projet::class);
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
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

    public function tags(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'tache_tag');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function checklists(): HasMany
    {
        return $this->hasMany(Checklist::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }
}
