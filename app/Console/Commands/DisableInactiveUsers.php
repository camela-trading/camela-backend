<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class DisableInactiveUsers extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'users:disable-inactive';

    /**
     * The console command description.
     */
    protected $description = 'Disable users who have not logged in for 15 days';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $count = User::where('is_active', true)
            ->whereNotNull('last_login_at')
            ->where('last_login_at', '<', now()->subDays(15))
            ->update([
                'is_active' => false,
            ]);

        $this->info("{$count} inactive user(s) disabled.");

        return self::SUCCESS;
    }
}