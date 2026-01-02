#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Wrapper script for Herd MCP server that filters log messages from stdout.
 *
 * MCP servers must only output JSON-RPC messages to stdout.
 * This script filters out log messages (starting with [info], [error], etc.)
 * and redirects them to stderr, keeping only valid JSON-RPC messages on stdout.
 */
$mcpScript = '/Applications/Herd.app/Contents/Resources/herd-mcp.phar';

if (! file_exists($mcpScript)) {
    fwrite(STDERR, "Error: Herd MCP server not found at {$mcpScript}\n");
    exit(1);
}

// Set up process with proper descriptors
$descriptorspec = [
    0 => ['pipe', 'r'], // stdin - forward from our stdin
    1 => ['pipe', 'w'], // stdout - filter
    2 => STDERR, // stderr - forward directly
];

$process = proc_open(
    ['php', $mcpScript],
    $descriptorspec,
    $pipes,
    null,
    $_ENV
);

if (! is_resource($process)) {
    fwrite(STDERR, "Error: Failed to start Herd MCP server\n");
    exit(1);
}

// Set pipes to non-blocking
stream_set_blocking($pipes[0], false);
stream_set_blocking($pipes[1], false);
stream_set_blocking(STDIN, false);

// Buffer for incomplete lines
$buffer = '';

// Main loop: bidirectional communication
while (true) {
    // Check if process is still running
    $status = proc_get_status($process);
    if (! $status['running'] && feof($pipes[1])) {
        break;
    }

    $read = [$pipes[1]];
    $write = [];
    $except = [];

    // Check if we have data to write to the process
    if (! feof(STDIN)) {
        $write[] = $pipes[0];
    }

    // Wait for data (100ms timeout)
    $changed = stream_select($read, $write, $except, 0, 100000);

    if ($changed === false) {
        continue;
    }

    // Forward stdin to process
    if (in_array($pipes[0], $write)) {
        $input = fread(STDIN, 8192);
        if ($input !== false && $input !== '') {
            fwrite($pipes[0], $input);
        }
    }

    // Read from stdout and filter
    if (in_array($pipes[1], $read)) {
        $data = fread($pipes[1], 8192);

        if ($data === false) {
            continue;
        }

        if ($data === '') {
            if (feof($pipes[1])) {
                break;
            }

            continue;
        }

        $buffer .= $data;

        // Process complete lines
        while (($newlinePos = mb_strpos($buffer, "\n")) !== false) {
            $line = mb_substr($buffer, 0, $newlinePos);
            $buffer = mb_substr($buffer, $newlinePos + 1);

            $trimmed = mb_trim($line);

            if ($trimmed === '') {
                continue;
            }

            // Check if line is a log message (starts with [info], [error], etc.)
            if (preg_match('/^\[(info|error|warn|debug|trace)\]/', $trimmed)) {
                // Log message - send to stderr
                fwrite(STDERR, $trimmed."\n");
            } elseif (preg_match('/^[\s]*[\{\[]/', $trimmed)) {
                // Looks like JSON - validate and send to stdout
                $decoded = json_decode($trimmed, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    // Valid JSON - send to stdout
                    fwrite(STDOUT, $trimmed."\n");
                } else {
                    // Invalid JSON - send to stderr
                    fwrite(STDERR, $trimmed."\n");
                }
            } else {
                // Unknown format - send to stderr to be safe
                fwrite(STDERR, $trimmed."\n");
            }
        }
    }
}

// Flush any remaining buffer
if ($buffer !== '') {
    $trimmed = mb_trim($buffer);
    if ($trimmed !== '') {
        if (preg_match('/^\[(info|error|warn|debug|trace)\]/', $trimmed)) {
            fwrite(STDERR, $trimmed."\n");
        } elseif (preg_match('/^[\s]*[\{\[]/', $trimmed)) {
            $decoded = json_decode($trimmed, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                fwrite(STDOUT, $trimmed."\n");
            } else {
                fwrite(STDERR, $trimmed."\n");
            }
        } else {
            fwrite(STDERR, $trimmed."\n");
        }
    }
}

// Clean up
fclose($pipes[0]);
fclose($pipes[1]);
proc_close($process);
