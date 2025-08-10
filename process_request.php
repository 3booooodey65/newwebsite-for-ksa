<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// إعدادات الشركة
define('COMPANY_PHONE', '+966574467922');
define('EXCEL_FILE', 'service_requests.xlsx');
define('UPLOAD_DIR', 'uploads/');

// التأكد من وجود مجلد الرفع
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}

// التحقق من طريقة الطلب
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'طريقة الطلب غير مدعومة']);
    exit;
}

try {
    // استقبال البيانات
    $fullName = trim($_POST['fullName'] ?? '');
    $phoneNumber = trim($_POST['phoneNumber'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $deviceType = trim($_POST['deviceType'] ?? '');
    $problemDescription = trim($_POST['problemDescription'] ?? '');
    $initialInspection = trim($_POST['initialInspection'] ?? 'لا');
    
    // التحقق من البيانات المطلوبة
    if (empty($fullName) || empty($phoneNumber) || empty($address) || empty($deviceType) || empty($problemDescription)) {
        throw new Exception('جميع الحقول مطلوبة');
    }
    
    // التحقق من صحة رقم الهاتف
    if (!preg_match('/^(05|5)[0-9]{8}$/', preg_replace('/[\s\-\(\)]/', '', $phoneNumber))) {
        throw new Exception('رقم الهاتف غير صحيح');
    }
    
    // معالجة رفع الصورة
    $imagePath = '';
    if (isset($_FILES['deviceImage']) && $_FILES['deviceImage']['error'] === UPLOAD_ERROR_OK) {
        $imageInfo = getimagesize($_FILES['deviceImage']['tmp_name']);
        if ($imageInfo !== false) {
            $extension = pathinfo($_FILES['deviceImage']['name'], PATHINFO_EXTENSION);
            $imageName = 'device_' . time() . '_' . uniqid() . '.' . $extension;
            $imagePath = UPLOAD_DIR . $imageName;
            
            if (!move_uploaded_file($_FILES['deviceImage']['tmp_name'], $imagePath)) {
                $imagePath = '';
            }
        }
    }
    
    // إعداد بيانات الطلب
    $requestData = [
        'التاريخ' => date('Y-m-d H:i:s'),
        'الاسم الكامل' => $fullName,
        'رقم الجوال' => $phoneNumber,
        'العنوان' => $address,
        'نوع الجهاز' => $deviceType,
        'وصف المشكلة' => $problemDescription,
        'فحص مبدئي' => $initialInspection,
        'صورة الجهاز' => $imagePath
    ];
    
    // حفظ البيانات في Excel
    $excelSaved = saveToExcel($requestData);
    
    // إرسال رسالة واتساب باستخدام API المحسن
    require_once 'whatsapp_api.php';
    $whatsappResult = sendServiceRequestWhatsApp($requestData);
    $whatsappSent = $whatsappResult['success'] ?? false;
    
    // الرد بالنجاح
    echo json_encode([
        'success' => true,
        'message' => 'تم إرسال طلبك بنجاح',
        'excel_saved' => $excelSaved,
        'whatsapp_sent' => $whatsappSent
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// وظيفة حفظ البيانات في Excel
function saveToExcel($data) {
    try {
        // التحقق من وجود مكتبة PhpSpreadsheet
        if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            // إنشاء ملف CSV بدلاً من Excel
            return saveToCSV($data);
        }
        
        require_once 'vendor/autoload.php';
        
        use PhpOffice\PhpSpreadsheet\Spreadsheet;
        use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
        use PhpOffice\PhpSpreadsheet\Style\Alignment;
        
        $spreadsheet = new Spreadsheet();
        
        // إنشاء ملف جديد أو فتح الموجود
        if (file_exists(EXCEL_FILE)) {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $spreadsheet = $reader->load(EXCEL_FILE);
        } else {
            // إنشاء العناوين للملف الجديد
            $sheet = $spreadsheet->getActiveSheet();
            $headers = array_keys($data);
            $col = 1;
            foreach ($headers as $header) {
                $sheet->setCellValueByColumnAndRow($col, 1, $header);
                $col++;
            }
            
            // تنسيق العناوين
            $sheet->getStyle('A1:' . chr(64 + count($headers)) . '1')->getFont()->setBold(true);
            $sheet->getStyle('A1:' . chr(64 + count($headers)) . '1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }
        
        $sheet = $spreadsheet->getActiveSheet();
        $lastRow = $sheet->getHighestRow() + 1;
        
        // إضافة البيانات الجديدة
        $col = 1;
        foreach ($data as $value) {
            $sheet->setCellValueByColumnAndRow($col, $lastRow, $value);
            $col++;
        }
        
        // حفظ الملف
        $writer = new Xlsx($spreadsheet);
        $writer->save(EXCEL_FILE);
        
        return true;
        
    } catch (Exception $e) {
        error_log('خطأ في حفظ Excel: ' . $e->getMessage());
        return saveToCSV($data);
    }
}

// وظيفة بديلة لحفظ البيانات في CSV
function saveToCSV($data) {
    try {
        $csvFile = 'service_requests.csv';
        $isNewFile = !file_exists($csvFile);
        
        $file = fopen($csvFile, 'a');
        
        // إضافة العناوين للملف الجديد
        if ($isNewFile) {
            fputcsv($file, array_keys($data));
        }
        
        // إضافة البيانات
        fputcsv($file, array_values($data));
        fclose($file);
        
        return true;
        
    } catch (Exception $e) {
        error_log('خطأ في حفظ CSV: ' . $e->getMessage());
        return false;
    }
}

// وظيفة إرسال إشعار واتساب
function sendWhatsAppNotification($data) {
    try {
        // إنشاء رسالة واتساب
        $message = "🔧 طلب خدمة جديد من موقع الروّاد للصيانة\n\n";
        $message .= "👤 الاسم: " . $data['الاسم الكامل'] . "\n";
        $message .= "📱 الهاتف: " . $data['رقم الجوال'] . "\n";
        $message .= "📍 العنوان: " . $data['العنوان'] . "\n";
        $message .= "🔌 نوع الجهاز: " . $data['نوع الجهاز'] . "\n";
        $message .= "❗ المشكلة: " . $data['وصف المشكلة'] . "\n";
        $message .= "🔍 فحص مبدئي: " . $data['فحص مبدئي'] . "\n";
        $message .= "📅 التاريخ: " . $data['التاريخ'] . "\n";
        
        if (!empty($data['صورة الجهاز'])) {
            $message .= "📷 تم رفع صورة للجهاز\n";
        }
        
        $message .= "\n✅ يرجى التواصل مع العميل في أقرب وقت";
        
        // ترميز الرسالة للرابط
        $encodedMessage = urlencode($message);
        $whatsappUrl = "https://wa.me/" . str_replace(['+', ' ', '-', '(', ')'], '', COMPANY_PHONE) . "?text=" . $encodedMessage;
        
        // في بيئة الإنتاج، يمكن استخدام WhatsApp Business API
        // هنا نقوم بحفظ الرابط في ملف لوج للمراجعة
        $logEntry = date('Y-m-d H:i:s') . " - WhatsApp URL: " . $whatsappUrl . "\n";
        file_put_contents('whatsapp_log.txt', $logEntry, FILE_APPEND);
        
        return true;
        
    } catch (Exception $e) {
        error_log('خطأ في إرسال واتساب: ' . $e->getMessage());
        return false;
    }
}

// وظيفة تنظيف البيانات
function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

// وظيفة التحقق من صحة رقم الهاتف السعودي
function validateSaudiPhone($phone) {
    $cleanPhone = preg_replace('/[\s\-\(\)]/', '', $phone);
    return preg_match('/^(05|5)[0-9]{8}$/', $cleanPhone);
}
?>