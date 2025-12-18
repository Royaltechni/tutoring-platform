<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            if (!Schema::hasColumn('meetings', 'provider_meeting_uuid')) {
                $table->string('provider_meeting_uuid', 120)->nullable()->after('provider_meeting_id');
            }
            if (!Schema::hasColumn('meetings', 'provider_meeting_number')) {
                $table->string('provider_meeting_number', 40)->nullable()->after('provider_meeting_uuid');
            }
            if (!Schema::hasColumn('meetings', 'provider_passcode')) {
                $table->string('provider_passcode', 255)->nullable()->after('provider_meeting_number');
            }
            if (!Schema::hasColumn('meetings', 'provider_host_user_id')) {
                $table->string('provider_host_user_id', 80)->nullable()->after('provider_passcode');
            }
            if (!Schema::hasColumn('meetings', 'provider_payload')) {
                $table->json('provider_payload')->nullable()->after('provider_host_user_id');
            }

            // provider موجود عندك بالفعل، provider_meeting_id موجود
            // هنستخدمهم كما هم.
        });
    }

    public function down(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            if (Schema::hasColumn('meetings', 'provider_payload')) $table->dropColumn('provider_payload');
            if (Schema::hasColumn('meetings', 'provider_host_user_id')) $table->dropColumn('provider_host_user_id');
            if (Schema::hasColumn('meetings', 'provider_passcode')) $table->dropColumn('provider_passcode');
            if (Schema::hasColumn('meetings', 'provider_meeting_number')) $table->dropColumn('provider_meeting_number');
            if (Schema::hasColumn('meetings', 'provider_meeting_uuid')) $table->dropColumn('provider_meeting_uuid');
        });
    }
};
