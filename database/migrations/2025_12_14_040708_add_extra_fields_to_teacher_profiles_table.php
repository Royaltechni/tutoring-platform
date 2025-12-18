<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teacher_profiles', function (Blueprint $table) {
            $table->text('subjects')->nullable()->after('main_subject');          // مواد إضافية (CSV)
            $table->string('languages', 255)->nullable()->after('subjects');      // لغات الشرح (CSV)
            $table->text('teaching_style')->nullable()->after('languages');       // أسلوب التدريس
            $table->text('cancel_policy')->nullable()->after('teaching_style');  // سياسة الإلغاء
            $table->json('availability')->nullable()->after('cancel_policy');    // جدول التوفر (JSON)
        });
    }

    public function down(): void
    {
        Schema::table('teacher_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'subjects',
                'languages',
                'teaching_style',
                'cancel_policy',
                'availability',
            ]);
        });
    }
};
