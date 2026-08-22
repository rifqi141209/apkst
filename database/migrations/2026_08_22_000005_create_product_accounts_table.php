<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('login');
            $table->text('password');
            $table->string('status')->default('available');
            $table->timestamps();
            $table->index(['product_id', 'status']);
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('product_account_id')->nullable()->after('product_id')->constrained('product_accounts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', fn (Blueprint $table) => $table->dropConstrainedForeignId('product_account_id'));
        Schema::dropIfExists('product_accounts');
    }
};