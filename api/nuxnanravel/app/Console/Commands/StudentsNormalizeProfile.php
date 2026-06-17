<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class StudentsNormalizeProfile extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'students:normalize-profile {--academy= : Filter by academy ID} {--dry-run : Only show what would be updated}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Normalize legacy student data from student_cards into detailed tables';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting normalization of legacy student data...');
        
        $query = \App\Models\StudentCard::whereNotNull('student_id');
        
        if ($this->option('academy')) {
            $query->where('academy_id', $this->option('academy'));
        }
        
        $cards = $query->get();
        $this->info("Found {$cards->count()} linked cards to process.");
        
        $addressCount = 0;
        $contactCount = 0;
        $guardianCount = 0;
        
        foreach ($cards as $card) {
            $student = $card->student;
            if (!$student) continue;

            // 1. Normalize Address (if card has notes that look like address)
            if (!empty($card->notes) && $student->addresses()->count() === 0) {
                if (!$this->option('dry-run')) {
                    $student->addresses()->create([
                        'academy_id' => $student->academy_id,
                        'address_type' => 'house',
                        'raw_address' => $card->notes,
                        'is_current' => true,
                    ]);
                }
                $addressCount++;
            }

            // 2. Normalize Contact (if we can find a phone number)
            // Note: student_cards doesn't have phone column in current schema, 
            // but we might find it in notes or other sources in future.
            
            // 3. Normalize Guardian
            // Same as above, check for existing guardians first
            if ($student->guardians()->count() === 0) {
                // If we had legacy guardian data, we would patch it here
            }
            
            if (!$this->option('dry-run') && ($addressCount > 0 || $contactCount > 0 || $guardianCount > 0)) {
                // Log the normalization
                \App\Models\AuditLog::create([
                    'entity_type' => \App\Models\Student::class,
                    'entity_id' => $student->id,
                    'action' => 'normalized',
                    'description' => "Profile normalized from StudentCard #{$card->id}",
                    'user_id' => 0, // System
                ]);
            }
        }
        
        $this->info('Normalization completed.');
        $this->info("Addresses patched: {$addressCount}");
        $this->info("Contacts patched: {$contactCount}");
        $this->info("Guardians patched: {$guardianCount}");
        
        if ($this->option('dry-run')) {
            $this->warn('This was a DRY RUN. No changes were made.');
        }
    }
}
