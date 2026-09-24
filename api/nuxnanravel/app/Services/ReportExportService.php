<?php

namespace App\Services;

use App\Exports\SavedReportArrayExport;
use App\Models\SavedReport;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

class ReportExportService
{
    /**
     * สร้างไฟล์ export จาก cached_data ของ SavedReport ลง disk 'public' แบบ synchronous
     *
     * @return array{file_name:string,file_path:string,file_type:string,file_size:int}
     */
    public function generate(SavedReport $report, string $format): array
    {
        $rows = $this->normalizeRows($report->cached_data);
        $headings = $rows ? array_keys($rows[0]) : [];

        $slug = Str::slug($report->name ?: 'report');
        $baseName = ($slug ?: 'report').'_'.$report->id.'_'.now()->format('Ymd_His');
        $fileName = $baseName.'.'.$format;
        $filePath = 'report-exports/'.$report->academy_id.'/'.$fileName;

        if ($format === 'pdf') {
            $this->writePdf($filePath, $report, $headings, $rows);
        } else {
            $writer = $format === 'csv' ? ExcelWriter::CSV : ExcelWriter::XLSX;
            Excel::store(new SavedReportArrayExport($headings, $rows), $filePath, 'public', $writer);
        }

        return [
            'file_name' => $fileName,
            'file_path' => $filePath,
            'file_type' => $format,
            'file_size' => (int) Storage::disk('public')->size($filePath),
        ];
    }

    private function normalizeRows($cached): array
    {
        if (! is_array($cached)) {
            return [];
        }

        return array_values(array_map(fn ($row) => (array) $row, $cached));
    }

    private function writePdf(string $filePath, SavedReport $report, array $headings, array $rows): void
    {
        $tempDir = storage_path('app/mpdf');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $html = view('exports.saved-report', [
            'report' => $report,
            'headings' => $headings,
            'rows' => $rows,
        ])->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'default_font' => 'garuda',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 12,
            'margin_bottom' => 12,
            'tempDir' => $tempDir,
        ]);
        $mpdf->WriteHTML($html);

        Storage::disk('public')->put($filePath, $mpdf->Output('', Destination::STRING_RETURN));
    }
}
