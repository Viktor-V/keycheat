<?php
declare(strict_types=1);

namespace Keycheat\Tests;

use Keycheat\Adapter\FlowLauncher;
use PHPUnit\Framework\TestCase;

final class FlowLauncherTest extends TestCase
{
    public function testMetadata(): void
    {
        $adapter = new FlowLauncher();

        $this->assertSame('flow-launcher', $adapter->id());
        $this->assertSame('Flow Launcher', $adapter->name());
        $this->assertSame('static', $adapter->extraction());
        $this->assertSame('AppData/Roaming/FlowLauncher/Settings/Settings.json', $adapter->target());
    }

    public function testExtractsAndNormalizesHotkeys(): void
    {
        $json = '{"Hotkey":"Alt \u002B Space","PreviewHotkey":"F1","AutoCompleteHotkey":"Ctrl \u002B Tab","OpenContextMenuHotkey":"Ctrl\u002BO","SelectPrevItemHotkey":"Shift \u002B Tab","AutoCompleteHotkey2":""}';

        $bindings = (new FlowLauncher())->extract($json);

        $byAction = [];
        foreach ($bindings as $binding) {
            $byAction[$binding['action']] = $binding['keys'];
        }

        $this->assertSame(['Alt+Space'], $byAction['Hotkey']);
        $this->assertSame(['F1'], $byAction['PreviewHotkey']);
        $this->assertSame(['Ctrl+Tab'], $byAction['AutoCompleteHotkey']);
        $this->assertSame(['Ctrl+O'], $byAction['OpenContextMenuHotkey']);
        $this->assertSame(['Shift+Tab'], $byAction['SelectPrevItemHotkey']);
        $this->assertArrayNotHasKey('AutoCompleteHotkey2', $byAction);
    }

    public function testDescriptionsArePresent(): void
    {
        $bindings = (new FlowLauncher())->extract('{"Hotkey":"Alt + Space"}');

        $this->assertCount(1, $bindings);
        $this->assertSame('Open / hide Flow Launcher', $bindings[0]['description']);
    }

    public function testEmptySettingsYieldsNoBindings(): void
    {
        $this->assertSame([], (new FlowLauncher())->extract('{}'));
    }

    public function testInvalidJsonThrows(): void
    {
        $this->expectException(\JsonException::class);

        (new FlowLauncher())->extract('{not json');
    }
}
