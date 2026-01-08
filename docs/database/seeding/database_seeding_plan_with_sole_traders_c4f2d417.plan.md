---
name: Database Seeding Plan with Sole Traders
overview: Create a comprehensive seeding strategy for 26 enterprises (1 landlord id=0 + 25 tenants including 5 sole traders), 14 roles (4 key/protected), diverse organizational structures, and translation files for 16 languages covering all translatable strings.
todos:
  - id: create-landlord-seeder
    content: Create LandlordSeeder to create Enterprise with id=0, system admin user, and global roles
    status: completed
  - id: enhance-role-seeder
    content: Enhance RoleSeeder to create 14 roles (4 key roles) per tenant context
    status: completed
  - id: create-enterprise-seeder
    content: "Create EnterpriseSeeder with 25 diverse enterprises (5 sole traders + 20 regular: 5 small, 10 medium, 5 large)"
    status: completed
  - id: create-user-seeder
    content: Create UserSeeder with diverse users and role assignments (different for sole traders vs regular)
    status: completed
  - id: enhance-org-seeder
    content: Enhance OrganisationSeeder to create diverse organizational structures (skip sole traders)
    status: completed
  - id: enhance-division-seeder
    content: Enhance DivisionSeeder to create varied division hierarchies (skip sole traders)
    status: completed
  - id: enhance-department-seeder
    content: Enhance DepartmentSeeder to create departments under divisions/orgs (skip sole traders)
    status: completed
  - id: enhance-project-seeder
    content: Enhance ProjectSeeder to create projects (include sole traders - projects can be under enterprise)
    status: completed
  - id: enhance-domain-seeder
    content: Enhance DomainSeeder to create domains for all enterprises including landlord
    status: completed
  - id: update-database-seeder
    content: Update DatabaseSeeder with correct execution order
    status: completed
  - id: create-translation-files
    content: Create 16 language translation files (lang/{locale}/messages.php) with all translatable strings from codebase
    status: completed
---

# Database Seeding Plan with Sole Traders

## Overview

This plan creates a complete seeding strategy for the multi-tenant application with:

- **1 Landlord Enterprise** (id=0)
- **25 Tenant Enterprises** including:
  - **5 Sole Trader Enterprises** (no organisations, divisions, or departments)
  - **20 Regular Enterprises** with diverse organizational structures
- **14 Roles** (4 key/protected: Admin, Executive, Deputy, Owner)
- **Translation files** for 16 languages covering all `__()` translatable strings

## Architecture

The seeding follows this hierarchy:

```
Enterprise (Tenant)
  └── Organisation (optional - not for sole traders)
      └── Division (optional)
          └── Department (optional)
              └── Project (optional)
```

## Implementation Strategy

### 1. Landlord Enterprise Seeder

**File**: `database/seeders/LandlordSeeder.php`

- Create Enterprise with `id=0` (requires direct DB insert or sequence manipulation)
- Name: "Landlord" (translatable)
- ULID: Generate normally
- Type: Enterprise
- No parent_id
- Create domain: `landlord.test`
- Create system admin user with Super Admin role (team_id=0 for global role)
- Create all 14 roles in global context (team_id=0)

### 2. Role Seeder Enhancement

**File**: `database/seeders/RoleSeeder.php`

Create 14 roles per tenant context:

- **Key Roles** (`is_key=true`): Admin, Executive, Deputy, Owner
- **Standard Roles**: Customer, Guest, Host, Manager, Member, Partner, Subscriber, User, Vendor, Visitor

**Implementation Notes**:

- Roles are scoped to `team_id` (tenant context)
- Use `setPermissionsTeamId($tenantId)` before creating roles
- For landlord (id=0), create global roles
- For each tenant, create tenant-scoped roles
- All roles use `guard_name = 'web'`

### 3. Enterprise Seeder

**File**: `database/seeders/EnterpriseSeeder.php`

Create 25 diverse enterprises:

**Distribution Strategy**:

- **5 Sole Trader Enterprises**:
  - No organisations, divisions, or departments
  - Direct projects under enterprise (if any)
  - Examples: "Freelance Design Studio", "Independent Consultant", "Solo Developer", "Personal Brand", "Individual Practitioner"

- **5 Small Enterprises**:
  - 1-2 organisations, minimal hierarchy
  - Examples: "Local Retail Chain", "Small Manufacturing Co", "Regional Services"

- **10 Medium Enterprises**:
  - 2-4 organisations, 1-2 divisions each
  - Examples: "Mid-Size Tech Corp", "Regional Healthcare Group", "Multi-Location Retailer"

- **5 Large Enterprises**:
  - 3-6 organisations, multiple divisions/departments
  - Examples: "Global Tech Solutions", "International Finance Group", "Enterprise Healthcare Partners"

**Naming Convention**:

- Use diverse industry names
- Sole traders: Personal/professional names
- Regular enterprises: Corporate names

**For Each Enterprise**:

