<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {

            // ✅ طلب إلغاء (بدون تغيير status)
            $table->timestamp('cancel_requested_at')->nullable()->after('status_updated_source');

            $table->foreignId('cancel_requested_by')
                ->nullable()
                ->after('cancel_requested_at')
                ->constrained('users')
                ->nullOnDelete();

            $table->text('cancel_request_reason')->nullable()->after('cancel_requested_by');

            $table->string('cancel_request_status', 20)
                ->nullable()
                ->after('cancel_request_reason'); // pending/approved/rejected

            // (اختياري للمستقبل: لو المعلم/الإدارة وافق/رفض)
            $table->timestamp('cancel_handled_at')->nullable()->after('cancel_request_status');

            $table->foreignId('cancel_handled_by')
                ->nullable()
                ->after('cancel_handled_at')
                ->constrained('users')
                ->nullOnDelete();

            $table->text('cancel_handle_note')->nullable()->after('cancel_handled_by');

            // ✅ Indexes (اختياري لكن مفيد)
            $table->index('cancel_request_status');
            $table->index('cancel_requested_at');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // لازم نسقط الـ FK قبل الأعمدة
            $table->dropForeign(['cancel_requested_by']);
            $table->dropForeign(['cancel_handled_by']);

            $table->dropIndex(['cancel_request_status']);
            $table->dropIndex(['cancel_requested_at']);

            $table->dropColumn([
                'cancel_requested_at',
                'cancel_requested_by',
                'cancel_request_reason',
                'cancel_request_status',
                'cancel_handled_at',
                'cancel_handled_by',
                'cancel_handle_note',
            ]);
        });
    }
};
