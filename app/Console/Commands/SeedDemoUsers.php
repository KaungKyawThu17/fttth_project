<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('demo:seed-users')]
#[Description('Create 5 customer accounts and 5 technician accounts with password "password"')]
class SeedDemoUsers extends Command
{
    public function handle(): int
    {
        $password = bcrypt('password');

        for ($i = 1; $i <= 5; $i++) {
            $user = User::query()->updateOrCreate(
                ['email' => "customer{$i}@example.com"],
                [
                    'name' => "Customer Account {$i}",
                    'password' => $password,
                    'role' => User::ROLE_CUSTOMER,
                    'email_verified_at' => now(),
                ],
            );

            Customer::query()->updateOrCreate(
                ['customer_code' => "ACCT-CUST-{$i}"],
                [
                    'user_id' => $user->getKey(),
                    'name' => "Customer Account {$i}",
                    'phone' => '09111111'.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                    'address' => 'Yangon',
                    'township' => 'Hlaing',
                    'city' => 'Yangon',
                    'status' => Customer::STATUS_ACTIVE,
                ],
            );

            $this->line("Created customer account: customer{$i}@example.com / password");
        }

        for ($i = 1; $i <= 5; $i++) {
            $user = User::query()->updateOrCreate(
                ['email' => "technician{$i}@example.com"],
                [
                    'name' => "Technician Account {$i}",
                    'password' => $password,
                    'role' => User::ROLE_TECHNICIAN,
                    'email_verified_at' => now(),
                ],
            );

            Technician::query()->updateOrCreate(
                ['email' => "technician{$i}@example.com"],
                [
                    'user_id' => $user->getKey(),
                    'name' => "Technician Account {$i}",
                    'phone' => '09222222'.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                    'address' => 'Yangon field office',
                    'status' => Technician::STATUS_ACTIVE,
                ],
            );

            $this->line("Created technician account: technician{$i}@example.com / password");
        }

        $this->info('Created 5 customer accounts and 5 technician accounts.');

        return self::SUCCESS;
    }
}
