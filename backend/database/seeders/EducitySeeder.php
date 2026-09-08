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
        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');
        $name = env('ADMIN_NAME', 'Administrator');

        if (! $email || ! $password) {
            $this->command?->warn('Administrator seed skipped: set ADMIN_NAME, ADMIN_EMAIL and ADMIN_PASSWORD in .env.');

            return;
        }

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
            Program::updateOrCreate(
                ['slug' => $program['slug']],
                $program + ['display_order' => $index + 1, 'is_active' => true]
            );
        }
    }

    private function seedTestimonials(): void
    {
        $names = ['William Jackson', 'Sarika Panwar', 'Vanshika Goyal', 'Shruti Sharma'];

        foreach ($names as $index => $name) {
            Testimonial::updateOrCreate(
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
            Gallery::updateOrCreate(
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
            SiteSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'type' => 'text', 'is_public' => true]
            );
        }
    }
}
