<?php

namespace App\Exceptions;

use Exception;

class RoomNotAvailableException extends Exception
{
    public function __construct(string $message = 'Ruangan tidak tersedia untuk dipesan')
    {
        parent::__construct($message);
    }

    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
        ], 422);
    }
}
