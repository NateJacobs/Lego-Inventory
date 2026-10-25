<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CreateVaultToken extends Command
{
    protected $signature = 'vault:token {--user= : User ID that should own the token}';

    protected $description = 'Create a token for vault.ngtj.com that can only read the collection value (replaces the previous one)';

    public function handle(): int
    {
        $user = $this->option('user')
            ? User::find($this->option('user'))
            : User::query()->first();

        if (! $user) {
            $this->error('No user found to own the token. Pass --user=ID or create a user first.');

            return self::FAILURE;
        }

        $replaced = $user->tokens()->where('name', 'vault.ngtj.com')->delete();

        $token = $user->createToken('vault.ngtj.com', ['vault:read'], now()->addYear())->plainTextToken;

        if ($replaced > 0) {
            $this->line('Revoked the previous vault token.');
        }

        $this->info('Token created (expires in a year). Paste it into vault under Settings → Integrations → Mursten (LEGO):');
        $this->line($token);

        return self::SUCCESS;
    }
}