1. Create Enterprise with unique name (translatable)
2. Generate ULID
3. Create domain: `{slug}.test`
4. Create initial users:

  - Sole traders: Owner (1), maybe 1-2 Members
  - Regular: Owner (1), Executive (1-2), Admin (1-3)

5. Assign roles in tenant context
6. Create roles for this tenant

### 4. Organisation Seeder

**File**: `database/seeders/OrganisationSeeder.php`

**Important**: Skip sole trader enterprises (no organisations)

Create diverse organisational structures:

- **Small enterprises**: 1-2 organisations
- **Medium**: 2-4 organisations
- **Large**: 3-6 organisations

**Distribution**:

- Flat structures: Some enterprises with 1 organisation
- Hierarchical: Some with multiple organisations
- Mixed: Varying depths

### 5. Division Seeder

**File**: `database/seeders/DivisionSeeder.php`

**Important**: Skip sole trader enterprises

Create divisions:

- **0-3 divisions per organisation** (some orgs have none, some have many)
- Not all organisations need divisions
- Some enterprises have flat org structure (no divisions)

### 6. Department Seeder

**File**: `database/seeders/DepartmentSeeder.php`

**Important**: Skip sole trader enterprises

Create departments:

- **0-5 departments per division** (or directly under org if no divisions)
- Some divisions have no departments
- Some organisations have departments directly (no divisions)

### 7. Project Seeder

**File**: `database/seeders/ProjectSeeder.php`

Create projects for ALL enterprises (including sole traders):

- **Sole Traders**: 0-5 projects directly under enterprise
- **Regular Enterprises**: 0-10 projects per department/division
- Projects can exist at any level:
  - Under enterprise (sole traders)
  - Under organisation (if no divisions/departments)
  - Under division (if no departments)
  - Under department (normal case)

### 8. User Seeder

**File**: `database/seeders/UserSeeder.php`

Create users with diverse role assignments:

**Sole Trader Enterprises**:

- Owner (1) - required
- Member (0-2) - optional
- Guest (0-1) - optional

**Regular Enterprises**:

- Owner (1) - required
- Executive (1-2)
- Admin (1-3)
- Deputy (0-1 per organisation)
- Manager (2-5)
- Member (5-20)
- Other roles as needed

**Cross-tenant users**: Some users belong to multiple enterprises

**Context assignment**: Users have `current_context_id` set to:

- For sole traders: null or enterprise itself
- For regular: an organisation within their tenant

### 9. Domain Seeder

**File**: `database/seeders/DomainSeeder.php`

Create domains for all enterprises:

- **Landlord**: `landlord.test`
- **Each Enterprise**: `{enterprise-slug}.test`
- Domains link to tenant via `tenant_id` (ULID)
- Use `Domain::query()->firstOrCreate()` to avoid duplicates

### 10. Translation Files

**Directory**: `lang/{locale}/`

Create translation files for 16 languages:

- `pt_BR`, `zh_CN`, `de_DE`, `es_ES`, `fr_FR`, `en_GB`, `it_IT`, `ja_JP`, `ko_KR`, `es_MX`, `nl_NL`, `fl_NL`, `ru_RU`, `tr_TR`, `en_US`, `vi_VN`

**Translation Keys** (extracted from codebase `__()` calls):

**Auth Strings**:

- `Confirm password`
- `This is a secure area of the application. Please confirm your password before continuing.`
- `Password`
- `Confirm`
- `Reset password`
- `Please enter your new password below`
- `Forgot password`
- `Enter your email to receive a password reset link`
- `Email Address`
- `Email password reset link`
- `Or, return to`
- `log in`
- `Authentication Code`
- `Enter the authentication code provided by your authenticator application.`
- `Recovery Code`
- `Continue`
- `or you can`
- `login using a recovery code`
- `login using an authentication code`

**Settings Strings**:

- `Settings`
- `Manage your profile and account settings`
- `Profile`
- `Update your name and email address`
- `Name`
- `Email`
- `Your email address is unverified.`
- `Click here to re-send the verification email.`
- `A new verification link has been sent to your email address.`
- `Save`
- `Saved.`
- `Delete Account`
- `Permanently delete your account and all of its resources`
- `Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.`
- `Delete account`
- `Are you sure you want to delete your account?`
- `Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.`
- `Cancel`
- `Two-Factor Auth`
- `Appearance`
- `Want to delete your account?`
- `Go to Delete Account page`
- `Password updated successfully.`

**Dashboard & Navigation**:

- `Dashboard`
- `Platform`
- `Repository`
- `Documentation`
- `Search`
- `Log Out`

**Teams/Context**:

- `Select Context`
- `Unknown Context`
- `Available Contexts`

**Two-Factor Auth**:

- `Enable Two Factor Authentication`
- `Scan this QR code with your authenticator app to enable two-factor authentication.`
- `I've scanned the QR code`
- `Failed to enable two-factor authentication.`
- `Failed to disable two-factor authentication.`
- `The provided two factor authentication code was invalid.`
- `2FA Recovery Codes`
- `Recovery codes let you regain access if you lose your 2FA device. Store them in a secure password manager.`
- `View Recovery Codes`
- `Hide Recovery Codes`
- `Regenerate Codes`
- `Each recovery code can be used once to access your account and will be removed after use. If you need more, click Regenerate Codes above.`

