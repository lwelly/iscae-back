<?php
// app/Exceptions/ReclamationException.php

namespace App\Exceptions;

use Exception;

class ReclamationException extends Exception
{
    public function __construct(
        string          $message,
        private string  $errorCode = 'RECLAMATION_ERROR',
        int             $status    = 422
    ) {
        parent::__construct($message, $status);
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
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
