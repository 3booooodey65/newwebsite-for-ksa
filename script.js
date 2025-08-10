// متغيرات عامة
const COMPANY_PHONE = '+966574467922';

// التحقق من صحة رقم الهاتف السعودي
function validateSaudiPhone(phone) {
    const phoneRegex = /^(05|5)[0-9]{8}$/;
    const cleanPhone = phone.replace(/[\s\-\(\)]/g, '');
    return phoneRegex.test(cleanPhone);
}

// التحقق من صحة البريد الإلكتروني
function validateEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// عرض رسالة نجاح
function showSuccessMessage(messageId, duration = 3000) {
    const messageElement = document.getElementById(messageId);
    if (messageElement) {
        messageElement.classList.remove('d-none');
        setTimeout(() => {
            messageElement.classList.add('d-none');
        }, duration);
    }
}

// حفظ البيانات في LocalStorage
function saveToLocalStorage(key, data) {
    try {
        localStorage.setItem(key, JSON.stringify(data));
        return true;
    } catch (error) {
        console.error('خطأ في حفظ البيانات:', error);
        return false;
    }
}

// استرجاع البيانات من LocalStorage
function getFromLocalStorage(key) {
    try {
        const data = localStorage.getItem(key);
        return data ? JSON.parse(data) : null;
    } catch (error) {
        console.error('خطأ في استرجاع البيانات:', error);
        return null;
    }
}

// تحميل البيانات المحفوظة في النموذج
function loadSavedData(formId) {
    const savedData = getFromLocalStorage(formId);
    if (savedData) {
        Object.keys(savedData).forEach(key => {
            const element = document.getElementById(key);
            if (element) {
                if (element.type === 'radio') {
                    const radioElement = document.querySelector(`input[name="${key}"][value="${savedData[key]}"]`);
                    if (radioElement) radioElement.checked = true;
                } else {
                    element.value = savedData[key];
                }
            }
        });
    }
}

// حفظ بيانات النموذج تلقائياً
function autoSaveForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return;

    const inputs = form.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
        input.addEventListener('change', () => {
            const formData = new FormData(form);
            const data = {};
            for (let [key, value] of formData.entries()) {
                data[key] = value;
            }
            saveToLocalStorage(formId, data);
        });
    });
}

// إضافة تأثيرات التحميل للأزرار
function addLoadingToButton(button, isLoading = true) {
    if (isLoading) {
        button.disabled = true;
        const originalText = button.innerHTML;
        button.setAttribute('data-original-text', originalText);
        button.innerHTML = '<span class="loading"></span> جاري الإرسال...';
    } else {
        button.disabled = false;
        const originalText = button.getAttribute('data-original-text');
        if (originalText) {
            button.innerHTML = originalText;
        }
    }
}

// معالجة نموذج طلب الخدمة
function handleServiceRequest(event) {
    event.preventDefault();
    
    const form = event.target;
    const submitButton = form.querySelector('button[type="submit"]');
    
    // التحقق من صحة البيانات
    if (!form.checkValidity()) {
        form.classList.add('was-validated');
        return;
    }

    // التحقق من رقم الهاتف
    const phoneNumber = document.getElementById('phoneNumber').value;
    if (!validateSaudiPhone(phoneNumber)) {
        document.getElementById('phoneNumber').setCustomValidity('رقم الهاتف غير صحيح');
        form.classList.add('was-validated');
        return;
    }

    // إضافة تأثير التحميل
    addLoadingToButton(submitButton, true);

    // جمع بيانات النموذج
    const formData = new FormData(form);
    
    // حفظ البيانات مؤقتاً
    const requestData = {};
    for (let [key, value] of formData.entries()) {
        requestData[key] = value;
    }
    saveToLocalStorage('lastServiceRequest', requestData);

    // إرسال البيانات
    fetch('process_request.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        addLoadingToButton(submitButton, false);
        
        if (data.success) {
            showSuccessMessage('successMessage');
            form.reset();
            form.classList.remove('was-validated');
            // مسح البيانات المحفوظة بعد الإرسال الناجح
            localStorage.removeItem('lastServiceRequest');
        } else {
            alert('حدث خطأ في إرسال الطلب: ' + (data.message || 'خطأ غير معروف'));
        }
    })
    .catch(error => {
        addLoadingToButton(submitButton, false);
        console.error('خطأ:', error);
        alert('حدث خطأ في الاتصال. يرجى المحاولة مرة أخرى.');
    });
}

