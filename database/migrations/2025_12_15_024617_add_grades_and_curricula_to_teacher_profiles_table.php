<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('teacher_profiles', function (Blueprint $table) {
            $table->tinyInteger('min_grade')->nullable()->after('experience_years');
            $table->tinyInteger('max_grade')->nullable()->after('min_grade');
            $table->text('curricula')->nullable()->after('subjects'); // JSON array
        });
    }

    public function down(): void
    {
        Schema::table('teacher_profiles', function (Blueprint $table) {
            $table->dropColumn(['min_grade', 'max_grade', 'curricula']);
        });
    }
};
