<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

final class GrantAdmin extends Command
{
    protected $signature = 'ukapi:grant-admin {email : Email address of the existing user to promote}';

    protected $description = 'Grant the UKAPI.io administrator role to an existing user.';

    public function handle(): int
    {
        $email = Str::lower(trim((string) $this->argument('email')));
        $user = User::query()->where('email', $email)->first();

        if (! $user instanceof User) {
            $this->error('No user was found for the supplied email address.');

            return self::FAILURE;
        }

        if ($user->is_admin) {
            $this->warn('That user is already an administrator.');

            return self::SUCCESS;
        }

        $user->forceFill(['is_admin' => true])->save();
        $this->info('Administrator role granted.');

        return self::SUCCESS;
    }
}
