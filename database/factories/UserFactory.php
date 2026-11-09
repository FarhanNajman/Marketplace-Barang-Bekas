<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'no_hp' => '08' . fake()->unique()->numerify('##########'),
            'email_verified_at' => now(),
            'no_hp_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the model's phone number should be unverified.
     */
    public function unverifiedPhone(): static
    {
        return $this->state(fn (array $attributes) => [
            'no_hp_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the model has no email address.
     */
    public function withoutEmail(): static
    {
        return $this->state(fn (array $attributes) => [
            'email' => null,
            'email_verified_at' => null,
        ]);
    }
}
