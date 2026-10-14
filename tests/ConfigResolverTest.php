<?php
declare(strict_types=1);

namespace Keycheat\Tests;

use Keycheat\ConfigResolver;
use PHPUnit\Framework\TestCase;

final class ConfigResolverTest extends TestCase
{
    private string $home;

    protected function setUp(): void
    {
        $this->home = sys_get_temp_dir() . '/keycheat-test-' . bin2hex(random_bytes(4));
        mkdir($this->home, 0o777, true);
    }

    protected function tearDown(): void
    {
        $this->removeTree($this->home);
    }

    public function testReadsFileFromHome(): void
    {
        $dir = $this->home . '/AppData/Roaming/FlowLauncher/Settings';
        mkdir($dir, 0o777, true);
        file_put_contents($dir . '/Settings.json', '{"Hotkey":"Alt + Space"}');

        $resolver = new ConfigResolver($this->home, useChezmoi: false);

        $this->assertSame(
            '{"Hotkey":"Alt + Space"}',
            $resolver->read('AppData/Roaming/FlowLauncher/Settings/Settings.json')
        );
    }

    public function testMissingFileReturnsNull(): void
    {
        $resolver = new ConfigResolver($this->home, useChezmoi: false);

        $this->assertNull($resolver->read('nope/missing.json'));
    }

    private function removeTree(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        $items = scandir($path) ?: [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $full = $path . DIRECTORY_SEPARATOR . $item;
            is_dir($full) ? $this->removeTree($full) : unlink($full);
        }
        rmdir($path);
    }
}
