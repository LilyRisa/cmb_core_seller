<?php

namespace Database\Factories;

use CMBcoreSeller\Models\AdminUser;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AdminUser>
 */
class AdminUserFactory extends Factory
{
    protected $model = AdminUser::class;

    public function definition(): array
    {
        return [
            'username' => 'admin_'.Str::lower(Str::random(6)),
            'email' => $this->faker->unique()->safeEmail(),
            'name' => $this->faker->name(),
            'password' => 'password',
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
