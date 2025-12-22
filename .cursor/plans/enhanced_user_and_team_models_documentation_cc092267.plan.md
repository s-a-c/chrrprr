---
name: Enhanced User and Team Models Documentation
overview: Create t3-enhanced.md as a consolidated, refactored guide with step-by-step installation instructions, complete composer.json, and implementation details for enhanced User and Team models with ULID primary keys, translatable slugs, and Enterprise-based tenancy with fine-grained scoping.
todos: []
---

# $\color{red}{\text{Enhanced User and Team Models Implementation Guide}}$

## $\color{orange}{\text{1. Overview}}$

This document consolidates and refactors the Team model implementation from `t3.md`, adding comprehensive step-by-step installation guides, complete package configuration, and enhanced architecture for a SaaS platform with:

- **ULID Primary Keys**: All models use ULID as route key (keeping integer `id` for relationships)
- **Translatable Slugs**: Using `cviebrock/eloquent-sluggable` + `spatie/laravel-translatable`
- **Enterprise-Based Tenancy**: Enterprise as tenant with fine-grained Organisation/Division/Department scoping
- **Enhanced User Model**: ULID, translatable slugs, tenant relationships
- **Complete Package Setup**: Full `composer.json` with all required packages
- **BDD/TDD Approach**: Behavior-Driven Development with Behat, Test-Driven Development with Pest
- **100% Test Coverage**: Complete test coverage for both PHP and JavaScript code
- **Maximum Quality Standards**: PHPStan level 9, ESLint strict mode, 100% test pass rate
- **Comprehensive Testing**: Unit, feature, browser, and BDD tests with complete test suites

## $\color{orange}{\text{2. Architecture Decisions}}$

### $\color{yellow}{\text{2.1 Tenancy Model}}$

- **Enterprise = Tenant**: Enterprise implements `TenantContract`, subdomain maps to Enterprise
- **Fine-Grained Scoping**: Application-level context for Organisation/Division/Department
- **Context Switching**: Users can switch between Organisations within their Enterprise

### $\color{yellow}{\text{2.2 Primary Key Strategy}}$

- **Dual Keys**: Integer `id` for foreign keys/performance, ULID `ulid` for routes/public APIs
- **Route Binding**: All models use ULID for route model binding
- **Foreign Keys**: Continue using integer `id` for database relationships

### $\color{yellow}{\text{2.3 Slug Strategy}}$

- **Translatable**: Slugs support multiple languages via `spatie/laravel-translatable` with `cviebrock/eloquent-sluggable`
- **Hierarchical**: Slugs generated from parent name + current name
- **Unique per Graph**: Slugs unique within team hierarchy graph
- **Implementation**: Slug column is translatable (JSON), generated per locale from translatable name attribute

### $\color{yellow}{\text{2.4 Model Architecture}}$

- **Trait-Based Composition**: All shared functionality implemented as reusable traits in `app/Models/Concerns/`
- **No Base Model**: Models extend appropriate base classes (User extends Authenticatable, Team extends Model)
- **Focused Traits**: Single responsibility per trait (HasUlid, HasTranslatableAttributes, HasTranslatableSlug)
- **Laravel Conventions**: Follows framework patterns using `boot{TraitName}()` methods
- **Flexible Composition**: Models can mix and match traits as needed without inheritance conflicts

## $\color{orange}{\text{3. Business Rules & Validation}}$

This section documents the business rules that govern the Team hierarchy and User model. **Clarifying questions are included where implementation details need to be confirmed.**

### $\color{yellow}{\text{3.1 Team Hierarchy Rules}}$

#### $\color{green}{\text{3.1.1 Hierarchy Structure}}$

**Defined Structure:**

- Enterprise → Organisation → Division → Department
- Project (non-root, can be child of any type)

**Root Constraint:**

- ✅ **Confirmed**: Only Enterprise can be root-level (parent_id = null)
- ✅ **Decision**: Project must always have a parent (cannot be root-level)

**Hierarchy Validation:**

- ✅ **Decision**: Strict parent-child type constraints enforced:
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                - **Organisation**: Can only have Enterprise as parent
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                - **Division**: Can only have Organisation as parent
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                - **Department**: Can only have Division as parent
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                - **Project**: Can be child of any type (Enterprise, Organisation, Division, or Department)
- ✅ **Recommendation**: Maximum depth of 10 levels to prevent performance issues and maintain hierarchy clarity
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                - **Rationale**: Prevents infinite nesting, maintains query performance, keeps UI manageable
- ✅ **Recommendation**: Teams can be moved between parents, but must maintain hierarchy constraints
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                - **Validation**: When moving, validate that new parent type is allowed for the team type
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                - **Cascade**: When moving a team, all children must also be valid under the new parent
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                - **Slug Regeneration**: Slugs must regenerate when parent changes (to maintain hierarchical slug structure)

#### $\color{green}{\text{3.1.2 Unique Name Per Graph}}$

**Current Understanding:**

- Names must be unique within their hierarchy graph

**Decisions:**

- ✅ **Decision**: "Graph" is defined as the entire Enterprise tree (Option A)
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                - **Rationale**: Ensures uniqueness across the entire organizational structure, prevents confusion, maintains data integrity
- ✅ **Decision**: "Sales" CAN exist in both Organisation A and Organisation B within the same Enterprise
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                - **Rationale**: Different parent contexts allow same names (e.g., "Sales" division in Org A vs Org B)
- ✅ **Decision**: "Sales" CAN exist as both a Division and a Department within the same Organisation
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                - **Rationale**: Different types with same name are allowed (e.g., "Sales" Division and "Sales" Department are distinct)
- ✅ **Decision**: When name conflict is detected, reject the operation with a clear validation error
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                - **Rationale**: Better UX than auto-append (user maintains control), prevents accidental duplicates, forces intentional naming
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                - **Error Message**: "A team with the name '{name}' already exists in this context. Please choose a different name."
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                - **Validation Rule**: Check uniqueness within same parent AND same type (allows same name in different parents or different types)

#### $\color{green}{\text{3.1.3 Executive and Deputy Constraints}}$

**Current Understanding:**

- All teams have a mandatory `Executive` (User model)
- All teams have an optional `Deputy` (User model)

**Decisions:**

- ✅ **Decision**: Executive and Deputy CANNOT be the same person
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                - **Rationale**: Deputy serves as backup/authority, must be different person for proper delegation
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                - **Validation**: Enforce `executive_id != deputy_id` (when deputy is set)
- ✅ **Decision**: One User CAN be Executive of multiple teams
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                - **Rationale**: Common in organizational structures (e.g., CEO is executive of multiple divisions)
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                - **No restrictions**: Allow unlimited assignments
- ✅ **Decision**: One User CAN be Deputy of multiple teams
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                - **Rationale**: Same logic as Executive - allows flexible organizational structures
- ✅ **Decision**: A User CAN be Executive of one team and Deputy of another
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                - **Rationale**: Allows for complex organizational hierarchies and cross-team relationships
- ✅ **Decision**: Executive/Deputy MUST be from the same Enterprise
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                - **Rationale**: Security and data isolation - prevents cross-tenant access, maintains tenancy boundaries
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                - **Validation**: Check `user.tenant_id == team.tenant_id` (via Enterprise relationship)
- ✅ **Decision**: When Executive/Deputy User is deleted or deactivated, require reassignment
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                - **Rationale**: Executive is mandatory field, cannot be null
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                - **Implementation**:
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                - On user deletion: Prevent deletion if user is Executive/Deputy, or require reassignment first
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                - On user deactivation: Allow but show warning, optionally auto-assign Deputy as Executive if Deputy exists
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                - **Auto-promotion**: If Executive is removed and Deputy exists, optionally promote Deputy to Executive (configurable)

### $\color{yellow}{\text{3.2 Slug Generation Rules}}$

#### $\color{green}{\text{3.2.1 Slug Uniqueness}}$

**Current Understanding:**

- Slugs are unique within team hierarchy graph
- Slugs are translatable (per locale)

**Decisions:**

- ✅ **Decision**: Slug uniqueness is per locale (Option B)
                                                                                                                                                                                                                                                                - **Rationale**: Allows same slug in different languages (e.g., "sales" in English, "ventas" in Spanish), supports internationalization
- ✅ **Decision**: Slug uniqueness is per graph (Enterprise tree), not globally
                                                                                                                                                                                                                                                                - **Rationale**: Matches name uniqueness strategy, allows same slug across different Enterprises
- ✅ **Decision**: On slug conflict, reject the operation with a clear validation error
                                                                                                                                                                                                                                                                - **Rationale**: Consistent with name conflict handling, maintains user control, prevents accidental duplicates
                                                                                                                                                                                                                                                                - **Error Message**: "A team with the slug '{slug}' already exists in this context for locale '{locale}'. Please choose a different name."
                                                                                                                                                                                                                                                                - **Note**: Since slugs are auto-generated from names, conflicts should be rare (handled at name validation level)
