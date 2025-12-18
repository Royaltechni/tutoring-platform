<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teacher_profiles', function (Blueprint $table) {
            // العنوان التعريفي أعلى البروفايل
            if (!Schema::hasColumn('teacher_profiles', 'headline')) {
                $table->string('headline')->nullable()->after('bio');
            }

            // المدينة
            if (!Schema::hasColumn('teacher_profiles', 'city')) {
                $table->string('city', 100)->nullable()->after('country');
            }

            // المادة الأساسية
            if (!Schema::hasColumn('teacher_profiles', 'main_subject')) {
                $table->string('main_subject', 150)->nullable()->after('city');
            }

            // سنوات الخبرة
            if (!Schema::hasColumn('teacher_profiles', 'experience_years')) {
                $table->unsignedTinyInteger('experience_years')->nullable()->after('main_subject');
            }

            // يدرّس أونلاين؟
            if (!Schema::hasColumn('teacher_profiles', 'teaches_online')) {
                $table->boolean('teaches_online')->default(false)->after('experience_years');
            }

            // يدرّس حضوري؟
            if (!Schema::hasColumn('teacher_profiles', 'teaches_onsite')) {
                $table->boolean('teaches_onsite')->default(false)->after('teaches_online');
            }

            // أسعار الأونلاين
            if (!Schema::hasColumn('teacher_profiles', 'hourly_rate_online')) {
                $table->decimal('hourly_rate_online', 8, 2)->nullable()->after('teaches_onsite');
            }
            if (!Schema::hasColumn('teacher_profiles', 'half_hour_rate_online')) {
                $table->decimal('half_hour_rate_online', 8, 2)->nullable()->after('hourly_rate_online');
            }

            // أسعار الحضوري
            if (!Schema::hasColumn('teacher_profiles', 'hourly_rate_onsite')) {
                $table->decimal('hourly_rate_onsite', 8, 2)->nullable()->after('half_hour_rate_online');
            }
            if (!Schema::hasColumn('teacher_profiles', 'half_hour_rate_onsite')) {
                $table->decimal('half_hour_rate_onsite', 8, 2)->nullable()->after('hourly_rate_onsite');
            }
        });
    }

    public function down(): void
    {
        Schema::table('teacher_profiles', function (Blueprint $table) {
            // في حالة rollback
            $table->dropColumn([
                'headline',
                'city',
                'main_subject',
                'experience_years',
                'teaches_online',
                'teaches_onsite',
                'hourly_rate_online',
                'half_hour_rate_online',
                'hourly_rate_onsite',
                'half_hour_rate_onsite',
            ]);
        });
    }
};
