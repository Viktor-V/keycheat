<?php
declare(strict_types=1);

namespace Keycheat\Tests;

use Keycheat\HtmlRenderer;
use PHPUnit\Framework\TestCase;

final class HtmlRendererTest extends TestCase
{
    private function model(): array
    {
        return [
            'generated_at' => '2026-10-13T00:00:00Z',
            'sources' => [
                [
                    'id' => 'demo',
                    'name' => 'Demo & Co',
                    'extraction' => 'static',
                    'sample' => false,
                    'bindings' => [
                        [
                            'keys' => ['Ctrl+Space', 'C'],
                            'description' => 'New <tab>',
                            'action' => 'SpawnTab',
                        ],
                    ],
                ],
            ],
        ];
    }

    public function testRendersSingleBinding(): void
    {
        $html = (new HtmlRenderer())->render($this->model());

        $this->assertSame(1, substr_count($html, 'class="binding"'));
        $this->assertSame(1, substr_count($html, '<section>'));
    }

    public function testRendersChordSequenceWithKeycaps(): void
    {
        $html = (new HtmlRenderer())->render($this->model());

        $this->assertStringContainsString('<kbd>Ctrl</kbd><kbd>Space</kbd>', $html);
        $this->assertStringContainsString('<span class="sep">then</span>', $html);
        $this->assertStringContainsString('<kbd>C</kbd>', $html);
    }

    public function testEscapesHtml(): void
    {
        $html = (new HtmlRenderer())->render($this->model());

        $this->assertStringContainsString('Demo &amp; Co', $html);
        $this->assertStringContainsString('New &lt;tab&gt;', $html);
        $this->assertStringNotContainsString('New <tab>', $html);
    }

    public function testDataHayIsNormalized(): void
    {
        $html = (new HtmlRenderer())->render($this->model());

        $this->assertStringContainsString(
            'data-hay="demo &amp; co ctrl space c new &lt;tab&gt; spawntab"',
            $html
        );
    }

    public function testSubLineCounts(): void
    {
        $html = (new HtmlRenderer())->render($this->model());

        $this->assertStringContainsString('1 shortcuts from 1 sources', $html);
    }

    public function testSampleBadgeRenderedWhenSample(): void
    {
        $model = $this->model();
        $model['sources'][0]['sample'] = true;

        $this->assertStringContainsString('>sample</span>', (new HtmlRenderer())->render($model));
    }

    public function testOutputIsDeterministic(): void
    {
        $renderer = new HtmlRenderer();

        $this->assertSame($renderer->render($this->model()), $renderer->render($this->model()));
    }

    public function testThrowsWhenSourcesMissing(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new HtmlRenderer())->render([]);
    }
}
