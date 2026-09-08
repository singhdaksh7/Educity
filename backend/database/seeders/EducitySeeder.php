<?php

namespace Database\Seeders;

use App\Models\Gallery;
use App\Models\Program;
use App\Models\SiteSetting;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class EducitySeeder extends Seeder
{
    public function run(): void
    {
        $this->seedAdministrator();
        $this->seedPrograms();
        $this->seedTestimonials();
        $this->seedGallery();
        $this->seedSiteSettings();
    }

    private function seedAdministrator(): void
    {
        $email = config('educity.admin.email');
        $password = config('educity.admin.password');
        $name = config('educity.admin.name');

        // Diagnostic-only: reports which named variable resolved empty, never its value.
        $missing = collect(['ADMIN_NAME' => $name, 'ADMIN_EMAIL' => $email, 'ADMIN_PASSWORD' => $password])
            ->filter(fn ($value) => blank($value))
            ->keys();

        if ($missing->isNotEmpty()) {
            $this->command?->warn('Administrator seed skipped: missing environment variable(s): '.$missing->implode(', ').'.');

            return;
        }

        // updateOrCreate (not firstOrCreate): the task brief explicitly requires that
        // repeated deployment updates the seeded administrator's password from the
        // current env var, keeps it active, and assigns super_admin. This only ever
        // touches the single row matched by ADMIN_EMAIL — no other user is affected.
        User::updateOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => Hash::make($password), 'role' => 'super_admin', 'is_active' => true]
        );
    }

    private function seedPrograms(): void
    {
        $programs = [
            ['title' => 'Graduation Degree', 'slug' => 'graduation-degree', 'short_description' => 'Foundation for future leaders.', 'degree_type' => 'Undergraduate'],
            ['title' => 'Post Graduation', 'slug' => 'post-graduation', 'short_description' => 'Advanced academic programmes.', 'degree_type' => 'Postgraduate'],
            ['title' => 'Professional Certificate', 'slug' => 'professional-certificate', 'short_description' => 'Career-focused learning.', 'degree_type' => 'Certificate'],
        ];

        foreach ($programs as $index => $program) {
            Program::withTrashed()->firstOrCreate(
                ['slug' => $program['slug']],
                $program + ['display_order' => $index + 1, 'is_active' => true]
            );
        }
    }

    private function seedTestimonials(): void
    {
        $names = ['William Jackson', 'Sarika Panwar', 'Vanshika Goyal', 'Shruti Sharma'];

        foreach ($names as $index => $name) {
            Testimonial::withTrashed()->firstOrCreate(
                ['student_name' => $name],
                [
                    'quote' => 'A supportive and inspiring learning environment with meaningful opportunities for growth.',
                    'course_or_role' => 'Educity Student',
                    'display_order' => $index + 1,
                    'is_active' => true,
                ]
            );
        }
    }

    private function seedGallery(): void
    {
        $items = [
            ['title' => 'Campus Front View', 'alt_text' => 'Educity campus front view'],
            ['title' => 'Library', 'alt_text' => 'Students studying in the library'],
            ['title' => 'Graduation Day', 'alt_text' => 'Students celebrating graduation day'],
        ];

        foreach ($items as $index => $item) {
            Gallery::withTrashed()->firstOrCreate(
                ['title' => $item['title']],
                $item + ['image_path' => null, 'display_order' => $index + 1, 'is_active' => true]
            );
        }
    }

    private function seedSiteSettings(): void
    {
        $settings = [
            'institution_name' => 'Educity',
            'contact_email' => 'contact@educity.example',
            'contact_phone' => '+91 123-456-7890',
            'address' => 'Aanand Vihar, near Delhi',
            'hero_title' => 'We ensure better education for a better world',
            'hero_description' => 'Our curriculum empowers students with knowledge, skills and experience.',
            'hero_button_label' => 'Explore Programs',
            'hero_button_link' => '/programs',
            'about_title' => 'About Educity',
            'about_description' => 'Educity is committed to delivering accessible, high-quality education.',
            'footer_text' => '© Educity, All Rights Reserved',
        ];

        foreach ($settings as $key => $value) {
            SiteSetting::firstOrCreate(
                ['key' => $key],
                ['value' => $value, 'type' => 'text', 'is_public' => true]
            );
        }
    }
}
