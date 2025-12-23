# Research & Technical Decisions

**Feature**: Enhanced User and Team Models
**Date**: 2025-12-22
**Status**: Complete

## Overview

This document consolidates all technical research and decisions for implementing enhanced User and Team models with multi-tenancy, hierarchical teams, translatable attributes, and state management.

## 1. ULID Implementation

**Decision**: Use dual-key strategy with integer `id` for foreign keys/performance and ULID `ulid` for routes/public APIs.

**Rationale**:

- ULIDs provide URL-safe, sortable identifiers for public-facing routes
- Integer IDs maintain foreign key performance and compatibility
- Laravel's `Str::ulid()` method provides native ULID generation (requires `symfony/uid:^7.2`)

**Implementation**:

- Route model binding uses ULID via `getRouteKeyName()` method
- Foreign keys continue using integer `id` for database performance
- ULID generation happens automatically via `HasUlid` trait

**Alternatives Considered**:

- UUIDs: Rejected - not sortable, longer strings
- Integer-only: Rejected - exposes internal IDs, not URL-safe
- ULID-only: Rejected - foreign key performance concerns

## 2. Single Table Inheritance (STI)

**Decision**: Use `tightenco/parental` package for Single Table Inheritance to model Enterprise, Organisation, Division, Department, and Project as a single `teams` table.

**Rationale**:

- Reduces database complexity (one table vs. five)
- Maintains referential integrity across team types
- Simplifies queries for hierarchical relationships
- Parental package provides clean Laravel integration

**Implementation**:

- Base `Team` model extends `Illuminate\Database\Eloquent\Model`
- Child models (Enterprise, Organisation, etc.) use `HasParent` trait
- Type discriminator column (`type`) identifies model class
- All team types share common attributes (ULID, translatable fields, executive/deputy)

**Alternatives Considered**:
- Separate tables per team type: Rejected - complex joins, referential integrity issues
- Polymorphic relationships: Rejected - performance overhead, complexity
- Class Table Inheritance: Rejected - too complex for this use case

## 3. Multi-Tenancy Strategy

**Decision**: Use `stancl/tenancy` package with Enterprise as tenant, subdomain-based identification, and single-database tenancy with `BelongsToTenant` trait.

**Rationale**:

- Enterprise naturally maps to tenant concept
- Subdomain pattern (`{enterprise-slug}.example.com`) provides clean URL structure
- Single-database approach simplifies deployment and maintenance
- `BelongsToTenant` trait provides automatic query scoping

**Implementation**:

- Enterprise model implements `TenantContract` from stancl/tenancy
- Middleware: `InitializeTenancyBySubdomain` handles tenant identification
- All tenant-aware models use `BelongsToTenant` trait
- Context scoping (Organisation/Division/Department) is application-level, not tenant-level

**Alternatives Considered**:

- Spatie Laravel Multi-Tenancy: Rejected - different approach, less Laravel-native
- Separate databases per tenant: Rejected - operational complexity, scaling concerns
- Path-based tenancy: Rejected - less clean URLs, harder to manage

## 4. Translatable Attributes

**Decision**: Use `spatie/laravel-translatable` package for translatable name, description, and slug attributes.

**Rationale**:

- Spatie package is well-maintained and Laravel-native
- JSON column storage is efficient and queryable
- Supports multiple locales without additional tables
- Integrates well with Filament for admin UI

**Implementation**:

- Translatable attributes stored as JSON in database columns
- Locale detection via Laravel's locale system
- Fallback to default locale when translation missing
- Slug generation per locale via `cviebrock/eloquent-sluggable`

**Alternatives Considered**:

- Separate translation tables: Rejected - more complex queries, joins required
- Separate models per locale: Rejected - too much duplication
- No translation support: Rejected - requirement for multi-language support

## 5. Hierarchical Slug Strategy

**Decision**: Use translatable hierarchical slugs with pattern `{parent-slug}/{current-slug}` (e.g., `acme-corp/sales-team`).

**Rationale**:

- Hierarchical slugs provide SEO-friendly URLs that reflect team structure
- Translatable slugs support multi-language SEO
- Unique per graph and locale prevents conflicts
- Automatic regeneration when parent changes maintains consistency

