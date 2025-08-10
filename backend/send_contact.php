<?php
header('Content-Type: application/json; charset=utf-8');

$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloadPath)) { require_once $autoloadPath; }
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode([ 'success' => false, 'message' => 'Method not allowed' ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name === '' || $message === '') {
        echo json_encode([ 'success' => false, 'message' => 'يرجى إدخال الاسم والرسالة' ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $phoneIntl = $phone ? normalizeSaudiPhone($phone) : '';

    $summary = "رسالة تواصل جديدة:\n" .
               "الاسم: " . $name . "\n" .
               "البريد: " . ($email ?: '—') . "\n" .
               "الجوال: " . ($phoneIntl ? ('+' . $phoneIntl) : '—') . "\n" .
               "الرسالة: " . $message;

    $waResult = sendWhatsAppText(COMPANY_WHATSAPP_NUMBER, $summary);

    $excelPath = STORAGE_DIR . '/contacts.xlsx';
    $headers = [ 'التاريخ', 'الاسم', 'البريد', 'الجوال', 'الرسالة' ];
    $row = [ date('Y-m-d H:i:s'), $name, $email, $phoneIntl, $message ];
    $saveResult = saveRowToExcel($excelPath, $headers, $row);

    $resp = 'تم إرسال رسالتك بنجاح.';
    if (!$waResult['ok']) { $resp .= ' (تنبيه: لم يتم إرسال إشعار واتساب تلقائياً قبل إكمال الإعدادات)'; }
    if (isset($saveResult['fallback'])) { $resp .= ' (تم الحفظ كـ CSV لعدم توفر Excel حالياً)'; }

    echo json_encode([ 'success' => true, 'message' => $resp ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([ 'success' => false, 'message' => 'خطأ داخلي: ' . $e->getMessage() ], JSON_UNESCAPED_UNICODE);
}