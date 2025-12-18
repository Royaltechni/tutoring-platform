<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meetings', function (Blueprint $table) {

            // Provider (Zoom/Google/etc)
            if (!Schema::hasColumn('meetings', 'provider')) {
                $table->string('provider', 50)->nullable()->index();
            }

            if (!Schema::hasColumn('meetings', 'provider_user_id')) {
                $table->string('provider_user_id')->nullable()->index(); // zoom user id
            }

            if (!Schema::hasColumn('meetings', 'provider_meeting_id')) {
                $table->string('provider_meeting_id')->nullable()->index(); // Zoom meeting id (as string)
            }

            if (!Schema::hasColumn('meetings', 'provider_meeting_uuid')) {
                $table->string('provider_meeting_uuid')->nullable()->index(); // Zoom uuid
            }

            if (!Schema::hasColumn('meetings', 'provider_meeting_number')) {
                $table->string('provider_meeting_number')->nullable()->index(); // sometimes same as meeting id
            }

            if (!Schema::hasColumn('meetings', 'provider_passcode')) {
                $table->string('provider_passcode')->nullable();
            }

            if (!Schema::hasColumn('meetings', 'provider_start_url')) {
                $table->text('provider_start_url')->nullable();
            }

            if (!Schema::hasColumn('meetings', 'provider_join_url')) {
                $table->text('provider_join_url')->nullable();
            }

            if (!Schema::hasColumn('meetings', 'provider_payload')) {
                $table->json('provider_payload')->nullable(); // full provider response
            }
        });
    }

    public function down(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $cols = [
                'provider',
                'provider_user_id',
                'provider_meeting_id',
                'provider_meeting_uuid',
                'provider_meeting_number',
                'provider_passcode',
                'provider_start_url',
                'provider_join_url',
                'provider_payload',
            ];

            foreach ($cols as $col) {
                if (Schema::hasColumn('meetings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
