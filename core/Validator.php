<?php
declare(strict_types=1);

namespace App\Core;

/** Tiny fluent validator. Collects field errors; call fails()/errors() at the end. */
class Validator
{
    private array $errors = [];

    public function __construct(private array $data) {}

    public static function make(array $data): self
    {
        return new self($data);
    }

    public function required(string $field, ?string $label = null): self
    {
        $v = $this->data[$field] ?? null;
        if ($v === null || (is_string($v) && trim($v) === '')) {
            $this->errors[$field] = ($label ?? ucfirst($field)) . ' is required';
        }
        return $this;
    }

    public function email(string $field, ?string $label = null): self
    {
        $v = $this->data[$field] ?? '';
        if ($v !== '' && !filter_var($v, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = ($label ?? ucfirst($field)) . ' must be a valid email';
        }
        return $this;
    }

    public function numeric(string $field, ?string $label = null): self
    {
        $v = $this->data[$field] ?? '';
        if ($v !== '' && !is_numeric($v)) {
            $this->errors[$field] = ($label ?? ucfirst($field)) . ' must be a number';
        }
        return $this;
    }

    public function in(string $field, array $allowed, ?string $label = null): self
    {
        $v = $this->data[$field] ?? null;
        if ($v !== null && $v !== '' && !in_array($v, $allowed, true)) {
            $this->errors[$field] = ($label ?? ucfirst($field)) . ' is invalid';
        }
        return $this;
    }

    public function minLen(string $field, int $min, ?string $label = null): self
    {
        $v = (string) ($this->data[$field] ?? '');
        if ($v !== '' && strlen($v) < $min) {
            $this->errors[$field] = ($label ?? ucfirst($field)) . " must be at least $min characters";
        }
        return $this;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): string
    {
        return $this->errors === [] ? '' : (string) reset($this->errors);
    }
}
