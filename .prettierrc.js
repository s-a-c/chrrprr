/** @format */

// Prettier configuration for consistent code formatting
// See: https://prettier.io/docs/en/options.html

export default {
    // Use single quotes instead of double quotes
    singleQuote: true,

    // Print trailing commas wherever possible in multi-line
    trailingComma: "all",

    // Specify the line length that the printer will wrap on
    printWidth: 200,

    // Use spaces instead of tabs
    useTabs: false,

    // Specify the number of spaces per indentation level
    tabWidth: 4,

    // Include parentheses around a sole arrow function parameter
    arrowParens: "always",

    // Print spaces between brackets in object literals
    bracketSpacing: true,

    // Put the > of a multi-line HTML element at the end of the last line
    bracketSameLine: false,

    // Use single quotes in JSX
    jsxSingleQuote: true,

    // Specify whether to add a semicolon at the end of every statement
    semi: true,

    // Maintain existing line endings
    endOfLine: "lf",

    // Format embedded content
    embeddedLanguageFormatting: "auto",

    // Require pragma at the top of files to format them
    requirePragma: false,

    // Insert pragma at the top of formatted files
    insertPragma: true,

    // Use prose wrapping for markdown text
    proseWrap: "preserve",

    // HTML whitespace sensitivity
    htmlWhitespaceSensitivity: "css",

    // Vue files script and style tags indentation
    vueIndentScriptAndStyle: true,

    // Enforce consistent quote style in object literals
    quoteProps: "as-needed",

    // Range of lines to format
    rangeStart: 0,
    rangeEnd: Infinity,

    // Override configurations for specific file patterns
    overrides: [
        {
            files: "*.blade.php",
            options: {
                parser: "html",
                htmlWhitespaceSensitivity: "ignore",
            },
        },
        {
            files: ["*.yml", "*.yaml"],
            options: {
                tabWidth: 4,
            },
        },
        {
            files: "*.md",
            options: {
                proseWrap: "always",
            },
        },
    ],
}
