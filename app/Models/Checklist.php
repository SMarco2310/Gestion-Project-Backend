<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Checklist extends Model
{
    use HasUuids;

    protected $fillable = [
        'tache_id',
        'title'
    ];

    public function items()
    {
        return $this->hasMany(ChecklistItem::class)->orderBy('created_at');
    }

    public function tache()
    {
        return $this->belongsTo(Tache::class);
    }
}
