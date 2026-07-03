<?php

namespace App\Http\Response;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;

trait ApiResponse
{
    protected function success(mixed $data = null, string $message = 'Berhasil', int $code = 200, array $meta = []): JsonResponse
    {
        $response = ['success' => true, 'message' => $message, 'data' => $data];

        if (! empty($meta)) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $code);
    }

    protected function created(mixed $data = null, string $message = 'Berhasil dibuat'): JsonResponse
    {
        return $this->success($data, $message, 201);
    }

    protected function error(string $message = 'Terjadi kesalahan', int $code = 400, array $errors = []): JsonResponse
    {
        $response = ['success' => false, 'message' => $message];

        if (! empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    protected function notFound(string $message = 'Data tidak ditemukan'): JsonResponse
    {
        return $this->error($message, 404);
    }

    protected function validationError(string $message, array $errors): JsonResponse
    {
        return $this->error($message, 422, $errors);
    }

    /**
     * Accepts either a raw LengthAwarePaginator or an API Resource collection wrapping one
     * (e.g. SomeResource::collection($paginator)). When collecting resources, the paginator
     * passed to ::collection() is preserved (with items replaced by wrapped resources) as
     * $collection->resource, so pagination meta is read from there either way.
     */
    protected function paginated($paginator): JsonResponse
    {
        if ($paginator instanceof ResourceCollection) {
            $inner = $paginator->resource;

            return response()->json([
                'success' => true,
                'message' => 'Berhasil',
                'data' => $paginator->toArray(request()),
                'meta' => [
                    'current_page' => $inner->currentPage(),
                    'last_page' => $inner->lastPage(),
                    'per_page' => $inner->perPage(),
                    'total' => $inner->total(),
                ],
            ]);
        }

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
