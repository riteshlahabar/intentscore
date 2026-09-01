<?php

namespace App\Services\ImportExport;

use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvService
{
    public function read(UploadedFile $file): iterable
    {
        $handle = fopen($file->getRealPath(), 'r');
        if (! $handle) {
            return;
        }

        try {
            $headers = fgetcsv($handle);
            if (! $headers) {
                return;
            }

            $headers = array_map(
                fn ($header) => strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '_', trim((string) $header)), '_')),
                $headers
            );

            while (($data = fgetcsv($handle)) !== false) {
                if (count(array_filter($data, fn ($value) => $value !== null && $value !== '')) === 0) {
                    continue;
                }

                $data = array_pad($data, count($headers), null);
                yield array_combine($headers, array_slice($data, 0, count($headers)));
            }
        } finally {
            fclose($handle);
        }
    }

    private function safeCell(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $trimmed = ltrim($value);
        return $trimmed !== '' && in_array($trimmed[0], ['=', '+', '-', '@'], true)
            ? "'".$value
            : $value;
    }

    public function download(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headers);

            foreach ($rows as $row) {
                fputcsv($out, array_map(fn ($value) => $this->safeCell($value), $row));
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
