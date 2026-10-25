<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('purchase_number', 40)->unique();
            $table->decimal('total', 15, 2)->default(0);
            // completed = stok sudah bertambah, cancelled = efek stok sudah dibalik.
            $table->string('status', 20)->default('completed')->index();
            $table->string('note')->nullable();
            $table->timestamp('purchased_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
