<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('insurers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::table('buildings', function (Blueprint $table) {
            $table->foreignId('insurer_id')->nullable()->after('account_number')->constrained('insurers');
        });

        Schema::table('building_management', function (Blueprint $table) {
            $table->foreignId('insurer_id')->nullable()->after('customer_id')->constrained('insurers');
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->foreignId('insurer_id')->nullable()->after('bond_number')->constrained('insurers');
        });

        $this->migrateInsurerData();

        Schema::table('buildings', function (Blueprint $table) {
            $table->dropColumn('insurer');
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn('insurer');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('buildings', function (Blueprint $table) {
            $table->string('insurer')->nullable()->after('account_number');
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->string('insurer')->nullable()->after('bond_number');
        });

        $this->restoreInsurerNames();

        Schema::table('building_management', function (Blueprint $table) {
            $table->dropConstrainedForeignId('insurer_id');
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->dropConstrainedForeignId('insurer_id');
        });

        Schema::table('buildings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('insurer_id');
        });

        Schema::dropIfExists('insurers');
    }

    private function migrateInsurerData(): void
    {
        $insurerNames = collect(
            DB::table('buildings')
                ->whereNotNull('insurer')
                ->pluck('insurer')
                ->all()
        )
            ->merge(
                DB::table('reports')
                    ->whereNotNull('insurer')
                    ->pluck('insurer')
                    ->all()
            )
            ->map(fn ($name) => trim((string) $name))
            ->filter(fn ($name) => $name !== '')
            ->unique()
            ->values();

        $insurerIdsByName = [];

        foreach ($insurerNames as $name) {
            $existing = DB::table('insurers')->where('name', $name)->value('id');
            if ($existing) {
                $insurerIdsByName[$name] = $existing;
                continue;
            }

            $insurerIdsByName[$name] = DB::table('insurers')->insertGetId([
                'uuid' => (string) Str::uuid(),
                'name' => $name,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $buildings = DB::table('buildings')
            ->select('id', 'insurer')
            ->whereNotNull('insurer')
            ->get();

        foreach ($buildings as $building) {
            $name = trim((string) $building->insurer);
            if ($name === '' || !isset($insurerIdsByName[$name])) {
                continue;
            }

            DB::table('buildings')
                ->where('id', $building->id)
                ->update(['insurer_id' => $insurerIdsByName[$name]]);
        }

        $buildingManagementRows = DB::table('building_management')
            ->join('buildings', 'building_management.building_id', '=', 'buildings.id')
            ->select('building_management.id', 'buildings.insurer_id')
            ->whereNotNull('buildings.insurer_id')
            ->get();

        foreach ($buildingManagementRows as $row) {
            DB::table('building_management')
                ->where('id', $row->id)
                ->update(['insurer_id' => $row->insurer_id]);
        }

        $reports = DB::table('reports')
            ->select('id', 'insurer')
            ->whereNotNull('insurer')
            ->get();

        foreach ($reports as $report) {
            $name = trim((string) $report->insurer);
            if ($name === '' || !isset($insurerIdsByName[$name])) {
                continue;
            }

            DB::table('reports')
                ->where('id', $report->id)
                ->update(['insurer_id' => $insurerIdsByName[$name]]);
        }
    }

    private function restoreInsurerNames(): void
    {
        $buildings = DB::table('buildings')
            ->leftJoin('insurers', 'buildings.insurer_id', '=', 'insurers.id')
            ->select('buildings.id', 'insurers.name')
            ->whereNotNull('buildings.insurer_id')
            ->get();

        foreach ($buildings as $building) {
            DB::table('buildings')
                ->where('id', $building->id)
                ->update(['insurer' => $building->name]);
        }

        $reports = DB::table('reports')
            ->leftJoin('insurers', 'reports.insurer_id', '=', 'insurers.id')
            ->select('reports.id', 'insurers.name')
            ->whereNotNull('reports.insurer_id')
            ->get();

        foreach ($reports as $report) {
            DB::table('reports')
                ->where('id', $report->id)
                ->update(['insurer' => $report->name]);
        }
    }
};
