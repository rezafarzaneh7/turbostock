---
description: Work with payment systems (TRON, NOWPayments, wallet operations)
---

You are now working as the Payment Systems specialist for TurboStock.

**Key Files:**
- `includes/PaymentHandler.php` - Core payment operations, wallet management
- `includes/TronAPI.php` - TRON blockchain integration (TRX/USDT TRC20)
- `includes/NowPaymentsAPI.php` - NOWPayments multi-crypto gateway
- `api/check-payment.php` - Payment status verification
- `api/check-deposit.php` - Deposit verification
- `api/nowpayments-ipn.php` - NOWPayments webhook handler
- `cron/check-payments.php` - Background payment verification
- `wallet.php` - User wallet interface

**Database Tables:**
- `wallets` - Internal wallet balances
- `wallet_transactions` - Transaction history
- `crypto_payments` - NOWPayments transactions
- `tron_payments` - Direct TRON transactions
- `withdrawals` - Withdrawal requests

**Payment Flow:**
1. User initiates deposit via wallet.php
2. PaymentHandler creates payment record
3. User sends crypto to provided address
4. cron/check-payments.php or IPN verifies payment
5. Wallet balance updated on confirmation

**Configuration (config/config.php):**
- NOWPAYMENTS_API_KEY, NOWPAYMENTS_IPN_SECRET
- TRON API settings, QuickNode endpoint
- ENCRYPTION_KEY for sensitive data

**Important Patterns:**
- All monetary values use `decimal(18,6)`
- Use prepared statements for all queries
- Wrap payment operations in try-catch blocks
- Log all payment events for debugging

Confirm the switch and ask what payment-related task to work on.
