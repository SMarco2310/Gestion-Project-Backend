<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationEntitlement extends Model
{
    protected $table = 'organization_entitlements';

    protected $fillable = [
        'organization_id',
        'klea_subscription_id',
        'klea_plan_id',
        'plan_name',
        'status',
        'features',
        'starts_at',
        'expires_at',
        'last_webhook_at',
        'last_transaction_id',
    ];

    protected $casts = [
        'features' => 'array',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'last_webhook_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && (! $this->expires_at || $this->expires_at->isFuture());
    }
}
