<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Lookup: Delivery Modes (Online, Onsite, Hybrid)
        Schema::create('lesson_delivery_modes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // 'online', 'onsite'
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Lookup: Cities (UAE Specific)
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->string('name_en');
            $table->string('name_ar')->nullable();
            $table->enum('emirate', [
                'Abu Dhabi', 'Dubai', 'Sharjah', 'Ajman',
                'Umm Al Quwain', 'Ras Al Khaimah', 'Fujairah'
            ]);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 3. Lookup: Subjects
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('name_en');
            $table->string('name_ar')->nullable();
            $table->string('category')->nullable(); // e.g. 'Languages', 'Science'
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 4. Teacher Profiles
        Schema::create('teacher_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->text('bio')->nullable();
            $table->integer('years_of_experience')->default(0);
            $table->string('country')->nullable(); // Origin
            $table->string('time_zone')->default('Asia/Dubai');
            $table->string('photo_url')->nullable();

            // Approval Workflow
            $table->enum('onboarding_status', ['pending_review', 'approved', 'rejected', 'incomplete'])
                  ->default('incomplete');

            // Flexible attributes (languages, education, certificates)
            $table->json('meta')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });

        // 5. Pivot: Teacher <-> Delivery Modes (With Pricing)
        Schema::create('teacher_delivery_modes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_profile_id')->constrained('teacher_profiles')->cascadeOnDelete();
            $table->foreignId('lesson_delivery_mode_id')->constrained('lesson_delivery_modes')->cascadeOnDelete();

            $table->decimal('price_per_hour', 10, 2);
            $table->char('currency', 3)->default('AED');
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->unique(['teacher_profile_id', 'lesson_delivery_mode_id'], 'teacher_mode_unique');
        });

        // 6. Pivot: Teacher <-> Cities (Coverage)
        Schema::create('teacher_cities', function (Blueprint $table) {
            $table->foreignId('teacher_profile_id')->constrained('teacher_profiles')->cascadeOnDelete();
            $table->foreignId('city_id')->constrained()->cascadeOnDelete();
            $table->primary(['teacher_profile_id', 'city_id']);
        });

        // 7. Pivot: Teacher <-> Subjects
        Schema::create('teacher_subjects', function (Blueprint $table) {
            $table->foreignId('teacher_profile_id')->constrained('teacher_profiles')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->primary(['teacher_profile_id', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_subjects');
        Schema::dropIfExists('teacher_cities');
        Schema::dropIfExists('teacher_delivery_modes');
        Schema::dropIfExists('teacher_profiles');
        Schema::dropIfExists('subjects');
        Schema::dropIfExists('cities');
        Schema::dropIfExists('lesson_delivery_modes');
    }
};
