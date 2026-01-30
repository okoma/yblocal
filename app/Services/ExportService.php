<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportService
{
    public static function streamCsv(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Excel compatibility
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $headers);

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public static function streamCsvFromCollection(string $filename, array $headers, $records, callable $rowMapper): StreamedResponse
    {
        $rows = collect($records)->map(fn ($record) => $rowMapper($record));

        return self::streamCsv($filename, $headers, $rows);
    }

    public static function streamCsvFromQuery(string $filename, array $headers, $query, callable $rowMapper): StreamedResponse
    {
        $rows = $query->cursor()->map(fn ($record) => $rowMapper($record));

        return self::streamCsv($filename, $headers, $rows);
    }
}
