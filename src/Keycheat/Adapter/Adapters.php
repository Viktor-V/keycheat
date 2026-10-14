<?php
declare(strict_types=1);

namespace Keycheat\Adapter;

/**
 * Registry of the built-in adapters.
 */
final class Adapters
{
    /**
     * @return list<Adapter>
     */
    public static function all(): array
    {
        return [
            new FlowLauncher(),
        ];
    }
}
