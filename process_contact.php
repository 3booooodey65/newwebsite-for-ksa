<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// إعدادات الشركة
define('COMPANY_PHONE', '+966574467922');
define('CONTACT_FILE', 'contact_messages.csv');

// التحقق من طريقة الطلب
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'طريقة الطلب غير مدعومة']);
    exit;
}

try {
    // استقبال البيانات
    $contactName = trim($_POST['contactName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $contactPhone = trim($_POST['contactPhone'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    // التحقق من البيانات المطلوبة
    if (empty($contactName) || empty($email) || empty($contactPhone) || empty($message)) {
        throw new Exception('جميع الحقول مطلوبة');
    }
    
    // التحقق من صحة البريد الإلكتروني
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('البريد الإلكتروني غير صحيح');
    }
    
    // التحقق من صحة رقم الهاتف
    if (!validateSaudiPhone($contactPhone)) {
        throw new Exception('رقم الهاتف غير صحيح');
    }
    
    // إعداد بيانات الرسالة
    $contactData = [
        'التاريخ' => date('Y-m-d H:i:s'),
        'الاسم' => $contactName,
        'البريد الإلكتروني' => $email,
        'رقم الهاتف' => $contactPhone,
        'الرسالة' => $message
    ];
    
    // حفظ البيانات في ملف CSV
    $csvSaved = saveContactToCSV($contactData);
    
    // إرسال إشعار واتساب باستخدام API المحسن
    require_once 'whatsapp_api.php';
    $whatsappResult = sendContactWhatsApp($contactData);
    $whatsappSent = $whatsappResult['success'] ?? false;
    
    // الرد بالنجاح
    echo json_encode([
        'success' => true,
        'message' => 'تم إرسال رسالتك بنجاح',
        'csv_saved' => $csvSaved,
        'whatsapp_sent' => $whatsappSent
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// وظيفة حفظ رسائل التواصل في CSV
function saveContactToCSV($data) {
    try {
        $isNewFile = !file_exists(CONTACT_FILE);
        
        $file = fopen(CONTACT_FILE, 'a');
        
        // إضافة العناوين للملف الجديد
        if ($isNewFile) {
            fputcsv($file, array_keys($data));
        }
        
        // إضافة البيانات
        fputcsv($file, array_values($data));
        fclose($file);
        
        return true;
        
    } catch (Exception $e) {
        error_log('خطأ في حفظ رسالة التواصل: ' . $e->getMessage());
        return false;
    }
}

// وظيفة إرسال إشعار واتساب للرسائل
function sendContactWhatsApp($data) {
    try {
        // إنشاء رسالة واتساب
        $message = "💬 رسالة جديدة من موقع الروّاد للصيانة\n\n";
        $message .= "👤 الاسم: " . $data['الاسم'] . "\n";
        $message .= "📧 البريد: " . $data['البريد الإلكتروني'] . "\n";
        $message .= "📱 الهاتف: " . $data['رقم الهاتف'] . "\n";
        $message .= "💭 الرسالة: " . $data['الرسالة'] . "\n";
        $message .= "📅 التاريخ: " . $data['التاريخ'] . "\n";
        
        // ترميز الرسالة للرابط
        $encodedMessage = urlencode($message);
        $whatsappUrl = "https://wa.me/" . str_replace(['+', ' ', '-', '(', ')'], '', COMPANY_PHONE) . "?text=" . $encodedMessage;
        
        // حفظ الرابط في ملف لوج للمراجعة
        $logEntry = date('Y-m-d H:i:s') . " - Contact WhatsApp URL: " . $whatsappUrl . "\n";
        file_put_contents('contact_whatsapp_log.txt', $logEntry, FILE_APPEND);
        
        return true;
        
    } catch (Exception $e) {
        error_log('خطأ في إرسال واتساب للتواصل: ' . $e->getMessage());
        return false;
    }
}

// وظيفة التحقق من صحة رقم الهاتف السعودي
function validateSaudiPhone($phone) {
    $cleanPhone = preg_replace('/[\s\-\(\)]/', '', $phone);
    return preg_match('/^(05|5)[0-9]{8}$/', $cleanPhone);
}

// وظيفة تنظيف البيانات
function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}
?>