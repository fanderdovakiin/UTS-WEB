<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_tamu_dialihkan_ke_halaman_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get(route('products.index'))->assertRedirect(route('login'));
    }

    public function test_login_berhasil_dengan_kredensial_valid(): void
    {
        $user = User::factory()->admin()->create(['email' => 'admin@contoh.test']);

        $response = $this->post(route('login.attempt'), [
            'email' => 'admin@contoh.test',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_gagal_dengan_password_salah(): void
    {
        User::factory()->create(['email' => 'staff@contoh.test']);

        $response = $this->from(route('login'))->post(route('login.attempt'), [
            'email' => 'staff@contoh.test',
            'password' => 'password-salah',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString(
            'Email atau password salah.',
            session('errors')->first('email')
        );
        $this->assertGuest();
    }

    public function test_login_dibatasi_setelah_banyak_percobaan_gagal(): void
    {
        User::factory()->create(['email' => 'target@contoh.test']);

        // 5 percobaan gagal pertama diterima (dan dihitung), lalu diblokir.
        foreach (range(1, 5) as $i) {
            $this->post(route('login.attempt'), [
                'email' => 'target@contoh.test',
                'password' => 'salah-'.$i,
            ]);
        }

        $response = $this->post(route('login.attempt'), [
            'email' => 'target@contoh.test',
            'password' => 'password-benar-tapi-diblokir',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString(
            'Terlalu banyak percobaan',
            session('errors')->first('email')
        );

        // Percobaan dengan kredensial yang benar pun tetap diblokir sampai jeda waktu.
        $this->post(route('login.attempt'), [
            'email' => 'target@contoh.test',
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        // Setelah jeda, login berhasil kembali.
        RateLimiter::clear('login:target@contoh.test|127.0.0.1');
        $this->post(route('login.attempt'), [
            'email' => 'target@contoh.test',
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));
    }

    public function test_logout_mengakhiri_sesi(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
