<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $category = Category::where('slug', 'molecular-hydrogen')->first();

        if (!$category) {
            return;
        }

        $product = Product::updateOrCreate(
            ['sku' => 'PSample001'],
            [
                'category_id' => $category->id,
                'title' => 'LivePure Molecular Hydrogen Bottle',
                'slug' => 'livepure-molecular-hydrogen-bottle',
                'short_description' => 'A sample featured product for the molecular hydrogen collection.',
                'description' => 'Sample product seeded for the frontend catalog and product detail pages.',
                'price' => 199.00,
                'cost_price' => 120.00,
                'stock' => 25,
                'low_stock_alert' => 5,
                'weight' => 1.20,
                'status' => 'ACTIVE',
                'featured' => true,
                'seo_title' => 'LivePure Molecular Hydrogen Bottle',
                'seo_description' => 'Sample featured product for integration testing.',
            ]
        );

        ProductImage::updateOrCreate(
            ['product_id' => $product->id, 'is_primary' => true],
            [
                'image_path' => 'products/livepure-product.png',
                'sort_order' => 0,
            ]
        );

        ProductImage::updateOrCreate(
            ['product_id' => $product->id, 'is_primary' => false, 'sort_order' => 1],
            [
                'image_path' => 'products/pZRLe003ReJOmeBIb2l9ejL5JyefKNfLztSAxvUN.jpg',
                'alt_text' => 'LivePure Molecular Hydrogen Bottle alternate view',
                'sort_order' => 1,
            ]
        );
    }
}
