<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('status', 20)->default('paid')->after('user_id'); // pending | paid
            $table->timestamp('paid_at')->nullable()->after('status');
            $table->timestamp('expires_at')->nullable()->after('paid_at');

            $table->index(['status', 'expires_at']);
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->string('code', 32)->nullable()->unique()->after('seat_id');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropColumn('code');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['status', 'expires_at']);
            $table->dropColumn(['status', 'paid_at', 'expires_at']);
        });
    }
};