- ✅ **Decision**: When parent name changes, all child slugs regenerate automatically
                                                                                                                                                                                                                                                                - **Rationale**: Maintains hierarchical slug structure (parent-slug/child-slug), ensures URLs remain consistent
                                                                                                                                                                                                                                                                - **Implementation**: Use model events (`updating` on parent) to trigger slug regeneration for all descendants
                                                                                                                                                                                                                                                                - **Performance**: Consider queuing slug regeneration for large hierarchies

#### $\color{green}{\text{3.2.2 Hierarchical Slug Pattern}}$

**Current Understanding:**

- Slugs generated from parent name + current name
- Pattern: `{parent-slug}/{current-slug}`

**Decisions:**

- ✅ **Decision**: Slug generation pattern uses forward slash separator: `{parent-slug}/{current-slug}`
                                                                                                                                - **Example**: Enterprise "Acme Corp" → Organisation "Sales Team" → slug: `acme-corp/sales-team`
                                                                                                                                - **Rationale**: Forward slash is URL-friendly, clearly shows hierarchy, works well with routing
                                                                                                                                - **Root level**: Enterprise slugs have no prefix (e.g., `acme-corp`)
- ✅ **Decision**: Maximum slug length is 200 characters (per segment)
                                                                                                                                - **Rationale**: Database limit is 255, but URLs should be shorter for usability, SEO, and sharing
                                                                                                                                - **Validation**: Truncate and append unique suffix if needed, or reject with error if name is too long
- ✅ **Decision**: Special characters handled via standard slugification
                                                                                                                                - **Process**:

                                                                                                                                                                                                                                                                1. Convert to lowercase
                                                                                                                                                                                                                                                                2. Replace spaces with hyphens
                                                                                                                                                                                                                                                                3. Remove special characters (keep only alphanumeric, hyphens, forward slashes)
                                                                                                                                                                                                                                                                4. Collapse multiple hyphens to single hyphen
                                                                                                                                                                                                                                                                5. Trim hyphens from start/end

                                                                                                                                - **Example**: "Acme Corp & Co." → "acme-corp-co"
- ✅ **Decision**: Slugs are case-insensitive
                                                                                                                                - **Rationale**: URLs are case-insensitive by standard, prevents confusion, better UX
                                                                                                                                - **Storage**: Always store in lowercase
                                                                                                                                - **Comparison**: Use case-insensitive comparison for uniqueness checks

### $\color{yellow}{\text{3.3 Context Scoping Rules}}$

#### $\color{green}{\text{3.3.1 Context Storage}}$

**Decisions:**

- ✅ **Decision**: Context storage uses database-based persistence with session caching (Hybrid: Option B + A)
                                                                - **Database**: Store `current_organisation_id`, `current_division_id`, `current_department_id` in `users` table
                                                                - **Session**: Cache context in session for performance (avoid DB query on every request)
                                                                - **Rationale**: Persists across sessions/logouts, survives server restarts, allows admin to see user's context
- ✅ **Decision**: Context is per-user (not per-session)
                                                                - **Rationale**: Consistent experience across devices/sessions, allows admin visibility, simpler implementation
- ✅ **Decision**: Context persisted via database, cached in session
                                                                - **Flow**:

                                                                                                                                1. On context switch: Update database, update session cache
                                                                                                                                2. On request: Check session cache first, fallback to database if missing
                                                                                                                                3. On login: Load from database into session

- ✅ **Decision**: Context persists on logout/login
                                                                - **On Logout**: Context remains in database (not cleared)
                                                                - **On Login**: Load user's last context from database into session
                                                                - **Rationale**: Better UX - user returns to their last working context
- ✅ **Decision**: Users have one active context across all browser tabs/sessions
                                                                - **Rationale**: Simpler implementation, prevents confusion, consistent data view
                                                                - **Note**: If user switches context in one tab, other tabs will see new context on next request (session-based)

#### $\color{green}{\text{3.3.2 Context Switching Permissions}}$

**Decisions:**

- ✅ **Decision**: Context switching requires permission checks
                                - **Default**: Users can switch to any Organisation in their Enterprise
                                - **Restriction**: Users can be restricted to specific Organisations via permissions/roles
                                - **Permission**: `switch-context` or `manage-context` permission
                                - **Rationale**: Security and access control, prevents unauthorized access to organizational data
- ✅ **Decision**: Users CAN be restricted to specific Organisations
                                - **Implementation**:
                                                                - Many-to-many relationship: `users` ↔ `organisations` (via `user_organisation_access` pivot table)
                                                                - Or: Use Spatie Permission with context-aware permissions
                                - **Enforcement**:
                                                                - Check access before allowing context switch
                                                                - Filter available contexts in UI to only show accessible Organisations
                                                                - Validate on API requests
- ✅ **Decision**: When user tries to switch to inaccessible Organisation, reject with 403 Forbidden error
                                - **UI**: Show error message, don't allow switch
                                - **API**: Return 403 with error message
                                - **Error Message**: "You do not have access to switch to this Organisation."
- ✅ **Decision**: Context switching available both via UI and programmatically (API)
                                - **Rationale**: Supports automation, integrations, admin tools
                                - **API Endpoint**: `POST /api/context/switch` with `organisation_id` parameter
                                - **Validation**: Same permission checks apply for API and UI

#### $\color{green}{\text{3.3.3 Context Scoping in Queries}}$

**Decisions:**

- ✅ **Decision**: Context scoping uses local scope (not global scope)
                - **Rationale**: More flexible, allows explicit control, easier to bypass when needed
                - **Implementation**: `scopeInContext()` method on models
                - **Usage**: `Team::inContext()->get()` (explicit) vs automatic global scope
- ✅ **Decision**: Only Team models (and subtypes) are affected by context scoping
                - **Affected**: Organisation, Division, Department, Project
                - **Not Affected**: Enterprise (tenant-level), User (user-level)
                - **Rationale**: Context is specifically for Organisation/Division/Department filtering
- ✅ **Decision**: Bypass context scoping using `withoutContextScope()` method
                - **Usage**: `Team::withoutContextScope()->get()`
                - **Permission**: Require `bypass-context-scope` permission for security
                - **Rationale**: Needed for admin views, reports, cross-context operations
- ✅ **Decision**: Default context is user's first accessible Organisation, or null if none
                - **On Login**:

                                1. Check if user has saved context in database
                                2. If yes, use saved context (if still accessible)
                                3. If no, use first accessible Organisation
                                4. If none accessible, context is null (user sees Enterprise-level only)

                - **Rationale**: Sensible default, good UX, prevents errors

### $\color{yellow}{\text{3.4 Tenancy Rules}}$

#### $\color{green}{\text{3.4.1 Enterprise as Tenant}}$

**Current Understanding:**

- Enterprise implements `TenantContract`
- Subdomain maps to Enterprise

**Decisions:**

- ✅ **Decision**: Subdomains mapped via wildcard DNS setup
                - **Pattern**: `{enterprise-slug}.example.com` → Enterprise
                - **Implementation**: Use `stancl/tenancy` domain identification
                - **Validation**: Validate subdomain matches Enterprise slug (for security)
                - **Rationale**: Scalable, automatic, standard SaaS pattern
- ✅ **Decision**: Main application uses root domain: `example.com` or `app.example.com`
                - **Purpose**: Marketing site, login page, Enterprise creation/management
                - **Tenancy**: Root domain is not tenant-scoped (public access)
- ✅ **Decision**: SSL certificate handling via Let's Encrypt wildcard certificate
                - **Option A**: Wildcard certificate (`*.example.com`) - simpler, one certificate
                - **Option B**: Per-subdomain certificates - more complex, better for custom domains
                - **Recommendation**: Start with wildcard, upgrade to per-subdomain if needed for custom domains
                - **Implementation**: Use Laravel Forge, Cloudflare, or similar for automatic SSL
- ✅ **Decision**: One Enterprise CAN have multiple domains/subdomains
                - **Use Case**: Custom domains (e.g., `acme.com` → Enterprise "Acme")
                - **Implementation**: `domains` table with `enterprise_id` foreign key
                - **Validation**: Ensure domain uniqueness, validate ownership (DNS TXT record)
                - **Rationale**: Supports white-labeling, custom branding

#### $\color{green}{\text{3.4.2 Data Isolation}}$

**Decisions:**

- ✅ **Decision**: Data isolation enforced via automatic scoping using `BelongsToTenant` trait
                - **Implementation**: All tenant-scoped models use `BelongsToTenant` from `stancl/tenancy`
                - **Automatic**: Queries automatically filtered by current tenant (Enterprise)
                - **Manual Checks**: Only needed for cross-tenant operations (which are restricted)
                - **Rationale**: Prevents data leaks, simplifies code, follows tenancy best practices
