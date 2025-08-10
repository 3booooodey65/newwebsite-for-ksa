<?php
/**
 * WhatsApp API Helper للروّاد للصيانة
 * يدعم عدة طرق لإرسال رسائل WhatsApp
 */

require_once 'config.php';

class WhatsAppAPI {
    
    private $companyPhone;
    private $apiUrl;
    private $apiToken;
    
    public function __construct() {
        $this->companyPhone = COMPANY_WHATSAPP;
        $this->apiUrl = getConfig('WHATSAPP_API_URL', '');
        $this->apiToken = getConfig('WHATSAPP_API_TOKEN', '');
    }
    
    /**
     * إرسال رسالة واتساب عبر URL (الطريقة الأساسية)
     */
    public function sendViaURL($message) {
        try {
            $encodedMessage = urlencode($message);
            $whatsappUrl = "https://wa.me/{$this->companyPhone}?text={$encodedMessage}";
            
            // حفظ الرابط في ملف لوج
            $this->logWhatsAppURL($whatsappUrl, 'URL Method');
            
            return [
                'success' => true,
                'method' => 'url',
                'url' => $whatsappUrl,
                'message' => 'تم إنشاء رابط واتساب بنجاح'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * إرسال رسالة عبر WhatsApp Business API (للاستخدام المتقدم)
     */
    public function sendViaAPI($message, $recipientPhone = null) {
        if (empty($this->apiUrl) || empty($this->apiToken)) {
            return $this->sendViaURL($message);
        }
        
        try {
            $phone = $recipientPhone ?: $this->companyPhone;
            
            $data = [
                'phone' => $phone,
                'message' => $message,
                'timestamp' => time()
            ];
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $this->apiUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $this->apiToken
                ],
                CURLOPT_TIMEOUT => 30
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                $this->logWhatsAppAPI($data, $response, 'API Success');
                return [
                    'success' => true,
                    'method' => 'api',
                    'response' => json_decode($response, true),
                    'message' => 'تم إرسال الرسالة عبر API'
                ];
            } else {
                throw new Exception("API Error: HTTP {$httpCode}");
            }
            
        } catch (Exception $e) {
            // العودة للطريقة الأساسية في حالة فشل API
            return $this->sendViaURL($message);
        }
    }
    
    /**
     * إنشاء رسالة واتساب لطلب خدمة
     */
    public function createServiceRequestMessage($data) {
        $message = "🔧 طلب خدمة جديد من موقع الروّاد للصيانة\n\n";
        $message .= "👤 الاسم: " . $data['fullName'] . "\n";
        $message .= "📱 الهاتف: " . $data['phoneNumber'] . "\n";
        $message .= "📍 العنوان: " . $data['address'] . "\n";
        $message .= "🔌 نوع الجهاز: " . $data['deviceType'] . "\n";
        $message .= "❗ المشكلة: " . $data['problemDescription'] . "\n";
        $message .= "🔍 فحص مبدئي: " . $data['initialInspection'] . "\n";
        
        if (!empty($data['deviceImage'])) {
            $message .= "📷 تم رفع صورة للجهاز\n";
        }
        
        $message .= "📅 التاريخ: " . date('Y-m-d H:i:s') . "\n";
        $message .= "\n✅ يرجى التواصل مع العميل في أقرب وقت";
        
        return $message;
    }
    
    /**
     * إنشاء رسالة واتساب لرسالة تواصل
     */
    public function createContactMessage($data) {
        $message = "💬 رسالة جديدة من موقع الروّاد للصيانة\n\n";
        $message .= "👤 الاسم: " . $data['contactName'] . "\n";
        $message .= "📧 البريد: " . $data['email'] . "\n";
        $message .= "📱 الهاتف: " . $data['contactPhone'] . "\n";
        $message .= "💭 الرسالة: " . $data['message'] . "\n";
        $message .= "📅 التاريخ: " . date('Y-m-d H:i:s') . "\n";
        
        return $message;
    }
    
    /**
     * حفظ رابط واتساب في ملف لوج
     */
    private function logWhatsAppURL($url, $method = 'Unknown') {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'method' => $method,
            'url' => $url,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
        ];
        
        $logLine = json_encode($logEntry, JSON_UNESCAPED_UNICODE) . "\n";
        file_put_contents('whatsapp_urls.log', $logLine, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * حفظ استدعاء API في ملف لوج
     */
    private function logWhatsAppAPI($request, $response, $status) {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'status' => $status,
            'request' => $request,
            'response' => $response,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
        ];
        
        $logLine = json_encode($logEntry, JSON_UNESCAPED_UNICODE) . "\n";
        file_put_contents('whatsapp_api.log', $logLine, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * إرسال رسالة (الطريقة الرئيسية)
     */
    public function sendMessage($message, $method = 'auto') {
        switch ($method) {
            case 'api':
                return $this->sendViaAPI($message);
            case 'url':
                return $this->sendViaURL($message);
            case 'auto':
            default:
                // جرب API أولاً، ثم URL
                if (!empty($this->apiUrl) && !empty($this->apiToken)) {
                    return $this->sendViaAPI($message);
                } else {
                    return $this->sendViaURL($message);
                }
        }
    }
    
    /**
     * الحصول على إحصائيات الرسائل
     */
    public function getStats() {
        $stats = [
            'total_urls' => 0,
            'total_api_calls' => 0,
            'last_message' => null
        ];
        
        // إحصائيات URL
        if (file_exists('whatsapp_urls.log')) {
            $urlLines = file('whatsapp_urls.log', FILE_IGNORE_NEW_LINES);
            $stats['total_urls'] = count($urlLines);
            if (!empty($urlLines)) {
                $lastUrl = json_decode(end($urlLines), true);
                $stats['last_url'] = $lastUrl['timestamp'] ?? null;
            }
        }
        
        // إحصائيات API
        if (file_exists('whatsapp_api.log')) {
            $apiLines = file('whatsapp_api.log', FILE_IGNORE_NEW_LINES);
            $stats['total_api_calls'] = count($apiLines);
            if (!empty($apiLines)) {
                $lastApi = json_decode(end($apiLines), true);
                $stats['last_api'] = $lastApi['timestamp'] ?? null;
            }
        }
        
        return $stats;
    }
}

// إنشاء مثيل عام للاستخدام
$whatsappAPI = new WhatsAppAPI();

// وظائف مساعدة للاستخدام السريع
function sendServiceRequestWhatsApp($data) {
    global $whatsappAPI;
    $message = $whatsappAPI->createServiceRequestMessage($data);
    return $whatsappAPI->sendMessage($message);
}

function sendContactWhatsApp($data) {
    global $whatsappAPI;
    $message = $whatsappAPI->createContactMessage($data);
    return $whatsappAPI->sendMessage($message);
}

function getWhatsAppStats() {
    global $whatsappAPI;
    return $whatsappAPI->getStats();
}
?>