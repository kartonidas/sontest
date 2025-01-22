<?php

use App\Jobs\ArchiveMessagesJob;
use Illuminate\Support\Facades\Schedule;

Schedule::job(new ArchiveMessagesJob)->weekly();