// معالجة نموذج التواصل
function handleContactForm(event) {
    event.preventDefault();
    
    const form = event.target;
    const submitButton = form.querySelector('button[type="submit"]');
    
    // التحقق من صحة البيانات
    if (!form.checkValidity()) {
        form.classList.add('was-validated');
        return;
    }

    // التحقق من البريد الإلكتروني
    const email = document.getElementById('email').value;
    if (!validateEmail(email)) {
        document.getElementById('email').setCustomValidity('البريد الإلكتروني غير صحيح');
        form.classList.add('was-validated');
        return;
    }

    // التحقق من رقم الهاتف
    const phone = document.getElementById('contactPhone').value;
    if (!validateSaudiPhone(phone)) {
        document.getElementById('contactPhone').setCustomValidity('رقم الهاتف غير صحيح');
        form.classList.add('was-validated');
        return;
    }

    // إضافة تأثير التحميل
    addLoadingToButton(submitButton, true);

    // جمع بيانات النموذج
    const formData = new FormData(form);

    // إرسال البيانات
    fetch('process_contact.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        addLoadingToButton(submitButton, false);
        
        if (data.success) {
            showSuccessMessage('contactSuccessMessage');
            form.reset();
            form.classList.remove('was-validated');
        } else {
            alert('حدث خطأ في إرسال الرسالة: ' + (data.message || 'خطأ غير معروف'));
        }
    })
    .catch(error => {
        addLoadingToButton(submitButton, false);
        console.error('خطأ:', error);
        alert('حدث خطأ في الاتصال. يرجى المحاولة مرة أخرى.');
    });
}

// تنسيق رقم الهاتف أثناء الكتابة
function formatPhoneInput(input) {
    input.addEventListener('input', function() {
        let value = this.value.replace(/\D/g, '');
        
        // إضافة 05 تلقائياً إذا بدأ بـ 5
        if (value.length > 0 && value[0] === '5') {
            value = '05' + value.substring(1);
        }
        
        // تحديد الطول الأقصى
        if (value.length > 10) {
            value = value.substring(0, 10);
        }
        
        this.value = value;
        
        // التحقق من الصحة في الوقت الفعلي
        if (value.length >= 10) {
            if (validateSaudiPhone(value)) {
                this.setCustomValidity('');
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            } else {
                this.setCustomValidity('رقم الهاتف غير صحيح');
                this.classList.remove('is-valid');
                this.classList.add('is-invalid');
            }
        } else {
            this.setCustomValidity('');
            this.classList.remove('is-valid', 'is-invalid');
        }
    });
}

// تنسيق البريد الإلكتروني
function formatEmailInput(input) {
    input.addEventListener('blur', function() {
        const email = this.value.trim();
        if (email) {
            if (validateEmail(email)) {
                this.setCustomValidity('');
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            } else {
                this.setCustomValidity('البريد الإلكتروني غير صحيح');
                this.classList.remove('is-valid');
                this.classList.add('is-invalid');
            }
        }
    });
}

// إضافة تأثيرات بصرية للكروت
function addCardAnimations() {
    const cards = document.querySelectorAll('.card');
    cards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
        card.classList.add('fade-in');
    });
}

// تهيئة الصفحة عند التحميل
document.addEventListener('DOMContentLoaded', function() {
    // إضافة معالجات النماذج
    const serviceForm = document.getElementById('serviceRequestForm');
    if (serviceForm) {
        serviceForm.addEventListener('submit', handleServiceRequest);
        autoSaveForm('serviceRequestForm');
        loadSavedData('serviceRequestForm');
    }

    const contactForm = document.getElementById('contactForm');
    if (contactForm) {
        contactForm.addEventListener('submit', handleContactForm);
    }

    // تنسيق حقول الهاتف
    const phoneInputs = document.querySelectorAll('input[type="tel"]');
    phoneInputs.forEach(formatPhoneInput);

    // تنسيق حقول البريد الإلكتروني
    const emailInputs = document.querySelectorAll('input[type="email"]');
    emailInputs.forEach(formatEmailInput);

    // إضافة تأثيرات الكروت
    addCardAnimations();

    // إزالة رسائل التحقق المخصصة عند بداية الكتابة
    const allInputs = document.querySelectorAll('input, select, textarea');
    allInputs.forEach(input => {
        input.addEventListener('input', function() {
            this.setCustomValidity('');
        });
    });
});

// وظائف إضافية للتفاعل

// تكبير الصور عند النقر
function setupImagePreview() {
    const imageInputs = document.querySelectorAll('input[type="file"]');
    imageInputs.forEach(input => {
        input.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    // يمكن إضافة معاينة للصورة هنا
                    console.log('تم تحميل الصورة بنجاح');
                };
                reader.readAsDataURL(file);
            }
        });
    });
}

