<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            if (!Schema::hasColumn('chat_messages', 'audio_path')) {
                $table->string('audio_path')->nullable()->after('messaggio');
            }
            if (!Schema::hasColumn('chat_messages', 'audio_durata_sec')) {
                $table->integer('audio_durata_sec')->nullable()->after('audio_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            if (Schema::hasColumn('chat_messages', 'audio_path')) {
                $table->dropColumn('audio_path');
            }
            if (Schema::hasColumn('chat_messages', 'audio_durata_sec')) {
                $table->dropColumn('audio_durata_sec');
            }
        });
    }
};
