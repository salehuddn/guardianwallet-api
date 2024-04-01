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
        Schema::create('dependant_guardian', function (Blueprint $table) {
            $table->unsignedBigInteger('guardian_id');
            $table->foreign('guardian_id')->references('id')->on('users');
            $table->unsignedBigInteger('dependant_id');
            $table->foreign('dependant_id')->references('id')->on('users');
            
            // add indexes
            $table->index('guardian_id');
            $table->index('dependant_id');
            
            // add composite index for both columns
            $table->unique(['guardian_id', 'dependant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dependant_guardian');
    }
};
