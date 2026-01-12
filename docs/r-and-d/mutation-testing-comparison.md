# Mutation Testing: Infection vs Pest

## Overview

This document compares **Infection** (dedicated mutation testing tool) and **Pest** (testing framework with built-in mutation testing) for PHP mutation testing.

## Current Setup

### Mutation Testing Scripts

#### Combined Script: `test:mutation`
- **Runs both**: `@test:mutation:infection` followed by `@test:mutation:pest`
- **Use case**: Comprehensive mutation testing using both tools
- **When to use**: Full codebase analysis, CI/CD pipelines, pre-release checks

#### Infection: `test:mutation:infection`
- **Package**: `infection/infection ^0.31.9`
- **Script**: `composer test:mutation:infection`
- **Config**: `infection.json.dist`
- **Integration**: Standalone tool, uses Pest via `bin/pest-infection`
- **When to use**: When you need detailed HTML reports and comprehensive mutator coverage

#### Pest Mutation Testing: `test:mutation:pest`
- **Package**: Built into Pest v4 (via `pestphp/pest ^4.3.0`)
- **Script**: `composer test:mutation:pest` (uses wrapper script)
- **Config**: Built into Pest, configured via CLI flags in `scripts/mutation-test-wrapper.sh`
- **Integration**: Native Pest feature
- **When to use**: Quick feedback during development, integrated workflow

## Detailed Comparison

### 1. **Architecture & Approach**

#### Infection
- ✅ **Standalone tool**: Dedicated mutation testing framework
- ✅ **Multi-framework support**: Works with PHPUnit, Pest, Codeception, PhpSpec
- ✅ **AST-based mutations**: Uses PHP-Parser for deep code analysis
- ✅ **Mature ecosystem**: Established since 2017, battle-tested
- ✅ **Rich mutator set**: 40+ mutators, highly configurable

#### Pest
- ✅ **Integrated experience**: Built directly into Pest framework
- ✅ **Unified workflow**: Same tool for tests and mutation testing
- ✅ **Simpler setup**: No separate configuration files needed
- ⚠️ **Pest-only**: Only works if you're using Pest
- ⚠️ **Newer feature**: Mutation testing added in Pest 3 (2024)

### 2. **Performance & Speed**

#### Infection
- ✅ **Highly optimized**: Years of performance optimization, battle-tested
- ✅ **Parallel execution**: Configurable threads (default: 4 in your config, can be increased)
- ✅ **Incremental mode**: Can run only on changed files for faster subsequent runs
- ✅ **Efficient mutation generation**: AST-based mutations are well-optimized
- ✅ **Better for large codebases**: Handles large projects more efficiently
- ⚠️ **Initial run slower**: First run needs to analyze entire codebase
- ⚠️ **Memory usage**: Can be memory-intensive on very large codebases

#### Pest
- ✅ **Integrated workflow**: No separate tool startup overhead
- ✅ **Parallel execution**: `--parallel` flag available (newer feature)
- ✅ **Leverages Pest infrastructure**: Can reuse Pest's test execution optimizations
- ⚠️ **Newer feature**: Mutation testing is newer (Pest 3+, 2024), less time to optimize
- ⚠️ **Less mature**: Not as battle-tested for performance as Infection
- ⚠️ **May be slower for comprehensive runs**: Less optimized than Infection for full codebase analysis

**Performance Verdict**: **Infection is generally more performant** for comprehensive mutation testing, especially on larger codebases. Pest may feel faster for small incremental changes due to integration, but Infection's mature optimization typically wins for full runs.

### 3. **Configuration & Flexibility**

#### Infection
- ✅ **Rich configuration**: JSON config file with many options
- ✅ **Mutator profiles**: `@default`, `@function_signature`, custom profiles
- ✅ **Fine-grained control**: Per-directory exclusions, mutator selection
- ✅ **MSI thresholds**: Separate `minMsi` and `minCoveredMsi`
- ✅ **Logging options**: HTML, text, summary logs

