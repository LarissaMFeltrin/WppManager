<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->string('sender_name', 255)->nullable()->after('from_jid');
            $table->string('participant_jid', 255)->nullable()->after('sender_name');
            $table->string('media_filename', 255)->nullable()->after('media_mime_type');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['sender_name', 'participant_jid', 'media_filename']);
        });
    }
};
