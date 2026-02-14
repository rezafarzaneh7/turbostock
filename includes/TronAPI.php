<?php
/**
 * HStore - TRON Blockchain API Integration
 * Handles USDT TRC20 payments via TronGrid API
 */

// Load Composer autoloader for elliptic curve libraries
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

use Elliptic\EC;
use kornrunner\Keccak;

class TronAPI {
    private $apiUrl;
    private $apiKey;
    private $usdtContract;
    private $quickNodeUrl;
    
    public function __construct() {
        $this->apiUrl = TRON_API_URL;
        $this->apiKey = TRON_API_KEY ?: getSetting('tron_api_key', '');
        $this->usdtContract = USDT_CONTRACT_ADDRESS;
        $this->quickNodeUrl = defined('QUICKNODE_TRON_URL') ? QUICKNODE_TRON_URL : null;
    }
    
    /**
     * Make API request to TronGrid
     */
    private function request($endpoint, $method = 'GET', $data = null) {
        $url = $this->apiUrl . $endpoint;
        
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json'
        ];
        
        if ($this->apiKey) {
            $headers[] = 'TRON-PRO-API-KEY: ' . $this->apiKey;
        }
        
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
            throw new Exception("TRON API Error: " . $error);
        }
        
        $decoded = json_decode($response, true);
        
        if ($httpCode >= 400) {
            $errorMsg = $decoded['error'] ?? $decoded['message'] ?? 'Unknown error';
            throw new Exception("TRON API Error ({$httpCode}): " . $errorMsg);
        }
        
        return $decoded;
    }
    
    /**
     * Generate a new TRON wallet address using proper ECDSA secp256k1
     */
    public function generateWallet() {
        // Use Elliptic library for proper secp256k1 key generation
        $ec = new EC('secp256k1');
        
        // Generate key pair
        $keyPair = $ec->genKeyPair();
        
        // Get private key (64 hex chars)
        $privateKey = $keyPair->getPrivate('hex');
        $privateKey = str_pad($privateKey, 64, '0', STR_PAD_LEFT);
        
        // Get public key (uncompressed, 130 hex chars starting with 04)
        $publicKey = $keyPair->getPublic('hex');
        
        // Remove '04' prefix for address generation
        $publicKeyWithoutPrefix = substr($publicKey, 2);
        
        // Keccak256 hash of public key
        $hash = Keccak::hash(hex2bin($publicKeyWithoutPrefix), 256);
        
        // Take last 20 bytes (40 hex chars) and add TRON prefix (41)
        $addressHex = '41' . substr($hash, -40);
        
        // Convert to Base58Check
        $address = $this->hexToBase58Check($addressHex);
        
        return [
            'address' => $address,
            'hex_address' => $addressHex,
            'private_key' => $privateKey
        ];
    }
    
    /**
     * Make JSON-RPC request to QuickNode
     */
    private function quickNodeRequest($method, $params = []) {
        if (!$this->quickNodeUrl) {
            throw new Exception("QuickNode URL not configured");
        }
        
        $payload = [
            'jsonrpc' => '2.0',
            'id' => time(),
            'method' => $method,
            'params' => $params
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->quickNodeUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("QuickNode Error: " . $error);
        }
        
        $decoded = json_decode($response, true);
        
        if (isset($decoded['error'])) {
            throw new Exception("QuickNode Error: " . ($decoded['error']['message'] ?? 'Unknown error'));
        }
        
        return $decoded;
    }
    
    /**
     * Convert hex address to Base58Check (TRON format)
     */
    private function hexToBase58Check($hex) {
        $address = hex2bin($hex);
        $hash0 = hash('sha256', $address, true);
        $hash1 = hash('sha256', $hash0, true);
        $checksum = substr($hash1, 0, 4);
        $address .= $checksum;
        
        return $this->base58Encode($address);
    }
    
    /**
     * Base58 encoding
     */
    private function base58Encode($data) {
        $alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
        $base = strlen($alphabet);
        
        if (is_string($data)) {
            $data = array_values(unpack('C*', $data));
        }
        
        $digits = [0];
        foreach ($data as $byte) {
            $carry = $byte;
            for ($j = 0; $j < count($digits); $j++) {
                $carry += $digits[$j] << 8;
                $digits[$j] = $carry % $base;
                $carry = (int)($carry / $base);
            }
            while ($carry > 0) {
                $digits[] = $carry % $base;
                $carry = (int)($carry / $base);
            }
        }
        
        // Leading zeros
        for ($i = 0; $i < count($data) && $data[$i] === 0; $i++) {
            $digits[] = 0;
        }
        
        $result = '';
        foreach (array_reverse($digits) as $digit) {
            $result .= $alphabet[$digit];
        }
        
        return $result;
    }
    
    /**
     * Get account info
     */
    public function getAccount($address) {
        try {
            $result = $this->request('/v1/accounts/' . $address);
            return $result['data'][0] ?? null;
        } catch (Exception $e) {
            error_log("TronAPI getAccount error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get TRC20 token balance (USDT)
     */
    public function getUSDTBalance($address) {
        try {
            $result = $this->request('/v1/accounts/' . $address);
            
            if (isset($result['data'][0]['trc20'])) {
                foreach ($result['data'][0]['trc20'] as $token) {
                    if (isset($token[$this->usdtContract])) {
                        // USDT has 6 decimals
                        return bcdiv($token[$this->usdtContract], '1000000', 6);
                    }
                }
            }
            
            return '0.000000';
        } catch (Exception $e) {
            error_log("TronAPI getUSDTBalance error: " . $e->getMessage());
            return '0.000000';
        }
    }
    
    /**
     * Get TRC20 transactions for an address
     */
    public function getTRC20Transactions($address, $limit = 20) {
        try {
            $endpoint = '/v1/accounts/' . $address . '/transactions/trc20';
            $endpoint .= '?limit=' . $limit;
            $endpoint .= '&contract_address=' . $this->usdtContract;
            
            $result = $this->request($endpoint);
            return $result['data'] ?? [];
        } catch (Exception $e) {
            error_log("TronAPI getTRC20Transactions error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Check for incoming USDT payment to address
     */
    public function checkPayment($address, $expectedAmount, $sinceTimestamp = null) {
        $transactions = $this->getTRC20Transactions($address);
        
        foreach ($transactions as $tx) {
            // Check if it's an incoming transaction
            if ($tx['to'] !== $address) continue;
            
            // Check timestamp if provided
            if ($sinceTimestamp && $tx['block_timestamp'] < $sinceTimestamp * 1000) continue;
            
            // Get amount (USDT has 6 decimals)
            $amount = bcdiv($tx['value'], '1000000', 6);
            
            // Check if amount matches or exceeds expected
            if (bccomp($amount, $expectedAmount, 6) >= 0) {
                return [
                    'found' => true,
                    'tx_hash' => $tx['transaction_id'],
                    'from' => $tx['from'],
                    'to' => $tx['to'],
                    'amount' => $amount,
                    'timestamp' => $tx['block_timestamp'] / 1000,
                    'confirmed' => true
                ];
            }
        }
        
        return ['found' => false];
    }
    
    /**
     * Get transaction details
     */
    public function getTransaction($txHash) {
        try {
            $result = $this->request('/v1/transactions/' . $txHash);
            return $result['data'][0] ?? null;
        } catch (Exception $e) {
            error_log("TronAPI getTransaction error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get transaction info (includes confirmation status)
     */
    public function getTransactionInfo($txHash) {
        try {
            $result = $this->request('/wallet/gettransactioninfobyid', 'POST', [
                'value' => $txHash
            ]);
            return $result;
        } catch (Exception $e) {
            error_log("TronAPI getTransactionInfo error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Check if transaction is confirmed
     */
    public function isTransactionConfirmed($txHash) {
        $info = $this->getTransactionInfo($txHash);
        
        if ($info && isset($info['blockNumber'])) {
            // Transaction is in a block, considered confirmed
            return true;
        }
        
        return false;
    }
    
    /**
     * Get current block number
     */
    public function getCurrentBlock() {
        try {
            $result = $this->request('/wallet/getnowblock', 'POST');
            return $result['block_header']['raw_data']['number'] ?? 0;
        } catch (Exception $e) {
            error_log("TronAPI getCurrentBlock error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Validate TRON address
     */
    public function validateAddress($address) {
        try {
            $result = $this->request('/wallet/validateaddress', 'POST', [
                'address' => $address
            ]);
            return $result['result'] ?? false;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Generate QR code data URL for payment
     */
    public function generatePaymentQR($address, $amount = null) {
        // Create TRON payment URI
        $uri = 'tron:' . $address;
        if ($amount) {
            $uri .= '?amount=' . $amount . '&token=' . $this->usdtContract;
        }
        
        // Generate QR code using Google Charts API (simple solution)
        // In production, use a proper QR library
        $qrUrl = 'https://chart.googleapis.com/chart?chs=250x250&cht=qr&chl=' . urlencode($uri);
        
        return $qrUrl;
    }
    
    /**
     * Get TronScan URL for address
     */
    public function getAddressUrl($address) {
        return TRON_SCAN_URL . '/#/address/' . $address;
    }
    
    /**
     * Get TronScan URL for transaction
     */
    public function getTransactionUrl($txHash) {
        return TRON_SCAN_URL . '/#/transaction/' . $txHash;
    }
}

// Helper function to get TronAPI instance
function tronApi() {
    static $instance = null;
    if ($instance === null) {
        $instance = new TronAPI();
    }
    return $instance;
}
