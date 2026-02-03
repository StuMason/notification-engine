<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DemoSetupCommand extends Command
{
    protected $signature = 'demo:setup';

    protected $description = 'Run migrations, seed demo data, and display login credentials';

    public function handle(): int
    {
        $this->info('Setting up Notification Engine demo...');
        $this->newLine();

        $this->components->task('Running migrations', function () {
            $this->callSilently('migrate:fresh', ['--force' => true]);
        });

        $this->components->task('Seeding demo data', function () {
            $this->callSilently('db:seed', ['--class' => 'Database\\Seeders\\DemoSeeder', '--force' => true]);
        });

        $this->newLine();
        $this->info('Demo setup complete!');
        $this->newLine();

        $this->components->twoColumnDetail('<fg=green;options=bold>Hotel</>', '<fg=green;options=bold>Login Credentials</>');
        $this->line('');

        $this->components->twoColumnDetail('<fg=yellow>The Grand Hotel</>', '');
        $this->components->twoColumnDetail('  Admin', 'alice@grandhotel.com');
        $this->components->twoColumnDetail('  Manager', 'bob@grandhotel.com');
        $this->components->twoColumnDetail('  Staff', 'carol@grandhotel.com');
        $this->components->twoColumnDetail('  Staff', 'dan@grandhotel.com');
        $this->components->twoColumnDetail('  Staff', 'eve@grandhotel.com');
        $this->line('');

        $this->components->twoColumnDetail('<fg=yellow>Seaside Resort</>', '');
        $this->components->twoColumnDetail('  Admin', 'frank@seasideresort.com');
        $this->components->twoColumnDetail('  Manager', 'grace@seasideresort.com');
        $this->components->twoColumnDetail('  Staff', 'henry@seasideresort.com');
        $this->components->twoColumnDetail('  Staff', 'ivy@seasideresort.com');
        $this->components->twoColumnDetail('  Staff', 'jack@seasideresort.com');

        $this->newLine();
        $this->components->twoColumnDetail('Password (all users)', '<fg=cyan>password</>');
        $this->newLine();

        return self::SUCCESS;
    }
}
