<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Extend `skus` with the catalogue/PIM fields shown on the "Thêm SKU đơn độc"
 * form (SPEC 0005): SPU link, category, GTINs, base unit, reference cost/sale
 * price, sale-start date, note, weight & dimensions, and an `image_url` slot
 * reserved for the (future) image upload. `cost_price` stays the reference cost.
 *
 * Also adds `cost_price` to `inventory_levels` — a per-warehouse cost so profit
 * reporting can use the warehouse-level figure when set, falling back to the
 * SKU-level reference cost.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('skus', function (Blueprint $table) {
            $table->string('spu_code')->nullable()->after('product_id');     // "Liên kết SPU" — grouping code
            $table->string('category')->nullable()->after('spu_code');
            $table->json('gtins')->nullable()->after('barcode');             // up to 10 GTIN/EAN/UPC codes
            $table->string('base_unit', 16)->default('PCS')->after('name');  // "Đơn vị cơ bản"
            $table->bigInteger('ref_sale_price')->nullable()->after('cost_price'); // "Giá bán tham khảo" (VND đồng)
            $table->date('sale_start_date')->nullable()->after('ref_sale_price');
            $table->text('note')->nullable()->after('sale_start_date');
            $table->unsignedInteger('weight_grams')->nullable()->after('note');
            $table->decimal('length_cm', 8, 2)->nullable()->after('weight_grams');
            $table->decimal('width_cm', 8, 2)->nullable()->after('length_cm');
            $table->decimal('height_cm', 8, 2)->nullable()->after('width_cm');
            $table->string('image_url')->nullable()->after('height_cm');     // reserved — see SPEC 0005 §7 (TODO upload)
        });

        Schema::table('inventory_levels', function (Blueprint $table) {
            $table->bigInteger('cost_price')->default(0)->after('available_cached'); // per-warehouse cost (VND đồng)
        });
    }

    public function down(): void
    {
        Schema::table('skus', function (Blueprint $table) {
            $table->dropColumn(['spu_code', 'category', 'gtins', 'base_unit', 'ref_sale_price', 'sale_start_date', 'note', 'weight_grams', 'length_cm', 'width_cm', 'height_cm', 'image_url']);
        });
        Schema::table('inventory_levels', function (Blueprint $table) {
            $table->dropColumn('cost_price');
        });
    }
};
