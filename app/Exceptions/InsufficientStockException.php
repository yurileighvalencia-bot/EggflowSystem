<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InsufficientStockException extends Exception
{
    protected $categoryId;
    protected $requestedQuantity;
    protected $availableQuantity;

    public function __construct(
        string $message = "Insufficient stock available",
        int $categoryId = 0,
        int $requestedQuantity = 0,
        int $availableQuantity = 0
    ) {
        parent::__construct($message);
        $this->categoryId = $categoryId;
        $this->requestedQuantity = $requestedQuantity;
        $this->availableQuantity = $availableQuantity;
    }

    public function getCategoryId(): int
    {
        return $this->categoryId;
    }

    public function getRequestedQuantity(): int
    {
        return $this->requestedQuantity;
    }

    public function getAvailableQuantity(): int
    {
        return $this->availableQuantity;
    }

    public function getShortfall(): int
    {
        return max(0, $this->requestedQuantity - $this->availableQuantity);
    }

    /**
     * Render the exception as an HTTP response.
     */
    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'errors' => [
                'stock' => [$this->getMessage()],
            ],
            'category_id' => $this->categoryId,
            'requested' => $this->requestedQuantity,
            'available' => $this->availableQuantity,
        ], 422);
    }
}