- ✅ **Decision**: Cross-tenant relationships are NOT allowed
                - **Rationale**: Security and data isolation - strict tenant boundaries
                - **Exception**: System-level operations (e.g., super admin views) require explicit bypass
                - **Implementation**:
                                - Foreign keys validate tenant_id matches
                                - Queries automatically scoped by tenant
                                - Validation rules prevent cross-tenant assignments
- ✅ **Decision**: Users CAN belong to multiple Enterprises
                - **Implementation**:
                                - `users` table has `tenant_id` (current/default Enterprise)
                                - Many-to-many: `users` ↔ `enterprises` (via `user_enterprise` pivot table)
                                - User can switch between Enterprises (similar to context switching)
                - **Management**:
                                - User selects Enterprise on login (if multiple)
                                - User can switch Enterprise (with permission)
                                - Each Enterprise maintains separate context (Organisation/Division/Department)
                - **Rationale**: Supports consultants, multi-tenant users, enterprise partnerships

## $\color{orange}{\text{4. Implementation Plan}}$

### $\color{yellow}{\text{4.1 Phase 1: Package Installation}}$

#### $\color{green}{\text{4.1.1 Core Framework Packages}}$

- `filament/filament:^5.0` - Admin panel
- `livewire/livewire:^4.0` - Livewire framework
- `stancl/tenancy` - Multi-tenancy

#### $\color{green}{\text{4.1.2 Filament Plugins}}$

- `bezhansalleh/filament-shield` - Role & permission management for Filament
- `filament/spatie-laravel-media-library-plugin:^5.0` - Media library integration
- `filament/spatie-laravel-settings-plugin:^5.0` - Settings management integration
- `lara-zeus/spatie-translatable` - Translatable fields support for Filament
- `pxlrbt/filament-activity-log` - Activity log viewer for Filament
- `shuvroroy/filament-spatie-laravel-backup` - Backup management for Filament
- `shuvroroy/filament-spatie-laravel-health` - Health checks for Filament

#### $\color{green}{\text{4.1.3 Model Enhancement Packages}}$

- `symfony/uid:^7.2` - ULID generation (required by Laravel for `Str::ulid()`, see Decisions section)
- `cviebrock/eloquent-sluggable` - Slug generation (works with translatable attributes)
- `spatie/laravel-translatable` - Translatable content (name, description, slug)
- `spatie/laravel-translation-loader` - Translation loading (for UI strings and messages)
- `staudenmeir/laravel-adjacency-list` - Hierarchical relationships
- `tightenco/parental` - Single Table Inheritance (STI)

#### $\color{green}{\text{4.1.4 State & Status Management}}$

- `spatie/laravel-model-states` - Model state machine
- `spatie/laravel-model-status` - Model status tracking

#### $\color{green}{\text{4.1.5 Data & Validation}}$

- `spatie/laravel-data` - DTOs

#### $\color{green}{\text{4.1.6 Permissions & Security}}$

- `spatie/laravel-permission` - Roles & permissions
- `ukeloop/laravel-impersonatable-guard` - User impersonation

#### $\color{green}{\text{4.1.7 Auditing & Monitoring}}$

- `laravel-auditing/auditing` - Model auditing
- `binafy/laravel-user-monitoring` - User activity monitoring
- `spatie/laravel-activitylog` - Activity logging

#### $\color{green}{\text{4.1.8 Additional Features}}$

- `spatie/laravel-tags` - Tagging system
- `spatie/laravel-medialibrary` - Media management
- `spatie/laravel-settings` - Settings management
- `spatie/laravel-backup` - Backup management
- `spatie/laravel-health` - Health checks
- `spatie/laravel-sitemap` - Sitemap generation
- `spatie/laravel-horizon-watcher` - Horizon monitoring

#### $\color{green}{\text{4.1.9 Laravel Ecosystem}}$

- `laravel/horizon` - Queue monitoring
- `laravel/pennant` - Feature flags
- `laravel/pulse` - Application monitoring
- `laravel/socialite` - Social authentication
- `laravel/telescope` - Debugging tool

#### 4.1.10 Additional Packages

- `lakshan-madushanka/commenter` - Comment system

#### 4.1.11 Testing & Quality Assurance Packages

- `behat/behat` - BDD testing framework
- `behat/mink` - Browser emulation for Behat
- `behat/mink-extension` - Mink extension for Behat
- `behat/mink-goutte-driver` - Headless browser driver (fallback when Playwright unavailable)
- `behat/mink-selenium2-driver` - Selenium driver for browser tests (fallback when Playwright unavailable)
- `pestphp/pest` - Already installed (v4 with Playwright integration)
- `phpunit/phpunit` - Already installed (via Pest)
- `phpstan/phpstan` - PHP static analysis
- `larastan/larastan` - Laravel-specific static analysis
- `rector/rector` - PHP refactoring and static analysis
- `vimeo/psalm` - PHP static analysis (required)
- `phpmd/phpmd` - PHP Mess Detector (required)

**Note**: `nunomaduro/larastan` is the same package as `larastan/larastan` (package was renamed), use only `larastan/larastan`

#### 4.1.12 JavaScript/TypeScript Quality Tools

- `vitest` - JavaScript/TypeScript testing framework
- `@vitest/ui` - Vitest UI for interactive testing
- `@vitest/coverage-v8` - Coverage provider for Vitest
- `@testing-library/jest-dom` - DOM testing utilities
- `eslint` - JavaScript/TypeScript linting
- `typescript` - TypeScript compiler
- `@typescript-eslint/eslint-plugin` - TypeScript ESLint plugin
- `@typescript-eslint/parser` - TypeScript ESLint parser
- `eslint-plugin-import` - Import/export linting
- `prettier` - Code formatting

### $\color{yellow}{\text{4.2 Phase 2: Testing Infrastructure Setup (TDD/BDD Foundation)}}$

#### $\color{green}{\text{4.2.1 Behat Configuration}}$

- Install and configure Behat (`behat.yml`)
- Set up Mink drivers (Goutte for fast tests, Selenium for browser tests)
- Configure Laravel context for Behat
- Set up feature file structure (`features/`)
- Configure test database and environment

#### $\color{green}{\text{4.2.2 Pest Configuration}}$

- Configure Pest v4 for browser testing with Playwright integration
- Set up browser test directory (`tests/Browser/`)
- Configure Playwright for Pest v4 (Pest includes Playwright support)
- Set up test database and factories
- Set up test helpers and custom assertions
- **Note**: Use Playwright for all browser testing where possible. Fallback to Mink/Selenium only when Playwright is unavailable.

#### $\color{green}{\text{4.2.3 Code Coverage Setup}}$

- Configure PHPUnit coverage for PHP (`phpunit.xml`)
- Set up Xdebug for PHP coverage (see Section 4.2.3.1 for Laravel/Herd installation)
- Configure coverage thresholds (100% requirement)
- Set up JavaScript coverage (Vitest with @vitest/coverage-v8)
- Configure coverage reporting (HTML, Clover, Cobertura, LCOV)

##### 4.2.3.1 Xdebug Setup in Laravel/Herd Environment

**Installation:**

```bash
# Herd uses its own PHP installation
# Check PHP version: herd php --version
# Install Xdebug via PECL (Herd's PHP)
herd php --pecl install xdebug
```

**Configuration:**

- Herd stores PHP configuration in: `~/Library/Application Support/Herd/config/php/{version}/conf.d/`
- Create `xdebug.ini` file in the conf.d directory:
```ini
[xdebug]
zend_extension=xdebug.so
xdebug.mode=coverage,debug
xdebug.start_with_request=yes
xdebug.coverage_enable=1
xdebug.log=/tmp/xdebug.log
```


**Verification:**

```bash
# Check Xdebug is loaded
herd php --phpinfo | grep xdebug

# Or via artisan
php artisan tinker
>>> phpinfo(INFO_MODULES)['xdebug']
```

**Note**: Herd may need restart after Xdebug installation: `herd restart`

#### $\color{green}{\text{4.2.4 Static Analysis Configuration}}$

- Configure PHPStan/Larastan (`phpstan.neon`)
- Set level to maximum (level 9) without ignoring rules
- Configure baseline generation for legacy code
- Configure Psalm (`psalm.xml`)
- Set Psalm to highest level without ignoring rules
- Configure PHPMD (`phpmd.xml`)
- Set up ESLint for JavaScript/TypeScript (`.eslintrc.js`)
- Configure TypeScript strict mode
- Set up Prettier for code formatting

**Static Analysis Tools:**

