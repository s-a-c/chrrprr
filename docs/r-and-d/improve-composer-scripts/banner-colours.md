# Banner Colours

## Color Assignment Summary

### Sequential Groups (High Variance):

`analyze`**group:**

- `analyze:mago`: Bright Cyan (96)
- `analyze:phpstan`: Bright Red (91)
- `analyze:psalm`: Bright Magenta (95)

`analyze:fix`**group:**

- `analyze:fix:mago`: Bright Green (92)
- `analyze:fix:phpstan`: Bright Blue (94)
- `analyze:fix:psalm`: Bright Magenta (95)

`lint`**group:**

- `lint:composer`: Bright Cyan (96)
- `lint:mago`: Bright Red (91)
- `lint:rector`: Bright Green (92)
- `lint:pint`: Bright Blue (94)
- `lint:js`: Bright Yellow (93)

`lintfix`**group:**

- `lintfix:composer`: Magenta (35)
- `lintfix:mago`: Red (31)
- `lintfix:rector`: Cyan (36)
- `lintfix:pint`: Yellow (33)
- `lintfix:js`: Blue (34)

`test:coverage`**group:**

- `test:coverage:pcov`: Magenta (35)
- `test:coverage:js`: Red (31)

`test:browser`**group:**

- `test:browser:pest`: Red on White (31;47)
- `test:browser:js`: Magenta on White (35;47)

### Other Unique Colors:

- `blueprint:build`: Bright Blue (94)
- `blueprint:trace`: Bright Green (92)
- `ci:local:*`: Various (31, 35, 36, 32, 94)
- `dev`: Bright Cyan on White (96;47)
- `dev:solo`: Bright Magenta on White (95;47)
- `ide-helper:generate`: Bright Yellow (93)
- `mago:guard`: Bright Red (91)
- `mago:guard:fix`: Bright Green (92)
- `mago:list-files`: Bright Cyan (96)
- `security:audit`: Reverse Bright Red (7;1;91)
- `setup`: Bright Blue (94)
- `test:architecture`: Bright Magenta (95)
- `test:mutation`: Bright Yellow on Cyan (93;46)
- `test:profanity`: Bright Red (91)
- `test:type-coverage`: Reverse Bright Cyan (7;1;96)
- `test:unit`: Green on White (32;47)
- `update:requirements`: Bright White (97)
- `local:ghaction:clean`: Bright Black/Gray (90)
- `local:ghaction:quality:solo`: Bright White (97)
- `lint:mago:pedantic`: Bright Magenta (95)
- `analyze:psalm:stats`: Bright Yellow (93)

All scripts now have unique colors, and sequential scripts in groups like `lint`, `analyze`, and `lintfix` have high color variance for easier visual distinction.
