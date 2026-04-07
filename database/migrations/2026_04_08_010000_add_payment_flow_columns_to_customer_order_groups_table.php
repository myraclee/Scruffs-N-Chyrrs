<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('customer_order_groups')) {
            return;
        }

        Schema::table('customer_order_groups', function (Blueprint $table) {
            if (! Schema::hasColumn('customer_order_groups', 'payment_status')) {
                $table->enum('payment_status', [
                    'awaiting_payment',
                    'waiting_payment_confirmation',
                    'payment_received',
                    'payment_cancelled',
                ])->default('awaiting_payment')->after('status');
                $table->index('payment_status');
            }

            if (! Schema::hasColumn('customer_order_groups', 'cancellation_reason')) {
                $table->enum('cancellation_reason', [
                    'owner_declined',
                    'customer_cancelled',
                ])->nullable()->after('payment_status');
                $table->index('cancellation_reason');
            }

            if (! Schema::hasColumn('customer_order_groups', 'payment_method')) {
                $table->string('payment_method')->nullable()->after('cancellation_reason');
            }

            if (! Schema::hasColumn('customer_order_groups', 'payment_reference_number')) {
                $table->string('payment_reference_number')->nullable()->after('payment_method');
            }

            if (! Schema::hasColumn('customer_order_groups', 'payment_proof_path')) {
                $table->string('payment_proof_path')->nullable()->after('payment_reference_number');
            }

            if (! Schema::hasColumn('customer_order_groups', 'payment_submitted_at')) {
                $table->timestamp('payment_submitted_at')->nullable()->after('payment_proof_path');
            }

            if (! Schema::hasColumn('customer_order_groups', 'payment_confirmed_at')) {
                $table->timestamp('payment_confirmed_at')->nullable()->after('payment_submitted_at');
            }

            if (! Schema::hasColumn('customer_order_groups', 'payment_confirmed_by')) {
                $table->foreignId('payment_confirmed_by')
                    ->nullable()
                    ->after('payment_confirmed_at')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('customer_order_groups', 'payment_confirmation_note')) {
                $table->text('payment_confirmation_note')->nullable()->after('payment_confirmed_by');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('customer_order_groups')) {
            return;
        }

        Schema::table('customer_order_groups', function (Blueprint $table) {
            if (Schema::hasColumn('customer_order_groups', 'payment_confirmed_by')) {
                $table->dropConstrainedForeignId('payment_confirmed_by');
            }

            if (Schema::hasColumn('customer_order_groups', 'payment_status')) {
                $table->dropIndex(['payment_status']);
            }

            if (Schema::hasColumn('customer_order_groups', 'cancellation_reason')) {
                $table->dropIndex(['cancellation_reason']);
            }

            $columnsToDrop = [];
            foreach ([
                'payment_status',
                'cancellation_reason',
                'payment_method',
                'payment_reference_number',
                'payment_proof_path',
                'payment_submitted_at',
                'payment_confirmed_at',
                'payment_confirmation_note',
            ] as $column) {
                if (Schema::hasColumn('customer_order_groups', $column)) {
                    $columnsToDrop[] = $column;
                }
            }

            if (! empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
