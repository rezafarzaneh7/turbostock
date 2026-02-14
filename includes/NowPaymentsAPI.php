<?php
/**
 * Demostore - NOWPayments API Integration
 * Multi-crypto payment processing via NOWPayments.io
 */

class NowPaymentsAPI {
    private $apiKey;
    private $ipnSecret;
    private $apiUrl = 'https://api.nowpayments.io/v1';
    
    public function __construct() {
        $this->apiKey = defined('NOWPAYMENTS_API_KEY') ? NOWPAYMENTS_API_KEY : getSetting('nowpayments_api_key', '');
        $this->ipnSecret = defined('NOWPAYMENTS_IPN_SECRET') ? NOWPAYMENTS_IPN_SECRET : getSetting('nowpayments_ipn_secret', '');
    }
    
    /**
     * Make API request to NOWPayments
     */
    private function request($endpoint, $method = 'GET', $data = null) {
        $url = $this->apiUrl . $endpoint;
        
        $headers = [
            'x-api-key: ' . $this->apiKey,
            'Content-Type: application/json',
            'Accept: application/json'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("NOWPayments API Error: " . $error);
        }
        
        $decoded = json_decode($response, true);
        
        if ($httpCode >= 400) {
            $errorMsg = $decoded['message'] ?? $decoded['error'] ?? 'Unknown error';
            throw new Exception("NOWPayments API Error ({$httpCode}): " . $errorMsg);
        }
        
        return $decoded;
    }
    
    /**
     * Check API status
     */
    public function getStatus() {
        try {
            $result = $this->request('/status');
            return $result['message'] === 'OK';
        } catch (Exception $e) {
            error_log("NOWPayments getStatus error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get available currencies
     */
    public function getCurrencies() {
        try {
            $result = $this->request('/currencies');
            return $result['currencies'] ?? [];
        } catch (Exception $e) {
            error_log("NOWPayments getCurrencies error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get minimum payment amount for currency pair
     */
    public function getMinimumAmount($currencyFrom, $currencyTo = 'usdttrc20') {
        try {
            $result = $this->request('/min-amount?currency_from=' . $currencyFrom . '&currency_to=' . $currencyTo);
            return $result['min_amount'] ?? 0;
        } catch (Exception $e) {
            error_log("NOWPayments getMinimumAmount error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get estimated price
     */
    public function getEstimate($amount, $currencyFrom = 'usd', $currencyTo = 'btc') {
        try {
            $result = $this->request('/estimate?amount=' . $amount . '&currency_from=' . $currencyFrom . '&currency_to=' . $currencyTo);
            return $result;
        } catch (Exception $e) {
            error_log("NOWPayments getEstimate error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Create a payment
     */
    public function createPayment($priceAmount, $priceCurrency, $payCurrency, $orderId = null, $orderDescription = null, $ipnCallbackUrl = null) {
        $data = [
            'price_amount' => $priceAmount,
            'price_currency' => $priceCurrency,
            'pay_currency' => $payCurrency,
            'is_fixed_rate' => true
        ];
        
        if ($orderId) {
            $data['order_id'] = $orderId;
        }
        
        if ($orderDescription) {
            $data['order_description'] = $orderDescription;
        }
        
        // IPN callback URL - optional but recommended for auto balance update
        if ($ipnCallbackUrl) {
            $data['ipn_callback_url'] = $ipnCallbackUrl;
        } elseif (defined('NOWPAYMENTS_IPN_URL') && NOWPAYMENTS_IPN_URL) {
            $data['ipn_callback_url'] = NOWPAYMENTS_IPN_URL;
        } else {
            // Default: use production domain
            $data['ipn_callback_url'] = 'https://hstore.site/api/nowpayments-ipn.php';
        }
        
        return $this->request('/payment', 'POST', $data);
    }
    
    /**
     * Create an invoice (redirects user to NOWPayments page)
     */
    public function createInvoice($priceAmount, $priceCurrency = 'usd', $orderId = null, $orderDescription = null, $successUrl = null, $cancelUrl = null) {
        $data = [
            'price_amount' => $priceAmount,
            'price_currency' => $priceCurrency
        ];
        
        if ($orderId) {
            $data['order_id'] = $orderId;
        }
        
        if ($orderDescription) {
            $data['order_description'] = $orderDescription;
        }
        
        if ($successUrl) {
            $data['success_url'] = $successUrl;
        }
        
        if ($cancelUrl) {
            $data['cancel_url'] = $cancelUrl;
        }
        
        $data['ipn_callback_url'] = BASE_URL . '/api/nowpayments-ipn.php';
        
        return $this->request('/invoice', 'POST', $data);
    }
    
    /**
     * Get payment status
     */
    public function getPaymentStatus($paymentId) {
        try {
            return $this->request('/payment/' . $paymentId);
        } catch (Exception $e) {
            error_log("NOWPayments getPaymentStatus error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Verify IPN callback signature
     */
    public function verifyIPN($requestBody, $signature) {
        if (empty($this->ipnSecret)) {
            error_log("NOWPayments IPN Secret not configured");
            return false;
        }
        
        // Sort the array by keys recursively
        $sorted = $this->sortArrayByKeys($requestBody);
        
        // Convert to JSON
        $jsonString = json_encode($sorted, JSON_UNESCAPED_SLASHES);
        
        // Create HMAC signature
        $calculatedSignature = hash_hmac('sha512', $jsonString, trim($this->ipnSecret));
        
        return hash_equals($calculatedSignature, $signature);
    }
    
    /**
     * Sort array by keys recursively
     */
    private function sortArrayByKeys($array) {
        ksort($array);
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = $this->sortArrayByKeys($value);
            }
        }
        return $array;
    }
    
    /**
     * Get popular crypto currencies for display
     */
    public function getPopularCurrencies() {
        return [
            'btc' => ['name' => 'Bitcoin', 'icon' => 'fab fa-bitcoin', 'color' => '#f7931a'],
            'eth' => ['name' => 'Ethereum', 'icon' => 'fab fa-ethereum', 'color' => '#627eea'],
            'ltc' => ['name' => 'Litecoin', 'icon' => 'fas fa-coins', 'color' => '#bfbbbb'],
            'usdttrc20' => ['name' => 'USDT (TRC20)', 'icon' => 'fas fa-dollar-sign', 'color' => '#26a17b'],
            'usdterc20' => ['name' => 'USDT (ERC20)', 'icon' => 'fas fa-dollar-sign', 'color' => '#26a17b'],
            'trx' => ['name' => 'TRON', 'icon' => 'fas fa-bolt', 'color' => '#ff0013'],
            'bnb' => ['name' => 'BNB', 'icon' => 'fas fa-coins', 'color' => '#f3ba2f'],
            'doge' => ['name' => 'Dogecoin', 'icon' => 'fas fa-dog', 'color' => '#c2a633'],
            'xrp' => ['name' => 'Ripple', 'icon' => 'fas fa-coins', 'color' => '#23292f'],
            'sol' => ['name' => 'Solana', 'icon' => 'fas fa-sun', 'color' => '#9945ff'],
            'matic' => ['name' => 'Polygon', 'icon' => 'fas fa-hexagon', 'color' => '#8247e5'],
            'ada' => ['name' => 'Cardano', 'icon' => 'fas fa-coins', 'color' => '#0033ad']
        ];
    }
}

// Helper function to get NowPaymentsAPI instance
function nowPayments() {
    static $instance = null;
    if ($instance === null) {
        $instance = new NowPaymentsAPI();
    }
    return $instance;
}
