<?php

namespace App\Exceptions;

use Exception;

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
}
