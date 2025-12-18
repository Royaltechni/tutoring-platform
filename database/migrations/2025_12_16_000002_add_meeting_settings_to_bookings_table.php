<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'meeting_early_join_minutes')) {
                $table->unsignedSmallInteger('meeting_early_join_minutes')->nullable()->after('duration_minutes');
            }
            if (!Schema::hasColumn('bookings', 'meeting_grace_after_minutes')) {
                $table->unsignedSmallInteger('meeting_grace_after_minutes')->nullable()->after('meeting_early_join_minutes');
            }
            if (!Schema::hasColumn('bookings', 'meeting_duration_minutes')) {
                $table->unsignedSmallInteger('meeting_duration_minutes')->nullable()->after('meeting_grace_after_minutes');
            }
            if (!Schema::hasColumn('bookings', 'meeting_notes')) {
                $table->text('meeting_notes')->nullable()->after('meeting_duration_minutes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'meeting_early_join_minutes')) $table->dropColumn('meeting_early_join_minutes');
            if (Schema::hasColumn('bookings', 'meeting_grace_after_minutes')) $table->dropColumn('meeting_grace_after_minutes');
            if (Schema::hasColumn('bookings', 'meeting_duration_minutes')) $table->dropColumn('meeting_duration_minutes');
            if (Schema::hasColumn('bookings', 'meeting_notes')) $table->dropColumn('meeting_notes');
        });
    }
};
