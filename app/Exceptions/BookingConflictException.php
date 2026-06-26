<?php

namespace App\Exceptions;

use Exception;

class BookingConflictException extends Exception
{
    public function __construct(string $message = 'Waktu yang dipilih bertabrakan dengan booking lain')
    {
        parent::__construct($message);
    }

    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'errors' => ['start_time' => [$this->getMessage()]],
        ], 409);
    }
}
