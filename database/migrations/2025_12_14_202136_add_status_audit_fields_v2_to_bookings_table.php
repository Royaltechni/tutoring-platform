<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {

            if (!Schema::hasColumn('bookings', 'status_updated_by')) {
                $table->unsignedBigInteger('status_updated_by')->nullable()->after('status');
            }

            if (!Schema::hasColumn('bookings', 'status_updated_at')) {
                $table->timestamp('status_updated_at')->nullable()->after('status_updated_by');
            }

            if (!Schema::hasColumn('bookings', 'status_updated_source')) {
                $table->string('status_updated_source', 20)->nullable()->after('status_updated_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {

            if (Schema::hasColumn('bookings', 'status_updated_source')) {
                $table->dropColumn('status_updated_source');
            }

            if (Schema::hasColumn('bookings', 'status_updated_at')) {
                $table->dropColumn('status_updated_at');
            }

            if (Schema::hasColumn('bookings', 'status_updated_by')) {
                $table->dropColumn('status_updated_by');
            }
        });
    }
};
