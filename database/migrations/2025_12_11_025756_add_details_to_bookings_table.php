<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDetailsToBookingsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('bookings', function (Blueprint $table) {
            // ✅ ملاحظات:
            // subject / grade / notes موجودين أصلًا في الجدول القديم عندك
            // لذلك مش هنضيفهم تاني عشان نتفادى duplicate column error

            // ✅ نوع المنهج: UAE / British / American ...
            if (!Schema::hasColumn('bookings', 'curriculum')) {
                $table->string('curriculum')->nullable();
            }

            // ✅ طريقة الدرس: أونلاين / حضوري
            if (!Schema::hasColumn('bookings', 'mode')) {
                $table->enum('mode', ['online', 'onsite'])->nullable()->after('curriculum');
            }

            // ✅ تفاصيل الحصة / الباقة
            if (!Schema::hasColumn('bookings', 'duration_minutes')) {
                $table->unsignedSmallInteger('duration_minutes')->nullable()->after('mode'); // 30 أو 60
            }

            if (!Schema::hasColumn('bookings', 'lessons_count')) {
                $table->unsignedTinyInteger('lessons_count')->default(1)->after('duration_minutes'); // عدد الحصص في الباقة
            }

            // ✅ أول موعد
            if (!Schema::hasColumn('bookings', 'first_lesson_at')) {
                $table->dateTime('first_lesson_at')->nullable()->after('lessons_count');
            }

            // ✅ عنوان الحصة لو Onsite
            if (!Schema::hasColumn('bookings', 'location')) {
                $table->string('location')->nullable()->after('first_lesson_at');
            }

            // ✅ الأسعار
            if (!Schema::hasColumn('bookings', 'price_per_lesson')) {
                $table->decimal('price_per_lesson', 8, 2)->nullable()->after('location');
            }

            if (!Schema::hasColumn('bookings', 'total_price')) {
                $table->decimal('total_price', 8, 2)->nullable()->after('price_per_lesson');
            }

            // ✅ حالة الدفع
            if (!Schema::hasColumn('bookings', 'payment_status')) {
                $table->string('payment_status')->default('pending')->after('total_price'); 
                // values: pending, paid, failed
            }

            // ✅ نوع الحجز: عادي / تجريبي
            if (!Schema::hasColumn('bookings', 'booking_type')) {
                $table->string('booking_type')->default('normal')->after('payment_status'); 
                // values: normal, trial
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('bookings', function (Blueprint $table) {
            // هنحذف بس الأعمدة اللي أضفناها هنا
            if (Schema::hasColumn('bookings', 'curriculum')) {
                $table->dropColumn('curriculum');
            }
            if (Schema::hasColumn('bookings', 'mode')) {
                $table->dropColumn('mode');
            }
            if (Schema::hasColumn('bookings', 'duration_minutes')) {
                $table->dropColumn('duration_minutes');
            }
            if (Schema::hasColumn('bookings', 'lessons_count')) {
                $table->dropColumn('lessons_count');
            }
            if (Schema::hasColumn('bookings', 'first_lesson_at')) {
                $table->dropColumn('first_lesson_at');
            }
            if (Schema::hasColumn('bookings', 'location')) {
                $table->dropColumn('location');
            }
            if (Schema::hasColumn('bookings', 'price_per_lesson')) {
                $table->dropColumn('price_per_lesson');
            }
            if (Schema::hasColumn('bookings', 'total_price')) {
                $table->dropColumn('total_price');
            }
            if (Schema::hasColumn('bookings', 'payment_status')) {
                $table->dropColumn('payment_status');
            }
            if (Schema::hasColumn('bookings', 'booking_type')) {
                $table->dropColumn('booking_type');
            }
        });
    }
}
