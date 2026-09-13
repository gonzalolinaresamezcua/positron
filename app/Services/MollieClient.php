<?php

declare(strict_types=1);

namespace Positron\Services;

use Positron\Core\Config;
use Positron\Http\CurlTransport;
use Positron\Http\HttpTransport;

final class MollieClient
{
    public function __construct(private ?HttpTransport $transport = null)
    {
        $this->transport ??= new CurlTransport(30);
    }

    public function configured(): bool
    {
        $key = $this->apiKey();
        return $key !== '' && !Config::isPlaceholderSecret($key);
    }

    public function apiKey(): string
    {
        return trim((string) Config::get('mollie.key', ''));
    }

    public function baseUrl(): string
    {
        return rtrim((string) Config::get('mollie.base', 'https://api.mollie.com/v2'), '/');
    }

    /** @param array<string, mixed> $payload */
    public function createCustomer(array $payload): array
    {
        return $this->api('POST', '/customers', $payload);
    }

    /** @param array<string, mixed> $payload */
    public function createPayment(array $payload): array
    {
        return $this->api('POST', '/payments', $payload);
    }

    public function getPayment(string $id): array
    {
        return $this->api('GET', '/payments/' . rawurlencode($id));
    }

    /** @param array<string, mixed> $payload */
    public function createSubscription(string $customerId, array $payload): array
    {
        return $this->api('POST', '/customers/' . rawurlencode($customerId) . '/subscriptions', $payload);
    }

    public function cancelSubscription(string $customerId, string $subscriptionId): array
    {
        return $this->api('DELETE', '/customers/' . rawurlencode($customerId) . '/subscriptions/' . rawurlencode($subscriptionId));
    }

    public function listMandates(string $customerId): array
    {
        return $this->api('GET', '/customers/' . rawurlencode($customerId) . '/mandates');
    }

    public function getSubscription(string $customerId, string $subscriptionId): array
    {
        return $this->api('GET', '/customers/' . rawurlencode($customerId) . '/subscriptions/' . rawurlencode($subscriptionId));
    }

    public function checkoutUrl(array $payment): ?string
    {
        return $payment['_links']['checkout']['href'] ?? null;
    }

    /** @param array<string, mixed>|null $payload */
    private function api(string $method, string $path, ?array $payload = null): array
    {
        if (!$this->configured()) {
            throw new MollieNotConfiguredException(
                'Mollie no está configurado. Un administrador debe guardar MOLLIE_API_KEY en Ajustes.'
            );
        }
        $body = $payload !== null ? json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
        $response = $this->transport->request($method, $this->baseUrl() . $path, [
            'Authorization' => 'Bearer ' . $this->apiKey(),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ], $body);
        $json = $response->json();
        if (!$response->ok() || $json === null) {
            $hint = $json['detail'] ?? $json['title'] ?? mb_substr($response->body, 0, 240);
            throw new MollieRequestException('Mollie: ' . $hint, $response->status);
        }
        return $json;
    }
}

class MollieNotConfiguredException extends \RuntimeException
{
}

class MollieRequestException extends \RuntimeException
{
    public function __construct(string $message, public readonly int $httpStatus = 0)
    {
        parent::__construct($message);
    }
}
