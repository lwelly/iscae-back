<?php
// app/Exceptions/AuthException.php

namespace App\Exceptions;

use Exception;

class AuthException extends Exception
{
    public function __construct(
        string          $message,
        private string  $errorCode  = 'AUTH_ERROR',
        int             $httpStatus = 401
    ) {
        parent::__construct($message, $httpStatus);
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getHttpStatus(): int
    {
        return $this->getCode();
    }

    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'code'    => $this->errorCode,
        ], $this->getCode());
    }
}
