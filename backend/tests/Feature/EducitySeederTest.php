<?php

namespace Tests\Feature;

use App\Models\Gallery;
use App\Models\Program;
use App\Models\SiteSetting;
use App\Models\Testimonial;
use App\Models\User;
use Database\Seeders\EducitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EducitySeederTest extends TestCase
{
    use RefreshDatabase;

    private const ADMIN_EMAIL = 'admin@educity.test';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('educity.admin', [
            'email' => self::ADMIN_EMAIL,
            'password' => 'demo-password',
            'name' => 'Educity Administrator',
        ]);
    }

    public function test_seeder_is_idempotent_and_does_not_duplicate_demo_records(): void
    {
        $this->seed(EducitySeeder::class);
        Program::where('slug', 'graduation-degree')->update(['title' => 'Custom programme title']);
        $this->seed(EducitySeeder::class);

        $this->assertSame(1, User::where('email', self::ADMIN_EMAIL)->count());
        $this->assertSame(3, Program::count());
        $this->assertSame('Custom programme title', Program::where('slug', 'graduation-degree')->value('title'));
        $this->assertSame(4, Testimonial::count());
        $this->assertSame(3, Gallery::count());
        $this->assertSame(11, SiteSetting::count());
    }

    public function test_seeder_repairs_an_existing_administrators_password_and_reactivates_it(): void
    {
        $stale = User::factory()->create([
            'email' => self::ADMIN_EMAIL,
            'password' => bcrypt('stale-password'),
            'role' => 'admin',
            'is_active' => false,
        ]);

        $this->seed(EducitySeeder::class);

        $this->assertSame(1, User::where('email', self::ADMIN_EMAIL)->count());
        $admin = User::where('email', self::ADMIN_EMAIL)->first();
        $this->assertSame($stale->id, $admin->id);
        $this->assertTrue($admin->is_active);
        $this->assertSame('super_admin', $admin->role);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('demo-password', $admin->password));
    }

    public function test_seeder_skips_administrator_when_any_env_var_is_missing_and_does_not_throw(): void
    {
        config()->set('educity.admin', ['email' => null, 'password' => 'demo-password', 'name' => 'Educity Administrator']);

        $this->seed(EducitySeeder::class);

        $this->assertSame(0, User::count());
    }
}
