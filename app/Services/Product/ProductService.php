<?php

namespace App\Services\Product;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class ProductService
{
    public function create(array $data): Product
    {
        return DB::transaction(function () use ($data) {

           $product = Product::create([

            'category_id'=>$data['category_id'],

            'title'=>$data['title'],

            'slug'=>Str::slug($data['title']),

            'sku'=>$data['sku'],

            'short_description'=>$data['short_description'] ?? null,

            'description'=>$data['description'],

            'price'=>$data['price'],

            'compare_price'=>$data['compare_price'] ?? null,

            'cost_price'=>$data['cost_price'] ?? null,

            'stock'=>$data['stock'],

            'low_stock_alert'=>$data['low_stock_alert'] ?? 5,

            'weight'=>$data['weight'] ?? null,

            'status'=>$data['status'],

            'featured'=>$data['featured'] ?? false,

            'seo_title'=>$data['seo_title'] ?? null,

            'seo_description'=>$data['seo_description'] ?? null,

        ]);

            if (!empty($data['images'])) {

                foreach ($data['images'] as $index => $image) {

                    $path = $image->store(
                        'products',
                        'public'
                    );

                    $product->images()->create([

                        'image_path' => $path,

                        'is_primary' => $index === 0,

                        'sort_order' => $index,

                    ]);
                }
            }

            return $product;

        });
    }

    public function getAll()
    {
        return Product::with([
            'category',
            'images'
        ])
        ->latest()
        ->paginate(10);
    }

    public function update(
        Product $product,
        array $data
    ): Product
    {
        return DB::transaction(function () use ($product, $data) {

            if (array_key_exists('title', $data)) {
                $data['slug'] = Str::slug($data['title']);
            }

            $product->update([

                'category_id'=>$data['category_id'] ?? $product->category_id,

                'title'=>$data['title'] ?? $product->title,

                'slug'=>$data['slug'] ?? $product->slug,

                'sku'=>$data['sku'] ?? $product->sku,

                'short_description'=>$data['short_description'] ?? $product->short_description,

                'description'=>$data['description'] ?? $product->description,

                'price'=>$data['price'] ?? $product->price,

                'compare_price'=>$data['compare_price'] ?? $product->compare_price,

                'cost_price'=>$data['cost_price'] ?? $product->cost_price,

                'stock'=>$data['stock'] ?? $product->stock,

                'low_stock_alert'=>$data['low_stock_alert'] ?? $product->low_stock_alert,

                'weight'=>$data['weight'] ?? $product->weight,

                'status'=>$data['status'] ?? $product->status,

                'featured'=>$data['featured'] ?? $product->featured,

                'seo_title'=>$data['seo_title'] ?? $product->seo_title,

                'seo_description'=>$data['seo_description'] ?? $product->seo_description,

            ]);

            return $product;
        });
    }

    public function delete(Product $product): void
    {
        $product->delete();
    }

    public function duplicate(Product $product): Product
    {
        return $this->duplicateMany(new Collection([$product]))->first();
    }

    public function duplicateByIds(array $productIds): Collection
    {
        $products = Product::query()
            ->with('images')
            ->whereKey($productIds)
            ->get();

        return $this->duplicateMany($products);
    }

    public function updateStatusByIds(array $productIds, string $status): Collection
    {
        return DB::transaction(function () use ($productIds, $status) {
            $products = Product::query()
                ->whereKey($productIds)
                ->lockForUpdate()
                ->get();

            if ($products->pluck('status')->unique()->count() !== 1) {
                throw ValidationException::withMessages([
                    'product_ids' => 'Only products with the same status can be updated together.',
                ]);
            }

            Product::whereKey($productIds)->update(['status' => $status]);

            return Product::with(['category', 'images'])
                ->whereKey($productIds)
                ->get();
        });
    }

    private function duplicateMany(Collection $products): Collection
    {
        $copiedImagePaths = [];

        try {
            return DB::transaction(function () use ($products, &$copiedImagePaths) {
                return $products->map(function (Product $source) use (&$copiedImagePaths) {
                    $source->loadMissing('images');
                    $title = $this->uniqueCopyTitle($source->title);

                    $duplicate = Product::create([
                        'category_id' => $source->category_id,
                        'title' => $title,
                        'slug' => $this->uniqueSlug($title),
                        'sku' => $this->uniqueCopySku($source->sku),
                        'short_description' => $source->short_description,
                        'description' => $source->description,
                        'price' => $source->price,
                        'compare_price' => $source->compare_price,
                        'cost_price' => $source->cost_price,
                        'stock' => $source->stock,
                        'low_stock_alert' => $source->low_stock_alert,
                        'weight' => $source->weight,
                        'status' => $source->status,
                        'featured' => $source->featured,
                        'seo_title' => $source->seo_title,
                        'seo_description' => $source->seo_description,
                    ]);

                    foreach ($source->images as $image) {
                        $newPath = $this->copyProductImage($image->image_path);
                        $copiedImagePaths[] = $newPath;

                        $duplicate->images()->create([
                            'image_path' => $newPath,
                            'alt_text' => $image->alt_text,
                            'sort_order' => $image->sort_order,
                            'is_primary' => $image->is_primary,
                        ]);
                    }

                    return $duplicate->load(['category', 'images']);
                });
            });
        } catch (\Throwable $exception) {
            if ($copiedImagePaths !== []) {
                Storage::disk('public')->delete($copiedImagePaths);
            }

            throw $exception;
        }
    }

