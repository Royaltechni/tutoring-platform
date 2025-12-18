<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // ✅ نضيف teacher_id ولكن يسمح بـ NULL
            $table->unsignedBigInteger('teacher_id')
                  ->nullable()
                  ->after('user_id');

            // لو حابب تضيف علاقة مستقبلاً:
            // $table->foreign('teacher_id')
            //       ->references('id')
            //       ->on('users')
            //       ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // لو فعّلت الـ foreign key لازم تشيله هنا أولاً
            // $table->dropForeign(['teacher_id']);

            $table->dropColumn('teacher_id');
        });
    }
};