**Example configuration flexibility:**
```json
{
  "mutators": {
    "@default": true,
    "TrueValue": false,  // Disable specific mutator
    "ArrayItem": {
      "ignore": ["array_unique"]  // Ignore specific functions
    }
  },
  "source": {
    "directories": ["app"],
    "excludes": ["vendor", "storage"]
  }
}
```

#### Pest
- ✅ **CLI-based**: Simple flags, no config file needed
- ✅ **Quick setup**: `--mutate` flag with options
- ⚠️ **Less flexible**: Limited mutator customization
- ⚠️ **Flag-based only**: All configuration via CLI

**Example Pest usage:**
```bash
pest --mutate --parallel --min=90 --covered-only
```

### 4. **Mutator Coverage & Quality**

#### Infection
- ✅ **40+ mutators**: Comprehensive mutation operators
- ✅ **Well-documented**: Each mutator explained in docs
- ✅ **Custom mutators**: Can create custom mutators
- ✅ **Proven mutators**: Battle-tested across many projects

**Common mutators include:**
- Arithmetic operators (`+`, `-`, `*`, `/`)
- Logical operators (`&&`, `||`, `!`)
- Comparison operators (`==`, `!=`, `>`, `<`)
- Conditional boundaries
- Function/constant removal
- Array/item removal
- Return value mutations

#### Pest
- ✅ **Growing set**: Mutators improving with each release
- ✅ **Pest-optimized**: Mutators tuned for Pest's syntax
- ⚠️ **Smaller set**: Fewer mutators than Infection (exact count varies)
- ⚠️ **Less documentation**: Newer feature, docs still evolving

### 5. **Reporting & Output**

#### Infection
- ✅ **HTML reports**: Rich, interactive HTML reports
- ✅ **Detailed metrics**: MSI, Covered MSI, killed/escaped/timeout counts
- ✅ **Per-file breakdown**: See mutation scores per file
- ✅ **Survivor details**: Shows exactly which mutations survived
- ✅ **CI-friendly**: Text output for CI/CD pipelines

**Output includes:**
- Mutation Score Indicator (MSI)
- Covered Code MSI
- Total mutations generated
- Killed/Escaped/Timeout/Not Covered counts
- Execution time

#### Pest
- ✅ **Integrated output**: Uses Pest's beautiful output format
- ✅ **Test-like experience**: Familiar output for Pest users
- ⚠️ **Less detailed**: May not have same level of detail as Infection
- ⚠️ **Newer feature**: Reporting still evolving

### 6. **CI/CD Integration**

#### Infection
- ✅ **Exit codes**: Proper exit codes for CI
- ✅ **Threshold enforcement**: Fails if MSI below threshold
- ✅ **Artifact generation**: HTML reports can be uploaded
- ✅ **Well-documented**: Clear CI integration guides

#### Pest
- ✅ **Exit codes**: Standard Pest exit codes
- ✅ **Threshold enforcement**: `--min` flag enforces thresholds
- ✅ **Pest ecosystem**: Works with existing Pest CI setup
- ⚠️ **Newer**: Less CI-specific documentation

### 7. **Learning Curve & Developer Experience**

#### Infection
- ⚠️ **Additional tool**: Need to learn separate tool
- ⚠️ **Configuration file**: Need to understand JSON config
- ✅ **Comprehensive docs**: Well-documented with examples
- ✅ **Community**: Large community, many examples

#### Pest
- ✅ **Already using Pest**: No new tool to learn
- ✅ **Simple syntax**: Just add `--mutate` flag
- ✅ **Consistent workflow**: Same commands, same output style
- ⚠️ **Less documentation**: Newer feature, fewer examples

### 8. **Maintenance & Updates**

#### Infection
- ✅ **Active development**: Regular releases (0.31.9 is recent)
- ✅ **Stable API**: Configuration format stable
- ✅ **Backward compatible**: Good version compatibility
- ⚠️ **Separate dependency**: Additional package to maintain

