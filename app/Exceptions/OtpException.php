<?php
// app/Exceptions/OtpException.php

namespace App\Exceptions;

use Exception;

class OtpException extends Exception
{
    public function __construct(
        string         $message,
        private string $errorCode = 'OTP_ERROR',
        int            $status    = 422
    ) {
        parent::__construct($message, $status);
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
