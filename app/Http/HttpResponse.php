<?php

declare(strict_types=1);

namespace Positrom\Http;

final class HttpResponse
{
    public function __construct(
        public readonly int $status,
        public readonly string $body,
        public readonly array $headers = []
    ) {
    }

    public function json(): ?array
    {
        $data = json_decode($this->body, true);
        return is_array($data) ? $data : null;
    }

    public function ok(): bool
    {
        return $this->status >= 200 && $this->status < 300;
    }
}