**Email Verification**:

- `Please verify your email address by clicking on the link we just emailed to you.`
- `A new verification link has been sent to the email address you provided during registration.`
- `Resend verification email`

**File Structure**:

```
lang/
├── pt_BR/
│   └── messages.php
├── zh_CN/
│   └── messages.php
├── de_DE/
│   └── messages.php
├── es_ES/
│   └── messages.php
├── fr_FR/
│   └── messages.php
├── en_GB/
│   └── messages.php
├── it_IT/
│   └── messages.php
├── ja_JP/
│   └── messages.php
├── ko_KR/
│   └── messages.php
├── es_MX/
│   └── messages.php
├── nl_NL/
│   └── messages.php
├── fl_NL/
│   └── messages.php
├── ru_RU/
│   └── messages.php
├── tr_TR/
│   └── messages.php
├── en_US/
│   └── messages.php
└── vi_VN/
    └── messages.php
```

**Translation File Format**:

Each `messages.php` file contains:

```php
<?php

return [
    'Confirm password' => '...',
    'Password' => '...',
    // ... all other strings
];
```

### 11. Updated DatabaseSeeder

**File**: `database/seeders/DatabaseSeeder.php`

Execution order:

1. **LandlordSeeder** - Creates id=0 enterprise, global roles, system admin
2. **EnterpriseSeeder** - Creates 25 tenant enterprises (5 sole traders + 20 regular)
3. **RoleSeeder** - Creates roles for each tenant (or handle in EnterpriseSeeder)
4. **UserSeeder** - Creates users with role assignments
5. **OrganisationSeeder** - Creates organisations (skips sole traders)
6. **DivisionSeeder** - Creates divisions (skips sole traders)
7. **DepartmentSeeder** - Creates departments (skips sole traders)
8. **ProjectSeeder** - Creates projects (includes sole traders)
9. **DomainSeeder** - Creates domains for all enterprises

## Key Implementation Details

### Landlord Identification

- Use `id=0` for landlord enterprise
- Requires direct DB insert: `DB::table('teams')->insert([...]) `with `id => 0`
- Or use sequence manipulation: `DB::statement("SELECT setval('teams_id_seq', 0, false)")` then create
- Landlord has global roles (team_id=0)

### Sole Trader Enterprises

- **5 enterprises** with NO organisations, divisions, or departments
- Can have projects directly under enterprise
- Minimal user structure (Owner + optional Members)
- Still get all 14 roles created
- Still get domain created

### Role Creation

- Use Spatie Permission's `setPermissionsTeamId()` to scope roles
- Key roles: `is_key=true` in roles table
- Roles created per tenant context
- All roles use `guard_name = 'web'`

### Organizational Diversity

- **Sole Traders (5)**: No hierarchy
- **Small (5)**: 1-2 orgs, flat structure
- **Medium (10)**: 2-4 orgs, 1-2 levels
- **Large (5)**: 3-6 orgs, complex hierarchies

### Translation Strategy

- Use Laravel's standard `lang/` directory structure
- Each locale gets a `messages.php` file
- Keys match the exact strings used in `__()` calls (case-sensitive)
- Use professional translation services or AI for initial translations
- English (en_US) as base, others as translations
- All strings from grep results included

### Domain Creation

- Each enterprise gets a domain: `{enterprise-slug}.test`
- Landlord: `landlord.test`
- Domains link to tenant via `tenant_id` (ULID)
- Use `Domain::query()->firstOrCreate()` for idempotency

## Testing Considerations

- Seeders should be idempotent (use `firstOrCreate`, `updateOrCreate`)
- Handle foreign key constraints properly
- Test with `php artisan db:seed --class=DatabaseSeeder`
- Verify role assignments work in tenant context
- Verify translations load correctly
- Verify sole traders have no orgs/divs/depts
- Verify landlord has id=0

## Files to Create/Modify

**New Seeders**:

- `database/seeders/LandlordSeeder.php`
- `database/seeders/UserSeeder.php`

**Modified Seeders**:

- `database/seeders/DatabaseSeeder.php`
- `database/seeders/RoleSeeder.php`
- `database/seeders/EnterpriseSeeder.php`
- `database/seeders/OrganisationSeeder.php` (skip sole traders)
- `database/seeders/DivisionSeeder.php` (skip sole traders)
- `database/seeders/DepartmentSeeder.php` (skip sole traders)
- `database/seeders/ProjectSeeder.php` (include sole traders)
- `database/seeders/DomainSeeder.php`

**New Translation Files**:

- 16 `lang/{locale}/messages.php` files

## Dependencies

- Spatie Permission package (roles, `setPermissionsTeamId()`)
- Spatie Translatable (model attributes like `name`, `bio`, `slug`)
- Stancl Tenancy (domains, tenant context)
- Laravel's translation system (`__()` helper, `lang/` directory)
