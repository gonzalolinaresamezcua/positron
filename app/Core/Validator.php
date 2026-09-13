<?php

declare(strict_types=1);

namespace Positron\Core;

final class Validator
{
    /** @var array<string, string> */
    private array $errors = [];

    /** @param array<string, mixed> $data */
    public function __construct(private array $data)
    {
    }

    public function required(string $field, string $label): self
    {
        $value = trim((string) ($this->data[$field] ?? ''));
        if ($value === '') {
            $this->errors[$field] = $label . ' es obligatorio.';
        }
        return $this;
    }

    public function email(string $field, string $label): self
    {
        $value = trim((string) ($this->data[$field] ?? ''));
        if ($value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = $label . ' no es un correo válido.';
        }
        return $this;
    }

    public function min(string $field, int $min, string $label): self
    {
        $value = (string) ($this->data[$field] ?? '');
        if ($value !== '' && mb_strlen($value) < $min) {
            $this->errors[$field] = $label . ' debe tener al menos ' . $min . ' caracteres.';
        }
        return $this;
    }

    public function max(string $field, int $max, string $label): self
    {
        $value = (string) ($this->data[$field] ?? '');
        if (mb_strlen($value) > $max) {
            $this->errors[$field] = $label . ' es demasiado largo.';
        }
        return $this;
    }

    public function same(string $field, string $other, string $label): self
    {
        if (($this->data[$field] ?? '') !== ($this->data[$other] ?? '')) {
            $this->errors[$field] = $label . ' no coincide.';
        }
        return $this;
    }

    public function numeric(string $field, string $label): self
    {
        $value = $this->data[$field] ?? '';
        if ($value !== '' && !is_numeric($value)) {
            $this->errors[$field] = $label . ' debe ser numérico.';
        }
        return $this;
    }

    public function ok(): bool
    {
        return $this->errors === [];
    }

    /** @return array<string, string> */
    public function errors(): array
    {
        return $this->errors;
    }

    public function first(): string
    {
        return (string) (array_values($this->errors)[0] ?? '');
    }
}
