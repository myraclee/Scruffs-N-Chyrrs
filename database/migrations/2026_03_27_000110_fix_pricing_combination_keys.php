<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\OrderTemplate;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fix pricing combination keys to use numeric IDs instead of text names
        // Current database has:
        //   Option: Paper (ID: 1)
        //   - Matte (type_id: 1)
        //   - Glossy (type_id: 2)
        // Pricings should use numeric keys: "1" and "2"

        $orderTemplate = OrderTemplate::find(1);
        if ($orderTemplate) {
            // Delete old pricings that have text keys
            DB::table('order_template_pricings')
                ->where('order_template_id', $orderTemplate->id)
                ->whereIn('combination_key', ['Matte', 'Glossy', 'glossy_2x2', 'glossy_3x3', 'glossy_4x4', 'matte_2x2', 'matte_3x3', 'matte_4x4', 'holographic_2x2', 'holographic_3x3', 'holographic_4x4'])
                ->delete();

            // Create new pricings with numeric keys
            // Based on the single Paper option with Matte (1) and Glossy (2)
            $pricings = [
                ['combination_key' => '1', 'price' => 1.00],  // Matte
                ['combination_key' => '2', 'price' => 2.00],  // Glossy
            ];

            foreach ($pricings as $pricing) {
                DB::table('order_template_pricings')->insert([
                    'order_template_id' => $orderTemplate->id,
                    'combination_key' => $pricing['combination_key'],
                    'price' => $pricing['price'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to previous pricings (this would need to be defined if needed)
        // For now, just clear any numeric keys
        DB::table('order_template_pricings')
            ->whereIn('combination_key', ['1', '2'])
            ->delete();
    }
};