**Implementation**:

- `cviebrock/eloquent-sluggable` for slug generation
- Custom slug generation method that includes parent slug
- Slug column is translatable (JSON), generated per locale
- Unique constraint per enterprise graph and locale

**Alternatives Considered**:

- Flat slugs: Rejected - no hierarchy context, potential conflicts
- Non-translatable slugs: Rejected - SEO requirement for multiple languages
- Manual slug management: Rejected - error-prone, maintenance burden

## 6. State Machine Implementation

**Decision**: Use `spatie/laravel-model-states` for user and team state management with PHP 8.1+ backed enums.

**Rationale**:

- Spatie package provides clean state machine implementation
- PHP enums provide type safety and IDE support
- State transitions are explicit and testable
- Filament integration for UI display

**Implementation**:

- User states: Draft → Pending → Active → Suspended → Archived
- Team states: Draft → Active → Inactive → Archived
- State transitions enforced via state machine
- Role-based permissions control who can trigger transitions

**Alternatives Considered**:

- Custom state management: Rejected - reinventing wheel, more bugs
- Simple enum without state machine: Rejected - no transition validation
- Database-only state tracking: Rejected - no transition rules enforcement

## 7. Context Scoping Strategy

**Decision**: Store context (current organisation/division/department) in database, cache in session, use local scopes (not global scopes) for query filtering.

**Rationale**:

- Database persistence ensures context survives sessions/logouts
- Session caching improves performance
- Local scopes provide flexibility (can bypass for admin/reporting)
- Application-level scoping (not tenant-level) allows cross-organisation admin access

**Implementation**:

- `users` table columns: `current_organisation_id`, `current_division_id`, `current_department_id`
- Session caching for performance
- `scopeInContext()` local scope method on team models
- `withoutContextScope()` method for privileged users

**Alternatives Considered**:

- Session-only context: Rejected - lost on logout, not persistent
- Global scopes: Rejected - too restrictive, can't bypass for admin
- Tenant-level scoping: Rejected - doesn't support organisation-level context switching

## 8. Hierarchical Relationships

**Decision**: Use `staudenmeir/laravel-adjacency-list` for efficient hierarchical queries and tree traversal.

**Rationale**:

- Package provides efficient tree queries (ancestors, descendants, siblings)
- Supports depth limits and tree constraints
- Well-maintained and Laravel-native
- Performance optimized for large hierarchies

**Implementation**:

- Adjacency list pattern (parent_id column)
- Package provides query methods: `ancestors()`, `descendants()`, `siblings()`, etc.
- Supports depth validation
- Efficient for read-heavy operations

**Alternatives Considered**:

- Nested Set Model: Rejected - complex updates, write-heavy overhead
- Closure Table: Rejected - more storage, complexity
- Recursive queries: Rejected - performance issues with deep hierarchies

## 9. Performance Optimization

**Decision**: Use database indexes, eager loading, query optimization, and horizontal scaling support.

**Rationale**:

- Targets: <500ms list queries, <200ms single operations (95th percentile)
- Scale: 100 enterprises, 10,000 teams per enterprise, 1,000 users per enterprise
- Indexes on foreign keys, ULIDs, slugs, and tenant columns
- Eager loading prevents N+1 queries

**Implementation**:

- Composite indexes on (tenant_id, parent_id, type) for team queries
- Index on ULID columns for route lookups
- Index on slug columns for SEO queries
- Eager loading relationships in controllers/Livewire components

**Alternatives Considered**:

- No optimization: Rejected - won't meet performance targets
- Vertical scaling only: Rejected - limited growth potential
- Over-engineering: Rejected - premature optimization

## 10. Backward Compatibility

**Decision**: Maintain backward compatibility with integer ID-based routes/APIs during transition period, support both ULID and integer ID lookups.

**Rationale**:

- Existing integrations may depend on integer IDs
- Gradual migration reduces deployment risk
- Allows time for external systems to update
- Reduces support burden during transition

**Implementation**:

- Route model binding supports both ULID and integer ID
- API endpoints accept both identifier types
- Migration scripts generate ULIDs for existing records
- Deprecation warnings for integer ID usage (future removal)

