<?php
declare(strict_types=1);

namespace Keycheat;

use Opis\JsonSchema\Errors\ValidationError;
use Opis\JsonSchema\Validator as JsonValidator;

/**
 * Validates a normalized keybindings document against the bundled JSON Schema
 * (draft 2020-12).
 */
final class Validator
{
    private ?string $schemaJson = null;

    public function __construct(private readonly string $schemaPath)
    {
    }

    /**
     * @return list<string> validation errors; empty means the document is valid
     */
    public function validate(string $json): array
    {
        try {
            $data = json_decode($json, false, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return ['Invalid JSON: ' . $e->getMessage()];
        }

        $validator = new JsonValidator(null, 100, false);
        $validator->resolver()->registerRaw($this->schema(), 'keycheat://schema.json');

        $result = $validator->validate($data, 'keycheat://schema.json');
        if ($result->isValid()) {
            return [];
        }

        $errors = [];
        $this->collect($result->error(), $errors);
        return $errors;
    }

    /** @param list<string> $out */
    private function collect(ValidationError $error, array &$out): void
    {
        $sub = $error->subErrors();
        if ($sub !== []) {
            foreach ($sub as $child) {
                $this->collect($child, $out);
            }
            return;
        }

        $pointer = self::pointer($error->data()->fullPath());
        $out[] = ($pointer === '' ? '/' : $pointer) . ' ' . self::message($error);
    }

    /** @param array<int|string> $path */
    private static function pointer(array $path): string
    {
        if ($path === []) {
            return '';
        }

        $parts = array_map(
            static fn ($p): string => str_replace(['~', '/'], ['~0', '~1'], (string)$p),
            $path
        );

        return '/' . implode('/', $parts);
    }

    private static function message(ValidationError $error): string
    {
        $message = $error->message();
        foreach ($error->args() as $key => $value) {
            $replacement = is_array($value)
                ? implode(', ', array_map('strval', $value))
                : (string)$value;
            $message = str_replace('{' . $key . '}', $replacement, $message);
        }

        return $message;
    }

    private function schema(): string
    {
        if ($this->schemaJson === null) {
            $json = @file_get_contents($this->schemaPath);
            if ($json === false) {
                throw new \RuntimeException("Cannot read schema: {$this->schemaPath}");
            }
            $this->schemaJson = $json;
        }

        return $this->schemaJson;
    }
}
