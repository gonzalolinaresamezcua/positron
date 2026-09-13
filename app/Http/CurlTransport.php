<?php

declare(strict_types=1);

namespace Positrom\Http;

final class CurlTransport implements HttpTransport
{
    public function __construct(private int $timeout = 60)
    {
    }

    public function request(string $method, string $url, array $headers = [], ?string $body = null): HttpResponse
    {
        $ch = curl_init($url);
        if ($ch === false) {
            throw new \RuntimeException('No se pudo iniciar cURL.');
        }
        $headerLines = [];
        foreach ($headers as $name => $value) {
            $headerLines[] = $name . ': ' . $value;
        }
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_HTTPHEADER => $headerLines,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HEADER => true,
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        $raw = curl_exec($ch);
        if ($raw === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException('Error de red: ' . $error);
        }
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);
        $headerBlob = substr((string) $raw, 0, $headerSize);
        $bodyOut = substr((string) $raw, $headerSize);
        $parsed = [];
        foreach (preg_split("/\r\n|\n|\r/", $headerBlob) ?: [] as $line) {
            if (str_contains($line, ':')) {
                [$k, $v] = explode(':', $line, 2);
                $parsed[strtolower(trim($k))] = trim($v);
            }
        }
        return new HttpResponse($status, $bodyOut, $parsed);
    }
}