- **PHPStan/Larastan**: Type checking, dead code detection
- **Psalm**: Alternative type checker, additional security checks
- **PHPMD**: Code quality, complexity, design patterns
- **Rector**: Automated refactoring suggestions

#### $\color{green}{\text{4.2.5 Quality Gates & CI/CD}}$

- Set up GitHub Actions workflows (`.github/workflows/`)
- Configure quality gates (100% coverage, max static analysis)
- Set up automated test execution (PHP and JavaScript)
- Configure static analysis in CI (PHPStan, ESLint)
- Set up coverage reporting (Codecov integration)
- Configure quality reports generation
- Set up workflow for Behat BDD tests
- Set up workflow for Pest browser tests

### $\color{yellow}{\text{4.3 Phase 3: BDD Feature Files (Write Features First)}}$

#### $\color{green}{\text{4.3.1 User Management Features}}$

- `features/user/user_registration.feature` - User registration scenarios
- `features/user/user_authentication.feature` - Login/logout scenarios
- `features/user/user_profile.feature` - Profile management scenarios
- `features/user/user_tenant_assignment.feature` - Tenant assignment scenarios

#### $\color{green}{\text{4.3.2 Team Hierarchy Features}}$

- `features/team/enterprise_creation.feature` - Enterprise creation scenarios
- `features/team/organisation_management.feature` - Organisation CRUD scenarios
- `features/team/team_hierarchy.feature` - Hierarchy validation scenarios
- `features/team/team_slug_generation.feature` - Slug generation scenarios
- `features/team/team_translations.feature` - Translatable content scenarios

#### $\color{green}{\text{4.3.3 Tenancy Features}}$

- `features/tenancy/enterprise_tenant.feature` - Enterprise as tenant scenarios
- `features/tenancy/subdomain_routing.feature` - Subdomain identification scenarios
- `features/tenancy/context_switching.feature` - Organisation context switching scenarios
- `features/tenancy/data_isolation.feature` - Tenant data isolation scenarios

#### $\color{green}{\text{4.3.4 Filament Admin Features}}$

- `features/admin/team_resource.feature` - Team resource CRUD scenarios
- `features/admin/user_resource.feature` - User resource scenarios
- `features/admin/permissions.feature` - Permission management scenarios
- `features/admin/context_switching_ui.feature` - Context switching UI scenarios
- `features/admin/translatable_fields.feature` - Translatable field editing scenarios
- `features/admin/media_library.feature` - Media library integration scenarios
- `features/admin/activity_log.feature` - Activity log viewing scenarios

#### $\color{green}{\text{4.3.5 Behat Step Definitions}}$

- `features/bootstrap/FeatureContext.php` - Main Behat context
- `features/bootstrap/LaravelContext.php` - Laravel-specific steps
- `features/bootstrap/MinkContext.php` - Browser interaction steps
- Custom contexts for domain-specific steps (UserContext, TeamContext, TenantContext)

### $\color{yellow}{\text{4.4 Phase 4: TDD Implementation (Red-Green-Refactor)}}$

#### $\color{green}{\text{4.4.1 Trait Implementation (TDD)}}$

**HasUlid Trait**

1. Write Pest unit test for ULID generation
2. Write Pest test for route key binding
3. Implement trait to pass tests
4. Achieve 100% coverage
5. Pass PHPStan level 9

**HasTranslatableAttributes Trait**

1. Write Pest tests for translatable attributes
2. Write tests for translation helpers
3. Implement trait to pass tests
4. Achieve 100% coverage
5. Pass PHPStan level 9

**HasTranslatableSlug Trait**

1. Write Pest tests for slug generation
2. Write tests for hierarchical slugs
3. Write tests for multi-locale slugs
4. Implement trait to pass tests
5. Achieve 100% coverage
6. Pass PHPStan level 9

#### $\color{green}{\text{4.4.2 Model Implementation (TDD)}}$

**User Model**

1. Write Pest feature tests for User model
2. Write Behat scenarios for user features
3. Implement User model to pass tests
4. Write Pest browser tests for user UI
5. Achieve 100% coverage
6. Pass PHPStan level 9

**Team Model Hierarchy**

1. Write Pest tests for Team model
2. Write Behat scenarios for team features
3. Implement Team model to pass tests
4. Write Pest browser tests for team UI
5. Achieve 100% coverage
6. Pass PHPStan level 9

### $\color{yellow}{\text{4.5 Phase 5: Database Migrations (TDD Approach)}}$

**Note**: In TDD, migrations are written as part of the test-first approach. Write migration stubs first, then implement models to pass tests, then finalize migrations.

#### $\color{green}{\text{4.5.1 Update Users Table}}$

- Add `ulid` column (string, 26, unique, indexed)
- Add `slug` column (string, nullable, indexed) for translatable slugs
- Add `tenant_id` column (foreign key to teams, nullable) for Enterprise relationship
- Add `state` column for state machine (backed by native enum)
- Add `status` column for status tracking (backed by native enum)
- Add context columns: `current_organisation_id`, `current_division_id`, `current_department_id` (foreign keys, nullable)
- Add indexes for performance
- **Database**: PostgreSQL 18

#### $\color{green}{\text{4.5.2 Create Teams Table}}$

- Standard columns: `id`, `ulid`, `name`, `slug`, `description`
- Hierarchy: `parent_id` (self-referential), `type` (enum/string)
- Leadership: `executive_id`, `deputy_id` (foreign keys to users)
- State: `state` column for state machine (backed by native PostgreSQL enum type)
- Status: `status` column for status tracking (backed by native PostgreSQL enum type)
- Tenancy: `tenant_id` (for Enterprise tenant relationship)
- Translatable: JSONB columns for translatable fields (PostgreSQL JSONB for better performance)
- Timestamps and soft deletes
- **Database**: PostgreSQL 18
- **PostgreSQL-Specific Features**:
- Use JSONB instead of JSON for better query performance
- Use native PostgreSQL ENUM types for state/status columns
- GIN indexes for JSONB columns (full-text search, containment queries)
- B-tree indexes for hierarchy queries (`parent_id`, `tenant_id`, `type`)
- Composite indexes: `(tenant_id, parent_id, type)`, `(tenant_id, ulid)`
- Use `ltree` extension for hierarchical queries (optional, if needed)

#### $\color{green}{\text{4.5.3 Create Supporting Tables}}$

- `team_translations` - For translatable team fields
- `user_translations` - For translatable user fields
- `domains` - For stancl/tenancy domain mapping
- Permission tables (via spatie/laravel-permission)
- Activity log tables (via spatie/laravel-activitylog)
- Audit tables (via laravel-auditing)

### $\color{yellow}{\text{4.6 Phase 6: Model Implementation (Continued from Phase 4)}}$

#### $\color{green}{\text{4.6.1 Reusable Traits (app/Models/Concerns/)}}$

Create focused, composable traits instead of base model:**HasUlid Trait** (`app/Models/Concerns/HasUlid.php`)

- Generates ULID on model creation using `bootHasUlid()` method
- Sets route key name to 'ulid' via `getRouteKeyName()`
- Provides `scopeByUlid()` query scope
- Uses Laravel's `Str::ulid()` method: `(string) \Illuminate\Support\Str::ulid()`
- Requires `symfony/uid:^7.2` package (managed by Laravel as dependency)

**HasTranslatableAttributes Trait** (`app/Models/Concerns/HasTranslatableAttributes.php`)

- Wrapper for `spatie/laravel-translatable` HasTranslations trait
- Configures translatable attributes array
- Provides helper methods for translation management
- Handles JSON casting for translatable columns
- **Default Locales**: Initializes with `en_GB`, `en_US`, `de_DE`, `nl_NL` (Netherlands Dutch), `nl_BE` (Netherlands Flemish)
- **Locale Configuration**: Set in `config/translatable.php` with fallback chain

**HasTranslatableSlug Trait** (`app/Models/Concerns/HasTranslatableSlug.php`)

- Uses `cviebrock/eloquent-sluggable` Sluggable trait
- Uses `spatie/laravel-translatable` HasTranslations trait (depends on HasTranslatableAttributes)
- Generates slugs per locale from translatable name
- Handles hierarchical slug generation (parent name + current name)
- Provides `generateSlugsForAllLocales()` method
- Slug column is translatable (JSON), generated per locale

**Trait Benefits:**

- Flexible composition: Models can extend different base classes (User extends Authenticatable, Team extends Model)
- Single responsibility: Each trait has one clear purpose
- Reusable: Traits can be mixed and matched as needed
- Laravel-aligned: Follows framework patterns (like SoftDeletes, HasFactory)
- Testable: Traits can be tested independently
- No inheritance conflicts: Avoids PHP single inheritance limitations

#### $\color{green}{\text{4.6.2 Enhanced User Model}}$

