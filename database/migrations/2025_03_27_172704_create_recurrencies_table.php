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
        Schema::create('recurrencies', function (Blueprint $table) {
            $table->id();
            $table->integer('type');
            $table->date('end')->nullable();
            $table->foreignId('first_event_id')->nullable()->constrained('events')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::table('events', function (Blueprint $table) {
            $table->foreignId('recurrency_id')->nullable()->constrained()->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropForeign(['recurrency_id']);
            $table->dropColumn('recurrency_id');
        });

        Schema::dropIfExists('recurrencies');
    }
};
