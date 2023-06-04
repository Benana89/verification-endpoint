<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('verification_results', function (Blueprint $table) {
            $table->id();
            $table->string('user_id', 100)->index();
            $table->set('file_type', ['JSON']);
            $table->set('verification_result', ['verified', 'invalid_recipient', 'invalid_issuer', 'invalid_signature']);
            $table->timestamps();
            $table->unique('user_id');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('verification_results');
    }
};