- **Extends**: `Illuminate\Foundation\Auth\User` (Authenticatable)
- **Uses Traits**:
- `HasUlid` - ULID generation and route binding
- `HasTranslatableAttributes` - Translatable name/description
- `HasTranslatableSlug` - Translatable slug generation
- `BelongsToTenant` (from stancl/tenancy) - Enterprise relationship
- `HasStates` (from spatie/laravel-model-states) - State machine with native enum backing
- `HasStatuses` (from spatie/laravel-model-status) - Status tracking with native enum backing
- Existing: `HasFactory`, `Notifiable`, `TwoFactorAuthenticatable`
- **State & Status**:
- `UserState` enum (Draft, Pending, Active, Suspended, Archived) - backed by native PHP enum
- `UserStatus` enum (Online, Offline, Away, Busy) - backed by native PHP enum
- States/Statuses enhanced with Filament colors (e.g., Active=green, Suspended=orange, Archived=gray)
- **Relationships**:
- `tenant()` - Belongs to Enterprise (via BelongsToTenant)
- `organisations()` - Many-to-many with Organisation teams
- `currentOrganisation()` - Accessor for session context
- **Methods**:
- Context scoping methods (setCurrentOrganisation, etc.)
- Integration with auditing and monitoring packages

#### $\color{green}{\text{4.6.3 Team Model Hierarchy}}$

- **Base Team Model** extends `Illuminate\Database\Eloquent\Model`
- **Uses Traits**:
- `HasUlid` - ULID generation and route binding
- `HasTranslatableAttributes` - Translatable name/description
- `HasTranslatableSlug` - Hierarchical translatable slug generation
- `BelongsToTenant` (from stancl/tenancy) - Enterprise tenant relationship
- `HasRecursiveRelationships` (from staudenmeir) - Hierarchy support
- `HasChildren` (from tightenco/parental) - STI support
- `HasStates` (from spatie) - State machine
- `HasStatuses` (from spatie) - Status tracking
- `SoftDeletes` - Soft delete support
- **Child Models** (STI):
- **Enterprise** - Implements `TenantContract`, uses `HasChildren`
- **Organisation, Division, Department, Project** - Use `HasParent`
- All child models compose same traits as base Team model
- **Business Logic**:
- Hierarchy validation (via TeamHierarchy Value Object)
- Unique name per graph validation
- Slug generation from parent + name (hierarchical, translatable per locale)
- Executive/Deputy constraint validation
- Graph root identification methods

#### $\color{green}{\text{4.6.4 Supporting Classes}}$

- **Enums** (Native PHP 8.1+ enums):
- `TeamType` enum (Enterprise, Organisation, Division, Department, Project)
        - Enhanced with Filament colors: `getColor(): string`, `getIcon(): string`
- `TeamState` enum (Draft, Active, Inactive, Archived) - backed by native enum
        - Filament colors: Draft=gray, Active=green, Inactive=yellow, Archived=red
        - Methods: `getColor(): string`, `getLabel(): string`, `getBadge(): Badge`
- `TeamStatus` enum (Operational, UnderReview, Merging, Splitting) - backed by native enum
        - Filament colors: Operational=green, UnderReview=orange, Merging=blue, Splitting=purple
- `UserState` enum (Draft, Pending, Active, Suspended, Archived) - backed by native enum
        - Filament colors: Draft=gray, Pending=yellow, Active=green, Suspended=orange, Archived=red
- `UserStatus` enum (Online, Offline, Away, Busy) - backed by native enum
        - Filament colors: Online=green, Offline=gray, Away=yellow, Busy=red
- All enums implement `Filament\Enums\HasColor` interface or similar pattern
- Enum values stored as native PostgreSQL enum types in database
- **DTOs**: TeamData, UserData
- **Value Objects**: TeamHierarchy
- **Validation Rules**: UniqueNameInGraph
- **Policies**: TeamPolicy, EnterprisePolicy, OrganisationPolicy, UserPolicy (documented in Section 4.12.1)
- **Guards**: ContextGuard, TenantGuard (documented in Section 4.12.1)

### $\color{yellow}{\text{4.7 Phase 7: Tenancy Setup}}$

#### $\color{green}{\text{4.7.1 Tenancy Configuration}}$

- Configure Enterprise as tenant model
- Set up subdomain identification
- Configure single-database tenancy
- Set up bootstrappers (cache, filesystem, queue)

#### $\color{green}{\text{4.7.2 Context Scoping}}$

- Create context service/middleware
- Store current Organisation/Division/Department in database (users table) and session cache
- Create query scopes for context filtering
- Implement context switching UI using Folio file-based routing
- **Folio Integration**:
- Use Folio pages for context switching UI (`resources/views/pages/context/switch.blade.php`)
- Use Folio for all public-facing routes where possible
- Filament resources for admin panel, Folio for user-facing pages
- Folio already integrated with Livewire in layout
- Create Folio pages: `php artisan folio:page "context/switch"`, `php artisan folio:page "teams/{team}"`, etc.

### $\color{yellow}{\text{4.8 Phase 8: Filament Integration}}$

#### $\color{green}{\text{4.8.1 Filament v5 Setup}}$

- Install Filament panels
- Configure tenant panel
- Set up resource discovery

#### $\color{green}{\text{4.8.2 Filament Plugins Configuration}}$

- **bezhansalleh/filament-shield**: Configure role & permission management
- **filament/spatie-laravel-media-library-plugin**: Set up media library integration
- **filament/spatie-laravel-settings-plugin**: Configure settings management
- **lara-zeus/spatie-translatable**: Set up translatable fields support
- **pxlrbt/filament-activity-log**: Configure activity log viewer
- **shuvroroy/filament-spatie-laravel-backup**: Set up backup management UI
- **shuvroroy/filament-spatie-laravel-health**: Configure health checks UI

#### $\color{green}{\text{4.8.3 Team Resource}}$

- Form with translatable fields (using lara-zeus/spatie-translatable)
- Table with context filtering
- Actions for context switching
- Hierarchy visualization
- Media library integration (using filament plugin)
- Activity log integration (using filament plugin)

### $\color{yellow}{\text{4.9 Phase 9: Complete Test Suite}}$

#### $\color{green}{\text{4.9.1 Behat Feature Implementation}}$

- Implement all Behat step definitions
- Create custom Behat contexts for Laravel
- Set up database transactions for Behat tests
- Implement Mink page objects for complex UI interactions
- Ensure all Behat scenarios pass

#### $\color{green}{\text{4.9.2 Pest Test Suite}}$

**Unit Tests**

- Trait tests (HasUlid, HasTranslatableAttributes, HasTranslatableSlug)
- Model tests (User, Team, Enterprise, Organisation, etc.)
- Value Object tests (TeamHierarchy)
- DTO tests (TeamData, UserData)
- Enum tests (TeamType)
- State tests (TeamState)
- Validation rule tests (UniqueNameInGraph)

**Feature Tests**

- User authentication tests
- Team CRUD tests
- Hierarchy validation tests
- Slug generation tests
- Translation tests
- Tenant isolation tests
- Context switching tests

**Browser Tests (Pest v4 with Playwright)**

- User registration flow
- User login/logout
- Team creation flow
- Organisation context switching
- Filament admin panel interactions
- Translatable field editing
- Media library uploads
- **Note**: All browser tests use Playwright via Pest v4 integration. Fallback to Mink/Selenium only when Playwright unavailable.

#### $\color{green}{\text{4.9.3 JavaScript/TypeScript Tests}}$

- Utility function tests
- API integration tests
- Frontend validation tests
- UI interaction tests
- Livewire component tests (if applicable)
- Alpine.js component tests (if applicable)

#### $\color{green}{\text{4.9.4 Test Coverage Requirements}}$

- **PHP Coverage**: 100% for all application code
- Exclude vendor, migrations, config (if appropriate)
- Include all models, traits, services, controllers, actions
- Generate coverage reports (HTML, Clover, Cobertura)
- **JavaScript Coverage**: 100% for all application code
- Exclude node_modules, vendor
- Include all components, utilities, services
- Generate coverage reports

#### $\color{green}{\text{4.9.5 Static Analysis Requirements}}$

**PHP Static Analysis**

- PHPStan/Larastan level 9 (maximum) without ignoring rules
- Psalm highest level without ignoring rules
- PHPMD with all rules enabled
- Generate baseline for any legacy code that cannot be fixed immediately
- Include remediation recommendations in reports
- Run Rector for automated refactoring suggestions
- **All tools must pass**: PHPStan, Larastan, Psalm, PHPMD, Rector

**JavaScript/TypeScript Static Analysis**

- ESLint with strictest rules (no warnings allowed)
- TypeScript strict mode enabled
- All type errors must be resolved
- Generate static analysis reports with recommendations