#### Pest
- ✅ **Active development**: Very active, frequent releases
- ✅ **Integrated updates**: Updates with Pest itself
- ✅ **No extra dependency**: Already using Pest
- ⚠️ **Feature maturity**: Mutation testing is newer feature

### 9. **Your Current Setup Analysis**

#### Combined Script (`test:mutation`)
```bash
composer test:mutation
# Runs: @test:mutation:infection, then @test:mutation:pest
```

**Use Case**: Full comprehensive mutation testing with both tools
- Runs Infection first for detailed analysis
- Then runs Pest for integrated workflow validation
- Best for: CI/CD, pre-release checks, comprehensive validation

#### Pest Mutation Testing (`test:mutation:pest`)
```bash
pest --mutate --parallel --min=90 --covered-only --default-time-limit=10
```

**Characteristics:**
- Uses wrapper script (`scripts/mutation-test-wrapper.sh`) for output filtering
- Filters ValidationException noise (killed mutations appear as successes)
- Memory: 1GB (via wrapper script environment)
- Database: SQLite in-memory (`DB_CONNECTION=sqlite DB_DATABASE=:memory:`)
- Parallel execution enabled (`--parallel`)
- 90% minimum threshold (`--min=90`)
- Only tests covered code (`--covered-only`)
- 10 second timeout per mutation (`--default-time-limit=10`)

#### Infection (`test:mutation:infection`)
```bash
vendor/bin/infection
# Uses infection.json.dist configuration
```

**Characteristics:**
- Uses `infection.json.dist` config file
- 4 threads (parallel execution)
- 90% minMsi and minCoveredMsi thresholds
- 10 second timeout per mutation
- Memory: 2GB (configured in composer script)
- HTML report: `infection.html`
- Summary log: `infection-summary.log`
- Temporary directory: `tmp/infection`

## Recommendations

### **Primary Recommendation: Use Both Tools Strategically**

Your setup with three scripts provides excellent flexibility:

### **Recommended Workflow**

1. **During Development (Quick Feedback)**:
   ```bash
   composer test:mutation:pest  # Integrated Pest workflow
   ```
   - Integrated workflow (no separate tool)
   - Uses your custom wrapper script for clean output
   - Good for quick checks during development
   - Note: May be slower than Infection for comprehensive runs, but feels faster due to integration

2. **Before Commits/PRs (Comprehensive Check)**:
   ```bash
   composer test:mutation:infection  # More performant, detailed HTML reports
   ```
   - **More performant** for comprehensive analysis
   - Comprehensive mutator coverage (40+ mutators)
   - Detailed HTML reports for analysis
   - Better optimized for full codebase runs

3. **Full Validation (Both Tools)**:
   ```bash
   composer test:mutation  # Runs both Infection and Pest
   ```
   - Comprehensive analysis with both tools
   - Best for: CI/CD pipelines, pre-release checks
   - Ensures maximum test coverage validation
   - Note: Runs sequentially (Infection first, then Pest)

4. **CI/CD Pipeline**:
   ```bash
   composer test:mutation  # Runs both for comprehensive validation
   ```
   - Both tools run sequentially
   - Comprehensive coverage with different approaches
   - HTML reports can be uploaded as artifacts
   - **For faster CI**: Consider using only `test:mutation:infection` (more performant)

### **Specific Recommendations for Your Codebase**

1. **Three-Tier Script Structure**: Your setup is excellent:
   - `test:mutation` → Comprehensive (both tools)
   - `test:mutation:infection` → Detailed analysis
   - `test:mutation:pest` → Quick feedback

2. **Optimize Infection Config**: Consider:
   ```json
   {
     "timeout": 15,  // Increase from 10 if needed
     "threads": 8,   // Increase if you have more CPU cores
     "minMsi": 90,
     "minCoveredMsi": 90
   }
   ```

3. **Consider Incremental Mode**: For Infection, you could add:
   ```json
   {
     "incremental": true  // Only test changed files
   }
   ```

