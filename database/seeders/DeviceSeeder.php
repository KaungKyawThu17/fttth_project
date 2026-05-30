<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Device;
use Illuminate\Database\Seeder;

class DeviceSeeder extends Seeder
{
    public function run(): void
    {
        $phoneNumbers = collect([
            '0911111101',
            '0911111102',
            '0911111103',
            '0911111104',
            '0911111105',
            '0911111106',
        ]);

        $customers = Customer::query()->whereIn('phone', $phoneNumbers)->get();

        if ($customers->isEmpty()) {
            $this->command?->warn('No customers found with phones 0911111101–0911111106. Skipping DeviceSeeder.');

            return;
        }

        $deviceData = [
            '0911111101' => [
                ['onu_serial_number' => 'ONU-HW-0001A', 'onu_model' => 'HG8245Q', 'mac_address' => 'AA:BB:CC:01:01:01', 'router_model' => 'TP-Link AX1500', 'installation_date' => '2026-01-15'],
                ['onu_serial_number' => 'ONU-HW-0001B', 'onu_model' => 'HG8245Q', 'mac_address' => 'AA:BB:CC:01:01:02', 'router_model' => 'MikroTik hAP ac2', 'installation_date' => '2026-03-20'],
            ],
            '0911111102' => [
                ['onu_serial_number' => 'ONU-ZTE-0002A', 'onu_model' => 'F660', 'mac_address' => 'AA:BB:CC:02:02:01', 'router_model' => 'Asus RT-AX58U', 'installation_date' => '2026-02-10'],
            ],
            '0911111103' => [
                ['onu_serial_number' => 'ONU-FH-0003A', 'onu_model' => 'AN5506-01', 'mac_address' => 'AA:BB:CC:03:03:01', 'router_model' => 'TP-Link Archer C6', 'installation_date' => '2025-12-01'],
                ['onu_serial_number' => 'ONU-FH-0003B', 'onu_model' => 'AN5506-02', 'mac_address' => 'AA:BB:CC:03:03:02', 'router_model' => 'Xiaomi AX3000T', 'installation_date' => '2026-04-05'],
            ],
            '0911111104' => [
                ['onu_serial_number' => 'ONU-NOK-0004A', 'onu_model' => 'G-240W-A', 'mac_address' => 'AA:BB:CC:04:04:01', 'router_model' => 'Netgear R7000', 'installation_date' => '2026-01-20'],
            ],
            '0911111105' => [
                ['onu_serial_number' => 'ONU-HW-0005A', 'onu_model' => 'HG8245W5', 'mac_address' => 'AA:BB:CC:05:05:01', 'router_model' => 'TP-Link Deco X50', 'installation_date' => '2026-02-28'],
                ['onu_serial_number' => 'ONU-HW-0005B', 'onu_model' => 'HG8245W5', 'mac_address' => 'AA:BB:CC:05:05:02', 'router_model' => 'MikroTik RB750Gr3', 'installation_date' => '2026-05-01'],
            ],
            '0911111106' => [
                ['onu_serial_number' => 'ONU-ZTE-0006A', 'onu_model' => 'F680', 'mac_address' => 'AA:BB:CC:06:06:01', 'router_model' => 'Asus RT-AX86U', 'installation_date' => '2025-11-15'],
            ],
        ];

        foreach ($customers as $customer) {
            $devices = $deviceData[$customer->phone] ?? [];

            foreach ($devices as $device) {
                Device::query()->firstOrCreate(
                    ['onu_serial_number' => $device['onu_serial_number']],
                    [
                        'customer_id' => $customer->getKey(),
                        'onu_model' => $device['onu_model'],
                        'mac_address' => $device['mac_address'],
                        'router_model' => $device['router_model'],
                        'installation_date' => $device['installation_date'],
                        'status' => Device::STATUS_ACTIVE,
                    ],
                );
            }

            $count = count($devices);
            $this->command?->info("Created {$count} device(s) for customer {$customer->phone} ({$customer->name}).");
        }
    }
}
