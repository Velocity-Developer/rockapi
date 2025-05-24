<?php

namespace App\Observers;

use App\Models\CsMainProject;
use App\Models\Webhost;

class CsMainProjectObserver
{
    /**
     * Handle the CsMainProject "created" event.
     */
    public function created(CsMainProject $csMainProject): void
    {
        //
    }

    /**
     * Handle the CsMainProject "updated" event.
     */
    public function updated(CsMainProject $csMainProject): void
    {
        //
    }

    /**
     * Handle the CsMainProject "deleted" event.
     */
    public function deleted(CsMainProject $csMainProject): void
    {
        //
    }

    /**
     * Handle the CsMainProject "restored" event.
     */
    public function restored(CsMainProject $csMainProject): void
    {
        //
    }

    /**
     * Handle the CsMainProject "force deleted" event.
     */
    public function forceDeleted(CsMainProject $csMainProject): void
    {
        //
    }
}
