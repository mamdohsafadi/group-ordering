<?php

namespace Tests\Feature;

use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_home_page_lists_active_restaurants(): void
    {
        Restaurant::factory()->create(['name' => 'Shawarma House']);
        Restaurant::factory()->inactive()->create(['name' => 'Closed Doors']);

        $this->get('/')
            ->assertStatus(200)
            ->assertSee('Shawarma House')
            ->assertDontSee('Closed Doors');
    }
}
