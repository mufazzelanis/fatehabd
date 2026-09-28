<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Bangladesh administrative hierarchy for the dealership ("Business") system:
 * 8 divisions → 64 districts → thanas (upazilas) → unions. Seeded here rather than in a
 * seeder so a plain `migrate` on the live server brings the data along, matching how
 * the vendor role was seeded. Source: nuhil/bangladesh-geocode (database/data).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('divisions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('bn_name')->nullable();
            $table->timestamps();
        });

        Schema::create('districts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('division_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('bn_name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('thanas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('district_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('bn_name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('unions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thana_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('bn_name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $this->seedLocations();

        DB::table('permissions')->insert([
            'name' => 'business.manage',
            'display_name' => 'Manage Business (Dealers & Locations)',
            'group' => 'Business',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedLocations(): void
    {
        $data = json_decode(file_get_contents(database_path('data/bangladesh-locations.json')), true);
        $now = now();

        foreach ($data as [$divName, $divBn, $districts]) {
            $divisionId = DB::table('divisions')->insertGetId([
                'name' => $divName, 'bn_name' => $divBn, 'created_at' => $now, 'updated_at' => $now,
            ]);

            foreach ($districts as [$disName, $disBn, $thanas]) {
                $districtId = DB::table('districts')->insertGetId([
                    'division_id' => $divisionId, 'name' => $disName, 'bn_name' => $disBn,
                    'created_at' => $now, 'updated_at' => $now,
                ]);

                foreach ($thanas as [$thanaName, $thanaBn, $unions]) {
                    $thanaId = DB::table('thanas')->insertGetId([
                        'district_id' => $districtId, 'name' => $thanaName, 'bn_name' => $thanaBn,
                        'created_at' => $now, 'updated_at' => $now,
                    ]);

                    $rows = array_map(fn ($u) => [
                        'thana_id' => $thanaId, 'name' => $u[0], 'bn_name' => $u[1],
                        'created_at' => $now, 'updated_at' => $now,
                    ], $unions);
                    if ($rows) {
                        DB::table('unions')->insert($rows);
                    }
                }
            }
        }
    }

    public function down(): void
    {
        DB::table('permissions')->where('name', 'business.manage')->delete();
        Schema::dropIfExists('unions');
        Schema::dropIfExists('thanas');
        Schema::dropIfExists('districts');
        Schema::dropIfExists('divisions');
    }
};
