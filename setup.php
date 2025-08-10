<?php
/**
 * ملف الإعداد السريع لموقع الروّاد للصيانة
 * يقوم بالتحقق من المتطلبات وإنشاء المجلدات المطلوبة
 */

echo "<!DOCTYPE html>";
echo "<html lang='ar' dir='rtl'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<title>إعداد موقع الروّاد للصيانة</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css' rel='stylesheet'>";
echo "<link href='https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap' rel='stylesheet'>";
echo "<style>body { font-family: 'Cairo', sans-serif; background: #f8f9fa; }</style>";
echo "</head>";
echo "<body>";

echo "<div class='container mt-5'>";
echo "<div class='row justify-content-center'>";
echo "<div class='col-lg-8'>";
echo "<div class='card shadow'>";
echo "<div class='card-header bg-primary text-white text-center'>";
echo "<h2 class='mb-0'>🔧 إعداد موقع الروّاد للصيانة</h2>";
echo "</div>";
echo "<div class='card-body p-4'>";

$allChecks = true;

// التحقق من إصدار PHP
echo "<h4 class='text-primary mb-3'>التحقق من المتطلبات:</h4>";
echo "<div class='alert alert-info'>";
echo "<strong>إصدار PHP:</strong> " . PHP_VERSION;
if (version_compare(PHP_VERSION, '7.4.0') >= 0) {
    echo " <span class='badge bg-success'>✓ مدعوم</span>";
} else {
    echo " <span class='badge bg-danger'>✗ يتطلب PHP 7.4 أو أحدث</span>";
    $allChecks = false;
}
echo "</div>";

// التحقق من الإضافات المطلوبة
$extensions = ['curl', 'json', 'mbstring', 'fileinfo'];
foreach ($extensions as $ext) {
    echo "<div class='alert alert-info'>";
    echo "<strong>إضافة {$ext}:</strong> ";
    if (extension_loaded($ext)) {
        echo "<span class='badge bg-success'>✓ متوفرة</span>";
    } else {
        echo "<span class='badge bg-warning'>⚠ غير متوفرة</span>";
    }
    echo "</div>";
}

// التحقق من الصلاحيات
echo "<h4 class='text-primary mb-3 mt-4'>التحقق من الصلاحيات:</h4>";

$directories = ['uploads', '.'];
foreach ($directories as $dir) {
    echo "<div class='alert alert-info'>";
    echo "<strong>مجلد {$dir}:</strong> ";
    
    if (!file_exists($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "<span class='badge bg-success'>✓ تم إنشاؤه</span>";
        } else {
            echo "<span class='badge bg-danger'>✗ فشل في الإنشاء</span>";
            $allChecks = false;
        }
    } else {
        if (is_writable($dir)) {
            echo "<span class='badge bg-success'>✓ قابل للكتابة</span>";
        } else {
            echo "<span class='badge bg-warning'>⚠ غير قابل للكتابة</span>";
        }
    }
    echo "</div>";
}

// التحقق من وجود Composer
echo "<h4 class='text-primary mb-3 mt-4'>التحقق من المكتبات:</h4>";
echo "<div class='alert alert-info'>";
echo "<strong>Composer:</strong> ";
if (file_exists('vendor/autoload.php')) {
    echo "<span class='badge bg-success'>✓ مثبت</span>";
} else {
    echo "<span class='badge bg-warning'>⚠ غير مثبت</span>";
    echo "<br><small class='text-muted'>قم بتشغيل: composer install</small>";
}
echo "</div>";

// إنشاء ملفات الاختبار
echo "<h4 class='text-primary mb-3 mt-4'>إنشاء ملفات الاختبار:</h4>";

// ملف اختبار PHP
$testPhpContent = "<?php\necho json_encode(['status' => 'PHP يعمل بنجاح', 'time' => date('Y-m-d H:i:s')]);\n?>";
if (file_put_contents('test_php.php', $testPhpContent)) {
    echo "<div class='alert alert-success'>✓ تم إنشاء ملف اختبار PHP</div>";
} else {
    echo "<div class='alert alert-danger'>✗ فشل في إنشاء ملف اختبار PHP</div>";
    $allChecks = false;
}

// ملف اختبار الرفع
$uploadsTest = file_put_contents('uploads/test.txt', 'اختبار الرفع - ' . date('Y-m-d H:i:s'));
if ($uploadsTest) {
    echo "<div class='alert alert-success'>✓ مجلد الرفع يعمل بنجاح</div>";
    unlink('uploads/test.txt'); // حذف ملف الاختبار
} else {
    echo "<div class='alert alert-danger'>✗ مشكلة في مجلد الرفع</div>";
    $allChecks = false;
}

// النتيجة النهائية
echo "<hr>";
if ($allChecks) {
    echo "<div class='alert alert-success text-center'>";
    echo "<h3>🎉 تم الإعداد بنجاح!</h3>";
    echo "<p>الموقع جاهز للاستخدام</p>";
    echo "<a href='index.html' class='btn btn-primary btn-lg'>زيارة الموقع</a>";
    echo "</div>";
} else {
    echo "<div class='alert alert-warning text-center'>";
    echo "<h3>⚠ يتطلب إعداد إضافي</h3>";
    echo "<p>يرجى حل المشاكل المذكورة أعلاه</p>";
    echo "</div>";
}

// معلومات إضافية
echo "<div class='mt-4'>";
echo "<h5 class='text-primary'>معلومات مفيدة:</h5>";
echo "<ul class='list-group'>";
echo "<li class='list-group-item'>📱 رقم الشركة: +966 57 446 7922</li>";
echo "<li class='list-group-item'>🌐 اسم الموقع: الروّاد للصيانة - rwadcool</li>";
echo "<li class='list-group-item'>📂 مجلد الرفع: uploads/</li>";
echo "<li class='list-group-item'>📊 ملفات البيانات: service_requests.xlsx, contact_messages.csv</li>";
echo "</ul>";
echo "</div>";

echo "</div></div></div></div></div>";
echo "</body></html>";
?>