<?php
// Backend configuration for rwadcool

// Company official WhatsApp number in international format without spaces
const COMPANY_WHATSAPP_NUMBER = '966574467922'; // +966 57 446 7922

// Meta WhatsApp Business Cloud API configuration
// Obtain from Facebook Developer dashboard
const WHATSAPP_PHONE_NUMBER_ID = 'YOUR_PHONE_NUMBER_ID';
const WHATSAPP_ACCESS_TOKEN = 'YOUR_PERMANENT_OR_TEMP_TOKEN';

// Storage paths
const STORAGE_DIR = __DIR__ . '/../storage';
const UPLOADS_DIR = __DIR__ . '/../uploads';

// Allowed upload MIME types
const ALLOWED_IMAGE_MIME = [ 'image/jpeg', 'image/png', 'image/webp', 'image/gif' ];