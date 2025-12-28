<?php

namespace App\Observers;

use App\Models\Variety;
use Illuminate\Support\Facades\Cache;


class VarietyObserver
{
    /**
     * Handle the Variety "created" event.
     */
    public function created(Variety $variety): void
    {
        Cache::increment('admin_dashboard_v');
    }

    /**
     * Handle the Variety "updated" event.
     */
    public function updated(Variety $variety): void
    {
        Cache::increment('admin_dashboard_v');
    }

    /**
     * Handle the Variety "deleted" event.
     */
    public function deleted(Variety $variety): void
    {
        Cache::increment('admin_dashboard_v');
    }

    /**
     * Handle the Variety "restored" event.
     */
    public function restored(Variety $variety): void
    {
        //
    }

    /**
     * Handle the Variety "force deleted" event.
     */
    public function forceDeleted(Variety $variety): void
    {
        //
    }
}
