<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user_transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('savings_id')->nullable()->after('merchant_id')->nullable();
            $table->foreign('savings_id')->references('id')->on('savings')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_transactions', function (Blueprint $table) {
            $table->dropForeign(['savings_id']);
            $table->dropColumn('savings_id');
        });
    }
};
