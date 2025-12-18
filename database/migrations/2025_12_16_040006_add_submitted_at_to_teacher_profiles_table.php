<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teacher_profiles', function (Blueprint $table) {
            // ✅ وقت إرسال الملف للمراجعة (Submit)
            $table->timestamp('submitted_at')->nullable()->after('account_status');

            // (اختياري للمستقبل) وقت انتهاء المراجعة - هنستخدمه لاحقًا إن أحببت
            // $table->timestamp('reviewed_at')->nullable()->after('submitted_at');
        });
    }

    public function down(): void
    {
        Schema::table('teacher_profiles', function (Blueprint $table) {
            $table->dropColumn(['submitted_at']);
            // $table->dropColumn(['reviewed_at']);
        });
    }
};
