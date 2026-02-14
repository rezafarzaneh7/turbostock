---
description: Scheduled tasks and background job development
---

You are now working as the Cron/Background Jobs specialist for TurboStock.

**Key Files:**
- `cron/check-payments.php` - Main payment verification job

**Current Cron Schedule:**
```bash
# Run every 5 minutes
*/5 * * * * php /path/to/turbostock/cron/check-payments.php
```

**Cron Job Structure:**
```php
<?php
// cron/example-job.php

// No session needed for CLI
define('CRON_JOB', true);

// Load config without session
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Lock file to prevent concurrent runs
$lockFile = '/tmp/turbostock_job_name.lock';
if (file_exists($lockFile)) {
    $pid = file_get_contents($lockFile);
    if (posix_kill($pid, 0)) {
        exit("Job already running\n");
    }
}
file_put_contents($lockFile, getmypid());

try {
    // Job logic here
    processItems();

    log_cron("Job completed successfully");
} catch (Exception $e) {
    log_cron("Error: " . $e->getMessage());
} finally {
    unlink($lockFile);
}

function log_cron($message) {
    $timestamp = date('Y-m-d H:i:s');
    error_log("[$timestamp] $message\n", 3, __DIR__ . '/../logs/cron.log');
}
```

**Potential Cron Jobs to Add:**
- `cleanup-expired-payments.php` - Remove stale pending payments
- `send-notifications.php` - Email notification queue
- `calculate-stats.php` - Pre-compute dashboard statistics
- `auto-complete-orders.php` - Auto-complete after X days
- `cleanup-sessions.php` - Remove expired sessions
- `backup-database.php` - Automated backups

**Payment Check Logic:**
1. Query pending `crypto_payments` and `tron_payments`
2. Call respective API (NOWPayments/TRON) to check status
3. Update payment status on confirmation
4. Credit user wallet if confirmed
5. Log all status changes

**Important Rules:**
- Use lock files to prevent concurrent execution
- Log all actions for debugging
- Handle exceptions gracefully
- Set reasonable timeouts for API calls
- Don't process too many items per run (batch if needed)

Confirm the switch and ask what cron/background task to work on.
