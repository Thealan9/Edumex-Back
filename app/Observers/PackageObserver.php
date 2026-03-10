<?php

namespace App\Observers;

use App\Models\Package;
use App\Models\Pallet;

class PackageObserver
{
   public function saved(Package $package)
    {
        if ($package->isDirty('pallet_id')) {
            $oldPalletId = $package->getOriginal('pallet_id');
            if ($oldPalletId) {
                $this->updatePalletStatus($oldPalletId);
            }
        }

        if ($package->pallet_id) {
            $this->updatePalletStatus($package->pallet_id);
        }
    }

    public function deleted(Package $package)
    {
        if ($package->pallet_id) {
            $this->updatePalletStatus($package->pallet_id);
        }
    }

    protected function updatePalletStatus($palletId)
    {
        $pallet = Pallet::withCount(['packages' => function ($query) {
            $query->whereIn('status', ['pending', 'available', 'reserved']);
        }])->find($palletId);

        if (!$pallet) return;

        $count = $pallet->packages_count;
        $max = $pallet->max_packages_capacity;

        $newStatus = match (true) {
            $count <= 0 => 'empty',
            $count >= $max => 'full',
            default => 'open',
        };

        if ($pallet->status !== $newStatus) {
            $pallet->update(['status' => $newStatus]);
        }
    }
}
