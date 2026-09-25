<?php

namespace App\Console\Commands;

use App\Services\Customer\CustomerReminderService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class SendCustomerReminders extends Command
{
    protected $signature = 'customer-reminders:send {--dry-run : Count eligible reminders without sending or saving changes}';

    protected $description = 'Send three-day email verification and abandoned cart reminders';

    public function handle(CustomerReminderService $reminders): int
    {
        $requiredColumns = [
            'verification_reminder_sent_at',
            'cart_activity_at',
            'abandoned_cart_reminder_activity_at',
        ];

        foreach ($requiredColumns as $column) {
            if (!Schema::hasColumn('users', $column)) {
                $this->error('Customer reminder tracking is not installed. Run the pending reminder migration before this command.');

                return self::FAILURE;
            }
        }

        $dryRun = (bool) $this->option('dry-run');
        $stats = $reminders->send($dryRun);

        $this->info('Verification reminders eligible: '.$stats['verification_eligible']);
        $this->info('Abandoned carts eligible: '.$stats['cart_eligible']);

        if (!$dryRun) {
            $this->info('Verification reminders sent: '.$stats['verification_sent']);
            $this->info('Abandoned cart reminders sent: '.$stats['cart_sent']);

            if ($stats['verification_failed'] + $stats['cart_failed'] > 0) {
                $this->warn('Some reminders failed. Check the application log; they remain eligible for retry.');
            }
        }

        return self::SUCCESS;
    }
}
