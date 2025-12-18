<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teacher_profiles', function (Blueprint $table) {
            // عنوان قصير يظهر تحت اسم المعلّم
            $table->string('headline')
                  ->nullable()
                  ->after('bio'); // لو bio موجود، وإلا سيب after عادي أو امسحه
        });
    }

    public function down(): void
    {
        Schema::table('teacher_profiles', function (Blueprint $table) {
            $table->dropColumn('headline');
        });
    }
};
