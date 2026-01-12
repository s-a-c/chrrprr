# Implementation Plan: Enhanced User and Team Models

**Branch**: `002-enhanced-user-models` | **Date**: 2025-12-22 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/002-enhanced-user-models/spec.md`

## Summary

Implement enhanced User and Team models with ULID primary keys, translatable attributes, Enterprise-based multi-tenancy, hierarchical team structure (Enterprise→Organisation→Division→Department→Project), user context switching, state machines, comprehensive validation, rate limiting, GDPR/CCPA/SOC 2 compliance, full observability stack, bulk operations, team move/reparenting capabilities, real-time presence status tracking and history, follow functionality for users and teams, online chat capabilities using custom Livewire 4 Islands implementation with Laravel Reverb Presence Channels, and biography (bio) support in markdown format. The system will support 100 enterprises, 10,000 teams per enterprise, and 1,000 users per enterprise with sub-500ms list queries and sub-200ms single entity operations, tenant-configurable hierarchy depth limits (soft limit default 5, hard limit 10), per-enterprise rate limiting (configurable quotas, default 1000 requests/minute, 10000 requests/hour), presence status updates within 2 seconds, chat message delivery within 1 second, support for 100 concurrent chat conversations per enterprise, bio markdown rendering within 100ms, and syntax highlighting within 500ms. Data retention policies: presence history (tenant-configurable, default 90 days), audit logs (tenant-configurable, default 7 years for SOC 2), and chat history (tenant-configurable, default 1 year). **Architecture Note**: WireChat package is incompatible with Livewire 4, so chat is implemented as custom Livewire 4 Islands components. Optional CQRS refactor paths are documented for Teams and User modules (see research/ ADRs).

## Technical Context

**Language/Version**: PHP 8.5.1
**Primary Dependencies**: Laravel 12, Livewire 4, Flux UI (Free), Laravel Folio, Laravel Fortify, stancl/tenancy, Parental (STI), Spatie packages (translatable, permissions, media, activity log, markdown), Filament plugins, Laravel Reverb (WebSocket server, Presence Channels), Laravel Echo (WebSocket client), stevebauman/purify (XSS sanitization), highlight.js (client-side syntax highlighting), @catppuccin/tailwindcss, @catppuccin/highlightjs, @tailwindcss/typography. **Note**: WireChat package is incompatible with Livewire 4, so chat is custom-built using Livewire 4 Islands.
**Storage**: PostgreSQL 18 (production), SQLite (development/testing)
**Testing**: Pest 4, PHPUnit 12
**Target Platform**: Linux server (Laravel Herd for local development)
**Project Type**: Web application (Laravel monolith with Livewire frontend)
**Performance Goals**: <500ms for list queries (95th percentile), <200ms for single entity operations (95th percentile), <2s for presence status updates (95th percentile), <1s for chat message delivery (95th percentile), <100ms for bio markdown rendering (95th percentile), <500ms for syntax highlighting (95th percentile), support horizontal scaling
**Constraints**: Must maintain backward compatibility with integer ID routes/APIs during transition, 99% PHP test coverage, 100% type coverage, all code must pass PHPStan level 9, WebSocket infrastructure required for real-time features, bio content must be sanitized to prevent XSS
**Scale/Scope**: 100 enterprises, 10,000 teams per enterprise, 1,000 users per enterprise, hierarchical team depth with tenant-configurable soft limit (default 5 levels) and hard limit (10 levels), 100 concurrent chat conversations per enterprise, tenant-configurable data retention (presence: 90 days default, audit: 7 years default, chat: 1 year default), tenant-configurable rate limiting quotas (default 1000 requests/minute, 10000 requests/hour), bulk operations with tenant-configurable batch size (default 500 items)

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

### I. Test-First Development (NON-NEGOTIABLE)

✅ **PASS**: All features will be developed using TDD with Pest 4. Tests will be written before implementation following Red-Green-Refactor cycle. Test coverage target: 99% PHP, 100% type coverage.

### II. Laravel Best Practices

✅ **PASS**: Implementation follows Laravel 12 conventions, uses Eloquent relationships, Form Requests for validation, Laravel's service container, and streamlined structure (bootstrap/app.php for configuration).

### III. Type Safety

✅ **PASS**: All methods will have explicit return types, all parameters will have type hints, PHPStan level 9 will pass, 100% type coverage required, strict types declared in all PHP files.

### IV. Code Quality Standards

✅ **PASS**: All code will pass Laravel Pint, Rector, and Mago checks. Code will be formatted before commit.

### V. Component Reusability

✅ **PASS**: Flux UI components will be used when available. Existing components will be checked before creating new ones. Chat is custom-built using Livewire 4 Islands for optimal performance (WireChat package is incompatible with Livewire 4).

### VI. Documentation and Clarity

✅ **PASS**: Code will be self-documenting with clear naming and PHPDoc blocks for complex logic.

**GATE STATUS**: ✅ **PASS** - All constitution principles satisfied. No violations detected.

## Project Structure

### Documentation (this feature)

```text
specs/002-enhanced-user-models/
├── plan.md              # This file (/speckit.plan command output)
├── research.md          # Phase 0 output (/speckit.plan command)
├── research-wirechat.md # WireChat integration research
├── research-bio-as-markdown.md # Bio markdown implementation research
├── data-model.md        # Phase 1 output (/speckit.plan command)
├── quickstart.md        # Phase 1 output (/speckit.plan command)
├── contracts/           # Phase 1 output (/speckit.plan command)
└── tasks.md             # Phase 2 output (/speckit.tasks command - NOT created by /speckit.plan)
```

### Source Code (repository root)

```text
app/
├── Models/
│   ├── User.php
│   ├── Team.php (base STI model)
│   ├── Enterprise.php
│   ├── Organisation.php
│   ├── Division.php
│   ├── Department.php
│   ├── Project.php
│   ├── Domain.php
│   ├── Chat/
│   │   ├── Conversation.php (custom implementation)
│   │   ├── Participant.php (custom implementation)
│   │   └── Message.php (custom implementation with reactions, replies, edits)
│   ├── Presence/
│   │   └── PresenceHistory.php
│   └── Traits/
│       ├── HasUlid.php
│       ├── HasTranslatableAttributes.php
│       ├── HasTranslatableSlug.php
│       └── HasPresence.php
├── Enums/
│   ├── UserState.php
│   ├── UserStatus.php
│   ├── TeamType.php
│   ├── TeamState.php
│   └── TeamStatus.php
├── Http/
│   ├── Requests/
│   │   ├── StoreTeamRequest.php
│   │   ├── UpdateTeamRequest.php
│   │   └── SwitchContextRequest.php
│   └── Middleware/ (if needed, otherwise use bootstrap/app.php)
├── Livewire/
│   ├── Teams/
│   │   ├── CreateTeam.php
│   │   ├── EditTeam.php
│   │   └── TeamList.php
│   ├── Context/
│   │   └── SwitchContext.php
│   ├── Presence/
│   │   ├── PresenceIndicator.php
│   │   └── PresenceHistory.php
│   ├── Follow/
│   │   ├── FollowUser.php
│   │   ├── FollowTeam.php
│   │   ├── FollowList.php
│   │   └── NotificationPreferences.php
│   └── Chat/
│       ├── ChatRoom.php (Livewire 4 Island component)
│       ├── ChatList.php (Livewire 4 component)
│       └── ChatWidget.php (Livewire 4 Island component)
├── Actions/ (Fortify actions and optional CQRS Actions)
│   ├── Fortify/
│   └── Teams/ (optional: CreateTeam, MoveTeam, UpdateTeam, AssignExecutive)
│   └── Users/ (optional: RegisterUser, BanUser, UpdateUserProfile, TransitionUserState)
├── Models/
│   └── Builders/ (optional CQRS: TeamBuilder, UserBuilder)
├── Support/
│   └── Validation/ (optional CQRS: TeamHierarchyValidator)
└── Providers/
    └── AppServiceProvider.php

