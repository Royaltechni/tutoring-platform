<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Indexes (آمنة)
        Schema::table('teacher_profiles', function (Blueprint $table) {
            // نحاول نضيف indexes بدون ما نكسر لو كانت موجودة
            try { $table->index('account_status', 'tp_account_status_idx'); } catch (\Throwable $e) {}
            try { $table->index('country_id', 'tp_country_id_idx'); } catch (\Throwable $e) {}
            try { $table->index('teaches_online', 'tp_teaches_online_idx'); } catch (\Throwable $e) {}
            try { $table->index('teaches_onsite', 'tp_teaches_onsite_idx'); } catch (\Throwable $e) {}
        });

        // 2) Backfill: experience_years من years_of_experience لو الجديدة NULL/0 والقديمة فيها قيمة
        if (
            Schema::hasColumn('teacher_profiles', 'experience_years') &&
            Schema::hasColumn('teacher_profiles', 'years_of_experience')
        ) {
            DB::table('teacher_profiles')
                ->where(function ($q) {
                    $q->whereNull('experience_years')->orWhere('experience_years', 0);
                })
                ->whereNotNull('years_of_experience')
                ->update([
                    'experience_years' => DB::raw('years_of_experience'),
                ]);
        }

        // 3) Backfill: account_status من onboarding_status (تحويل قيم)
        if (
            Schema::hasColumn('teacher_profiles', 'account_status') &&
            Schema::hasColumn('teacher_profiles', 'onboarding_status')
        ) {
            // approved -> approved
            DB::table('teacher_profiles')
                ->whereNull('account_status')
                ->where('onboarding_status', 'approved')
                ->update(['account_status' => 'approved']);

            // rejected -> rejected
            DB::table('teacher_profiles')
                ->whereNull('account_status')
                ->where('onboarding_status', 'rejected')
                ->update(['account_status' => 'rejected']);

            // pending_review أو incomplete -> pending
            DB::table('teacher_profiles')
                ->whereNull('account_status')
                ->whereIn('onboarding_status', ['pending_review', 'incomplete'])
                ->update(['account_status' => 'pending']);
        }

        // 4) (اختياري) Foreign Key لـ country_id لو MySQL فقط (SQLite غالبًا مش هيفيد)
        try {
            $driver = Schema::getConnection()->getDriverName();

            if ($driver === 'mysql') {
                // نضيف FK باسم ثابت
                Schema::table('teacher_profiles', function (Blueprint $table) {
                    // لو فيه FK قديم بنفس الاسم هيترمي exception وهنسكبه
                    $table->foreign('country_id', 'tp_country_id_fk')
                        ->references('id')->on('countries')
                        ->nullOnDelete();
                });
            }
        } catch (\Throwable $e) {
            // نتجاهل لو حصل تعارض أو القيد موجود
        }
    }

    public function down(): void
    {
        // إزالة FK لو موجود (MySQL)
        try {
            $driver = Schema::getConnection()->getDriverName();
            if ($driver === 'mysql') {
                Schema::table('teacher_profiles', function (Blueprint $table) {
                    try { $table->dropForeign('tp_country_id_fk'); } catch (\Throwable $e) {}
                });
            }
        } catch (\Throwable $e) {}

        // إزالة Indexes
        Schema::table('teacher_profiles', function (Blueprint $table) {
            try { $table->dropIndex('tp_account_status_idx'); } catch (\Throwable $e) {}
            try { $table->dropIndex('tp_country_id_idx'); } catch (\Throwable $e) {}
            try { $table->dropIndex('tp_teaches_online_idx'); } catch (\Throwable $e) {}
            try { $table->dropIndex('tp_teaches_onsite_idx'); } catch (\Throwable $e) {}
        });
    }
};