**Alternatives Considered**:

- Breaking change only: Rejected - too risky, breaks integrations
- No migration: Rejected - existing data needs ULIDs
- Permanent dual support: Rejected - technical debt, maintenance burden

## 11. Presence Status and History

**Decision**: Implement database-backed presence status tracking with real-time updates via WebSocket connections (Laravel Echo/Pusher), storing presence history in a separate table for analytics with tenant-configurable retention (default 90 days).

**Rationale**:

- Database persistence ensures presence survives server restarts
- WebSocket connections provide true real-time bidirectional communication with low latency (<2s update target)
- Presence history enables analytics and audit trails
- Team presence aggregation provides useful collaboration insights
- Tenant-configurable retention allows enterprises to adjust based on compliance needs

**Implementation**:

- `users` table columns: `presence_status` (enum: Online, Offline, Away, Busy), `last_seen_at` (timestamp)
- `presence_history` table: `user_id`, `status`, `changed_at`, indexed for efficient queries, tenant-configurable retention (default 90 days)
- Automatic status transitions: Online on login, Away after inactivity timeout (5 minutes), Offline on logout
- Real-time updates via WebSocket connections (Laravel Echo/Pusher) for true real-time bidirectional communication
- Team presence aggregation via query of team members' presence status
- Scheduled cleanup job for presence history older than retention period

**Alternatives Considered**:

- Session-only presence: Rejected - lost on server restart, not persistent
- Redis-only presence: Rejected - requires additional infrastructure, not queryable for history
- No presence history: Rejected - analytics requirement, audit trail needed
- Livewire polling only: Rejected - doesn't meet <2s update target, higher server load than WebSockets

## 12. Follow Functionality

**Decision**: Implement follow relationships via pivot tables (user_follows_user, user_follows_team) with automatic cleanup on access loss and configurable notification preferences (in-app WebSocket + optional email).

**Rationale**:

- Pivot tables provide efficient many-to-many relationships
- Automatic cleanup prevents orphaned follow relationships
- Simple query pattern for follow lists
- Supports both user-to-user and user-to-team follows
- Configurable notifications provide user control over update types and delivery methods

**Implementation**:

- `user_follows_user` pivot table: `follower_id`, `followed_id`, `created_at`, unique constraint
- `user_follows_team` pivot table: `user_id`, `team_id`, `created_at`, unique constraint
- `follow_notification_preferences` pivot table: notification type flags (activity, presence, bio_changes, etc.), email preferences
- Validation: Prevent self-follow, check team access permissions before allowing team follow
- Automatic cleanup: Remove follow relationships when user loses team access or when followed entity is deleted/archived
- Query methods: `followedUsers()`, `followedTeams()`, `followers()` relationships
- Notification delivery: In-app via WebSocket channels, optional email based on user preferences

**Alternatives Considered**:

- Single polymorphic follow table: Rejected - less type-safe, harder to query efficiently
- No automatic cleanup: Rejected - creates orphaned relationships, data integrity issues
- No notification preferences: Rejected - users need control over notification types and delivery methods

## 13. Chat Capabilities (WireChat Integration)

**Decision**: Use `wirechat/wirechat` package as foundation, extending via class inheritance and configuration rather than modifying vendor files, with WebSocket-based real-time delivery and tenant-configurable message retention (default 1 year).

**Rationale**:

- WireChat provides ~40 hours of boilerplate (database schema, read receipts, unread counts)
- Class extension pattern allows customization while preserving package updates
- Polymorphic conversation structure supports both user-to-user and team conversations
- Livewire integration aligns with existing frontend architecture
- WebSocket delivery meets <1s message delivery target
- Tenant-configurable retention allows enterprises to adjust based on compliance needs

**Implementation**:

- Install WireChat package: `composer require namu/wirechat`
- Extend WireChat models: `App\Models\Chat\Conversation extends Namu\WireChat\Models\Conversation`
- Extend WireChat Livewire components: `App\Livewire\Chat\ChatList extends Wirechat\Livewire\Chats\Chats`
- Configure model binding in `config/wirechat.php` to use extended models
- Publish and customize views: `php artisan vendor:publish --tag=wirechat-views`
- Integrate with tenant isolation: Ensure conversations respect enterprise boundaries
- Real-time delivery via WebSocket connections (Laravel Echo/Pusher) for true real-time bidirectional communication
- Implement tenant-configurable message retention with automatic cleanup of messages older than retention period

