<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SchoolEvent;
use App\Services\EventToPostMirror;

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
