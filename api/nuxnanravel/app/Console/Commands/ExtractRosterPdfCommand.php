<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ExtractRosterPdfCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'roster:extract-pdf {pdf_path} {--output=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Extract roster classrooms and students from PDF or companion JSON/txt files';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $pdfPath = $this->argument('pdf_path');
        $outputPath = $this->option('output');

        if (! File::exists($pdfPath)) {
            $this->error("File not found: {$pdfPath}");

            return 1;
        }

        $extension = strtolower(pathinfo($pdfPath, PATHINFO_EXTENSION));

        // If the file itself is a JSON, just copy or return it.
        if ($extension === 'json') {
            $content = File::get($pdfPath);
            $data = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error('Invalid JSON file.');

                return 1;
            }

            if ($outputPath) {
                File::put($outputPath, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
                $this->info("Saved to {$outputPath}");
            } else {
                $this->line(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            }

            return 0;
        }

        // Check if there is a companion JSON file (e.g. file.pdf -> file.json)
        $companionJson = preg_replace('/\.pdf$/i', '.json', $pdfPath);
        if (File::exists($companionJson)) {
            $this->info("Companion JSON found: {$companionJson}");
            $content = File::get($companionJson);
            $data = json_decode($content, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                if ($outputPath) {
                    File::put($outputPath, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
                    $this->info("Saved parsed data to {$outputPath}");
                } else {
                    $this->line(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
                }

                return 0;
            }
        }

        $this->error("No extraction tools or companion JSON available for: {$pdfPath}");
        $this->info("Please run extraction externally or provide the companion JSON at {$companionJson}");

        return 1;
    }
}