#### $\color{green}{\text{4.9.6 Quality Reports}}$

- Generate PHPStan baseline report with remediation steps
- Generate ESLint report with fixable issues highlighted
- Generate code coverage reports (HTML, Clover, Cobertura)
- Generate test execution reports
- Create quality dashboard/metrics
- Document any baselined issues with remediation plans

### $\color{yellow}{\text{4.10 Phase 10: Documentation Structure}}$

The `t3-enhanced.md` will include:**Documentation Requirements:**

- **High-contrast, colored Mermaid diagrams** for:
- **Infrastructure Architecture**: System components, services, databases, deployment architecture
        - Show: Web servers, application servers, database (PostgreSQL 18), cache layers, queue workers
        - Use high-contrast colors: Blue for services, Green for databases, Orange for caches, Red for queues
- **Application Architecture**: Layers, models, relationships, trait composition
        - Show: Model hierarchy, trait relationships, service layers, controller/action patterns
        - Use high-contrast colors: Different colors per layer (Models=blue, Services=green, Controllers=orange)
- **Data Flow Diagrams**: User flows, data transformations, API flows
        - Show: User registration → Team creation → Context switching flows
        - Use high-contrast colors: Green for success paths, Red for error paths, Yellow for validation
- **Process Flow Diagrams**: Workflows, state machines, lifecycle transitions
        - Show: User lifecycle (Draft → Pending → Active → Suspended → Archived)
        - Show: Team lifecycle (Draft → Active → Inactive → Archived)
        - Use high-contrast colors: State-specific colors matching Filament enum colors
- **BDD Scenarios/Stories**: Gherkin feature visualizations
        - Show: Feature → Scenario → Steps flow
        - Use high-contrast colors: Green for Given, Blue for When, Orange for Then
- All diagrams must use high-contrast colors for accessibility (WCAG AA compliant)
- Diagrams should be interactive where possible (Mermaid supports clickable nodes)
- Include diagram code blocks in documentation for easy editing

**Content Structure:**

1. **Introduction & Architecture**

- Overview of the system
- Architecture decisions and rationale
- Tenancy model explanation
- Primary key strategy

2. **Package Installation Guide**

- Step-by-step installation commands
- Configuration requirements
- Post-installation setup

3. **Complete Composer.json**

- Full `composer.json` with all packages including Filament plugins
- Version constraints for all packages
- Repository configurations (if needed)
- Organized by category (Core, Filament Plugins, Model Enhancement, etc.)

4. **Database Setup**

- Migration files for all tables (PostgreSQL 18)
- Index strategies (optimized for PostgreSQL)
- Foreign key relationships
- Enum types (PostgreSQL native enums for states/statuses)

5. **Model Implementation**

- Reusable traits (HasUlid, HasTranslatableAttributes, HasTranslatableSlug)
- User model enhancements (using traits, extends Authenticatable)
- Team model hierarchy (using traits, extends Model)
- Supporting classes (Enums, States, DTOs, VOs)

6. **Tenancy Implementation**

- Enterprise tenant setup
- Context scoping implementation
- Middleware and services

7. **Filament Integration**

- Filament v5 panel setup
- Filament plugins configuration (Shield, Media Library, Settings, Translatable, Activity Log, Backup, Health)
- Team resource implementation
- User resource implementation
- Context switching UI
- Enum color enhancements (Filament badge colors for states/statuses)
- Folio integration (use Folio pages where possible, Filament resources where needed)

8. **Testing & Quality Assurance**

- BDD with Behat (complete feature files and step definitions)
- TDD approach and test-first development workflow
- Pest unit, feature, and browser tests (complete test suite)
- JavaScript/TypeScript test suite
- Code coverage setup and requirements (100% PHP and JS)
- Static analysis configuration (PHPStan level 9, ESLint strict)
- Quality gates and CI/CD integration
- Test execution and reporting
- Static analysis reports with remediation recommendations
- Code coverage reports (HTML, Clover, Cobertura)
- Quality metrics and dashboards
- Behat feature file examples
- Pest test examples (unit, feature, browser)
- JavaScript test examples
- Coverage configuration examples
- Static analysis configuration examples
- CI/CD workflow examples
- Quality report generation

### $\color{yellow}{\text{4.11 Phase 11: Performance & Optimization}}$

#### 4.11.1 Database Indexing Strategy

- Create composite indexes for common queries
- `(tenant_id, parent_id, type)` for team hierarchy queries
- `(tenant_id, ulid)` for route lookups
- `(executive_id, deputy_id)` for user-team relationships
- Add foreign key indexes (automatic in most databases)
- Create unique constraint indexes
- `(tenant_id, slug)` for unique slugs per tenant
- `(tenant_id, name, parent_id)` for unique names per graph
- Consider full-text search indexes for translatable fields (if needed)
- Document index usage in query optimization guide

#### 4.11.2 Query Optimization

- Implement eager loading strategy
- Default eager loads for common relationships (parent, children, executive, deputy)
- Lazy eager loading patterns for conditional relationships
- Create query result caching
- Cache team hierarchy trees
- Cache user permissions
- Cache context information
- Optimize N+1 query prevention
- Use `with()` for eager loading
- Use `loadMissing()` for conditional loading
- Document query optimization patterns

#### 4.11.3 Caching Strategy

- Translation caching
- Cache translatable content per locale
- Cache slug mappings
- Team hierarchy caching
- Cache full hierarchy trees per Enterprise
- Cache parent-child relationships
- Permission caching
- Cache user permissions per context
- Cache role assignments
- Context caching
- Cache current context per user session
- Cache available contexts per user
- Cache invalidation strategy
- Clear cache on team updates
- Clear cache on permission changes
- Clear cache on context switches

#### 4.11.4 Performance Testing

- Load testing
- Test team hierarchy queries under load
- Test context switching performance
- Test slug generation performance
- Database query performance tests
- Monitor query execution times
- Identify slow queries
- Optimize based on test results
- API performance tests (if applicable)
- Test API response times
- Test concurrent request handling

### $\color{yellow}{\text{4.12 Phase 12: Security Implementation}}$

#### 4.12.1 Authorization Policies & Guards

**Policy Classes:**

- `TeamPolicy` - CRUD operations for Team models
- `viewAny`, `view`, `create`, `update`, `delete`, `restore`, `forceDelete`
- `move` - Move team between parents
- `assignExecutive`, `assignDeputy` - Leadership assignment
- `EnterprisePolicy` - Enterprise-specific operations
- `viewAny`, `view`, `create`, `update`, `delete`
- `manageTenancy` - Manage tenant settings
- `switchEnterprise` - Switch between Enterprises
- `OrganisationPolicy` - Organisation-specific operations
- `viewAny`, `view`, `create`, `update`, `delete`
- `switchContext` - Switch Organisation context
- `UserPolicy` - User management operations
- `viewAny`, `view`, `create`, `update`, `delete`
- `impersonate` - User impersonation
- `managePermissions` - Permission management

**Gate Definitions:**

- `switch-context` - Context switching permission
- `manage-context` - Context management permission
- `bypass-context-scope` - Bypass context scoping
- `modify-hierarchy` - Modify team hierarchy
- `assign-leadership` - Assign Executive/Deputy
- `switch-enterprise` - Switch between Enterprises

**Guards:**

- `ContextGuard` - Custom guard for context-based authentication
- Validates user has access to requested context
- Checks Organisation/Division/Department access
- `TenantGuard` - Custom guard for tenant-based authentication
- Validates user belongs to Enterprise
- Checks cross-tenant access attempts

**Filament Integration:**

- Resource access permissions (via Filament Shield)
- Action permissions (create, update, delete actions)
- Field-level permissions (show/hide fields based on permissions)
- Context-aware permissions (permissions vary by context)

**Spatie Permission Integration:**

- Role-based access control (RBAC)
- Permission inheritance (roles inherit permissions)
- Context-aware permissions (permissions scoped to context)
- Team-level permissions (permissions per Organisation/Division/Department)

#### 4.12.2 Data Validation

- Create Form Request classes for all mutations
- `StoreTeamRequest`
- `UpdateTeamRequest`
- `SwitchContextRequest`
- Implement custom validation rules
- `UniqueNameInGraph` - Unique name validation
- `ValidHierarchy` - Hierarchy validation
- `ValidExecutiveDeputy` - Executive/Deputy constraint validation
- Add input sanitization
- Sanitize user input
- Validate data types
- Prevent injection attacks
- Implement XSS prevention
- Escape output in Blade templates
- Sanitize user-generated content

#### 4.12.3 API Security (if applicable)

- Rate limiting
- Per-user rate limits
- Per-tenant rate limits
- Per-endpoint rate limits
- API authentication
- Token-based authentication
- OAuth2 implementation (if needed)
- CORS configuration
- Configure allowed origins
- Configure allowed methods
- Configure allowed headers
- API versioning
- Version endpoints
- Maintain backward compatibility

