<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {

            if (!Schema::hasColumn('bookings', 'delivery_mode')) {
                $table->string('delivery_mode', 20)->nullable()->after('teacher_id'); // online|onsite
            }

            if (!Schema::hasColumn('bookings', 'duration_minutes')) {
                $table->unsignedInteger('duration_minutes')->nullable()->after('delivery_mode'); // 30|60
            }

            if (!Schema::hasColumn('bookings', 'city_id')) {
                $table->unsignedBigInteger('city_id')->nullable()->after('duration_minutes');
                $table->index('city_id');
            }

            if (!Schema::hasColumn('bookings', 'price')) {
                $table->decimal('price', 10, 2)->nullable()->after('city_id');
            }

        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {

            if (Schema::hasColumn('bookings', 'price')) {
                $table->dropColumn('price');
            }

            if (Schema::hasColumn('bookings', 'city_id')) {
                $table->dropIndex(['city_id']);
                $table->dropColumn('city_id');
            }

            if (Schema::hasColumn('bookings', 'duration_minutes')) {
                $table->dropColumn('duration_minutes');
            }

            if (Schema::hasColumn('bookings', 'delivery_mode')) {
                $table->dropColumn('delivery_mode');
            }

        });
    }
};