database/
├── migrations/
│   ├── 2025_XX_XX_add_ulid_to_users.php
│   ├── 2025_XX_XX_create_teams_table.php
│   ├── 2025_XX_XX_create_domains_table.php
│   ├── 2025_XX_XX_add_presence_to_users.php
│   ├── 2025_XX_XX_create_presence_history_table.php
│   ├── 2025_XX_XX_create_user_follows_table.php
│   ├── 2025_XX_XX_create_team_follows_table.php
│   ├── 2025_XX_XX_add_bio_to_users.php
│   ├── 2025_XX_XX_add_bio_to_teams.php
│   ├── 2025_XX_XX_create_follow_notification_preferences_table.php
│   ├── 2025_XX_XX_create_conversations_table.php (custom chat schema)
│   ├── 2025_XX_XX_create_participants_table.php (custom chat schema)
│   ├── 2025_XX_XX_create_messages_table.php (custom chat schema with reactions, replies, edits)
│   └── [additional migrations]
├── factories/
│   ├── UserFactory.php
│   ├── EnterpriseFactory.php
│   └── [other factories]
└── seeders/
    └── DatabaseSeeder.php

resources/
├── views/
│   ├── livewire/
│   │   ├── teams/
│   │   ├── context/
│   │   ├── presence/
│   │   ├── follow/
│   │   └── chat/
│   └── pages/ (Folio pages)
│       ├── team/[Team].blade.php (bio display)
│       └── user/[User].blade.php (bio display)
└── lang/ (translations)

