<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Extra shop photos and every page of the licence and GST documents.
     * The first page of each document, and the cover photo, also stay in the
     * old pharmacies columns so the old apps and admin keep working.
     */
    public function up(): void
    {
        Schema::create('pharmacy_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pharmacy_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 20); // photo | license | gst
            $table->string('path');
            $table->timestamps();
            $table->index(['pharmacy_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_files');
    }
};