4. **Pest Wrapper Script**: Your wrapper script (`scripts/mutation-test-wrapper.sh`) is excellent:
   - Filters ValidationException noise (correctly identifies killed mutations)
   - Provides clean, readable output
   - Handles database and memory configuration
   - Keep it as-is - it's well-optimized for your workflow

5. **Memory Configuration**:
   - Pest (`test:mutation:pest`): 1GB (good for incremental/quick runs)
   - Infection (`test:mutation:infection`): 2GB (good for comprehensive analysis)
   - Combined (`test:mutation`): Both run with their respective memory limits

### **When to Use Each Script**

#### Use `test:mutation:pest` when:
- ✅ You want integrated Pest workflow (convenience)
- ✅ Quick feedback needed during development
- ✅ Making incremental changes
- ✅ Already running Pest tests
- ✅ Prefer unified tooling over maximum performance

#### Use `test:mutation:infection` when:
- ✅ **Performance is important** (faster for comprehensive runs)
- ✅ Comprehensive analysis needed
- ✅ Detailed HTML reports required
- ✅ Need to share results with team
- ✅ Want maximum mutator coverage (40+ mutators)
- ✅ Preparing releases or major reviews
- ✅ Large codebase (better optimized)

#### Use `test:mutation` (both) when:
- ✅ CI/CD pipeline validation
- ✅ Pre-release comprehensive checks
- ✅ Maximum test quality assurance
- ✅ Need validation from both tools
- ✅ Full codebase analysis

### **Final Verdict**

**Your current setup is optimal**: Three-tier script structure provides maximum flexibility and coverage.

**Workflow Summary**:
- **Development**: `test:mutation:pest` (integrated workflow, convenient)
- **Pre-commit/PR**: `test:mutation:infection` (more performant, detailed analysis)
- **CI/CD/Release**: `test:mutation` (comprehensive validation with both tools) or `test:mutation:infection` (faster, more performant option)

This gives you:
- ✅ Fast feedback during development (Pest)
- ✅ Comprehensive analysis when needed (Infection)
- ✅ Full validation when required (both tools)
- ✅ Flexibility to choose based on context
- ✅ Both tools complement each other perfectly

## Additional Considerations

### Performance Tips

**Infection:**
- Use `incremental: true` for faster subsequent runs
- Adjust `threads` based on CPU cores
- Use `onlyCovered: true` to skip uncovered code
- Consider `ignoreSourceCodeMutators` for specific patterns

**Pest:**
- Use `--parallel` for faster execution (but still may be slower than Infection)
- `--covered-only` skips uncovered code
- `--default-time-limit` prevents hanging mutations
- Best for small/incremental changes due to integration benefits

**Performance Note**: Infection is generally faster for comprehensive mutation testing due to years of optimization. Pest's mutation testing is newer and may be slower for full codebase runs, but offers better integration with your existing Pest workflow.

### Integration with CI/CD

**Recommended: Use combined script for CI**
```yaml
- run: composer test:mutation  # Runs both Infection and Pest
- uses: actions/upload-artifact@v3
  if: always()
  with:
    name: mutation-reports
    path: |
      infection.html
      infection-summary.log
```

**Alternative: Use individual scripts for different stages**
```yaml
# Quick check (faster feedback)
- run: composer test:mutation:pest

# Comprehensive analysis (detailed reports)
- run: composer test:mutation:infection
- uses: actions/upload-artifact@v3
  if: always()
  with:
    name: infection-report
    path: infection.html
```

## Conclusion

Your current three-tier script structure (`test:mutation`, `test:mutation:infection`, `test:mutation:pest`) is optimal and provides excellent flexibility:

- **`test:mutation:pest`**: Fast, integrated workflow for development
- **`test:mutation:infection`**: Comprehensive analysis with detailed reports
- **`test:mutation`**: Full validation using both tools for maximum coverage

This setup allows you to choose the right tool(s) for each situation, from quick development feedback to comprehensive pre-release validation. Both tools serve different purposes and complement each other perfectly.
