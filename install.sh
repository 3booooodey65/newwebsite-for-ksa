#!/bin/bash

echo "🔧 بدء تثبيت موقع الروّاد للصيانة - rwadcool"
echo "============================================="

# التحقق من وجود PHP
if ! command -v php &> /dev/null; then
    echo "❌ PHP غير مثبت. يرجى تثبيت PHP 7.4 أو أحدث"
    exit 1
fi

echo "✅ تم العثور على PHP: $(php -v | head -n1)"

# التحقق من وجود Composer
if ! command -v composer &> /dev/null; then
    echo "⚠️  Composer غير مثبت. جاري التحميل..."
    
    # تحميل Composer
    curl -sS https://getcomposer.org/installer | php
    mv composer.phar /usr/local/bin/composer
    chmod +x /usr/local/bin/composer
    
    if [ $? -eq 0 ]; then
        echo "✅ تم تثبيت Composer بنجاح"
    else
        echo "❌ فشل في تثبيت Composer"
        exit 1
    fi
else
    echo "✅ تم العثور على Composer"
fi

# إنشاء المجلدات المطلوبة
echo "📁 إنشاء المجلدات..."
mkdir -p uploads
mkdir -p vendor
chmod 755 uploads

# تثبيت مكتبات PHP
echo "📦 تثبيت مكتبات PHP..."
composer install --no-dev --optimize-autoloader

if [ $? -eq 0 ]; then
    echo "✅ تم تثبيت المكتبات بنجاح"
else
    echo "⚠️  تم التثبيت مع تحذيرات"
fi

# إعداد الصلاحيات
echo "🔒 إعداد الصلاحيات..."
chmod 644 *.html *.css *.js *.php
chmod 755 uploads/
chmod 600 composer.json

# إنشاء ملف اختبار
echo "🧪 إنشاء ملفات الاختبار..."
echo "<?php echo json_encode(['status' => 'success', 'message' => 'PHP يعمل بنجاح']); ?>" > test.php

# التحقق من إعدادات الخادم
echo "⚙️  التحقق من إعدادات الخادم..."
php -m | grep -E "(curl|json|mbstring|fileinfo)" > /dev/null
if [ $? -eq 0 ]; then
    echo "✅ جميع الإضافات المطلوبة متوفرة"
else
    echo "⚠️  بعض الإضافات قد تكون غير متوفرة"
fi

echo ""
echo "🎉 تم تثبيت الموقع بنجاح!"
echo "============================================="
echo "📋 الخطوات التالية:"
echo "1. ارفع الملفات إلى خادم الويب"
echo "2. تأكد من تشغيل PHP"
echo "3. افتح setup.php للتحقق النهائي"
echo "4. ابدأ باستخدام الموقع من index.html"
echo ""
echo "📞 رقم الشركة: +966 57 446 7922"
echo "🌐 اسم الموقع: الروّاد للصيانة - rwadcool"
echo ""
echo "✨ شكراً لاستخدام موقع الروّاد للصيانة!"