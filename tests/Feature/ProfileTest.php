<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create([
            'activo' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create([
            'activo' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create([
            'activo' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_usuario_inactivo_no_puede_acceder_al_perfil(): void
    {
        $user = User::factory()->create([
            'activo' => false,
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertRedirect(
            route('login')
        );

        $this->assertGuest();
    }


    public function test_usuario_inactivo_no_puede_actualizar_contrasena(): void
    {
        $user = User::factory()->create([
            'activo' => false,
        ]);

        $response = $this
            ->actingAs($user)
            ->put('/password', [
                'current_password' => 'password',
                'password' => 'NuevaClave123',
                'password_confirmation' => 'NuevaClave123',
            ]);

        $response->assertRedirect(
            route('login')
        );

        $this->assertGuest();
    }

}
