<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Facades\Storage;

class Attachment extends Model
{
    use HasUuids;

    protected $fillable = [
        'tache_id',
        'user_id',
        'file_name',
        'file_path',
        'mime_type',
        'size'
    ];

    protected $appends = ['url'];

    public function getUrlAttribute()
    {
        return Storage::url($this->file_path);
    }

    public function tache()
    {
        return $this->belongsTo(Tache::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
