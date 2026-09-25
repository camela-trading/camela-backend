<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicStorageRouteTest extends TestCase
{
    public function test_it_serves_public_files_with_url_encoded_names(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put(
            'products/Medical Breakthroughs & Scientifically proven.png',
            'image-content'
        );

        $this->get('/storage/products/Medical%20Breakthroughs%20%26%20Scientifically%20proven.png')
            ->assertOk();
    }

    public function test_it_returns_not_found_for_missing_public_files(): void
    {
        Storage::fake('public');

        $this->get('/storage/products/not-found.png')
            ->assertNotFound();
    }
}
