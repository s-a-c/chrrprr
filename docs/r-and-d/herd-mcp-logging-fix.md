# Herd MCP Server Logging Fix

## Problem

The Herd MCP server was outputting log messages (e.g., `[info]`, `[error]`) to stdout, which interfered with JSON-RPC communication. MCP servers must only output valid JSON-RPC messages to stdout - any other output causes parsing errors.

## Solution

Created a PHP wrapper script (`scripts/herd-mcp-wrapper.php`) that:

1. **Filters log messages**: Redirects log messages (starting with `[info]`, `[error]`, etc.) to stderr
2. **Preserves JSON-RPC**: Only valid JSON-RPC messages are sent to stdout
3. **Handles buffering**: Properly handles incomplete JSON messages that span multiple reads
4. **Forwards stdin**: Correctly forwards input from the MCP client to the Herd server

## Configuration

The wrapper is configured in `opencode.json`:

```json
{
  "herd": {
    "type": "local",
    "enabled": true,
    "command": [
      "php",
      "/Users/s-a-c/Herd/chrrprr/scripts/herd-mcp-wrapper.php"
    ],
    "environment": {
      "SITE_PATH": "/Users/s-a-c/Herd/chrrprr"
    }
  }
}
```

## How It Works

1. The wrapper starts the Herd MCP server as a subprocess
2. It reads from the server's stdout and stderr separately
3. Lines starting with `[info]`, `[error]`, `[warn]`, `[debug]`, or `[trace]` are sent to stderr
4. Lines that are valid JSON (starting with `{` or `[`) are sent to stdout
5. All other output is sent to stderr to be safe

## Testing

After updating the configuration, restart your MCP client (Cursor) to pick up the changes. The JSON parsing errors should no longer appear in the logs.

## Files Modified

- `scripts/herd-mcp-wrapper.php` - New wrapper script
- `opencode.json` - Updated to use the wrapper

---

**Date**: 2025-01-27
**Status**: ✅ Implemented
