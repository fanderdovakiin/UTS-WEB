<?php

namespace Tests\Unit;

use App\Support\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_kali_desimal_presisi_penuh(): void
    {
        // Kasus klasik floating-point: 1.15 × 100 = 114.99999999999999 di float.
        $this->assertSame('115.00', Money::kali('1.15', 100));

        // 3333.33 × 3 = 9999.989999999998 di float, tepat 9999.99 dengan desimal.
        $this->assertSame('9999.99', Money::kali('3333.33', 3));
    }

    public function test_tambah_menghindari_error_float(): void
    {
        // 0.10 + 0.20 = 0.30000000000000004 di float.
        $this->assertSame('0.30', Money::tambah('0.10', '0.20'));
        $this->assertSame('0.50', Money::tambah(Money::tambah('0.10', '0.20'), '0.20'));
    }

    public function test_input_int_dinormalkan_ke_desimal(): void
    {
        $this->assertSame('30000.00', Money::kali(10000, 3));
        $this->assertSame('10000.00', Money::tambah(5000, 5000));
    }

    public function test_angka_besar_tetap_presisi(): void
    {
        $this->assertSame('199999999999.98', Money::kali('99999999999.99', 2));
        $this->assertSame('999999999999.99', Money::tambah('999999999999.98', '0.01'));
    }

    public function test_kali_dengan_nol(): void
    {
        $this->assertSame('0.00', Money::kali('12500.00', 0));
    }
}