**Alternatives Considered**:

- Building chat from scratch: Rejected - significant development time, reinventing wheel
- Forking WireChat: Rejected - loses package updates, maintenance burden
- Modifying vendor files: Rejected - lost on composer update, not maintainable
- Different chat package: Rejected - WireChat best fits Laravel/Livewire stack
- Livewire polling for chat: Rejected - doesn't meet <1s delivery target, higher server load

## 14. Real-Time Communication (WebSockets)

**Decision**: Use Laravel Echo with Pusher (or compatible WebSocket service) for real-time bidirectional communication for presence status updates, chat messages, and follow notifications.

**Rationale**:

- WebSockets provide true real-time bidirectional communication with low latency
- Laravel Echo integrates seamlessly with Laravel and Livewire
- Pusher (or compatible services like Laravel Reverb) provides scalable WebSocket infrastructure
- Supports presence status updates within 2 seconds and chat message delivery within 1 second targets
- Enables real-time follow notifications without polling overhead

**Implementation**:

- Install Laravel Echo and Pusher JavaScript SDK (or configure Laravel Reverb for self-hosted)
- Configure broadcasting driver in `config/broadcasting.php`
- Use Laravel's broadcasting events for presence status changes, chat messages, and follow notifications
- Frontend subscribes to channels: `presence.{enterprise_id}`, `chat.{conversation_id}`, `user.{user_id}.notifications`
- Ensure tenant isolation in channel naming and authorization
- Handle WebSocket connection failures and reconnection logic

**Alternatives Considered**:

- Livewire polling only: Rejected - doesn't meet <2s presence and <1s chat delivery targets, higher server load
- Server-Sent Events (SSE): Rejected - unidirectional only, doesn't support chat bidirectional communication
- Long polling: Rejected - higher latency, more server resources than WebSockets

## 15. Bio Markdown Implementation

**Decision**: Implement bio functionality using `spatie/laravel-markdown` for server-side parsing, `stevebauman/purify` for XSS sanitization, `highlight.js` for client-side syntax highlighting, and unified Catppuccin Mocha theming.

**Rationale**:

- Spatie markdown package is well-maintained and Laravel-native
- Purify provides robust XSS protection while preserving valid markdown/HTML
- Client-side highlighting avoids Node.js dependencies on server
- Catppuccin Mocha theming provides unified visual experience across Flux UI, Filament, and code blocks
- Meets performance targets: <100ms rendering, <500ms syntax highlighting

**Implementation**:

- Add `bio` column (longText, nullable) to `users` and `teams` tables
- Install packages: `spatie/laravel-markdown`, `stevebauman/purify`, `highlight.js`, `@catppuccin/highlightjs`, `@tailwindcss/typography`
- Configure Tailwind CSS v4 with Typography plugin and Catppuccin theme
- Sanitize bio content with Purify before storage and rendering
- Parse markdown server-side with Spatie (code highlighting disabled)
- Apply client-side syntax highlighting via highlight.js on page load and Livewire navigation
- Integrate Filament MarkdownEditor component for bio editing
- Enforce role-based editing permissions and size limits (soft: 10K chars default, hard: 50K chars)

**Alternatives Considered**:

- Server-side syntax highlighting (Shiki): Rejected - requires Node.js on server, adds complexity
- No syntax highlighting: Rejected - code blocks in bios would be plain text, poor UX
- Different markdown parser: Rejected - Spatie is Laravel-native and well-maintained
- No XSS sanitization: Rejected - security risk, unacceptable

## 16. Data Retention Policies

**Decision**: Implement tenant-configurable retention periods with automatic cleanup for presence history (default 90 days), audit logs (default 7 years for SOC 2), and chat history (default 1 year).

**Rationale**:

