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

    $fullName = trim($_POST['fullName'] ?? '');
    $phoneRaw = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $deviceType = trim($_POST['deviceType'] ?? '');
    $issueDescription = trim($_POST['issueDescription'] ?? '');
    $initialCheck = isset($_POST['initialCheck']) ? 'نعم' : 'لا';

    if ($phoneRaw === '') {
        echo json_encode([ 'success' => false, 'message' => 'رقم الجوال مطلوب' ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Basic server-side phone validation (Saudi mobile)
    $digits = preg_replace('/\D+/', '', $phoneRaw);
    if (!preg_match('/^(?:\+?966|0)?5\d{8}$/', $phoneRaw) && !preg_match('/^(966)?5\d{8}$/', $digits)) {
        echo json_encode([ 'success' => false, 'message' => 'رقم الجوال غير صالح' ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    ensureDirectory(UPLOADS_DIR);
    $savedImageName = '';
    if (!empty($_FILES['image']['name']) && is_uploaded_file($_FILES['image']['tmp_name'])) {
        $mime = mime_content_type($_FILES['image']['tmp_name']);
        if (!in_array($mime, ALLOWED_IMAGE_MIME, true)) {
            echo json_encode([ 'success' => false, 'message' => 'نوع الصورة غير مدعوم' ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION) ?: 'jpg';
        $savedImageName = 'req_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
        $dest = rtrim(UPLOADS_DIR,'/') . '/' . $savedImageName;
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
            echo json_encode([ 'success' => false, 'message' => 'تعذر حفظ الصورة' ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    // Build WhatsApp message
    $phoneIntl = normalizeSaudiPhone($phoneRaw);
    $summary = "طلب خدمة جديد:\n" .
               "الاسم: " . ($fullName ?: '—') . "\n" .
               "الجوال: +" . $phoneIntl . "\n" .
               "العنوان: " . ($address ?: '—') . "\n" .
               "نوع الجهاز: " . ($deviceType ?: '—') . "\n" .
               "فحص مبدئي: " . $initialCheck . "\n" .
               "الوصف: " . ($issueDescription ?: '—');

    // Send WhatsApp to company number
    $waResult = sendWhatsAppText(COMPANY_WHATSAPP_NUMBER, $summary);

    // Save to Excel
    $excelPath = STORAGE_DIR . '/requests.xlsx';
    $headers = [ 'التاريخ', 'الاسم', 'الجوال', 'العنوان', 'نوع الجهاز', 'فحص مبدئي', 'الوصف', 'الملف' ];
    $row = [ date('Y-m-d H:i:s'), $fullName, $phoneIntl, $address, $deviceType, $initialCheck, $issueDescription, $savedImageName ];
    $saveResult = saveRowToExcel($excelPath, $headers, $row);

    $message = 'تم استلام طلبك بنجاح وسيتم التواصل معك قريباً.';
    if (!$waResult['ok']) { $message .= ' (تنبيه: لم يتم إرسال إشعار واتساب تلقائياً قبل إكمال الإعدادات)'; }
    if (isset($saveResult['fallback'])) { $message .= ' (تم الحفظ كـ CSV لعدم توفر Excel حالياً)'; }

    echo json_encode([ 'success' => true, 'message' => $message ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([ 'success' => false, 'message' => 'خطأ داخلي: ' . $e->getMessage() ], JSON_UNESCAPED_UNICODE);
}