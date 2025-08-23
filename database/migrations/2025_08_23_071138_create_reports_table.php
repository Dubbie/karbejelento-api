<?php

use App\Constants\ReportStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // --- Core Relationships ---
            $table->foreignId('building_id')->constrained('buildings');
            $table->foreignId('created_by_user_id')->constrained('users');
            $table->foreignId('notifier_id')->constrained('notifiers');

            // --- Snapshot fields ---
            $table->string('bond_number');
            $table->string('insurer');

            // --- Damage Details ---
            $table->string('damage_id')->nullable()->unique();
            $table->string('damage_type');
            $table->text('damage_description');
            $table->string('damaged_building_name')->nullable();
            $table->string('damaged_building_number')->nullable();
            $table->string('damaged_floor')->nullable();
            $table->string('damaged_unit_or_door')->nullable();
            $table->date('damage_date');
            $table->string('estimated_cost')->nullable();
            $table->string('current_status')->default(ReportStatus::NEW);

            // --- Claimant & Contact Details ---
            $table->string('claimant_type');
            $table->string('claimant_name')->nullable();
            $table->string('claimant_email')->nullable();
            $table->string('claimant_phone_number')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_phone_number')->nullable();
            $table->string('claimant_account_number')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
