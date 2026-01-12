<?php

declare(strict_types=1);

namespace App\Support\Console;

final class Banner
{
    /**
     * Display a banner with a title and colors.
     *
     * @param  array<int, string>  $args  Arguments passed to the script
     */
    public static function display(array $args): void
    {
        $config = self::parseArgs($args, '1;32');
        $title = $config['message'];
        $color = $config['color'];

        $banner = self::getBanner($title, 120);

        if (mb_trim($banner) === '') {
            return;
        }

        printf("\e[%sm%s\e[0m\n", $color, $banner);
    }

    /**
     * Display a simple colored message.
     *
     * @param  array<int, string>  $args  Arguments passed to the script
     */
    public static function log(array $args): void
    {
        $config = self::parseArgs($args, '0;32');
        $message = $config['message'];
        $color = $config['color'];

        printf("\e[%sm%s\e[0m\n", $color, $message);
    }

    /**
     * Parse raw arguments into a message and a color.
     *
     * @param  array<int, string>  $args
     * @return array{message: string, color: string}
     */
    private static function parseArgs(array $args, string $defaultColor): array
    {
        if ($args === []) {
            return ['message' => '', 'color' => $defaultColor];
        }

        // Sanitize all arguments first
        $args = array_map(static fn (string $a): string => mb_trim($a, "\"' "), $args);

        $color = $defaultColor;
        // If the last argument looks like an ANSI color code (e.g., "1;32", "0;36", "31")
        if (count($args) > 1 && preg_match('/^[0-9;]+$/', end($args))) {
            $color = array_pop($args);
        }

        return [
            'message' => implode(' ', $args),
            'color' => $color,
        ];
    }

    /**
     * Get the banner text from figlet or fallback to plain text.
     */
    private static function getBanner(string $title, int $width): string
    {
        if ($title === '' || $title === '0') {
            return PHP_EOL;
        }

        $escapedTitle = escapeshellarg($title);

        // Try figlet with standard font first
        /** @psalm-suppress ForbiddenCode */
        $banner = shell_exec("figlet -w {$width} -k -f standard {$escapedTitle} 2>/dev/null");

        // Fallback to figlet with default font
        if (in_array($banner, ['', '0', false, null], true)) {
            /** @psalm-suppress ForbiddenCode */
            $banner = shell_exec("figlet {$escapedTitle} 2>/dev/null");
        }

        // Fallback to plain text
        if (in_array($banner, ['', '0', false, null], true)) {
            return $title.PHP_EOL;
        }

        return $banner;
    }
}
