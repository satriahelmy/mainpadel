<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rounds', function (Blueprint $table): void {
            $table->json('drawing_metrics')->nullable()->after('locked_at');
        });
    }

    public function down(): void
    {
        Schema::table('rounds', function (Blueprint $table): void {
            $table->dropColumn('drawing_metrics');
        });
    }
};
