# Architecture Testing Documentation

This directory contains comprehensive documentation for the architecture testing suite.

## Main Documentation

📘 **[Architecture Testing Guide](ARCHITECTURE_TESTING_GUIDE.md)** - Complete guide and manifest

This is the **primary documentation** for architecture testing. It includes:
- Overview and strategy
- Test suite structure
- Tool comparison
- Usage guide
- Performance considerations
- Best practices
- Future enhancements
- Troubleshooting

## Reports

Date-stamped execution reports are stored in the `reports/` directory:

- `pest-arch-test-report-YYYY-MM-DD_HH-MM-SS.md` - Pest architecture test execution reports
- `mago-guard-report-YYYY-MM-DD_HH-MM-SS.md` - Mago Guard execution reports

## Quick Start

### Run Architecture Tests

```bash
# Recommended: Mago Guard (fast, reliable)
composer mago:guard

# Comprehensive: Pest Architecture Tests
composer test:arch:pest
```

### Documentation Structure

```
docs/architecture-tests/
├── README.md                           # This file
├── ARCHITECTURE_TESTING_GUIDE.md       # Main comprehensive guide
├── reports/                            # Date-stamped execution reports
│   ├── pest-arch-test-report-*.md
│   └── mago-guard-report-*.md
└── [Legacy files - see guide for details]
```

## Legacy Documentation

The following files have been consolidated into `ARCHITECTURE_TESTING_GUIDE.md`:

- `architecture-test-coverage.md` - Coverage analysis (now in guide)
- `architecture-testing-summary.md` - Summary (now in guide)
- `architecture-tests-optimization.md` - Optimization details (now in guide)

**Note:** These files are kept for historical reference but the comprehensive guide is the authoritative source.

## Getting Help

1. Read the [Architecture Testing Guide](ARCHITECTURE_TESTING_GUIDE.md)
2. Check recent reports in `reports/` directory
3. Review test files in `tests/Arch/`
4. Consult team documentation

---

**Last Updated:** 2025-12-31
