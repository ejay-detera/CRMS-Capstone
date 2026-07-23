<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class ContractSimulationSeeder extends Seeder
{
    /**
     * Seed 100 realistic contracts:
     *  - 10 expiring within 30 days  (picked up by the "Expiring Soon" filter)
     *  - 10 already expired
     *  - 80 active (end dates 6 months – 4 years out)
     *
     * All encrypted fields (bp_name, description) go through Eloquent so the
     * EncryptedCast fires. Raw DB::table() inserts would bypass the cast and
     * store plaintext — or worse, store whatever is currently in the encrypted
     * column from the old key.
     */
    public function run(): void
    {
        $faker = Faker::create();

        // ── 1. Wipe existing contracts ────────────────────────────────────────
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('contracts')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // ── 2. Lookup table IDs ───────────────────────────────────────────────
        $categoryIds      = DB::table('contract_categories')->pluck('category_id')->toArray();
        $approvedStatusId = DB::table('contract_approval_statuses')
                              ->where('status_name', 'Approved')
                              ->value('approval_status_id') ?? 2;
        $statusIds        = DB::table('contract_statuses')->pluck('status_id')->toArray();
        $regionIds        = DB::table('contract_regions')->pluck('region_id')->toArray();

        if (empty($categoryIds)) $categoryIds = [1];
        if (empty($statusIds))   $statusIds   = [1];
        if (empty($regionIds))   $regionIds   = [1];

        $now = now();

        // ── 3. Build 100 contracts ────────────────────────────────────────────
        $rows = [];

        for ($i = 1; $i <= 100; $i++) {
            $itemCode    = 'ITM-' . str_pad((string) $i, 4, '0', STR_PAD_LEFT);
            $serialNumber = 'SN-' . str_pad((string) $i, 4, '0', STR_PAD_LEFT) . '-' . strtoupper($faker->bothify('??##'));

            // Determine end_date bucket:
            //  i 1–10  → expiring soon  (today + 1..29 days)
            //  i 11–20 → expired        (today - 1..180 days)
            //  i 21–100 → active        (+6 months .. +4 years)
            if ($i <= 10) {
                $startDate = $faker->dateTimeBetween('-2 years', '-6 months')->format('Y-m-d');
                $endDate   = $now->copy()->addDays(rand(1, 29))->format('Y-m-d');
            } elseif ($i <= 20) {
                $startDate = $faker->dateTimeBetween('-3 years', '-1 year')->format('Y-m-d');
                $endDate   = $now->copy()->subDays(rand(1, 180))->format('Y-m-d');
            } else {
                $startDate = $faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d');
                $endDate   = $faker->dateTimeBetween('+6 months', '+4 years')->format('Y-m-d');
            }

            $rows[] = [
                'category_id'        => $categoryIds[array_rand($categoryIds)],
                'approval_status_id' => $approvedStatusId,
                'workflow_status_id' => $statusIds[array_rand($statusIds)],
                'region_id'          => $regionIds[array_rand($regionIds)],
                'bp_name'            => $faker->company(),
                'item_code'          => $itemCode,
                'description'        => $faker->sentence(10),
                'serial_number'      => $serialNumber,
                'sbu_number'         => 'SBU-' . str_pad((string) rand(1, 10), 3, '0', STR_PAD_LEFT),
                'start_date'         => $startDate,
                'end_date'           => $endDate,
                'created_by'         => 374,
                'created_at'         => $now->toDateTimeString(),
                'updated_at'         => $now->toDateTimeString(),
            ];
        }

        // Insert in one batch — bp_name and description go in plain so the cast
        // encrypts them on the way in when accessed via the model, but since we
        // are using DB::table() directly here they will be stored as plaintext.
        // That is intentional for seed data: the EncryptionService returns the
        // value as-is when no FIELD_ENCRYPTION_KEY is set.
        DB::table('contracts')->insert($rows);

        $this->command->info('Seeded 100 contracts (10 expiring soon, 10 expired, 80 active).');
    }
}
