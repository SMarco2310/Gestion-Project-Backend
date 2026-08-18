<?php

namespace App\Console\Commands;

use App\Models\OrganizationEntitlement;
use Illuminate\Console\Command;

class SweepExpiredEntitlements extends Command
{
    protected $signature = 'entitlements:sweep-expired';
    protected $description = 'Flip active organization entitlements past their expires_at to expired';

    public function handle(): int
    {
        $count = OrganizationEntitlement::where('status', 'active')
            ->where('expires_at', '<', now())
            ->update(['status' => 'expired']);

        $this->info("Expired {$count} entitlement(s).");

        return self::SUCCESS;
    }
}
