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
        Schema::create('material_consumptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained('materials')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('order_template_option_type_id')
                ->nullable()
                ->constrained('order_template_option_types')
                ->nullOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();

            $table->index('material_id');
            $table->index('product_id');
            $table->index('order_template_option_type_id');
            $table->unique(
                ['material_id', 'product_id', 'order_template_option_type_id'],
                'material_consumptions_unique',
            );
        });

        if (! Schema::hasTable('product_material')) {
            return;
        }

        $now = now();
        $legacyRows = DB::table('product_material')
            ->select(['material_id', 'product_id', 'quantity'])
            ->get();

        foreach ($legacyRows as $legacyRow) {
            $exists = DB::table('material_consumptions')
                ->where('material_id', (int) $legacyRow->material_id)
                ->where('product_id', (int) $legacyRow->product_id)
                ->whereNull('order_template_option_type_id')
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('material_consumptions')->insert([
                'material_id' => (int) $legacyRow->material_id,
                'product_id' => (int) $legacyRow->product_id,
                'order_template_option_type_id' => null,
                'quantity' => max(1, (int) $legacyRow->quantity),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_consumptions');
    }
};
