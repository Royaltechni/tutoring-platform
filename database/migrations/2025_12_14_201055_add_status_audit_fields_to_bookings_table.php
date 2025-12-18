<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // مين غيّر الحالة آخر مرة
            $table->unsignedBigInteger('status_updated_by')->nullable()->after('status');

            // إمتى اتغيرت الحالة آخر مرة
            $table->timestamp('status_updated_at')->nullable()->after('status_updated_by');

            // (اختياري) مصدر التغيير: teacher/admin/student
            $table->string('status_updated_source', 20)->nullable()->after('status_updated_at');

            $table->index('status_updated_by');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['status_updated_by']);
            $table->dropColumn(['status_updated_by', 'status_updated_at', 'status_updated_source']);
        });
    }
};
