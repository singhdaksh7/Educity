<?php

namespace Tests\Feature;

use App\Models\Program;
use App\Models\Testimonial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_program_list_only_returns_active_programs(): void
    {
        Program::create(['title' => 'Active programme', 'slug' => 'active-programme', 'short_description' => 'A programme', 'degree_type' => 'Degree', 'is_active' => true]);
        Program::create(['title' => 'Hidden programme', 'slug' => 'hidden-programme', 'short_description' => 'A programme', 'degree_type' => 'Degree', 'is_active' => false]);

        $response = $this->getJson('/api/v1/programs');

        $response->assertOk()->assertJsonPath('success', true)->assertJsonCount(1, 'data');
    }

    public function test_public_testimonials_only_returns_active_records(): void
    {
        Testimonial::create(['student_name' => 'Active student', 'quote' => 'A useful testimonial.', 'is_active' => true]);
        Testimonial::create(['student_name' => 'Hidden student', 'quote' => 'A hidden testimonial.', 'is_active' => false]);

        $response = $this->getJson('/api/v1/testimonials');

        $response->assertOk()->assertJsonCount(1, 'data');
    }
}