resources/
├── css/
│   ├── app.css (Catppuccin Mocha theming, Typography plugin, Highlight.js theme)
│   └── filament/admin/theme.css (Filament Catppuccin theming)
└── js/
    └── app.js (highlight.js initialization, Livewire navigation handling)

tests/
├── Feature/
│   ├── Teams/
│   ├── Context/
│   ├── Tenancy/
│   ├── Presence/
│   ├── Follow/
│   ├── Chat/
│   └── Bio/
├── Unit/
│   ├── Models/
│   └── Enums/
└── Browser/ (Pest 4 browser tests)
```

**Structure Decision**: Single Laravel 12 web application following Laravel's streamlined structure. Models use Single Table Inheritance (STI) via Parental package. Frontend uses Livewire 4 with Flux UI components. Routing uses Laravel Folio for file-based routing. Multi-tenancy handled via stancl/tenancy package with subdomain-based tenant identification. Chat functionality built as custom Livewire 4 Islands components (WireChat package is incompatible with Livewire 4) using Laravel Reverb Presence Channels for real-time updates. Chat schema supports modern features: reactions (JSON), replies (foreign key), and edits (timestamp). Presence tracking implemented via database-backed status with real-time updates via WebSockets (Laravel Reverb Presence Channels). Follow relationships implemented via pivot tables with automatic cleanup on access loss and configurable notification preferences. Bio functionality implemented with markdown support, XSS sanitization, client-side syntax highlighting, and unified Catppuccin Mocha theming. WebSocket infrastructure (Laravel Reverb) provides real-time bidirectional communication for presence status, chat messages, and follow notifications. **Optional CQRS Refactor**: Architecture Decision Records (ADRs) document optional CQRS refactor paths for Teams and User modules to improve separation of concerns in Filament v5 contexts (see research/ ADRs). These refactors extract complex queries to Builders, validation to Services, and write operations to Action classes.

## Architecture Decisions

### Chat Implementation: Custom Livewire 4 Islands

**Decision**: Build custom chat implementation using Livewire 4 Islands rather than WireChat package.

**Rationale**: WireChat package is incompatible with Livewire 4 due to significant architectural changes (view-first system, deprecated hooks). Building custom solution provides:
- Better performance via Livewire 4 Islands (only chat area re-renders, not entire page)
- Native Laravel Reverb integration (no external WebSocket service costs)
- Full control over features (presence, following logic, reactions, replies, edits)
- Modern schema optimized for current requirements

**Implementation**: Livewire 4 Island components (`wire:island`) with Laravel Reverb Presence Channels. Custom database schema (conversations, participants, messages) with JSON reactions column, reply foreign keys, and edit timestamps. See `research/wirechat.md` for detailed architectural blueprint.

### Optional CQRS Refactor for Teams and Users

**Decision**: Document optional CQRS (Command Query Responsibility Segregation) refactor paths for Teams and User modules.

**Rationale**: As complexity grows, Active Record models become "God Models" with mixed concerns (validation, queries, side effects). CQRS provides:
- Separation of reads (Builders) and writes (Actions)
- Explicit transaction boundaries
- Better Filament v5 integration via `->using()` callbacks
- Improved testability and maintainability

**Implementation Status**: Optional future work. ADRs document the approach:
- `research/adr-cqrs-refactor-team.md`: TeamBuilder, TeamHierarchyValidator, CreateTeam/MoveTeam/UpdateTeam/AssignExecutive Actions
- `research/adr-cqrs-refactor-user.md`: UserBuilder, RegisterUser/BanUser/UpdateUserProfile/TransitionUserState Actions

Current implementation uses Active Record pattern. CQRS refactor can be performed incrementally if complexity warrants it.

## Complexity Tracking

> **Fill ONLY if Constitution Check has violations that must be justified**

No violations detected. All complexity is justified by feature requirements.
