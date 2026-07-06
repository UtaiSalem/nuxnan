<?php

namespace Database\Seeders;

use App\Models\SchoolEvent;
use App\Services\EventToPostMirror;
use Illuminate\Database\Seeder;

class MirrorExistingEventsSeeder extends Seeder
{
    public function run(EventToPostMirror $mirror): void
    {
        SchoolEvent::where('status', 'published')->chunk(50, function ($events) use ($mirror) {
            foreach ($events as $e) {
                $mirror->mirror($e);
            }
        });
    }
}
