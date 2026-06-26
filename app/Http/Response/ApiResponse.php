<?php

namespace App\Http\Response;

trait ApiResponse
{
    protected function success(mixed $data = null, string $message = 'Berhasil', int $code = 200, array $meta = []): \Illuminate\Http\JsonResponse
    {
        $response = ['success' => true, 'message' => $message, 'data' => $data];

        if (!empty($meta)) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $code);
    }

    protected function created(mixed $data = null, string $message = 'Berhasil dibuat'): \Illuminate\Http\JsonResponse
    {
        return $this->success($data, $message, 201);
    }

    protected function error(string $message = 'Terjadi kesalahan', int $code = 400, array $errors = []): \Illuminate\Http\JsonResponse
    {
        $response = ['success' => false, 'message' => $message];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    protected function notFound(string $message = 'Data tidak ditemukan'): \Illuminate\Http\JsonResponse
    {
        return $this->error($message, 404);
    }

    protected function validationError(string $message, array $errors): \Illuminate\Http\JsonResponse
    {
        return $this->error($message, 422, $errors);
    }

    protected function paginated($paginator): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Berhasil',
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }
}
