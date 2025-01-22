<?php

namespace Database\Seeders;

use App\Models\Message;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MessageSeeder extends Seeder
{
    public function run(): void
    {
        DB::disableQueryLog();

        DB::beginTransaction();

        Message::factory()->count(100000)->create();

        DB::commit();
    }
}
