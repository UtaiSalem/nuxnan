<?php

namespace App\Console\Commands;

use App\Models\Academy;
use App\Models\AcademyGroup;
use App\Services\Gamification\ClassroomPointsService;
use App\Services\Gamification\XpService;
use Illuminate\Console\Command;

class InitializeCycleRowsCommand extends Command
{
    protected $signature = 'gamification:init-cycles';

    protected $description = 'Pre-create gamification cycle rows for academies and classrooms';

    public function handle(XpService $xpService, ClassroomPointsService $classroomPointsService): void
    {
        $this->info('Starting gamification cycle initialization...');

        Academy::cursor()->each(function (Academy $academy) use ($xpService, $classroomPointsService) {
            $this->info("Initializing cycles for Academy: {$academy->name}");
            $xpService->ensureCurrentCycles($academy);

            $classrooms = AcademyGroup::where('academy_id', $academy->id)
                ->where('type', 'classroom')
                ->get();

            foreach ($classrooms as $classroom) {
                $classroomPointsService->ensureCurrentCycles($classroom);
            }
        });

        $this->info('Gamification cycle rows initialized successfully.');
    }
}
