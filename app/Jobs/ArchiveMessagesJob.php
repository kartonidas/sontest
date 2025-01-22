<?php

namespace App\Jobs;

use App\Services\ArchiveMessages;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ArchiveMessagesJob implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Queueable;

    public function handle(ArchiveMessages $archiveMessages): void
    {
        $archiveMessages->handle();
    }
}
