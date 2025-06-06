<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class SeniParticipantSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('id_ID');

        $contingents = \App\Models\Contingent::pluck('id')->toArray();
        $tournamentId = 2;

        DB::beginTransaction();
        try {
            $createTeamMember = function ($contingentId, $matchCategoryId, $gender) use ($faker) {
                return \App\Models\TeamMember::create([
                    'contingent_id' => $contingentId,
                    'name' => $faker->name($gender === 'male' ? 'male' : 'female'),
                    'birth_place' => $faker->city,
                    'birth_date' => $faker->date(),
                    'gender' => $gender,
                    'body_weight' => 70,
                    'body_height' => 170,
                    'blood_type' => 'O',
                    'nik' => $faker->numerify('###############'),
                    'family_card_number' => $faker->numerify('###############'),
                    'country_id' => 103,
                    'province_id' => 32,
                    'district_id' => 3217,
                    'subdistrict_id' => 321714,
                    'ward_id' => 3217142009,
                    'address' => $faker->address,
                    'championship_category_id' => 2, // Seni
                    'match_category_id' => $matchCategoryId,
                    'age_category_id' => 4,
                    'category_class_id' => 131,
                    'registration_status' => 'approved',
                ]);
            };

            foreach (['male', 'female'] as $gender) {
                // 🟢 Seni Tunggal (3 peserta per gender)
                for ($i = 0; $i < 3; $i++) {
                    $member = $createTeamMember($faker->randomElement($contingents), 2, $gender);
                    \App\Models\TournamentParticipant::create([
                        'tournament_id' => $tournamentId,
                        'team_member_id' => $member->id,
                    ]);
                }

                // 🟢 Seni Ganda (3 pasang → 6 peserta per gender)
                foreach (array_slice($contingents, 0, 3) as $contingentId) {
                    foreach ([1, 2] as $_) {
                        $member = $createTeamMember($contingentId, 3, $gender);
                        \App\Models\TournamentParticipant::create([
                            'tournament_id' => $tournamentId,
                            'team_member_id' => $member->id,
                        ]);
                    }
                }

                // 🟢 Seni Regu (3 regu → 9 peserta per gender)
                foreach (array_slice($contingents, 0, 3) as $contingentId) {
                    foreach ([1, 2, 3] as $_) {
                        $member = $createTeamMember($contingentId, 4, $gender);
                        \App\Models\TournamentParticipant::create([
                            'tournament_id' => $tournamentId,
                            'team_member_id' => $member->id,
                        ]);
                    }
                }
            }

            DB::commit();
            echo "✅ Seeder SeniParticipantSeeder completed with 3 match per category per gender.\n";
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
