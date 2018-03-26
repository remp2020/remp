<?php

namespace App\Console\Commands;

use App\User;
use Illuminate\Console\Command;
use Redis;

class ReconstructLoggedOutCache extends Command
{
    protected $signature = 'reconstruct:logged_out_cache';

    protected $description = 'Reconstructs last logout date cache stored in redis';

    public function handle()
    {
        /** @var User[] $users */
        $users = User::all();
        $bar = $this->output->createProgressBar(count($users));

        Redis::del(User::USER_LAST_LOGOUT_KEY);

        foreach ($users as $user) {
            if (!$user->last_logout_at) {
                $bar->advance();
                continue;
            }
            Redis::hset(User::USER_LAST_LOGOUT_KEY, $user->id, $user->last_logout_at->timestamp);
            $bar->advance();
        }

        $bar->finish();
        $this->line('');
    }
}