- Different data types have different retention needs (presence: short-term analytics, audit: long-term compliance, chat: medium-term history)
- Tenant-configurable allows enterprises to adjust based on their compliance requirements
- Automatic cleanup prevents unbounded storage growth
- Defaults balance utility with storage costs

**Implementation**:

- Add retention period configuration to Enterprise model (presence_retention_days, audit_retention_years, chat_retention_days)
- Create scheduled artisan commands for cleanup: `presence:cleanup`, `audit:cleanup`, `chat:cleanup`
- Run cleanup commands daily via Laravel scheduler
- Soft delete or hard delete based on data type (audit logs may require soft delete for compliance)
- Log cleanup operations for audit trail

**Alternatives Considered**:

- Fixed retention periods: Rejected - doesn't accommodate different enterprise compliance needs
- No automatic cleanup: Rejected - unbounded storage growth, cost concerns
- Single retention period for all data: Rejected - different data types have different requirements

## 17. Rate Limiting Configuration

**Decision**: Implement tenant-configurable rate limiting quotas with system defaults (1000 requests/minute, 10000 requests/hour) using Laravel's rate limiting middleware.

**Rationale**:

- Per-enterprise quotas protect tenants from each other
- Configurable quotas allow enterprises to scale based on needs
- System defaults provide reasonable protection without configuration
- Laravel's built-in rate limiting is well-tested and performant

**Implementation**:

- Store rate limit configuration in Enterprise model (requests_per_minute, requests_per_hour)
- Use Laravel's `RateLimiter` facade with tenant-specific cache keys
- Apply rate limiting middleware to API routes
- Return HTTP 429 with Retry-After header and detailed error body when exceeded
- Log rate limit violations for monitoring

**Alternatives Considered**:

- Fixed quotas for all: Rejected - doesn't accommodate different enterprise sizes/needs
- No rate limiting: Rejected - security risk, potential abuse
- Third-party rate limiting service: Rejected - adds external dependency, Laravel's solution is sufficient

## 18. Bulk Operations and Batch Size Limits

**Decision**: Implement tenant-configurable maximum batch size (default 500 items) for bulk create/update operations with validation and partial success handling.

**Rationale**:

- Batch size limits prevent oversized requests that could impact performance
- Configurable limits allow enterprises to adjust based on their needs
- Default 500 provides good balance between usability and performance
- Partial success handling allows graceful degradation when some items fail

**Implementation**:

- Store batch size limit in Enterprise model (bulk_operation_max_batch_size)
- Validate batch size before processing
- Process items individually with validation, collect results
- Return detailed per-item results (success/error status, validation details)
- Allow partial success (some items succeed, others fail)

**Alternatives Considered**:

- No batch size limit: Rejected - risk of performance degradation with very large batches
- Fixed small batch size (e.g., 50): Rejected - too restrictive, poor UX for large operations
- Transactional all-or-nothing: Rejected - too strict, partial success is more user-friendly

## 19. Follow Notification Preferences

**Decision**: Implement configurable notification preferences per follow relationship with in-app WebSocket delivery and optional email notifications.

**Rationale**:

- Users have different preferences for what updates they want to receive
- Per-relationship preferences provide granular control
- In-app WebSocket delivery provides instant notifications
- Optional email provides backup and offline notification capability
- User-configurable email preferences respect user choice

**Implementation**:

- Create `follow_notification_preferences` pivot table with notification type flags (activity, presence, bio_changes, etc.)
- Store email notification preferences per follow relationship
- Deliver in-app notifications via WebSocket channels
- Send email notifications based on user preferences (if enabled)
- Allow users to configure preferences per followed user/team

**Alternatives Considered**:

- No notifications: Rejected - follow functionality loses value without awareness
- Fixed notification types: Rejected - users have different preferences
- Email only: Rejected - slower than WebSocket, less immediate
- No email option: Rejected - users may want email for important updates

## Summary

All technical decisions have been made based on:

- Laravel best practices and conventions
- Package maturity and community support
- Performance requirements and scalability needs
- Maintainability and developer experience
- Backward compatibility requirements
- Real-time communication requirements
- Security and compliance requirements

No unresolved technical questions remain. Implementation can proceed to Phase 1: Design & Contracts.
