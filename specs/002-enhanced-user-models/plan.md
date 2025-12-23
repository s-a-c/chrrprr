# Implementation Plan: Enhanced User and Team Models

**Branch**: `002-enhanced-user-models` | **Date**: 2025-12-22 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/002-enhanced-user-models/spec.md`

## Summary

Implement enhanced User and Team models with ULID primary keys, translatable attributes, Enterprise-based multi-tenancy, hierarchical team structure (Enterprise→Organisation→Division→Department→Project), user context switching, state machines, comprehensive validation, rate limiting, GDPR/CCPA/SOC 2 compliance, full observability stack, bulk operations, team move/reparenting capabilities, real-time presence status tracking and history, follow functionality for users and teams, online chat capabilities using WireChat foundation, and biography (bio) support in markdown format. The system will support 100 enterprises, 10,000 teams per enterprise, and 1,000 users per enterprise with sub-500ms list queries and sub-200ms single entity operations, tenant-configurable hierarchy depth limits (soft limit default 5, hard limit 10), per-enterprise rate limiting (configurable quotas, default 1000 requests/minute, 10000 requests/hour), presence status updates within 2 seconds, chat message delivery within 1 second, support for 100 concurrent chat conversations per enterprise, bio markdown rendering within 100ms, and syntax highlighting within 500ms. Data retention policies: presence history (tenant-configurable, default 90 days), audit logs (tenant-configurable, default 7 years for SOC 2), and chat history (tenant-configurable, default 1 year).

## Technical Context

**Language/Version**: PHP 8.5.1
**Primary Dependencies**: Laravel 12, Livewire 4, Flux UI (Free), Laravel Folio, Laravel Fortify, stancl/tenancy, Parental (STI), Spatie packages (translatable, permissions, media, activity log, markdown), Filament plugins, wirechat/wirechat (chat foundation), Laravel Echo/Pusher (WebSocket), stevebauman/purify (XSS sanitization), highlight.js (client-side syntax highlighting), @catppuccin/tailwindcss, @catppuccin/highlightjs, @tailwindcss/typography
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

✅ **PASS**: Flux UI components will be used when available. Existing components will be checked before creating new ones. WireChat package provides reusable chat foundation.

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
│   │   └── Conversation.php (extends WireChat Conversation)
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
│       ├── ChatList.php (extends WireChat Chats)
│       ├── ChatWindow.php (extends WireChat Chat)
│       └── ChatWidget.php
├── Actions/ (Fortify actions)
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

**Structure Decision**: Single Laravel 12 web application following Laravel's streamlined structure. Models use Single Table Inheritance (STI) via Parental package. Frontend uses Livewire 4 with Flux UI components. Routing uses Laravel Folio for file-based routing. Multi-tenancy handled via stancl/tenancy package with subdomain-based tenant identification. Chat functionality built on WireChat foundation using class extension pattern (extends WireChat models/components rather than modifying vendor files). Presence tracking implemented via database-backed status with real-time updates via WebSockets (Laravel Echo/Pusher). Follow relationships implemented via pivot tables with automatic cleanup on access loss and configurable notification preferences. Bio functionality implemented with markdown support, XSS sanitization, client-side syntax highlighting, and unified Catppuccin Mocha theming. WebSocket infrastructure (Laravel Echo/Pusher) provides real-time bidirectional communication for presence status, chat messages, and follow notifications.

## Complexity Tracking

> **Fill ONLY if Constitution Check has violations that must be justified**

No violations detected. All complexity is justified by feature requirements.
