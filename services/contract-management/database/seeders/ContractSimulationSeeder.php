<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class ContractSimulationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        // Clean existing contracts before seeding to avoid duplicates
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('contracts')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Retrieve existing IDs from lookup tables
        $categoryIds = DB::table('contract_categories')->pluck('category_id')->toArray();
        $approvedStatusId = DB::table('contract_approval_statuses')->where('status_name', 'Approved')->value('approval_status_id');
        $statusIds = DB::table('contract_statuses')->pluck('status_id')->toArray();
        $regionIds = DB::table('contract_regions')->pluck('region_id')->toArray();

        // Fallbacks in case tables are empty
        if (empty($categoryIds)) $categoryIds = [1];
        if (!$approvedStatusId) $approvedStatusId = 1;
        if (empty($statusIds)) $statusIds = [1];
        if (empty($regionIds)) $regionIds = [1];

        $contracts = [];
        $batchSize = 2000;

        for ($i = 1; $i <= 50000; $i++) {
            // Guarantee unique codes and serials by utilizing the loop index $i
            $itemCode = 'ITEM-' . str_pad((string)$i, 6, '0', STR_PAD_LEFT);
            $serialNumber = 'SN-' . str_pad((string)$i, 6, '0', STR_PAD_LEFT) . '-' . strtoupper($faker->bothify('??##'));

            $contracts[] = [
                'category_id'        => $categoryIds[array_rand($categoryIds)],
                'approval_status_id' => $approvedStatusId,
                'workflow_status_id' => rand(0, 10) > 3 ? $statusIds[array_rand($statusIds)] : null,
                'region_id'          => $regionIds[array_rand($regionIds)],
                'bp_name'            => $faker->company,
                'item_code'          => $itemCode,
                'description'        => $faker->paragraph(2),
                'serial_number'      => $serialNumber,
                'sbu_number'         => 'SBU-' . rand(100, 999),
                'start_date'         => $faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
                'end_date'           => $faker->dateTimeBetween('+4 years', '+5 years')->format('Y-m-d'),
                'created_by'         => 1, // Simulated default user ID
                'created_at'         => now(),
                'updated_at'         => now(),
            ];

            if (count($contracts) >= $batchSize) {
                DB::table('contracts')->insert($contracts);
                $contracts = [];
            }
        }

        if (!empty($contracts)) {
            DB::table('contracts')->insert($contracts);
        }
    }
}
