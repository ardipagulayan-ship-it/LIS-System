<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

/**
 * Thrown by LisResultIngestionService when ingestion fails.
 *
 * Each exception carries:
 *   - $field: the wire-format field that caused the failure (for the error message)
 *   - $context: key-value debugging data (written to lis_ingest_log)
 *
 * Renders as HTTP 422 for validation-style failures (unknown analyzer, unknown sample,
 * unknown test_code, unknown control_id) and HTTP 503 for transient failures
 * (DB write conflict, downstream timeout).
 *
 * The wire format's send_to_lis() interprets:
 *   - 422 → hard-fail, no inner retry, row stays PENDING for next poll cycle
 *   - 503 → inner retry with exponential backoff (1s, 2s, 4s, 8s)
 *
 * @see LisResultIngestionService
 */
class LisIngestException extends Exception
{
    /**
     * The wire-format field that caused the failure.
     *
     * @var string|null
     */
    protected ?string $field = null;

    /**
     * Key-value context for debugging (written to lis_ingest_log).
     *
     * @var array
     */
    protected array $context = [];

    /**
     * HTTP status code to return.
     *
     * @var int
     */
    protected int $statusCode;

    /**
     * @param  string    $message
     * @param  array    $context    Key-value debugging data
     * @param  int      $statusCode  HTTP status (422 for validation, 503 for transient)
     * @param  string|null $field    Wire-format field name for operator error message
     */
    public function __construct(
        string $message,
        array $context = [],
        int $statusCode = 422,
        ?string $field = null
    ) {
        parent::__construct($message, $statusCode);
        $this->context    = $context;
        $this->statusCode = $statusCode;
        $this->field      = $field;
    }

    /**
     * @return string|null
     */
    public function getField(): ?string
    {
        return $this->field;
    }

    /**
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Build the JSON error response body.
     *
     * @return array
     */
    public function toResponsePayload(): array
    {
        $body = [
            'error'   => $this->getHttpStatusLabel(),
            'message' => $this->getMessage(),
        ];

        // Include field-level detail for 422 responses so the forwarder operator
        // can identify exactly which field is wrong
        if ($this->field !== null && $this->statusCode === 422) {
            $body['field'] = $this->field;
        }

        // Include context in development / when explicitly configured
        if (!empty($this->context) && config('app.debug')) {
            $body['context'] = $this->context;
        }

        return $body;
    }

    /**
     * Render as a JSON response.
     *
     * @return JsonResponse
     */
    public function render(): JsonResponse
    {
        return response()->json($this->toResponsePayload(), $this->statusCode);
    }

    /**
     * Human-readable HTTP status label for the error body.
     *
     * @return string
     */
    protected function getHttpStatusLabel(): string
    {
        return match ($this->statusCode) {
            422 => 'Unprocessable Entity',
            503 => 'Service Unavailable',
            400 => 'Bad Request',
            404 => 'Not Found',
            default => 'Error',
        };
    }
}
