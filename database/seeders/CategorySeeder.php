<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Product::where('sku', 'PSample001')->delete();
        Category::whereIn('slug', ['samplecategory1', 'samplecategory2'])->delete();

        Category::updateOrCreate(['slug' => 'molecular-hydrogen'], [
            'name' => 'Molecular Hydrogen',
            'description' => 'Discover our molecular hydrogen wellness range.',
            'image' => 'storage/products/molecular-hydrogen.jpeg',
            'banner' => 'storage/products/molecular-hydrogen.jpeg',
            'images' => ['storage/products/molecular-hydrogen.jpeg'],
            'landing_page' => '/molecular-hydrogen',
            'sort_order' => 1,
            'seo_title' => 'Molecular Hydrogen',
            'seo_description' => 'Discover our molecular hydrogen wellness range.',
            'is_active' => true,
        ]);

        Category::updateOrCreate(['slug' => 'peptide'], [
            'name' => 'Peptide',
            'description' => 'Explore our peptide wellness range.',
            'image' => 'storage/products/Peptide product - Dark Blue color.png',
            'banner' => 'storage/products/Peptide product - Dark Blue color.png',
            'images' => ['storage/products/Peptide product - Dark Blue color.png'],
            'landing_page' => '/peptide',
            'sort_order' => 2,
            'seo_title' => 'Peptide',
            'seo_description' => 'Explore our peptide wellness range.',
            'is_active' => true,
        ]);

    }

}
