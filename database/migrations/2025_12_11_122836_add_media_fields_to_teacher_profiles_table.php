<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teacher_profiles', function (Blueprint $table) {
            $table->string('profile_photo_path')->nullable()->after('user_id');
            $table->string('intro_video_url')->nullable()->after('profile_photo_path');
            $table->string('id_document_path')->nullable()->after('intro_video_url');
            $table->string('teaching_permit_path')->nullable()->after('id_document_path');
        });
    }

    public function down(): void
    {
        Schema::table('teacher_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'profile_photo_path',
                'intro_video_url',
                'id_document_path',
                'teaching_permit_path',
            ]);
        });
    }
};
