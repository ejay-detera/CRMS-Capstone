<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ContractLookupSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Service Agreement',
            'Partnership Agreement',
            'Supply Contract',
            'Equipment Lease',
            'Equipment Maintenance',
        ];

        foreach ($categories as $name) {
            DB::table('contract_categories')->updateOrInsert(
                ['category_name' => $name],
                ['category_name' => $name]
            );
        }

        $statuses = [
            ['status_name' => 'Notarized PDF', 'color_code' => '#6B7280'],
            ['status_name' => 'Client Review',  'color_code' => '#10B981'],
            ['status_name' => 'SBSI Review',    'color_code' => '#EF4444'],
        ];

        foreach ($statuses as $status) {
            DB::table('contract_statuses')->updateOrInsert(
                ['status_name' => $status['status_name']],
                $status
            );
        }
    }
}
