<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            if (!Schema::hasColumn('chat_messages', 'attachment_path')) {
                $table->string('attachment_path')->nullable()->after('audio_durata_sec');
            }
            if (!Schema::hasColumn('chat_messages', 'attachment_name')) {
                $table->string('attachment_name')->nullable()->after('attachment_path');
            }
            if (!Schema::hasColumn('chat_messages', 'attachment_size')) {
                $table->unsignedInteger('attachment_size')->nullable()->after('attachment_name');
            }
            if (!Schema::hasColumn('chat_messages', 'attachment_mime')) {
                $table->string('attachment_mime', 100)->nullable()->after('attachment_size');
            }
            if (!Schema::hasColumn('chat_messages', 'is_pinned')) {
                $table->boolean('is_pinned')->default(false)->after('attachment_mime');
                $table->index('is_pinned');
            }
        });
    }

    public function down(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            foreach (['attachment_path', 'attachment_name', 'attachment_size', 'attachment_mime', 'is_pinned'] as $col) {
                if (Schema::hasColumn('chat_messages', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
