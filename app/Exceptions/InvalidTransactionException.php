<?php

namespace App\Exceptions;

use RuntimeException;

/** Dilempar saat status transaksi tidak memperbolehkan aksi yang diminta. */
class InvalidTransactionException extends RuntimeException {}
