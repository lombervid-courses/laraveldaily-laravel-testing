<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_redirects_to_products(): void
    {
        $password = 'password123';
        $user = User::create([
            'name' => 'User',
            'email' => 'demo@testing.com',
            'password' => Hash::make($password),
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => $password,
        ]);

        $response->assertRedirect('/products');

    }

    public function test_unauthenticated_user_cannot_access_products(): void
    {
        $response = $this->get('/products');

        $response->assertRedirect('/login');
    }
}
