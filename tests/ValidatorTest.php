<?php
declare(strict_types=1);

namespace Keycheat\Tests;

use Keycheat\Validator;
use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{
    private function validator(): Validator
    {
        return new Validator(dirname(__DIR__) . '/keybindings.schema.json');
    }

    public function testValidModelHasNoErrors(): void
    {
        $json = (string)json_encode([
            'sources' => [
                [
                    'id' => 'demo',
                    'name' => 'Demo',
                    'bindings' => [
                        ['keys' => ['Ctrl+A'], 'description' => 'Select all'],
                    ],
                ],
            ],
        ]);

        $this->assertSame([], $this->validator()->validate($json));
    }

    public function testMissingSourceNameIsReported(): void
    {
        $errors = $this->validator()->validate('{"sources":[{"id":"x","bindings":[]}]}');

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('/sources/0', $errors[0]);
        $this->assertStringContainsString('name', implode("\n", $errors));
    }

    public function testEmptyKeysIsReported(): void
    {
        $json = '{"sources":[{"id":"x","name":"X","bindings":[{"keys":[],"description":"d"}]}]}';

        $errors = $this->validator()->validate($json);

        $this->assertStringContainsString('/sources/0/bindings/0/keys', implode("\n", $errors));
    }

    public function testInvalidJsonIsReported(): void
    {
        $errors = $this->validator()->validate('{not json');

        $this->assertCount(1, $errors);
        $this->assertStringStartsWith('Invalid JSON:', $errors[0]);
    }
}
