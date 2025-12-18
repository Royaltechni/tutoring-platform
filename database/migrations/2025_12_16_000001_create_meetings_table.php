<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('booking_id')->unique();
            $table->uuid('uuid')->unique();

            // scheduled | live | ended | cancelled
            $table->string('status')->default('scheduled')->index();

            // وقت الحصة (من السيرفر)
            $table->dateTime('scheduled_start_at')->nullable()->index();
            $table->dateTime('scheduled_end_at')->nullable()->index();

            // نافذة السماح بالدخول (من السيرفر)
            $table->dateTime('allow_join_from')->nullable()->index();
            $table->dateTime('allow_join_until')->nullable()->index();

            // وقت فعلي
            $table->dateTime('actual_started_at')->nullable();
            $table->dateTime('actual_ended_at')->nullable();

            // Token داخلي (بدون مزود الآن) - للتوسعة لاحقًا
            $table->string('room_token', 80)->nullable()->unique();
            $table->string('provider', 30)->nullable(); // null حاليا (Batch2 لاحقًا)
            $table->string('provider_meeting_id', 120)->nullable();

            // التسجيل: إجباري + تحت تحكم الأدمن
            $table->boolean('recording_required')->default(true);
            $table->boolean('recording_admin_enabled')->default(false); // الأدمن هو اللي يسمح
            $table->string('recording_status')->default('disabled'); // disabled|ready|recording|processing|available|failed
            $table->string('recording_path')->nullable(); // Batch2/3

            // عمليات الأدمن
            $table->unsignedBigInteger('recording_enabled_by_admin_id')->nullable();
            $table->dateTime('recording_enabled_at')->nullable();

            $table->unsignedBigInteger('forced_ended_by_admin_id')->nullable();
            $table->dateTime('forced_ended_at')->nullable();
            $table->string('forced_end_reason')->nullable();

            $table->timestamps();

            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('cascade');
            // (اختياري) لو عندك جدول users:
            // $table->foreign('recording_enabled_by_admin_id')->references('id')->on('users')->nullOnDelete();
            // $table->foreign('forced_ended_by_admin_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meetings');
    }
};
