<?php

namespace App\Exceptions;

use RuntimeException;

/** Dilempar saat stok produk tidak mencukupi untuk sebuah transaksi. */
class InsufficientStockException extends RuntimeException {}
