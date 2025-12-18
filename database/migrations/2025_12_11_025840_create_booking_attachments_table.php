<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBookingAttachmentsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('booking_attachments', function (Blueprint $table) {
            $table->id();

            // ✅ الحجز المرتبط به الملف
            $table->unsignedBigInteger('booking_id');

            // ✅ مين اللي رفع الملف؟ (طالب / مدرس)
            $table->string('uploaded_by_type'); // 'student' أو 'teacher'
            $table->unsignedBigInteger('uploaded_by_id'); // user id

            // ✅ بيانات الملف
            $table->string('original_name'); // اسم الملف الأصلي
            $table->string('file_path');     // مسار الملف في storage

            $table->timestamps();

            // ✅ الربط مع جدول الحجوزات
            $table->foreign('booking_id')
                ->references('id')
                ->on('bookings')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('booking_attachments');
    }
}