#### 4.12.4 Security Testing

- Penetration testing
- Test for common vulnerabilities
- Test authorization bypass attempts
- SQL injection tests
- Test all database queries
- Test input validation
- XSS tests
- Test output escaping
- Test user-generated content
- CSRF tests
- Test form submissions
- Test API requests
- Authorization bypass tests
- Test permission checks
- Test context switching security

### $\color{yellow}{\text{4.13 Phase 13: Monitoring & Observability}}$

#### 4.13.1 Application Monitoring

- Performance monitoring
- Monitor response times
- Monitor database query times
- Monitor cache hit rates
- Error tracking
- Integrate Sentry or Bugsnag
- Track exceptions and errors
- Set up error alerts
- Uptime monitoring
- Monitor application availability
- Set up downtime alerts
- Health check endpoints
- Database connectivity
- Cache connectivity
- Queue connectivity
- External service connectivity

#### 4.13.2 Logging Strategy

- Structured logging
- Use Laravel's logging channels
- Log in JSON format
- Include context information
- Log channels
- `teams` - Team-related logs
- `users` - User-related logs
- `tenancy` - Tenancy-related logs
- `security` - Security-related logs
- Log rotation
- Configure log rotation
- Archive old logs
- Clean up old log files
- Log aggregation
- Centralize logs (if using external service)
- Search and filter logs
- Set up log alerts

#### 4.13.3 Business Metrics

- Team creation metrics
- Track team creation rates
- Track team type distribution
- Track hierarchy depth
- User activity metrics
- Track user logins
- Track context switches
- Track feature usage
- Context switching metrics
- Track context switch frequency
- Track context switch patterns
- Track context switch errors
- Feature usage metrics
- Track feature adoption
- Track feature usage patterns
- Identify unused features

### $\color{yellow}{\text{4.14 Phase 14: Documentation & Deployment}}$

#### 4.14.1 API Documentation (if applicable)

- Endpoint documentation
- Document all API endpoints
- Include request/response examples
- Include authentication requirements
- Request/response examples
- Provide example requests
- Provide example responses
- Include error responses
- Authentication examples
- Document authentication flow
- Provide code examples
- Error response documentation
- Document error codes
- Document error messages
- Provide troubleshooting guides

#### 4.14.2 Developer Documentation

- Architecture diagrams
- System architecture diagram
- Database schema diagram
- Component interaction diagram
- Data flow diagrams
- User registration flow
- Team creation flow
- Context switching flow
- Sequence diagrams for complex flows
- Team hierarchy validation flow
- Slug generation flow
- Permission check flow
- Troubleshooting guides
- Common issues and solutions
- Debugging tips
- Performance optimization tips

#### 4.14.3 User Documentation

- Context switching guide
- How to switch contexts
- Context switching limitations
- Troubleshooting context issues
- Team management guide
- How to create teams
- How to manage team hierarchy
- How to assign executives/deputies
- Permission management guide
- How to assign permissions
- How to manage roles
- How to check permissions

#### 4.14.4 Deployment Procedures

- Zero-downtime deployment
- Blue-green deployment strategy
- Rolling deployment strategy
- Database migration during deployment
- Rollback procedures
- Database rollback procedures
- Code rollback procedures
- Configuration rollback procedures
- Feature flags for gradual rollout
- Implement feature flags using Laravel Pennant
- Gradual feature rollout
- A/B testing capabilities

#### 4.14.5 Environment Configuration

- Development environment
- Local development setup
- Docker configuration (if applicable)
- Development database setup
- Staging environment
- Staging server configuration
- Staging database setup
- Staging testing procedures
- Production environment
- Production server configuration
- Production database setup
- Production monitoring setup
- Environment-specific configurations
- Configuration management
- Secret management
- Environment variable documentation

#### 4.14.6 CI/CD Enhancements

- Parallel test execution
- Run tests in parallel
- Optimize test execution time
- Test result caching
- Cache test results
- Cache dependencies
- Artifact storage
- Store build artifacts
- Store test reports
- Store coverage reports
- Deployment automation
- Automated deployment pipelines
- Automated rollback procedures
- Automated health checks

## $\color{orange}{\text{5. Files to Create/Update}}$

### $\color{yellow}{\text{5.1 New Files}}$

- `docs/user-model-enhancements/t3-enhanced.md` - Main documentation file

### $\color{yellow}{\text{5.2 Key Sections in Documentation}}$

1. Package list with installation commands
2. Complete `composer.json` output
3. Migration files (users, teams, translations, etc.)
4. Trait implementations (HasUlid, HasTranslatableAttributes, HasTranslatableSlug)
5. Model implementations (User, Team, Enterprise, etc.)
6. Tenancy configuration
7. Context scoping implementation
8. Filament plugins setup and configuration
9. Filament resources
10. Behat configuration and feature files
11. Pest test suite (unit, feature, browser)
12. JavaScript/TypeScript tests
13. Code coverage configuration and reports
14. Static analysis configuration (PHPStan, ESLint)
15. Quality gates and CI/CD setup
16. Test execution and quality reports

## $\color{orange}{\text{6. Implementation Notes}}$

- All models will have both `id` (integer) and `ulid` (string) columns
- Route model binding uses `ulid` via `getRouteKeyName()`
- Foreign keys continue using integer `id` for performance
- **Database**: PostgreSQL 18 with optimized indexes (GIN for JSON, B-tree for hierarchies)
- Translatable slugs stored as JSON in slug column (using spatie/laravel-translatable), generated per locale using cviebrock/eloquent-sluggable
- Slug generation pattern: Use cviebrock/eloquent-sluggable with source as closure that gets current locale's translatable name
- **Default Locales**: `en_GB` (British English), `en_US` (American English), `de_DE` (German), `nl_NL` (Netherlands Dutch), `nl_BE` (Netherlands Flemish/Belgian Dutch) - initialized for all translatables
- **Locale Configuration**: Set in `config/translatable.php` with fallback chain: `en_GB` → `en_US` → `de_DE` → `nl_NL` → `nl_BE`
- Enterprise is the tenant boundary, Organisation/Division/Department are context scopes
- **Trait-Based Architecture**: All shared functionality implemented as reusable traits in `app/Models/Concerns/`
- **Trait Composition**: Models compose behaviors via traits rather than extending a base model
- **Boot Method Pattern**: Traits use `boot{TraitName}()` methods following Laravel conventions (e.g., `bootHasUlid()`)
- **Trait Dependencies**: HasTranslatableSlug depends on HasTranslatableAttributes (compose in correct order)
- **No Base Model**: Models extend their appropriate base classes (User extends Authenticatable, Team extends Model)
- **State & Status**: All states and statuses use native PHP 8.1+ enums, enhanced with Filament colors
- **User Lifecycle**: User model uses states (Draft, Pending, Active, Suspended, Archived) and statuses (Online, Offline, Away, Busy)
- **Routing**: Use Folio file-based routing where possible (already integrated with Livewire)
- **Browser Testing**: Use Playwright via Pest v4 integration (fallback to Mink/Selenium when unavailable)
- **Static Analysis**: PHPStan, Larastan, Psalm, PHPMD all required and must pass
- **Documentation**: Include high-contrast, colored Mermaid diagrams for infrastructure, architecture, data flows, process flows, and BDD scenarios

### $\color{yellow}{\text{6.1 Testing & Quality Strategy}}$

- **BDD with Behat**: Write feature files first, then implement step definitions
- **TDD Approach**: Write tests before implementation (Red-Green-Refactor)
- **100% Code Coverage**: Both PHP and JavaScript must achieve 100% coverage
- **Maximum Static Analysis**: PHPStan level 9, ESLint strict mode, no ignored rules
- **Quality Gates**: All tests must pass, coverage must be 100%, static analysis must pass
- **Test Types**: Unit tests (Pest), Feature tests (Pest), Browser tests (Pest v4), BDD tests (Behat), JavaScript tests (Jest/Vitest)
- **Reporting**: Generate comprehensive reports for coverage, static analysis, and quality metrics
- **Remediation**: Document all baselined issues with remediation plans

### $\color{yellow}{\text{6.2 TDD/BDD Workflow}}$

1. **Write Behat Feature** - Define behavior in Gherkin syntax
2. **Write Pest Test** - Write failing unit/feature test
3. **Run Tests** - Verify tests fail (Red)
4. **Implement Code** - Write minimal code to pass tests
5. **Run Tests** - Verify tests pass (Green)
6. **Refactor** - Improve code while keeping tests green
7. **Check Coverage** - Ensure 100% coverage
8. **Run Static Analysis** - Fix all issues or baseline with remediation plan
9. **Generate Reports** - Create coverage and quality reports
10. **CI/CD Validation** - Ensure all quality gates pass

