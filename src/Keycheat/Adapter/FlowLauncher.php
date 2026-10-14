<?php
declare(strict_types=1);

namespace Keycheat\Adapter;

/**
 * Flow Launcher stores its hotkeys as string fields in Settings.json.
 */
final class FlowLauncher implements Adapter
{
    /**
     * Settings.json field => human description.
     *
     * @var array<string, string>
     */
    private const HOTKEYS = [
        'Hotkey' => 'Open / hide Flow Launcher',
        'PreviewHotkey' => 'Preview result',
        'AutoCompleteHotkey' => 'Autocomplete query',
        'AutoCompleteHotkey2' => 'Autocomplete query (alt)',
        'SelectNextItemHotkey' => 'Select next item',
        'SelectNextItemHotkey2' => 'Select next item (alt)',
        'SelectPrevItemHotkey' => 'Select previous item',
        'SelectPrevItemHotkey2' => 'Select previous item (alt)',
        'SelectNextPageHotkey' => 'Next page of results',
        'SelectPrevPageHotkey' => 'Previous page of results',
        'OpenContextMenuHotkey' => 'Open context menu',
        'SettingWindowHotkey' => 'Open settings',
        'OpenHistoryHotkey' => 'Open history',
        'CycleHistoryUpHotkey' => 'Cycle history up',
        'CycleHistoryDownHotkey' => 'Cycle history down',
        'DialogJumpHotkey' => 'Dialog jump',
    ];

    public function id(): string
    {
        return 'flow-launcher';
    }

    public function name(): string
    {
        return 'Flow Launcher';
    }

    public function extraction(): string
    {
        return 'static';
    }

    public function target(): string
    {
        return 'AppData/Roaming/FlowLauncher/Settings/Settings.json';
    }

    public function extract(string $content): array
    {
        $settings = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($settings)) {
            return [];
        }

        $bindings = [];
        foreach (self::HOTKEYS as $field => $description) {
            $value = $settings[$field] ?? null;
            if (!is_string($value) || trim($value) === '') {
                continue;
            }

            $bindings[] = [
                'keys' => [self::normalizeChord($value)],
                'action' => $field,
                'description' => $description,
            ];
        }

        return $bindings;
    }

    private static function normalizeChord(string $raw): string
    {
        $parts = preg_split('/\s*\+\s*/', trim($raw));

        return implode('+', array_map('trim', $parts === false ? [] : $parts));
    }
}
