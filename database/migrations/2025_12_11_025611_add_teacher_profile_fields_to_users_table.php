<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTeacherProfileFieldsToUsersTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // ✅ بيانات عامة للمدرّس
            $table->string('country')->nullable()->after('email');
            $table->string('city')->nullable()->after('country');
            $table->string('headline')->nullable()->after('city'); // عنوان مختصر
            $table->text('bio')->nullable()->after('headline');    // نبذة تعريفية

            // ✅ التخصص والخبرة
            $table->string('main_subject')->nullable()->after('bio');
            $table->unsignedTinyInteger('experience_years')->nullable()->after('main_subject');

            // ✅ طرق التدريس
            $table->boolean('teaches_online')->default(false)->after('experience_years');
            $table->boolean('teaches_onsite')->default(false)->after('teaches_online');

            // ✅ الأسعار (أونلاين)
            $table->decimal('hourly_rate_online', 8, 2)->nullable()->after('teaches_onsite');
            $table->decimal('half_hour_rate_online', 8, 2)->nullable()->after('hourly_rate_online');

            // ✅ الأسعار (حضوري / Onsite)
            $table->decimal('hourly_rate_onsite', 8, 2)->nullable()->after('half_hour_rate_online');
            $table->decimal('half_hour_rate_onsite', 8, 2)->nullable()->after('hourly_rate_onsite');

            // ✅ المراحل الدراسية التي يدرّسها (درجة أدنى وأعلى)
            $table->unsignedTinyInteger('grade_min')->nullable()->after('half_hour_rate_onsite');
            $table->unsignedTinyInteger('grade_max')->nullable()->after('grade_min');

            // ✅ أنواع المناهج (مثال: "UAE,British,American")
            $table->string('curricula')->nullable()->after('grade_max');

            // ✅ الحصة التجريبية
            $table->boolean('offers_trial')->default(false)->after('curricula');
            $table->unsignedSmallInteger('trial_duration_minutes')->nullable()->after('offers_trial'); // 30 أو 60
            $table->decimal('trial_price', 8, 2)->nullable()->after('trial_duration_minutes'); // 0 تعني مجانًا

            // ✅ التقييم
            $table->decimal('average_rating', 3, 2)->default(0)->after('trial_price'); // من 0 إلى 5
            $table->unsignedInteger('ratings_count')->default(0)->after('average_rating');

            // ✅ التوفّر (اليوم / غدًا) للفلاتر
            $table->boolean('available_today')->default(false)->after('ratings_count');
            $table->boolean('available_tomorrow')->default(false)->after('available_today');

            // ✅ فيديو تعريفي
            $table->string('intro_video_url')->nullable()->after('available_tomorrow');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'country',
                'city',
                'headline',
                'bio',
                'main_subject',
                'experience_years',
                'teaches_online',
                'teaches_onsite',
                'hourly_rate_online',
                'half_hour_rate_online',
                'hourly_rate_onsite',
                'half_hour_rate_onsite',
                'grade_min',
                'grade_max',
                'curricula',
                'offers_trial',
                'trial_duration_minutes',
                'trial_price',
                'average_rating',
                'ratings_count',
                'available_today',
                'available_tomorrow',
                'intro_video_url',
            ]);
        });
    }
}
