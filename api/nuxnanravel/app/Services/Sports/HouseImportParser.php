<?php

namespace App\Services\Sports;

use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;

class HouseImportParser
{
    public function parse(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        if ($extension === 'csv') {
            return $this->parseCsv($file->getRealPath());
        }
        if ($extension === 'xlsx') {
            $sheet = IOFactory::load($file->getRealPath())->getActiveSheet();
            $values = $sheet->toArray(null, true, true, true);

            return $this->mapRows($values);
        }
        throw new \InvalidArgumentException('Only CSV and XLSX files are supported.');
    }

    private function parseCsv(string $path): array
    {
        $handle = fopen($path, 'rb');
        $values = [];
        while (($row = fgetcsv($handle)) !== false) {
            $values[] = $row;
        }
        fclose($handle);

        return $this->mapRows($values);
    }

    private function mapRows(array $values): array
    {
        if ($values === []) {
            return [];
        }
        $headers = array_map(fn ($value) => trim(ltrim((string) $value, "\xEF\xBB\xBF")), array_values(array_shift($values)));
        $rows = [];
        foreach ($values as $value) {
            $cells = array_values($value);
            $cells = array_map(fn ($cell) => trim((string) $cell), $cells);
            if (count(array_filter($cells, fn ($cell) => $cell !== '')) === 0) {
                continue;
            }
            $cells = array_pad($cells, count($headers), '');
            $row = [];
            foreach ($headers as $index => $header) {
                if ($header !== '') {
                    $row[$header] = $cells[$index] ?? '';
                }
            }
            $rows[] = $row;
        }

        return $rows;
    }
}
