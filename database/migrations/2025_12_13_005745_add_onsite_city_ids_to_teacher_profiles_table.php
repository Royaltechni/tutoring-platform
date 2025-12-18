<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('teacher_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('teacher_profiles', 'onsite_city_ids')) {
                $table->text('onsite_city_ids')->nullable(); // JSON as TEXT for sqlite
            }
        });
    }

    public function down(): void
    {
        Schema::table('teacher_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('teacher_profiles', 'onsite_city_ids')) {
                $table->dropColumn('onsite_city_ids');
            }
        });
    }
};
