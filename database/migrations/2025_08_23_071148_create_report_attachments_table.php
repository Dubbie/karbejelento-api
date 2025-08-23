<?php

use App\Constants\AttachmentCategory;
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
        Schema::create('report_attachments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('report_id')->constrained('reports')->onDelete('cascade');
            $table->foreignId('uploaded_by_user_id')->constrained('users');
            $table->string('file_path');
            $table->string('file_name_original');
            $table->string('file_mime_type');
            $table->unsignedInteger('file_size_bytes');
            $table->string('category')->default(AttachmentCategory::OTHER);
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_attachments');
    }
};