// تشغيل معاينة الصور
document.addEventListener('DOMContentLoaded', setupImagePreview);

// وظيفة لتنظيف البيانات المحفوظة (يمكن استخدامها عند الحاجة)
function clearSavedData(key) {
    localStorage.removeItem(key);
}

// وظيفة لإرسال رسالة واتساب مباشرة
function sendWhatsAppMessage(phone, message) {
    const whatsappUrl = `https://wa.me/${phone.replace(/[\s\-\(\)\+]/g, '')}?text=${encodeURIComponent(message)}`;
    window.open(whatsappUrl, '_blank');
}

// إضافة معالج للنقر على أرقام الهاتف
document.addEventListener('DOMContentLoaded', function() {
    const phoneLinks = document.querySelectorAll('a[href^="tel:"]');
    phoneLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            // يمكن إضافة تتبع للنقرات هنا
            console.log('تم النقر على رقم الهاتف');
        });
    });
});

// وظيفة للتمرير السلس
function smoothScrollTo(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    }
}

// إضافة تأثيرات التمرير
window.addEventListener('scroll', function() {
    const navbar = document.querySelector('.navbar');
    if (window.scrollY > 100) {
        navbar.classList.add('shadow');
    } else {
        navbar.classList.remove('shadow');
    }
});

// وظيفة لإرسال إحصائيات بسيطة (اختيارية)
function trackEvent(eventName, eventData = {}) {
    // يمكن إضافة تتبع الأحداث هنا
    console.log(`Event: ${eventName}`, eventData);
}

// تتبع النقرات على الأزرار المهمة
document.addEventListener('DOMContentLoaded', function() {
    const ctaButtons = document.querySelectorAll('a[href="request.html"], a[href="tel:+966574467922"]');
    ctaButtons.forEach(button => {
        button.addEventListener('click', function() {
            trackEvent('cta_click', {
                type: this.href.includes('tel:') ? 'phone' : 'request_service',
                page: window.location.pathname
            });
        });
    });
});

// وظيفة للتحقق من الاتصال بالإنترنت
function checkConnection() {
    return navigator.onLine;
}

// معالجة حالات عدم الاتصال
window.addEventListener('offline', function() {
    const offlineMessage = document.createElement('div');
    offlineMessage.className = 'alert alert-warning position-fixed top-0 start-50 translate-middle-x';
    offlineMessage.style.zIndex = '9999';
    offlineMessage.innerHTML = '<i class="fas fa-wifi me-2"></i>لا يوجد اتصال بالإنترنت';
    document.body.appendChild(offlineMessage);
});

window.addEventListener('online', function() {
    const offlineMessages = document.querySelectorAll('.alert-warning');
    offlineMessages.forEach(msg => msg.remove());
});

// وظائف مساعدة للنماذج
const FormHelpers = {
    // تنظيف النموذج
    clearForm: function(formId) {
        const form = document.getElementById(formId);
        if (form) {
            form.reset();
            form.classList.remove('was-validated');
            // إزالة كلاسات التحقق
            form.querySelectorAll('.is-valid, .is-invalid').forEach(el => {
                el.classList.remove('is-valid', 'is-invalid');
            });
        }
    },

    // التحقق من جميع الحقول المطلوبة
    validateRequired: function(form) {
        const requiredFields = form.querySelectorAll('[required]');
        let isValid = true;
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                field.classList.add('is-invalid');
                isValid = false;
            } else {
                field.classList.remove('is-invalid');
                field.classList.add('is-valid');
            }
        });
        
        return isValid;
    },

    // إنشاء رسالة واتساب من بيانات النموذج
    createWhatsAppMessage: function(formData) {
        let message = `طلب خدمة جديد من موقع الروّاد للصيانة:\n\n`;
        message += `الاسم: ${formData.get('fullName')}\n`;
        message += `الهاتف: ${formData.get('phoneNumber')}\n`;
        message += `العنوان: ${formData.get('address')}\n`;
        message += `نوع الجهاز: ${formData.get('deviceType')}\n`;
        message += `وصف المشكلة: ${formData.get('problemDescription')}\n`;
        message += `فحص مبدئي: ${formData.get('initialInspection')}\n`;
        message += `\nتاريخ الطلب: ${new Date().toLocaleString('ar-SA')}`;
        
        return message;
    }
};

// تصدير الوظائف للاستخدام العام
window.RwadCool = {
    validateSaudiPhone,
    validateEmail,
    showSuccessMessage,
    saveToLocalStorage,
    getFromLocalStorage,
    sendWhatsAppMessage,
    FormHelpers,
    trackEvent
};