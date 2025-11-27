<?php

use App\Constants\NotificationEvent;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_rules', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('event');
            $table->foreignId('status_id')->nullable()->constrained('statuses');
            $table->foreignId('sub_status_id')->nullable()->constrained('sub_statuses');
            $table->boolean('is_active')->default(true);
            $table->json('options')->nullable();
            $table->timestamps();
        });

        Schema::create('notification_rule_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_rule_id')->constrained('notification_rules')->cascadeOnDelete();
            $table->string('recipient_type');
            $table->string('recipient_value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_rule_recipients');
        Schema::dropIfExists('notification_rules');
    }
};
