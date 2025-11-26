<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('report_id')->constrained();
            $table->foreignId('requested_by_user_id')->constrained('users');
            $table->string('email_title');
            $table->text('email_body');
            $table->json('requested_documents');
            $table->text('other_document_note')->nullable();
            $table->boolean('is_fulfilled')->default(false);
            $table->string('public_token', 64)->unique();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });

        Schema::create('document_request_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('document_request_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->text('note')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('document_request_item_files', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('document_request_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users');
            $table->string('file_path');
            $table->string('file_name_original');
            $table->string('file_mime_type');
            $table->unsignedBigInteger('file_size_bytes');
            $table->timestamp('uploaded_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_request_item_files');
        Schema::dropIfExists('document_request_items');
        Schema::dropIfExists('document_requests');
    }
};
