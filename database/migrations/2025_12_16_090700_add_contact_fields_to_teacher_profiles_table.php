<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teacher_profiles', function (Blueprint $table) {
            // أرقام التواصل
            $table->string('phone_mobile', 50)->nullable()->after('availability'); // موبايل/هاتف
            $table->string('whatsapp_number', 50)->nullable()->after('phone_mobile');

            // عنوان تفصيلي (للإدارة)
            $table->text('address_details')->nullable()->after('whatsapp_number');

            // روابط التواصل
            $table->string('website_url', 2048)->nullable()->after('address_details');
            $table->string('facebook_url', 2048)->nullable()->after('website_url');
            $table->string('instagram_url', 2048)->nullable()->after('facebook_url');
            $table->string('tiktok_url', 2048)->nullable()->after('instagram_url');
            $table->string('youtube_url', 2048)->nullable()->after('tiktok_url');
            $table->string('linkedin_url', 2048)->nullable()->after('youtube_url');
        });
    }

    public function down(): void
    {
        Schema::table('teacher_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'phone_mobile',
                'whatsapp_number',
                'address_details',
                'website_url',
                'facebook_url',
                'instagram_url',
                'tiktok_url',
                'youtube_url',
                'linkedin_url',
            ]);
        });
    }
};
