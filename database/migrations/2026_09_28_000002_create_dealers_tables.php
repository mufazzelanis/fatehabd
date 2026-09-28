<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dealership ("Business") accounts. Dealers are a separate auth guard from storefront
 * customers — they log in with phone + password at /business/login. Level (district/thana/
 * union) is assigned by the admin on approval; one approved district dealer per district and
 * one approved thana dealer per thana is enforced in App\Models\Dealer::levelConflict().
 *
 * dealer_targets holds one monthly sales target per dealer. Sales are tracked manually by the
 * admin, who "settles" each month (achieved_amount/settlement_note) — those settlement fields
 * are admin-only and never shown in the dealer panel.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dealers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone', 20)->unique();
            $table->string('email')->nullable();
            $table->string('password');
            $table->string('photo')->nullable();
            $table->string('nid_number', 50)->nullable();
            $table->string('nid_front')->nullable();
            $table->string('nid_back')->nullable();
            $table->string('bank_slip')->nullable();
            $table->text('address');
            $table->foreignId('district_id')->constrained()->restrictOnDelete();
            $table->foreignId('thana_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('union_id')->nullable()->constrained()->restrictOnDelete();
            $table->enum('level', ['district', 'thana', 'union'])->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'suspended'])->default('pending');
            $table->text('admin_note')->nullable();
            $table->foreignId('created_by_dealer_id')->nullable()->constrained('dealers')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();

            $table->index(['level', 'status']);
        });

        Schema::create('dealer_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dealer_id')->constrained()->cascadeOnDelete();
            $table->date('month'); // always the 1st of the month
            $table->decimal('amount', 14, 2);
            $table->string('note')->nullable();
            $table->string('set_by_type', 10)->default('admin'); // admin | dealer
            $table->unsignedBigInteger('set_by_id')->nullable();
            $table->decimal('achieved_amount', 14, 2)->nullable();
            $table->text('settlement_note')->nullable();
            $table->timestamp('settled_at')->nullable();
            // No FK: `users` is MyISAM on this install, which can't be a foreign-key target.
            $table->unsignedBigInteger('settled_by')->nullable()->index();
            $table->timestamps();

            $table->unique(['dealer_id', 'month']);
            $table->index('month');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dealer_targets');
        Schema::dropIfExists('dealers');
    }
};
