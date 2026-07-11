<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ChecklistItem extends Model
{
    use HasUuids;

    protected $fillable = [
        'checklist_id',
        'content',
        'is_done'
    ];

    protected $casts = [
        'is_done' => 'boolean',
    ];

    public function checklist()
    {
        return $this->belongsTo(Checklist::class);
    }
}