    private function uniqueCopyTitle(string $title): string
    {
        $base = preg_replace('/ \(Copy(?: \d+)?\)$/', '', $title) ?: $title;
        $number = 1;

        do {
            $suffix = $number === 1 ? ' (Copy)' : " (Copy {$number})";
            $candidate = Str::limit($base, 255 - strlen($suffix), '') . $suffix;
            $number++;
        } while (Product::where('title', $candidate)->exists());

        return $candidate;
    }

    private function uniqueCopySku(string $sku): string
    {
        $base = preg_replace('/-COPY(?:-\d+)?$/i', '', $sku) ?: $sku;
        $number = 1;

        do {
            $suffix = $number === 1 ? '-COPY' : "-COPY-{$number}";
            $candidate = Str::limit($base, 255 - strlen($suffix), '') . $suffix;
            $number++;
        } while (Product::where('sku', $candidate)->exists());

        return $candidate;
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::limit(Str::slug($title), 240, '');
        $candidate = $base;
        $number = 2;

        while (Product::where('slug', $candidate)->exists()) {
            $candidate = "{$base}-{$number}";
            $number++;
        }

        return $candidate;
    }

    private function copyProductImage(string $path): string
    {
        $disk = Storage::disk('public');

        if (!$disk->exists($path)) {
            throw new RuntimeException('A product image could not be copied.');
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $newPath = 'products/' . pathinfo($path, PATHINFO_FILENAME)
            . '-copy-' . Str::uuid()
            . ($extension ? ".{$extension}" : '');

        if (!$disk->copy($path, $newPath)) {
            throw new RuntimeException('A product image could not be copied.');
        }

        return $newPath;
    }

    public function catalog(array $filters = [], bool $includeInactive = false)
    {
        $query = Product::query()
            ->with(['category', 'images']);

        if (!$includeInactive) {
            $query->where('status', 'ACTIVE');
        }

        if (!empty($filters['search'])) {

            $query->where(function ($q) use ($filters) {

                $q->where('title', 'like', '%' . $filters['search'] . '%')
                ->orWhere('description', 'like', '%' . $filters['search'] . '%');

            });

        }

        if (!empty($filters['category'])) {

            $query->whereHas('category', function ($q) use ($filters) {

                $q->where('name', $filters['category']);

            });

        }

        if (!empty($filters['featured'])) {

            $query->where('featured', true);

        }

        if (!empty($filters['sort'])) {

            switch ($filters['sort']) {

                case 'price_asc':
                    $query->orderBy('price');
                    break;

                case 'price_desc':
                    $query->orderByDesc('price');
                    break;

                case 'latest':
                    $query->latest();
                    break;

                default:
                    $query->latest();
                    break;
            }

        } else {

            $query->latest();

        }
        
        return $query->paginate(12);
    }

    public function findBySlug(string $slug): Product
    {
        return Product::with([

            'category',

            'images'

        ])
        ->where('slug', $slug)
        ->where('status', 'ACTIVE')
        ->firstOrFail();
    }
    
    public function related(Product $product)
    {
        return Product::with([

            'images',

            'category'

        ])
        ->where('category_id', $product->category_id)

        ->where('id', '!=', $product->id)

        ->where('status', 'ACTIVE')

        ->take(4)

        ->get();
    }

    public function search(string $keyword)
    {
        return Product::with([
            'category',
            'images'
        ])
        ->where('status', 'ACTIVE')
        ->where(function ($q) use ($keyword) {

            $q->where('title', 'like', "%{$keyword}%")
            ->orWhere('description', 'like', "%{$keyword}%");

        })
        ->take(10)
        ->get();
    }

    public function searchSuggestions(string $keyword)
    {
        return Product::query()

            ->where('status', 'ACTIVE')

            ->where('title', 'like', "%{$keyword}%")

            ->limit(8)

            ->get([

                'id',

                'title',

                'slug',

                'price'

            ]);
    }

    public function find($value): Product
    {
        return Product::with([
            'category',
            'images'
        ])
        ->where('status', 'ACTIVE')
        ->where(function ($query) use ($value) {

            if (is_numeric($value)) {
                $query->where('id', $value);
            } else {
                $query->where('slug', $value);
            }

        })
        ->firstOrFail();
    }

    public function byCategory(string $category)
    {
        return Product::with([
                'category',
                'images'
            ])
            ->whereHas('category', function ($q) use ($category) {

                $q->whereRaw('LOWER(slug) = ?', [strtolower($category)])
                ->orWhereRaw('LOWER(name) = ?', [strtolower($category)]);

            })
            ->where('status', 'ACTIVE')
            ->paginate(12);
    }

}
