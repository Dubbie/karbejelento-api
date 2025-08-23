<?php

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
        Schema::create('building_management', function (Blueprint $table) {
            $table->id();

            // Foreign key to the building being managed.
            $table->foreignId('building_id')->constrained('buildings')->onDelete('cascade');

            // Do the same for the customer (which is a user).
            $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');

            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('building_management');
    }
};
