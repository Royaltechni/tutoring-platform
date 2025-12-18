<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('teacher_profile_onsite_cities', function (Blueprint $table) {

            $table->foreignId('teacher_profile_id')
                ->constrained('teacher_profiles')
                ->cascadeOnDelete();

            $table->foreignId('city_id')
                ->constrained('cities')
                ->cascadeOnDelete();

            $table->timestamps();

            // ✅ يمنع التكرار + يعتبره المفتاح الأساسي للجدول
            $table->primary(['teacher_profile_id', 'city_id'], 'tp_city_pk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_profile_onsite_cities');
    }
};
