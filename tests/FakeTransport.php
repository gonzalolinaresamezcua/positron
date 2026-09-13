<?php

declare(strict_types=1);

namespace Positron\Tests;

use Positron\Http\HttpResponse;
use Positron\Http\HttpTransport;

final class FakeTransport implements HttpTransport
{
    /** @var list<array{method:string,url:string,headers:array,body:?string}> */
    public array $calls = [];

    public function __construct(
        private int $status = 200,
        private array $json = [],
        private string $raw = ''
    ) {
    }

    public function request(string $method, string $url, array $headers = [], ?string $body = null): HttpResponse
    {
        $this->calls[] = compact('method', 'url', 'headers', 'body');
        $payload = $this->raw !== '' ? $this->raw : (string) json_encode($this->json);
        return new HttpResponse($this->status, $payload);
    }
}
