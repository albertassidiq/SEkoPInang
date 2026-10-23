<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KedaiKopiCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_and_authenticated_pages_render_after_upgrade(): void
    {
        foreach (['/', '/form', '/mitra', '/monitor'] as $path) {
            $this->get($path)->assertOk();
        }

        foreach (['/dashboard', '/kedai-kopi', '/peta', '/user/profile'] as $path) {
            $this->get($path)->assertRedirect('/login');
        }

        $this->actingAs(User::factory()->create());

        foreach (['/dashboard', '/peta', '/user/profile'] as $path) {
            $this->get($path)->assertOk();
        }
    }

    public function test_mandiri_submission_preserves_currency_conversion_and_public_privacy(): void
    {
        $this->post('/kedai-kopi', [
            'kode_kecamatan' => '010',
            'kode_kelurahan' => '001',
            'alamat' => 'Alamat pengujian sintetis',
            'latitude' => '0.9150000',
            'longitude' => '104.4500000',
            'nama_kedai' => 'Kedai Uji Mandiri',
            'nama_pemilik' => 'Pemilik Sintetis Rahasia',
            'jenis_kelamin' => 'L',
            'handphone' => '080000000001',
            'omzet' => 'Rp 1.500.000',
            'jumlah_pekerja' => 2,
            'stan_sewa' => 0,
            'tren_pekerja' => 'tetap',
            'sumber' => 'mandiri',
        ])->assertSessionHasNoErrors()->assertRedirect(route('form'));

        $this->assertDatabaseHas('kedai_kopi', [
            'nama_kedai' => 'Kedai Uji Mandiri',
            'omzet' => 1500000,
            'sumber' => 'mandiri',
            'kode_kota' => '2172',
        ]);

        $this->get('/monitor')->assertOk()
            ->assertSee('Kedai Uji Mandiri')
            ->assertDontSee('Pemilik Sintetis Rahasia')
            ->assertDontSee('080000000001');
    }

    public function test_mitra_submission_accepts_optional_owner_fields(): void
    {
        $this->post('/kedai-kopi/mitra', [
            'kode_kecamatan' => '020',
            'kode_kelurahan' => '001',
            'alamat' => 'Alamat pengujian sintetis mitra',
            'latitude' => '0.9150000',
            'longitude' => '104.4500000',
            'nama_kedai' => 'Kedai Uji Mitra',
            'sumber' => 'mitra',
        ])->assertSessionHasNoErrors()->assertRedirect(route('mitra'));

        $this->assertDatabaseHas('kedai_kopi', [
            'nama_kedai' => 'Kedai Uji Mitra',
            'nama_pemilik' => null,
            'sumber' => 'mitra',
        ]);

        $this->get('/monitor?sumber=mitra')->assertOk()->assertSee('Kedai Uji Mitra');
    }
}
