<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'surname' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'description' => fake()->sentence(),
            'password' => 'password',
            'organization_id' => null,  // can't set organization_id here because organization_id
                                        // is constrained by foreign key and organizations table is still empty
            'image' => fake()->imageUrl(),
            'invite_accepted_at' => fake()->dateTime(),
            'role_id' => Role::USER,
        ];
    }
}
