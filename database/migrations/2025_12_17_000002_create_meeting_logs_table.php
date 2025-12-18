<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('meeting_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('meeting_id')->index();
            $table->unsignedBigInteger('booking_id')->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();

            $table->string('actor_role', 20)->nullable(); // teacher|student|admin|system
            $table->string('event', 50)->index();         // join|leave|denied|force_end|zoom_created...
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->foreign('meeting_id')->references('id')->on('meetings')->onDelete('cascade');
            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('cascade');
            // user_id اختياري تربطه لو تحب:
            // $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_logs');
    }
};
