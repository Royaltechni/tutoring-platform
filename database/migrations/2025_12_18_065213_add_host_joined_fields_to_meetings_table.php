<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->timestamp('host_joined_at')->nullable()->after('actual_started_at');
            $table->unsignedBigInteger('host_joined_by_user_id')->nullable()->after('host_joined_at');

            $table->index('host_joined_at');
            $table->index('host_joined_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->dropIndex(['host_joined_at']);
            $table->dropIndex(['host_joined_by_user_id']);
            $table->dropColumn(['host_joined_at', 'host_joined_by_user_id']);
        });
    }
};
