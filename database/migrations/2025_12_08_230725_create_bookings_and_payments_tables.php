<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Bookings (The "Order")
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique(); // Public reference

            // Relationships
            $table->foreignId('teacher_profile_id')->constrained('teacher_profiles'); // No cascade delete for financial records
            $table->foreignId('user_id')->constrained('users'); // The Parent/Student

            // Snapshot of the deal
            $table->foreignId('lesson_delivery_mode_id')->nullable()->constrained();
            $table->foreignId('city_id')->nullable()->constrained(); // Nullable if online

            // Financials
            $table->decimal('total_amount', 10, 2);
            $table->char('currency', 3)->default('AED');

            // State
            $table->string('status')->default('pending'); // pending, confirmed, cancelled, completed
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes(); // Preserve history
        });

        // 2. Lesson Sessions (The "Events")
        Schema::create('lesson_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();

            // Denormalized for query performance, but optional
            $table->foreignId('teacher_profile_id')->constrained('teacher_profiles');
            $table->foreignId('user_id')->constrained('users');

            // Schedule
            $table->dateTime('scheduled_start_at');
            $table->dateTime('scheduled_end_at');

            // Actuals (for verification)
            $table->dateTime('actual_start_at')->nullable();
            $table->dateTime('actual_end_at')->nullable();

            // Execution details
            $table->string('status')->default('scheduled'); // scheduled, in_progress, completed, no_show, cancelled
            $table->string('video_link')->nullable(); // Zoom/Meet
            $table->string('recording_url')->nullable();
            $table->text('teacher_notes')->nullable(); // Summary of what was taught

            $table->timestamps();
            $table->softDeletes();
        });

        // 3. Payments
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained(); // Keep payments if booking deleted? Typically yes, but via soft delete.

            $table->decimal('amount', 10, 2);
            $table->char('currency', 3)->default('AED');

            $table->string('payment_provider'); // stripe, tap, mamopay
            $table->string('payment_method')->nullable(); // card, apple_pay
            $table->string('status')->default('pending'); // pending, succeeded, failed, refunded

            $table->string('external_transaction_id')->nullable()->index();
            $table->json('raw_payload')->nullable(); // Store webhook data for debugging

            $table->timestamps();
        });

        // 4. Ratings
        Schema::create('lesson_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_session_id')->constrained();
            $table->foreignId('user_id')->constrained(); // The Rater

            $table->tinyInteger('rating')->unsigned(); // 1-5
            $table->text('comment')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_ratings');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('lesson_sessions');
        Schema::dropIfExists('bookings');
    }
};
