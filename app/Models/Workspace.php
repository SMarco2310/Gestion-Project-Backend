<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Workspace extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'description',
        'organization_id',
        'kanban_columns',
        'kanban_colors',
        'created_by',
        'color',
    ];

    protected $casts = [
        'kanban_columns' => 'array',
        'kanban_colors' => 'array',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function projets()
    {
        return $this->hasMany(Projet::class);
    }

    public function taches()
    {
        return $this->hasMany(Tache::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'workspace_user')
                    ->withPivot('role', 'joined_at')
                    ->withCasts(['joined_at' => 'datetime']);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function teams()
    {
        return $this->belongsToMany(Team::class, 'team_workspace')->withTimestamps();
    }
}
