<?php
/**
 * ملف الإعدادات المركزي لموقع الروّاد للصيانة
 */

// إعدادات الشركة
define('COMPANY_NAME_AR', 'الروّاد للصيانة');
define('COMPANY_NAME_EN', 'rwadcool');
define('COMPANY_PHONE', '+966574467922');
define('COMPANY_WHATSAPP', '966574467922');

// إعدادات الموقع
define('SITE_TITLE', 'الروّاد للصيانة - rwadcool');
define('SITE_DESCRIPTION', 'خدمات صيانة احترافية لجميع الأجهزة المنزلية');
define('SITE_KEYWORDS', 'صيانة, أجهزة منزلية, مكائن قهوة, مكيفات, غسالات, إصلاح');

// إعدادات الملفات
define('EXCEL_FILE', 'service_requests.xlsx');
define('CSV_BACKUP', 'service_requests.csv');
define('CONTACT_FILE', 'contact_messages.csv');
define('UPLOAD_DIR', 'uploads/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10 MB

// إعدادات قاعدة البيانات (للاستخدام المستقبلي)
define('DB_HOST', 'localhost');
define('DB_NAME', 'rwadcool_db');
define('DB_USER', 'rwadcool_user');
define('DB_PASS', '');

// إعدادات البريد الإلكتروني (للاستخدام المستقبلي)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', '');
define('SMTP_PASS', '');
define('FROM_EMAIL', 'info@rwadcool.com');
define('FROM_NAME', 'الروّاد للصيانة');

// إعدادات WhatsApp API (للاستخدام المستقبلي)
define('WHATSAPP_API_URL', '');
define('WHATSAPP_API_TOKEN', '');

// إعدادات الأمان
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('MAX_IMAGE_WIDTH', 2000);
define('MAX_IMAGE_HEIGHT', 2000);

// إعدادات التطبيق
define('TIMEZONE', 'Asia/Riyadh');
define('LANGUAGE', 'ar');
define('CHARSET', 'UTF-8');

// تعيين المنطقة الزمنية
date_default_timezone_set(TIMEZONE);

// وظائف مساعدة
function getConfig($key, $default = null) {
    return defined($key) ? constant($key) : $default;
}

function isProduction() {
    return !in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1', '::1']);
}

function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    return $protocol . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']);
}

// إعدادات الخطأ
if (isProduction()) {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', 'error.log');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// إعدادات الجلسة
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isProduction() ? 1 : 0);

?>