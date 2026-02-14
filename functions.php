<?php

declare(strict_types=1);

require_once 'inc/class-vite.php';

add_action('wp_enqueue_scripts', function (): void {
    Vite::enqueue([
        'src/js/main.js',
        'src/css/main.css',
    ], 'theme-front');
});

add_action('enqueue_block_editor_assets', function (): void {
    $deps = json_decode(Vite::content('editor.deps.json') ?? '[]', true);

    foreach ($deps as $dependency) {
        if (!wp_script_is($dependency)) {
            wp_enqueue_script($dependency);
        }
    }

    Vite::enqueue([
        'src/js/editor.js',
        'src/css/editor.css',
    ], 'theme-editor');
});

add_filter(
    'block_editor_settings_all',
    function (array $settings): array {
        $style = Vite::asset('src/css/editor.css');

        if ($style !== null) {
            $settings['styles'][] = [
                'css' => "@import url('{$style}')",
            ];
        }

        return $settings;
    }
);

add_filter(
    'theme_file_path',
    function (string $path, string $file): string {
        return $file === 'theme.json'
            ? get_theme_file_path('/assets/build/theme.json')
            : $path;
    },
    10,
    2
);
