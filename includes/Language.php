<?php
/**
 * Language Handler Class
 */
class Language {
    private static $instance = null;
    private $lang = 'en';
    private $translations = [];
    private $availableLanguages = [
        'en' => ['name' => 'English', 'native' => 'English', 'flag' => '🇺🇸'],
        'ru' => ['name' => 'Russian', 'native' => 'Русский', 'flag' => '🇷🇺'],
        'cn' => ['name' => 'Chinese', 'native' => '中文', 'flag' => '🇨🇳']
    ];
    
    private function __construct() {
        $this->detectLanguage();
        $this->loadTranslations();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function detectLanguage() {
        // Check URL parameter first
        if (isset($_GET['lang']) && $this->isValidLanguage($_GET['lang'])) {
            $this->lang = $_GET['lang'];
            $this->setLanguageCookie($this->lang);
            return;
        }
        
        // Check cookie
        if (isset($_COOKIE['site_lang']) && $this->isValidLanguage($_COOKIE['site_lang'])) {
            $this->lang = $_COOKIE['site_lang'];
            return;
        }
        
        // Check session
        if (isset($_SESSION['site_lang']) && $this->isValidLanguage($_SESSION['site_lang'])) {
            $this->lang = $_SESSION['site_lang'];
            return;
        }
        
        // Default to English
        $this->lang = 'en';
    }
    
    private function isValidLanguage($lang) {
        return array_key_exists($lang, $this->availableLanguages);
    }
    
    private function setLanguageCookie($lang) {
        setcookie('site_lang', $lang, time() + (365 * 24 * 60 * 60), '/');
        $_SESSION['site_lang'] = $lang;
    }
    
    private function loadTranslations() {
        $langFile = BASE_PATH . '/lang/' . $this->lang . '.php';
        if (file_exists($langFile)) {
            $this->translations = require $langFile;
        } else {
            // Fallback to English
            $this->translations = require BASE_PATH . '/lang/en.php';
        }
    }
    
    public function get($key, $default = null) {
        return $this->translations[$key] ?? $default ?? $key;
    }
    
    public function getCurrentLanguage() {
        return $this->lang;
    }
    
    public function getAvailableLanguages() {
        return $this->availableLanguages;
    }
    
    public function getLanguageInfo($lang = null) {
        $lang = $lang ?? $this->lang;
        return $this->availableLanguages[$lang] ?? null;
    }
    
    public function setLanguage($lang) {
        if ($this->isValidLanguage($lang)) {
            $this->lang = $lang;
            $this->setLanguageCookie($lang);
            $this->loadTranslations();
            return true;
        }
        return false;
    }
}

// Helper function for easy access
function __($key, $default = null) {
    return Language::getInstance()->get($key, $default);
}

function lang() {
    return Language::getInstance();
}
