# Business Rules & Validation

This document details the business rules that govern the Team hierarchy and User model, including all validation requirements and constraints.

## Team Hierarchy Rules

### Hierarchy Structure

**Defined Structure:**
- Enterprise → Organisation → Division → Department
- Project (non-root, can be child of any type)

**Root Constraint:**
- ✅ **Confirmed**: Only Enterprise can be root-level (`parent_id = null`)
- ✅ **Decision**: Project must always have a parent (cannot be root-level)

**Hierarchy Validation:**
- ✅ **Decision**: Strict parent-child type constraints enforced:
  - **Organisation**: Can only have Enterprise as parent
  - **Division**: Can only have Organisation as parent
  - **Department**: Can only have Division as parent
  - **Project**: Can be child of any type (Enterprise, Organisation, Division, or Department)
- ✅ **Recommendation**: Maximum depth of 10 levels
  - **Rationale**: Prevents infinite nesting, maintains query performance, keeps UI manageable
- ✅ **Recommendation**: Teams can be moved between parents, but must maintain hierarchy constraints
  - **Validation**: When moving, validate that new parent type is allowed for the team type
  - **Cascade**: When moving a team, all children must also be valid under the new parent
  - **Slug Regeneration**: Slugs must regenerate when parent changes (to maintain hierarchical slug structure)

### Unique Name Per Graph

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

### Executive and Deputy Constraints

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

## Slug Generation Rules

### Slug Uniqueness

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

### Hierarchical Slug Pattern

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

## Context Scoping Rules

### Context Storage

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

### Context Switching Permissions

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

### Context Scoping in Queries

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

## Tenancy Rules

### Enterprise as Tenant

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

### Data Isolation

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

## Validation Summary

### Required Validations

1. **Hierarchy Validation**
   - Parent-child type constraints
   - Maximum depth enforcement
   - Root-level constraints

2. **Name Uniqueness**
   - Unique within same parent AND same type
   - Per graph (Enterprise tree)

3. **Slug Uniqueness**
   - Unique per locale
   - Unique per graph
   - Case-insensitive comparison

4. **Executive/Deputy Constraints**
   - Executive and Deputy cannot be same person
   - Executive/Deputy must be from same Enterprise
   - Executive is mandatory

5. **Tenant Isolation**
   - All relationships must be within same tenant
   - Cross-tenant operations restricted
   - Automatic scoping via BelongsToTenant trait

6. **Context Switching**
   - Permission checks required
   - Access validation for Organisation access
   - API and UI validation

## Error Messages

Standard error messages for validation failures:

- Name conflict: "A team with the name '{name}' already exists in this context. Please choose a different name."
- Slug conflict: "A team with the slug '{slug}' already exists in this context for locale '{locale}'. Please choose a different name."
- Hierarchy violation: "A {type} cannot have a {parent_type} as parent."
- Executive/Deputy same person: "Executive and Deputy cannot be the same person."
- Cross-tenant: "Cross-tenant relationships are not allowed."
- Context access denied: "You do not have access to switch to this Organisation."
