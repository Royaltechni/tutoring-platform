<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teacher_profiles', function (Blueprint $table) {
            // وقت راحة بين الحصص بالدقائق (اختياري)
            $table->unsignedSmallInteger('break_minutes')->nullable()->default(0)->after('time_zone');
        });
    }

    public function down(): void
    {
        Schema::table('teacher_profiles', function (Blueprint $table) {
            $table->dropColumn('break_minutes');
        });
    }
};
