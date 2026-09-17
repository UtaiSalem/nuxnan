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
            'name' => fake()->userName(),
            'username' => fake()->unique()->userName().Str::random(4),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),

            // คอลัมน์สองตัวนี้เป็น NOT NULL + UNIQUE บน MySQL จริง (ไม่มีค่าตั้งต้น)
            // sqlite ของเทสต์ปล่อยผ่านมาตลอด — บน MySQL จะตายด้วย 1364 Field ... doesn't have a default value
            // ใช้ตัวสร้างชุดเดียวกับทางสมัคร/สร้างผู้ใช้จริง เพื่อให้ข้อมูลเทสต์เหมือนของจริง
            'personal_code' => User::generateReferralCode(),
            'reference_code' => User::generateReferenceCode(),
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
}
