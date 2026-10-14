<?php
declare(strict_types=1);

namespace Keycheat\Adapter;

/**
 * Turns one application's config into the normalized keycheat model.
 *
 * An adapter knows a single app: where its config lives (target) and how to
 * read bindings out of it (extract). Everything downstream works with the
 * normalized shape, so the core never needs to know about specific apps.
 */
interface Adapter
{
    /** Stable slug, e.g. "flow-launcher". */
    public function id(): string;

    /** Human-readable name, e.g. "Flow Launcher". */
    public function name(): string;

    /** How bindings are obtained: "static" (parsed from config) or "runtime". */
    public function extraction(): string;

    /** Target config path, relative to the home directory. */
    public function target(): string;

    /**
     * Parse raw config content into normalized bindings.
     *
     * @return list<array{keys: list<string>, description: string, action?: string, context?: string}>
     */
    public function extract(string $content): array;
}
