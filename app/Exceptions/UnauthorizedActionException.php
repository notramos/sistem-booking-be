<?php

namespace App\Exceptions;

use Exception;

class UnauthorizedActionException extends Exception
{
    public function __construct(string $message = 'Anda tidak memiliki izin untuk melakukan tindakan ini')
    {
        parent::__construct($message);
    }

    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
        ], 403);
    }
}
