<?php

use App\Enums\TransactionStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->foreignId('user_id');
            $table->decimal('amount', 15, 2);
            $table->string('cpf', 11);
            $table->string('document_path')->nullable();
            $table->string('status')->default(TransactionStatus::Processing->value);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreign(['tenant_id', 'user_id'])
                ->references(['tenant_id', 'id'])
                ->on('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->index(['tenant_id', 'created_at']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'cpf']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
