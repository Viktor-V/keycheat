<?php
declare(strict_types=1);

namespace Keycheat;

/**
 * Resolves a config's content for a given home-relative path.
 *
 * chezmoi-first: if the file is managed by chezmoi, we ask chezmoi for the
 * rendered content (`chezmoi cat`), so templates are resolved for this machine.
 * Otherwise we fall back to reading the file directly.
 */
final class ConfigResolver
{
    private string $home;

    private bool $useChezmoi;

    public function __construct(?string $home = null, bool $useChezmoi = true)
    {
        $this->home = $home ?? self::detectHome();
        $this->useChezmoi = $useChezmoi;
    }

    /**
     * Return the resolved content for a home-relative path, or null if absent.
     */
    public function read(string $target): ?string
    {
        if ($this->useChezmoi) {
            $managed = $this->chezmoiCat($target);
            if ($managed !== null) {
                return $managed;
            }
        }

        $path = rtrim($this->home, "/\\")
            . DIRECTORY_SEPARATOR
            . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $target);

        if (!is_file($path)) {
            return null;
        }

        $content = file_get_contents($path);

        return $content === false ? null : $content;
    }

    private function chezmoiCat(string $target): ?string
    {
        $absolute = rtrim($this->home, "/\\") . '/' . $target;

        $descriptors = [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = @proc_open(['chezmoi', 'cat', $absolute], $descriptors, $pipes);
        if (!is_resource($process)) {
            return null;
        }

        $stdout = (string)stream_get_contents($pipes[1]);
        stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $code = proc_close($process);

        return $code === 0 ? $stdout : null;
    }

    private static function detectHome(): string
    {
        $home = getenv('HOME');
        if ($home === false || $home === '') {
            $home = getenv('USERPROFILE');
        }
        if ($home === false || $home === '') {
            $home = getcwd();
        }

        return (string)($home === false ? '' : $home);
    }
}
