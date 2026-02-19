<?php

declare(strict_types=1);

final class Vite
{
    private const BUILD_DIR = 'assets/build/';
    private const MANIFEST_PATH = self::BUILD_DIR . 'manifest.json';
    private const HOT_PATH = 'assets/hot';

    private static ?array $manifest = null;
    private static ?string $hotUrl = null;

    private function __construct(
        private array $entries
    ) {}

    public static function withEntryPoints(array $entries): self
    {
        return new self($entries);
    }

    public static function isDev(): bool
    {
        return self::hotUrl() !== null;
    }

    /**
     * @throws JsonException
     */
    public static function enqueue(array $entries, string $prefix = 'vite'): void
    {
        self::isDev()
            ? self::enqueueDev($entries, $prefix)
            : self::enqueueProd($entries, $prefix);
    }

    /**
     * @throws JsonException
     */
    public static function asset(string $entry): ?string
    {
        if (self::isDev()) {
            $server = self::hotUrl();
            return $server ? "{$server}/" . ltrim($entry, '/') : null;
        }

        $manifest = self::manifest();
        if (!isset($manifest[$entry]['file'])) {
            return null;
        }

        return get_template_directory_uri()
            . '/'
            . self::BUILD_DIR
            . $manifest[$entry]['file'];
    }

    /**
     * @throws JsonException
     */
    public static function content(string $entry): ?string
    {
        $manifest = self::manifest();
        if (!isset($manifest[$entry]['file'])) {
            return null;
        }

        $path = get_theme_file_path(self::BUILD_DIR . $manifest[$entry]['file']);
        return file_exists($path) ? file_get_contents($path) : null;
    }

    public function toHtml(): string
    {
        if (self::isDev()) {
            return $this->devHtml();
        }
        return $this->prodHtml();
    }

    private function devHtml(): string
    {
        $server = self::hotUrl();
        if ($server === null) return '';

        $html = [
            sprintf('<script type="module" src="%s"></script>', esc_url("{$server}/@vite/client"))
        ];

        foreach ($this->entries as $entry) {
            if (str_ends_with($entry, '.js')) {
                $html[] = sprintf(
                    '<script type="module" src="%s"></script>',
                    esc_url("{$server}/" . ltrim($entry, '/'))
                );
            }
        }

        return implode("\n", $html);
    }

    private function prodHtml(): string
    {
        $manifest = self::manifest();
        $html = [];

        foreach ($this->entries as $entry) {
            if (!isset($manifest[$entry])) continue;
            $chunk = $manifest[$entry];

            if (isset($chunk['css'])) {
                foreach ($chunk['css'] as $css) {
                    $html[] = $this->styleTag($css);
                }
            }

            if (isset($chunk['imports'])) {
                foreach ($chunk['imports'] as $import) {
                    if (isset($manifest[$import]['file'])) {
                        $html[] = $this->modulePreloadTag($manifest[$import]['file']);
                    }
                }
            }

            if (isset($chunk['file']) && str_ends_with($chunk['file'], '.js')) {
                $html[] = $this->scriptTag($chunk['file']);
            }
        }

        return implode("\n", $html);
    }

    private static function enqueueDev(array $entries, string $prefix): void
    {
        $server = self::hotUrl();
        if ($server === null) return;

        wp_enqueue_script_module("{$prefix}-vite-client", "{$server}/@vite/client");

        foreach ($entries as $entry) {
            if (str_ends_with($entry, '.js')) {
                wp_enqueue_script_module(
                    "{$prefix}-" . md5($entry),
                    "{$server}/" . ltrim($entry, '/'),
                    ['jquery'],
                    false
                );
            } elseif (str_ends_with($entry, '.css')) {
                wp_enqueue_style(
                    "{$prefix}-" . md5($entry),
                    "{$server}/" . ltrim($entry, '/'),
                    [],
                    false
                );
            }
        }
    }

    private static function enqueueProd(array $entries, string $prefix): void
    {
        $manifest = self::manifest();

        foreach ($entries as $entry) {
            if (!isset($manifest[$entry])) continue;
            $chunk = $manifest[$entry];

            if (isset($chunk['css'])) {
                foreach ($chunk['css'] as $css) {
                    wp_enqueue_style(
                        "{$prefix}-" . md5($css),
                        get_template_directory_uri() . '/' . self::BUILD_DIR . $css
                    );
                }
            }

            if (isset($chunk['file']) && str_ends_with($chunk['file'], '.js')) {
                wp_enqueue_script(
                    "{$prefix}-" . md5($chunk['file']),
                    get_template_directory_uri() . '/' . self::BUILD_DIR . $chunk['file'],
                    ['jquery'],
                    null,
                    true
                );
            } elseif (isset($chunk['file']) && str_ends_with($chunk['file'], '.css')) {
                wp_enqueue_style(
                    "{$prefix}-" . md5($chunk['file']),
                    get_template_directory_uri() . '/' . self::BUILD_DIR . $chunk['file']
                );
            }
        }
    }

    private static function manifest(): array
    {
        if (self::$manifest !== null) return self::$manifest;

        $path = get_theme_file_path(self::MANIFEST_PATH);
        if (!file_exists($path)) return self::$manifest = [];

        $json = file_get_contents($path);
        return self::$manifest = json_decode($json ?: '{}', true, flags: JSON_THROW_ON_ERROR);
    }

    private static function hotUrl(): ?string
    {
        if (self::$hotUrl !== null) return self::$hotUrl;

        $path = get_theme_file_path(self::HOT_PATH);
        if (!file_exists($path)) return self::$hotUrl = null;

        $url = trim(file_get_contents($path));
        return self::$hotUrl = $url !== '' ? rtrim($url, '/') : null;
    }

    private function styleTag(string $file): string
    {
        $href = get_template_directory_uri() . '/' . self::BUILD_DIR . $file;
        return sprintf('<link rel="stylesheet" href="%s">', esc_url($href));
    }

    private function modulePreloadTag(string $file): string
    {
        $href = get_template_directory_uri() . '/' . self::BUILD_DIR . $file;
        return sprintf('<link rel="modulepreload" href="%s">', esc_url($href));
    }

    private function scriptTag(string $file): string
    {
        $src = get_template_directory_uri() . '/' . self::BUILD_DIR . $file;
        return sprintf('<script type="module" src="%s"></script>', esc_url($src));
    }
}
