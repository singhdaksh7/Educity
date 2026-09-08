<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvExportService
{
    /**
     * Stream a filtered query as a CSV file using chunked reads so the
     * whole result set never has to sit in memory at once. Every cell is
     * formula-sanitised to defuse spreadsheet formula injection.
     */
    public function stream(Builder $query, string $filename, array $headers, callable $mapRow): StreamedResponse
    {
        $response = new StreamedResponse(function () use ($query, $headers, $mapRow) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);

            $query->orderBy($query->getModel()->getKeyName())->chunk(200, function ($rows) use ($out, $mapRow) {
                foreach ($rows as $row) {
                    fputcsv($out, array_map([$this, 'sanitizeCell'], $mapRow($row)));
                }
            });

            fclose($out);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');

        return $response;
    }

    /**
     * Prefix cells that start with =, +, - or @ so spreadsheet
     * applications never interpret exported data as a formula.
     */
    private function sanitizeCell($value): string
    {
        $value = (string) ($value ?? '');

        if ($value !== '' && str_contains('=+-@', $value[0])) {
            return "'".$value;
        }

        return $value;
    }
}
