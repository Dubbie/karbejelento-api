<?php

use App\Constants\BuildingImportStatus;
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
        Schema::create('building_imports', function (Blueprint $table) {
            $table->id();

            $table->uuid('uuid')->unique();

            // Foreign key to the user who uploaded the file (Admin/Manager)
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // Foreign key to the user the buildings belong to (Customer)
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();

            $table->string('status')->default(BuildingImportStatus::PENDING);
            $table->string('original_filename');
            $table->string('stored_path');
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('processed_rows')->default(0);
            $table->unsignedInteger('successful_rows')->default(0);
            $table->json('errors')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('building_imports');
    }
};
