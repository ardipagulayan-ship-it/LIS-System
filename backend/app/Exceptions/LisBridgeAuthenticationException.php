<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

/**
 * Thrown by VerifyLisBridgeApiKey when authentication fails.
 *
 * Renders as HTTP 401 Unauthorized with a plain JSON body.
 *
 * @see VerifyLisBridgeApiKey
 */
class LisBridgeAuthenticationException extends Exception
{
    /**
     * The message is deliberately generic — never leak key existence or expiry detail.
     *
     * @param  string $message
     */
    public function __construct(string $message = 'Unauthorized')
    {
        parent::__construct($message, 401);
    }

    /**
     * Render the exception as a JSON 401 response.
     *
     * @return JsonResponse
     */
    public function render(): JsonResponse
    {
        return response()->json([
            'error'   => 'Unauthorized',
            'message' => $this->getMessage(),
        ], 401);
    }
}
