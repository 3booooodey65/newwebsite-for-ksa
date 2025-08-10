<?php
require_once __DIR__ . '/config.php';

if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool {
        return $needle === '' || strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}

function normalizeSaudiPhone(string $raw): string {
    $digits = preg_replace('/\D+/', '', $raw);
    if (preg_match('/^(966)?5\d{8}$/', $digits)) {
        if (str_starts_with($digits, '966')) return $digits;
        return '966' . $digits; // starts with 5XXXXXXXX
    }
    if (preg_match('/^05\d{8}$/', $digits)) {
        return '966' . substr($digits, 1);
    }
    return $digits; // return as-is if not matching; API may reject
}

function sendWhatsAppText(string $toInternational, string $body): array {
    if (!WHATSAPP_PHONE_NUMBER_ID || !WHATSAPP_ACCESS_TOKEN || WHATSAPP_PHONE_NUMBER_ID === 'YOUR_PHONE_NUMBER_ID' || WHATSAPP_ACCESS_TOKEN === 'YOUR_PERMANENT_OR_TEMP_TOKEN') {
        return [ 'ok' => false, 'error' => 'WhatsApp API not configured' ];
    }

    $url = 'https://graph.facebook.com/v18.0/' . urlencode(WHATSAPP_PHONE_NUMBER_ID) . '/messages';
    $payload = [
        'messaging_product' => 'whatsapp',
        'to' => $toInternational,
        'type' => 'text',
        'text' => [ 'body' => $body ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . WHATSAPP_ACCESS_TOKEN,
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) {
        return [ 'ok' => false, 'error' => $err ];
    }
    $json = json_decode($response, true);
    if ($status >= 200 && $status < 300) {
        return [ 'ok' => true, 'response' => $json ];
    }
    return [ 'ok' => false, 'status' => $status, 'response' => $json ];
}

function ensureDirectory(string $path): void {
    if (!is_dir($path)) { @mkdir($path, 0775, true); }
}

function saveRowToExcel(string $excelPath, array $headers, array $row): array {
    ensureDirectory(dirname($excelPath));

    $hasSpreadsheet = class_exists('PhpOffice\\PhpSpreadsheet\\IOFactory');

    if ($hasSpreadsheet) {
        try {
            $spreadsheet = null;
            $sheet = null;
            if (file_exists($excelPath)) {
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($excelPath);
                $sheet = $spreadsheet->getActiveSheet();
            } else {
                $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();
                foreach ($headers as $i => $header) {
                    $sheet->setCellValueByColumnAndRow($i + 1, 1, $header);
                }
            }
            $nextRow = $sheet->getHighestRow() + 1;
            foreach ($row as $i => $value) {
                $sheet->setCellValueByColumnAndRow($i + 1, $nextRow, $value);
            }
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save($excelPath);
            return [ 'ok' => true ];
        } catch (\Throwable $e) {
            // Fallback to CSV if Excel fails
        }
    }

    // CSV Fallback
    $csvPath = preg_replace('/\.xlsx$/i', '.csv', $excelPath);
    $isNew = !file_exists($csvPath);
    $fp = fopen($csvPath, 'a');
    if ($isNew) { fputcsv($fp, $headers); }
    fputcsv($fp, $row);
    fclose($fp);
    return [ 'ok' => true, 'fallback' => 'csv' ];
}