### $\color{yellow}{\text{6.3 Quality Requirements}}$

**Test Coverage**

- PHP: 100% coverage for all application code (models, traits, services, controllers, actions)
- JavaScript: 100% coverage for all application code (components, utilities, services)
- Coverage reports: HTML, Clover, Cobertura formats
- Coverage thresholds enforced in CI/CD

**Static Analysis**

- PHPStan/Larastan: Level 9 (maximum) without ignoring any rules
- Psalm: Highest level without ignoring any rules
- PHPMD: All rules enabled
- ESLint: Strictest configuration, no warnings allowed
- TypeScript: Strict mode enabled, all type errors resolved
- Baseline generation: Only for legacy code with documented remediation plans
- Reports: Include recommendations for fixing issues
- **All tools must pass**: PHPStan, Larastan, Psalm, PHPMD, Rector, ESLint

**Test Pass Rate**

- 100% test pass rate required
- All Behat scenarios must pass
- All Pest tests (unit, feature, browser) must pass
- All JavaScript tests must pass
- No skipped or incomplete tests allowed

**Code Quality Reports**

- PHPStan baseline report with remediation steps
- ESLint report with fixable issues highlighted
- Code coverage reports (HTML, Clover, Cobertura)
- Test execution reports
- Quality metrics dashboard
- Baselined issues documentation with remediation timelines

## $\color{orange}{\text{7. Decisions Made}}$

The following decisions have been finalized:

### $\color{yellow}{\text{7.1 ULID Generation Method}}$

**Decision**: Use Laravel's `Str::ulid()` method**Research Results**:

- ✅ **Confirmed**: `Str::ulid()` is available in Laravel 12
- ✅ **Implementation**: Located in `vendor/laravel/framework/src/Illuminate/Support/Str.php`
- ✅ **Returns**: `\Symfony\Component\Uid\Ulid` object (cast to string: `(string) Str::ulid()`)
- ✅ **Dependency**: Laravel requires `symfony/uid` package (^7.2) but manages it internally

**Options Analysis**:**Option A: Laravel's `Str::ulid()`** ✅ **SELECTED**

- **Pros**:
- Laravel-native method (idiomatic)
- Built into framework (no direct dependency management)
- Consistent with Laravel conventions
- Can be mocked/tested via Laravel's testing utilities
- Supports custom factory via `Str::createUlidsUsing()`
- **Cons**:
- Still requires `symfony/uid` package (but Laravel manages it)
- Returns object, needs string cast
- **Status**: ✅ Available in Laravel 12, Recommended

**Option B: Direct `symfony/uid` package**

- **Pros**:
- Direct control
- Well-maintained, widely used
- **Cons**:
- Less Laravel-idiomatic
- Bypasses Laravel's abstraction
- **Status**: Not recommended (use Laravel's method instead)

**Option C: Custom trait implementation**

- **Pros**: Full control
- **Cons**:
- Maintenance burden
- Reinventing the wheel
- **Status**: Not recommended

**Implementation**:

- Add `symfony/uid` to composer.json: `"symfony/uid": "^7.2"` (required by Laravel)
- Use in HasUlid trait: `(string) \Illuminate\Support\Str::ulid()`
- More Laravel-idiomatic than direct `new Ulid()`

### $\color{yellow}{\text{7.2 JavaScript Testing Framework}}$

**Decision**: Use Vitest**Installation & Setup**:

```bash
# Install Vitest and related packages
npm install --save-dev vitest @vitest/ui @vitest/coverage-v8 @testing-library/jest-dom
# or
bun add -d vitest @vitest/ui @vitest/coverage-v8 @testing-library/jest-dom
```

**Configuration** (`vitest.config.ts` or `vite.config.ts`):

```typescript
import { defineConfig } from 'vitest/config';
import { resolve } from 'path';

export default defineConfig({
  test: {
    globals: true,
    environment: 'jsdom', // or 'node' for backend tests
    coverage: {
      provider: 'v8',
      reporter: ['text', 'json', 'html', 'lcov', 'clover'],
      exclude: [
        'node_modules/',
        'tests/',
        '**/*.d.ts',
        '**/*.config.*',
        '**/dist/',
        '**/build/',
      ],
      thresholds: {
        lines: 100,
        functions: 100,
        branches: 100,
        statements: 100,
      },
    },
    include: ['resources/js/**/*.{test,spec}.{js,ts,jsx,tsx}'],
  },
  resolve: {
    alias: {
      '@': resolve(__dirname, './resources/js'),
    },
  },
});
```

**Package.json Scripts**:

```json
{
  "scripts": {
    "test": "vitest",
    "test:ui": "vitest --ui",
    "test:coverage": "vitest --coverage",
    "test:watch": "vitest --watch",
    "test:run": "vitest run"
  }
}
```

**Benefits of Vitest**:

- Faster execution (uses Vite's transform pipeline)
- Native ESM support
- Compatible with Vite (if using Vite for frontend)
- Jest-compatible API (easy migration)
- Built-in coverage support
- Watch mode with HMR

### $\color{yellow}{\text{7.3 PHP Coverage Tool}}$

**Decision**: Use Xdebug**Installation**:

```bash
# macOS (via Homebrew)
pecl install xdebug

# Ubuntu/Debian
sudo apt-get install php-xdebug

# Or via Docker/PHP-FPM
# Add to Dockerfile or install in container
```

**Configuration** (`php.ini` or `xdebug.ini`):

```ini
[xdebug]
zend_extension=xdebug.so
xdebug.mode=coverage,debug
xdebug.start_with_request=yes
xdebug.coverage_enable=1
```

**PHPUnit Configuration** (`phpunit.xml`):

```xml
<phpunit>
    <coverage>
        <report>
            <html outputDirectory="coverage/html"/>
            <clover outputFile="coverage/clover.xml"/>
            <cobertura outputFile="coverage/cobertura.xml"/>
        </report>
        <include>
            <directory suffix=".php">./app</directory>
        </include>
        <exclude>
            <directory>./vendor</directory>
            <directory>./database/migrations</directory>
            <directory>./config</directory>
        </exclude>
    </coverage>
</phpunit>
```

**Coverage Thresholds** (via Pest or PHPUnit):

```php
// In Pest.php or phpunit.xml
expect()->extend('toHaveCoverage', function (float $percentage) {
    // Custom coverage assertion
});
```

**Note**: Xdebug is chosen for compatibility and widespread use, despite being slower than PCOV. For production environments, ensure Xdebug is disabled or use separate PHP-FPM pools.

### $\color{yellow}{\text{7.4 CI/CD Platform}}$

**Decision**: Use GitHub Actions**Workflow File** (`.github/workflows/tests.yml`):

```yaml
name: Tests & Quality Checks

on:
  push:
    branches: [main, develop]
  pull_request:
    branches: [main, develop]

jobs:
  php-tests:
    runs-on: ubuntu-latest

    services:
      postgres:
        image: postgres:18
        env:
          POSTGRES_PASSWORD: password
          POSTGRES_DATABASE: testing
        ports:
                    - 5432:5432
        options: --health-cmd="pg_isready -U postgres" --health-interval=10s --health-timeout=5s --health-retries=3

    steps:
                        - uses: actions/checkout@v4

                        - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: xdebug, pdo, pdo_pgsql
          coverage: xdebug

                        - name: Install Composer Dependencies
        run: composer install --prefer-dist --no-progress

                        - name: Run Pest Tests
        run: php artisan test --coverage --min=100

            - name: Run PHPStan
        run: vendor/bin/phpstan analyse --level=9 --no-progress

            - name: Run Psalm
        run: vendor/bin/psalm --no-progress

            - name: Run PHPMD
        run: vendor/bin/phpmd app text phpmd.xml --minimumpriority 1

            - name: Upload Coverage
        uses: codecov/codecov-action@v3
        with:
          files: ./coverage/clover.xml
          flags: php

  js-tests:
    runs-on: ubuntu-latest

    steps:
                        - uses: actions/checkout@v4

                        - name: Setup Node.js
        uses: actions/setup-node@v4
        with:
          node-version: '20'

                        - name: Install Dependencies
        run: npm ci

                        - name: Run ESLint
        run: npm run lint

                        - name: Run Vitest Tests
        run: npm run test:coverage

                        - name: Upload Coverage
        uses: codecov/codecov-action@v3
        with:
          files: ./coverage/lcov.info
          flags: javascript
```

**Additional Workflows**:

- `.github/workflows/static-analysis.yml` - PHPStan, ESLint
- `.github/workflows/behat.yml` - Behat BDD tests
- `.github/workflows/quality-gates.yml` - Quality checks and reporting