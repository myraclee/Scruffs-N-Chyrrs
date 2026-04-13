<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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

        $hasPaymentStatus = Schema::hasColumn('customer_order_groups', 'payment_status');
        $hasLegacyPaymentProof = Schema::hasColumn('customer_order_groups', 'payment_proof');
        $hasPaymentProofPath = Schema::hasColumn('customer_order_groups', 'payment_proof_path');

        if ($hasLegacyPaymentProof && ! $hasPaymentProofPath) {
            Schema::table('customer_order_groups', function (Blueprint $table): void {
                $table->string('payment_proof_path')->nullable()->after('payment_proof');
            });

            $hasPaymentProofPath = true;
        }

        if ($hasPaymentStatus) {
            foreach ([
                'Awaiting Payment' => 'awaiting_payment',
                'Waiting for Payment Confirmation' => 'waiting_payment_confirmation',
                'Payment Received' => 'payment_received',
                'Payment Cancelled' => 'payment_cancelled',
            ] as $legacyStatus => $canonicalStatus) {
                DB::table('customer_order_groups')
                    ->where('payment_status', $legacyStatus)
                    ->update(['payment_status' => $canonicalStatus]);
            }
        }

        if ($hasLegacyPaymentProof && $hasPaymentProofPath) {
            DB::table('customer_order_groups')
                ->whereNull('payment_proof_path')
                ->whereNotNull('payment_proof')
                ->update(['payment_proof_path' => DB::raw('payment_proof')]);

            DB::table('customer_order_groups')
                ->where('payment_proof_path', '')
                ->whereNotNull('payment_proof')
                ->update(['payment_proof_path' => DB::raw('payment_proof')]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Irreversible data normalization migration.
    }
};