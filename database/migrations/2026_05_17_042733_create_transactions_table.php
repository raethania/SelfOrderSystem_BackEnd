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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained('orders');
            $table->enum('payment_method', ['cash', 'card', 'qris']);
            $table->decimal('amount_paid', 12, 2);
            $table->decimal('change', 12, 2)->default(0);
            $table->foreignId('processed_by')->constrained('users');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
