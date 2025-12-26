# Tasks: Enhanced User and Team Models

**Input**: Design documents from `/specs/002-enhanced-user-models/`
**Prerequisites**: plan.md ✅, spec.md ✅, research.md ✅, data-model.md ✅, contracts/ ✅

**Tests**: Tests are REQUIRED - TDD is NON-NEGOTIABLE per constitution. All tests MUST be written before implementation following Red-Green-Refactor cycle.

**Organisation**: Tasks are grouped by user story to enable independent implementation and testing of each story.

===

<details><Expand for Table of Contents</details>

## Table of Contents:

- [Tasks: Enhanced User and Team Models](#tasks-enhanced-user-and-team-models)
  - [Table of Contents:](#table-of-contents)
  - [1. Format: `[ID] [P?] [Story] Description`](#1-format-id-p-story-description)
  - [2. Path Conventions](#2-path-conventions)
  - [3. Phase 1: Setup (Shared Infrastructure)](#3-phase-1-setup-shared-infrastructure)
  - [4. Phase 2: Foundational (Blocking Prerequisites)](#4-phase-2-foundational-blocking-prerequisites)
    - [4.1. Database Migrations](#41-database-migrations)
    - [4.2. Traits (Reusable Model Concerns)](#42-traits-reusable-model-concerns)
    - [4.3. Enums](#43-enums)
    - [4.4. Base Models](#44-base-models)
    - [4.5. Factories and Seeders](#45-factories-and-seeders)
    - [4.6. Foundational Tests](#46-foundational-tests)
  - [5. Phase 3: User Story 1 - Team Hierarchy Management (Priority: P1) 🎯 MVP](#5-phase-3-user-story-1---team-hierarchy-management-priority-p1--mvp)
    - [5.1. Tests for User Story 1 ⚠️](#51-tests-for-user-story-1-️)
    - [5.2. Implementation for User Story 1](#52-implementation-for-user-story-1)
  - [6. Phase 4: User Story 2 - Context Switching (Priority: P2)](#6-phase-4-user-story-2---context-switching-priority-p2)
    - [6.1. Tests for User Story 2 ⚠️](#61-tests-for-user-story-2-️)
    - [6.2. Implementation for User Story 2](#62-implementation-for-user-story-2)
  - [7. Phase 5: User Story 3 - Enhanced User Attributes (Priority: P3)](#7-phase-5-user-story-3---enhanced-user-attributes-priority-p3)
    - [7.1. Tests for User Story 3 ⚠️](#71-tests-for-user-story-3-️)
    - [7.2. Implementation for User Story 3](#72-implementation-for-user-story-3)
  - [8. Phase 6: Multi-Tenancy Integration](#8-phase-6-multi-tenancy-integration)
    - [8.1. Tests for Multi-Tenancy](#81-tests-for-multi-tenancy)
    - [8.2. Implementation for Multi-Tenancy](#82-implementation-for-multi-tenancy)
  - [9. Phase 7: Polish \& Cross-Cutting Concerns](#9-phase-7-polish--cross-cutting-concerns)
  - [10. Phase 8: User Story 4 - Presence Status and Monitoring (Priority: P2)](#10-phase-8-user-story-4---presence-status-and-monitoring-priority-p2)
    - [10.1. Tests for User Story 4 ⚠️](#101-tests-for-user-story-4-️)
    - [10.2. Implementation for User Story 4](#102-implementation-for-user-story-4)
  - [11. Phase 9: User Story 5 - Follow Users and Teams (Priority: P3)](#11-phase-9-user-story-5---follow-users-and-teams-priority-p3)
    - [11.1. Tests for User Story 5 ⚠️](#111-tests-for-user-story-5-️)
    - [11.2. Implementation for User Story 5](#112-implementation-for-user-story-5)
  - [12. Phase 10: User Story 6 - Online Chat Capabilities (Priority: P2)](#12-phase-10-user-story-6---online-chat-capabilities-priority-p2)
    - [12.1. Tests for User Story 6 ⚠️](#121-tests-for-user-story-6-️)
    - [12.2. Implementation for User Story 6](#122-implementation-for-user-story-6)
  - [13. Dependencies \& Execution Order](#13-dependencies--execution-order)
    - [13.1. Phase Dependencies](#131-phase-dependencies)
    - [13.2. User Story Dependencies](#132-user-story-dependencies)
    - [13.3. Within Each User Story](#133-within-each-user-story)
    - [13.4. Parallel Opportunities](#134-parallel-opportunities)
  - [14. Parallel Example: User Story 1](#14-parallel-example-user-story-1)
  - [15. Implementation Strategy](#15-implementation-strategy)
    - [15.1. MVP First (User Story 1 Only)](#151-mvp-first-user-story-1-only)
    - [15.2. Incremental Delivery](#152-incremental-delivery)
    - [15.3. Parallel Team Strategy](#153-parallel-team-strategy)
  - [16. Task Summary](#16-task-summary)
    - [16.1. MVP Scope (User Story 1 Only)](#161-mvp-scope-user-story-1-only)
  - [17. Notes](#17-notes)

</details>

===

## 1. Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (e.g., US1, US2, US3)
- Include exact file paths in descriptions

## 2. Path Conventions

- **Laravel 12 structure**: `app/`, `database/`, `resources/`, `tests/` at repository root
- Models: `app/Models/`
- Traits: `app/Models/Concerns/`
- Enums: `app/Enums/`
- Migrations: `database/migrations/`
- Tests: `tests/Feature/`, `tests/Unit/`, `tests/Browser/`

## 3. Phase 1: Setup (Shared Infrastructure)

**Purpose**: Project initialization, package installation, and basic configuration

- [X] T001 Install core framework packages (stancl/tenancy, filament/filament) via composer
- [X] T001.5 [P] Configure Laravel Reverb for WebSocket/Presence Channels (required for custom chat implementation)
- [X] T001.6 [P] Install WebSocket packages (laravel/echo, pusher/pusher-php-server) or configure Laravel Reverb via composer
- [X] T001.7 [P] Install bio markdown packages (spatie/laravel-markdown, stevebauman/purify) via composer
- [X] T001.8 [P] Install client-side syntax highlighting (highlight.js, @catppuccin/highlightjs) via npm/bun
- [X] T001.9 [P] Install Tailwind Typography plugin (@tailwindcss/typography) via npm/bun
- [X] T002 [P] Install ULID support package (symfony/uid:^7.2) via composer
- [X] T003 [P] Install translatable and slug packages (spatie/laravel-translatable, cviebrock/eloquent-sluggable) via composer
- [X] T004 [P] Install hierarchical relationships package (staudenmeir/laravel-adjacency-list) via composer
- [X] T005 [P] Install STI package (tightenco/parental) via composer
- [X] T006 [P] Install state and status packages (spatie/laravel-model-states, spatie/laravel-model-status) via composer
- [X] T007 [P] Install permissions package (spatie/laravel-permission) via composer
- [X] T008 Configure stancl/tenancy in config/tenancy.php
- [X] T009 Configure bootstrap/app.php with tenancy middleware (InitializeTenancyBySubdomain, PreventAccessFromCentralDomains)
- [X] T009.5 [P] Configure Laravel Echo and broadcasting driver (Pusher or Reverb) in config/broadcasting.php
- [X] T009.6 [P] Configure frontend Laravel Echo client in resources/js/app.js
- [X] T009.7 [P] Configure Spatie markdown with code highlighting disabled in config/markdown.php
- [X] T009.8 [P] Configure Purify XSS sanitization settings in config/purify.php
- [X] T009.9 [P] Configure Tailwind CSS v4 with Typography plugin and Catppuccin Mocha theme in resources/css/app.css
- [X] T010 [P] Configure Laravel Pint in pint.json
- [X] T011 [P] Configure PHPStan level 9 in phpstan.neon

---

## 4. Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Core infrastructure that MUST be complete before ANY user story can be implemented

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

### 4.1. Database Migrations

- [X] T012 Create migration to add ULID and enhanced columns to users table in database/migrations/YYYY_MM_DD_HHMMSS_add_ulid_to_users.php
- [X] T012.5 [P] Create migration to add bio column to users table in database/migrations/YYYY_MM_DD_HHMMSS_add_bio_to_users_table.php
- [X] T013 Create migration to create teams table (STI base) in database/migrations/YYYY_MM_DD_HHMMSS_create_teams_table.php
- [X] T013.5 [P] Create migration to add bio column to teams table in database/migrations/YYYY_MM_DD_HHMMSS_add_bio_to_teams_table.php
- [X] T014 Create migration to create domains table in database/migrations/YYYY_MM_DD_HHMMSS_create_domains_table.php
- [X] T015 Create migration to create user_enterprise pivot table in database/migrations/YYYY_MM_DD_HHMMSS_create_user_enterprise_table.php
- [X] T016 Create migration to create user_organisation_access pivot table in database/migrations/YYYY_MM_DD_HHMMSS_create_user_organisation_access_table.php
- [X] T017 Run all migrations to verify schema creation

### 4.2. Traits (Reusable Model Concerns)

- [X] T018 [P] Create HasUlid trait in app/Models/Concerns/HasUlid.php
- [X] T019 [P] Create HasTranslatableAttributes trait in app/Models/Concerns/HasTranslatableAttributes.php
- [X] T020 [P] Create HasTranslatableSlug trait in app/Models/Concerns/HasTranslatableSlug.php

### 4.3. Enums

- [X] T021 [P] Create UserState enum in app/Enums/UserState.php
- [X] T022 [P] Create UserStatus enum in app/Enums/UserStatus.php
- [X] T023 [P] Create TeamType enum in app/Enums/TeamType.php
- [X] T024 [P] Create TeamState enum in app/Enums/TeamState.php
- [X] T025 [P] Create TeamStatus enum in app/Enums/TeamStatus.php

### 4.4. Base Models

- [X] T026 Create base Team model (STI) in app/Models/Team.php
- [X] T027 Create Domain model in app/Models/Domain.php
- [X] T028 Update User model with ULID, translatable attributes, and tenant relationship in app/Models/User.php

### 4.5. Factories and Seeders

- [X] T029 [P] Create UserFactory with ULID and enhanced attributes in database/factories/UserFactory.php
- [X] T030 [P] Create EnterpriseFactory in database/factories/EnterpriseFactory.php
- [X] T031 [P] Create OrganisationFactory in database/factories/OrganisationFactory.php
- [X] T032 [P] Create DivisionFactory in database/factories/DivisionFactory.php
- [X] T033 [P] Create DepartmentFactory in database/factories/DepartmentFactory.php
- [X] T034 [P] Create ProjectFactory in database/factories/ProjectFactory.php

### 4.6. Foundational Tests

- [X] T035 [P] Create unit test for HasUlid trait in tests/Unit/Models/Concerns/HasUlidTest.php
- [X] T036 [P] Create unit test for HasTranslatableAttributes trait in tests/Unit/Models/Concerns/HasTranslatableAttributesTest.php
- [X] T037 [P] Create unit test for HasTranslatableSlug trait in tests/Unit/Models/Concerns/HasTranslatableSlugTest.php
- [X] T038 [P] Create unit test for UserState enum in tests/Unit/Enums/UserStateTest.php
- [X] T039 [P] Create unit test for TeamType enum in tests/Unit/Enums/TeamTypeTest.php
- [X] T040 Create feature test for database migrations in tests/Feature/Database/MigrationsTest.php

**Checkpoint**: Foundation ready - user story implementation can now begin in parallel

---

## 5. Phase 3: User Story 1 - Team Hierarchy Management (Priority: P1) 🎯 MVP

**Goal**: Enterprise Admin can create and manage the complete team hierarchy (Enterprise → Organisation → Division → Department → Project) with validation, state management, and hierarchical slug generation.

**Independent Test**: Can be fully tested by creating an Enterprise, adding Organisations, Divisions, Departments, and Projects through the hierarchy, and verifying all validation rules (hierarchy constraints, unique names, executive/deputy constraints) are enforced. Delivers complete organizational structure modeling capability.

### 5.1. Tests for User Story 1 ⚠️

> **NOTE: Write these tests FIRST, ensure they FAIL before implementation**

- [X] T041 [P] [US1] Create feature test for Enterprise creation in tests/Feature/Teams/EnterpriseCreationTest.php
- [X] T042 [P] [US1] Create feature test for Organisation creation in tests/Feature/Teams/OrganisationCreationTest.php
- [X] T043 [P] [US1] Create feature test for Division creation in tests/Feature/Teams/DivisionCreationTest.php
- [X] T044 [P] [US1] Create feature test for Department creation in tests/Feature/Teams/DepartmentCreationTest.php
- [X] T045 [P] [US1] Create feature test for Project creation in tests/Feature/Teams/ProjectCreationTest.php
- [X] T046 [P] [US1] Create feature test for hierarchy validation rules in tests/Feature/Teams/HierarchyValidationTest.php
- [X] T047 [P] [US1] Create feature test for unique name validation in tests/Feature/Teams/UniqueNameValidationTest.php
- [X] T048 [P] [US1] Create feature test for executive/deputy constraints in tests/Feature/Teams/ExecutiveDeputyConstraintsTest.php
- [X] T048.5 [P] [US1] Create feature test for user deletion prevention when executive/deputy in tests/Feature/Teams/UserDeletionPreventionTest.php
- [X] T049 [P] [US1] Create feature test for state transitions in tests/Feature/Teams/StateTransitionsTest.php
- [X] T050 [P] [US1] Create feature test for hierarchical slug generation in tests/Feature/Teams/HierarchicalSlugTest.php
- [X] T050.5 [P] [US1] Create feature test for team bio editing permissions in tests/Feature/Teams/TeamBioPermissionsTest.php
- [X] T050.6 [P] [US1] Create feature test for team bio markdown rendering and syntax highlighting in tests/Feature/Teams/TeamBioRenderingTest.php
- [X] T050.7 [P] [US1] Create feature test for team bio XSS sanitization in tests/Feature/Teams/TeamBioSanitizationTest.php
- [X] T050.8 [P] [US1] Create feature test for team bio size limits (soft and hard) in tests/Feature/Teams/TeamBioSizeLimitsTest.php

### 5.2. Implementation for User Story 1

- [X] T051 [P] [US1] Create Enterprise model (implements TenantContract) in app/Models/Enterprise.php
- [X] T052 [P] [US1] Create Organisation model in app/Models/Organisation.php
- [X] T053 [P] [US1] Create Division model in app/Models/Division.php
- [X] T054 [P] [US1] Create Department model in app/Models/Department.php
- [X] T055 [P] [US1] Create Project model in app/Models/Project.php
- [X] T056 [US1] Implement hierarchy validation in Team model (parent-child type constraints, tenant-configurable soft limit default 5, hard limit 10) in app/Models/Team.php
- [X] T057 [US1] Implement unique name validation (per parent+type within enterprise) in app/Models/Team.php
- [X] T058 [US1] Implement executive/deputy constraint validation in app/Models/Team.php
- [X] T058.5 [US1] Implement model observer to prevent user deletion when referenced as executive/deputy in app/Observers/UserObserver.php
- [X] T059 [US1] Implement state machine for Team states in app/Models/Team.php
- [X] T060 [US1] Create StoreTeamRequest form request with validation rules in app/Http/Requests/StoreTeamRequest.php
- [X] T061 [US1] Create UpdateTeamRequest form request with validation rules in app/Http/Requests/UpdateTeamRequest.php
- [X] T062 [US1] Create CreateTeam Livewire component in app/Livewire/Teams/CreateTeam.php (implemented as Volt SFC in Folio page)
- [X] T063 [US1] Create EditTeam Livewire component in app/Livewire/Teams/EditTeam.php (implemented as Volt SFC in Folio page)
- [X] T064 [US1] Create TeamList Livewire component in app/Livewire/Teams/TeamList.php
- [X] T065 [US1] Create Folio page for teams index in resources/views/pages/teams/index.blade.php
- [X] T066 [US1] Create Folio page for team creation in resources/views/pages/teams/create.blade.php
- [X] T067 [US1] Create Folio page for team editing in resources/views/pages/teams/[ulid].blade.php
- [X] T068 [US1] Create Livewire view for CreateTeam in resources/views/livewire/teams/create-team.blade.php (implemented as Volt SFC in Folio page)
- [X] T069 [US1] Create Livewire view for EditTeam in resources/views/livewire/teams/edit-team.blade.php (implemented as Volt SFC in Folio page)
- [X] T070 [US1] Create Livewire view for TeamList in resources/views/livewire/teams/team-list.blade.php
- [X] T071 [US1] Implement optimistic locking for concurrent modifications in app/Models/Team.php
- [X] T072 [US1] Add error handling and user-friendly error messages in app/Livewire/Teams/CreateTeam.php and app/Livewire/Teams/EditTeam.php
- [X] T072.1 [US1] [OPTIONAL CQRS] Create TeamBuilder custom builder class for complex queries (Recursive CTE for scopeInContext) in app/Models/Builders/TeamBuilder.php (see research/adr-cqrs-refactor-team.md)
- [X] T072.2 [US1] [OPTIONAL CQRS] Update Team model to use TeamBuilder via newEloquentBuilder method (see research/adr-cqrs-refactor-team.md)
- [X] T072.3 [US1] [OPTIONAL CQRS] Create CreateTeam Action class for team creation with explicit transaction control in app/Actions/Teams/CreateTeam.php (see research/adr-cqrs-refactor-team.md)
- [X] T072.4 [US1] [OPTIONAL CQRS] Create UpdateTeam Action class for team updates with move detection in app/Actions/Teams/UpdateTeam.php (see research/adr-cqrs-refactor-team.md)
- [X] T072.5.1 [US1] [OPTIONAL CQRS] Refactor MoveTeam logic to use MoveTeam Action class in app/Actions/Teams/MoveTeam.php (see research/adr-cqrs-refactor-team.md)
- [X] T072.5.2 [US1] [OPTIONAL CQRS] Create AssignExecutive Action class for executive assignment in app/Actions/Teams/AssignExecutive.php (see research/adr-cqrs-refactor-team.md)
- [X] T072.5.3 [US1] [OPTIONAL CQRS] Update Folio pages to use Action classes instead of direct model manipulation (see research/adr-cqrs-refactor-team.md)
- [X] T072.5.4 [US1] [OPTIONAL CQRS] Update Filament TeamResource to use Action classes via ->using() callbacks (see research/adr-cqrs-refactor-team.md)
- [X] T072.5 [P] [US1] Create feature test for team move/reparenting in tests/Feature/Teams/TeamMoveTest.php
- [X] T072.6 [P] [US1] Create feature test for cycle prevention in team moves in tests/Feature/Teams/TeamMoveCyclePreventionTest.php
- [X] T072.7 [P] [US1] Create feature test for team move approval workflow in tests/Feature/Teams/TeamMoveApprovalTest.php
- [X] T072.8 [US1] Implement team move/reparenting validation (prevent cycles, depth violations, cross-tenant moves) in app/Models/Team.php
- [X] T072.9 [US1] Implement approval workflow for significant team moves with configurable thresholds in app/Services/TeamMoveService.php
- [X] T072.10 [US1] Create MoveTeamRequest form request with validation in app/Http/Requests/MoveTeamRequest.php
- [X] T072.11 [US1] Create MoveTeam Livewire component in app/Livewire/Teams/MoveTeam.php
- [X] T072.12 [US1] Create Livewire view for team move in resources/views/livewire/teams/move-team.blade.php
- [X] T072.7 [P] [US1] Create feature test for team move approval workflow in tests/Feature/Teams/TeamMoveApprovalTest.php
- [X] T072.13 [P] [US1] Create feature test for bulk team operations in tests/Feature/Teams/BulkTeamOperationsTest.php
- [X] T072.14 [US1] Create bulk create/update API endpoint with DTOs and partial success handling in app/Http/Controllers/Teams/BulkTeamController.php
- [X] T072.15 [US1] Create BulkTeamRequest DTO in app/Http/Requests/BulkTeamRequest.php
- [X] T072.16 [US1] Implement partial success response format for bulk operations in app/Http/Controllers/Teams/BulkTeamController.php
- [X] T072.16.5 [US1] Add tenant-configurable bulk operation batch size field (default 500 items) to Enterprise model in app/Models/Enterprise.php
- [X] T072.16.6 [US1] Implement batch size validation using tenant-configurable limit in app/Http/Controllers/Teams/BulkTeamController.php
- [X] T072.17 [P] [US1] Verify bio column is included in teams table migration (T013.5)
- [X] T072.18 [US1] Implement bio attribute (longText, nullable) in app/Models/Team.php
- [X] T072.19 [US1] Implement bio sanitization using Purify before storage in app/Models/Team.php
- [X] T072.20 [US1] Integrate bio editing in Filament Team resource using MarkdownEditor component in app/Filament/Tenant/Resources/Teams/Schemas/TeamForm.php
- [X] T072.21 [US1] Implement role-based bio editing permissions (Team Executive, Enterprise/Organisation Admins) in app/Policies/TeamPolicy.php
- [X] T072.22 [US1] Implement bio display on team profile pages with markdown rendering and syntax highlighting in resources/views/pages/teams/[ulid].blade.php
- [X] T072.23 [US1] Implement bio size limit validation (tenant-configurable soft limit default 10K, hard limit 50K) in app/Http/Requests/StoreTeamRequest.php and app/Http/Requests/UpdateTeamRequest.php
- [X] T072.24 [US1] Implement empty bio placeholder handling in resources/views/pages/teams/[ulid].blade.php
- [X] T072.25 [US1] Add client-side syntax highlighting initialization for bio code blocks in resources/js/app.js
- [X] T072.26 [US1] Create performance test for bio markdown rendering (<100ms for 95% of requests) validating SC-011 in tests/Feature/Performance/BioRenderingPerformanceTest.php
- [X] T072.27 [US1] Create performance test for syntax highlighting (<500ms after page load) validating SC-012 in tests/Feature/Performance/SyntaxHighlightingPerformanceTest.php

**Checkpoint**: At this point, User Story 1 should be fully functional and testable independently. Enterprise Admin can create and manage the complete team hierarchy with all validation rules enforced, move/reparent teams, perform bulk operations, and manage team biographies with markdown support.

---

## 6. Phase 4: User Story 2 - Context Switching (Priority: P2)

**Goal**: Users can switch context between accessible organisations, with context persisting across sessions and scoping team-related queries to the current context.

**Independent Test**: Can be fully tested by creating a user with access to multiple organisations, switching context between them, and verifying that team queries (Organisation, Division, Department, Project) are scoped to the current context. Delivers multi-organisation context management capability.

### 6.1. Tests for User Story 2 ⚠️

> **NOTE: Write these tests FIRST, ensure they FAIL before implementation**

- [X] T073 [P] [US2] Create feature test for context switching in tests/Feature/Context/ContextSwitchingTest.php
- [X] T074 [P] [US2] Create feature test for context persistence across sessions in tests/Feature/Context/ContextPersistenceTest.php
- [X] T075 [P] [US2] Create feature test for context scoping of team queries in tests/Feature/Context/ContextScopingTest.php
- [X] T076 [P] [US2] Create feature test for context restoration on login in tests/Feature/Context/ContextRestorationTest.php
- [X] T077 [P] [US2] Create feature test for invalid context handling in tests/Feature/Context/InvalidContextTest.php
- [X] T077.5 [P] [US2] Create feature test for invalid context detection and auto-correction in tests/Feature/Context/InvalidContextAutoCorrectionTest.php
- [X] T078 [P] [US2] Create feature test for context bypass permissions in tests/Feature/Context/ContextBypassTest.php

### 6.2. Implementation for User Story 2

- [X] T079 [US2] Add context scoping local scope (scopeInContext) to Team model in app/Models/Team.php
- [X] T080 [US2] Add withoutContextScope method for privileged users in app/Models/Team.php
- [X] T081 [US2] Create SwitchContextRequest form request with validation in app/Http/Requests/SwitchContextRequest.php
- [X] T082 [US2] Create SwitchContext Livewire component in app/Livewire/Context/SwitchContext.php (implemented as Volt SFC in Folio page)
- [X] T083 [US2] Implement context switching logic in User model (setCurrentContext method) in app/Models/User.php
- [X] T084 [US2] Implement context validation (user must have access to organisation) in app/Models/User.php
- [X] T085 [US2] Implement context restoration on login via Fortify's AuthenticatedSessionResponse in app/Actions/Fortify/AuthenticatedSessionResponse.php (or check existing Fortify structure first) - Implemented via ContextRestorationListener registered in FortifyServiceProvider
- [X] T086 [US2] Create Livewire view for context switcher in resources/views/livewire/context/switch-context.blade.php (implemented as Volt SFC in Folio page)
- [X] T087 [US2] Add context switcher to navigation/layout in resources/views/components/navbar.blade.php or similar - Already added in resources/views/layouts/app.blade.php
- [X] T088 [US2] Update TeamList component to use context scoping in app/Livewire/Teams/TeamList.php - Already uses inContext() scope
- [X] T089 [US2] Add context indicator to UI showing current organisation/division/department - Implemented in switch-context component via getCurrentContextName()

**Checkpoint**: At this point, User Story 2 should be fully functional and testable independently. Users can switch context between accessible organisations, and all team queries are properly scoped.

---

## 7. Phase 5: User Story 3 - Enhanced User Attributes (Priority: P3)

**Goal**: Users have enhanced attributes (ULID for routes, translatable name/slug, state machine, status tracking) that support the hierarchy and context management.

**Independent Test**: Can be fully tested by creating users with ULIDs, setting translatable attributes, transitioning states, and verifying status tracking. Delivers complete user enhancement capability.

### 7.1. Tests for User Story 3 ⚠️

> **NOTE: Write these tests FIRST, ensure they FAIL before implementation**

- [X] T090 [P] [US3] Create feature test for ULID route binding in tests/Feature/Users/UlidRouteBindingTest.php
- [X] T091 [P] [US3] Create feature test for translatable user attributes in tests/Feature/Users/TranslatableAttributesTest.php
- [X] T092 [P] [US3] Create feature test for user state transitions in tests/Feature/Users/UserStateTransitionsTest.php
- [X] T093 [P] [US3] Create feature test for user status tracking in tests/Feature/Users/UserStatusTest.php
- [X] T094 [P] [US3] Create feature test for user-enterprise relationships in tests/Feature/Users/UserEnterpriseRelationshipsTest.php
- [X] T095 [P] [US3] Create feature test for backward compatibility with integer IDs in tests/Feature/Users/BackwardCompatibilityTest.php

### 7.2. Implementation for User Story 3

- [X] T096 [US3] Integrate all User model enhancements (coordinate T097, T098, T099, T100) in app/Models/User.php
- [X] T097 [US3] Implement state machine for User states in app/Models/User.php
- [X] T098 [US3] Implement status tracking for User statuses in app/Models/User.php
- [X] T099 [US3] Implement user-enterprise many-to-many relationship in app/Models/User.php
- [X] T100 [US3] Implement user-organisation access many-to-many relationship in app/Models/User.php
- [X] T101 [US3] Add route model binding for ULID in app/Providers/RouteServiceProvider.php or bootstrap/app.php
- [X] T102 [US3] Implement backward compatibility for integer ID routes in app/Models/User.php
- [X] T103 [US3] Create data migration command to generate ULIDs for existing users in app/Console/Commands/GenerateUserUlids.php
- [X] T104 [US3] Create data migration command to set default states for existing users in app/Console/Commands/SetDefaultUserStates.php
- [X] T105 [US3] Update UserFactory to generate ULIDs and set states in database/factories/UserFactory.php
- [X] T105.5 [P] [US3] Verify bio column is included in users table migration (T012.5)
- [X] T105.6 [US3] Implement bio attribute (longText, nullable) in app/Models/User.php
- [X] T105.7 [US3] Implement bio sanitization using Purify before storage in app/Models/User.php
- [X] T105.8 [US3] Integrate bio editing in Filament User resource using MarkdownEditor component in app/Filament/Resources/UserResource.php
- [X] T105.9 [US3] Implement role-based bio editing permissions (Users can edit own, Enterprise/Organisation Admins can edit any) in app/Policies/UserPolicy.php
- [X] T105.10 [US3] Implement bio display on user profile pages with markdown rendering and syntax highlighting in resources/views/pages/users/[ulid].blade.php
- [X] T105.11 [US3] Implement bio size limit validation (tenant-configurable soft limit default 10K, hard limit 50K) in app/Http/Requests/UpdateUserRequest.php or similar
- [X] T105.12 [US3] Implement empty bio placeholder handling in resources/views/pages/users/[ulid].blade.php
- [X] T105.13 [US3] Update data migration to set bio fields to null for existing users in app/Console/Commands/GenerateUserUlids.php or separate command
- [X] T105.14 [US3] [OPTIONAL CQRS] Create UserBuilder custom builder class for complex queries (active, banned, onboarded, withRole) in app/Models/Builders/UserBuilder.php (see research/adr-cqrs-refactor-user.md)
- [X] T105.15 [US3] [OPTIONAL CQRS] Update User model to use UserBuilder via newEloquentBuilder method (see research/adr-cqrs-refactor-user.md)
- [X] T105.16 [US3] [OPTIONAL CQRS] Create RegisterUser Action class for user registration with explicit transaction control in app/Actions/Users/RegisterUser.php (see research/adr-cqrs-refactor-user.md)
- [X] T105.17 [US3] [OPTIONAL CQRS] Create BanUser Action class for banning users with side effects (revoke tokens, sessions) in app/Actions/Users/BanUser.php (see research/adr-cqrs-refactor-user.md)
- [X] T105.18 [US3] [OPTIONAL CQRS] Create UpdateUserProfile Action class for profile updates in app/Actions/Users/UpdateUserProfile.php (see research/adr-cqrs-refactor-user.md)
- [X] T105.19 [US3] [OPTIONAL CQRS] Create TransitionUserState Action class for state transitions in app/Actions/Users/TransitionUserState.php (see research/adr-cqrs-refactor-user.md)
- [X] T105.20 [US3] [OPTIONAL CQRS] Update Filament UserResource to use Action classes via ->using() callbacks (see research/adr-cqrs-refactor-user.md)
- [X] T105.21 [US3] [OPTIONAL CQRS] Update Livewire components to use Action classes instead of direct model manipulation (see research/adr-cqrs-refactor-user.md)

**Checkpoint**: At this point, User Story 3 should be fully functional and testable independently. Users have all enhanced attributes working with backward compatibility maintained, including biography support with markdown.

---

## 8. Phase 6: Multi-Tenancy Integration

**Purpose**: Integrate multi-tenancy with Enterprise as tenant, subdomain-based identification, and automatic query scoping

### 8.1. Tests for Multi-Tenancy

- [ ] T106 [P] Create feature test for tenant identification via subdomain in tests/Feature/Tenancy/TenantIdentificationTest.php
- [ ] T107 [P] Create feature test for tenant isolation in tests/Feature/Tenancy/TenantIsolationTest.php
- [ ] T108 [P] Create feature test for cross-tenant prevention in tests/Feature/Tenancy/CrossTenantPreventionTest.php
- [ ] T109 [P] Create feature test for multi-enterprise user support in tests/Feature/Tenancy/MultiEnterpriseUserTest.php

### 8.2. Implementation for Multi-Tenancy

- [ ] T110 Configure Enterprise model to implement TenantContract in app/Models/Enterprise.php
- [ ] T111 Add BelongsToTenant trait to all tenant-aware models (User, Team, Domain) in app/Models/
- [ ] T112 Configure Domain model for enterprise subdomain mapping in app/Models/Domain.php
- [ ] T113 Set up wildcard DNS configuration for local development (documentation)
- [ ] T114 Test subdomain-based tenant routing in development environment
- [ ] T114.5 [P] Create feature test for enterprise selection at login in tests/Feature/Authentication/EnterpriseSelectionTest.php
- [ ] T114.6 Implement enterprise selection UI in login flow (check if Fortify actions need extension) in app/Actions/Fortify/ or resources/views/auth/

---

## 9. Phase 7: Polish & Cross-Cutting Concerns

**Purpose**: Improvements that affect multiple user stories, performance optimization, and final validation

- [ ] T115 [P] Add database indexes for performance (composite indexes on tenant_id, parent_id, type) in database/migrations/
- [ ] T116 [P] Add GIN indexes for JSONB columns (name, slug) in database/migrations/
- [ ] T117 [P] Optimize queries with eager loading in app/Livewire/Teams/TeamList.php and other components
- [ ] T118 [P] Add query performance tests validating SC-001 through SC-007 in tests/Feature/Performance/QueryPerformanceTest.php
  - Validate SC-001: Support 100 enterprises (create 100 enterprises, verify performance)
  - Validate SC-002: Support 10,000 teams per enterprise (create 10,000 teams, verify queries <500ms)
  - Validate SC-003: Support 1,000 users per enterprise (create 1,000 users, verify acceptable response times)
  - Validate SC-004: List queries within 500ms for 95% of requests (load test with realistic data)
  - Validate SC-005: Single entity operations within 200ms for 95% of requests (load test fetch by ULID)
  - Validate SC-006: Horizontal scaling support (document architecture, verify stateless design)
  - Validate SC-007: Performance at 80% scale (test with 80 enterprises, 8,000 teams, 800 users)
- [ ] T119 [P] Update documentation in README.md or docs/ with feature usage
- [ ] T120 [P] Run Laravel Pint to format all code: `vendor/bin/pint`
- [ ] T121 [P] Run PHPStan level 9 analysis: `vendor/bin/phpstan analyse`
- [ ] T122 [P] Run Rector analysis: `vendor/bin/rector`
- [ ] T123 [P] Run Mago architecture checks: `composer run lint:architecture`
- [ ] T124 Verify 99% PHP test coverage: `php artisan test --coverage`
- [ ] T125 Verify 100% type coverage: `vendor/bin/phpstan analyse --level=9`
- [ ] T126 Run quickstart.md validation to ensure all steps work
- [ ] T127 Create browser test for critical user journey (team creation) in tests/Browser/TeamCreationTest.php
- [ ] T128 Create browser test for context switching in tests/Browser/ContextSwitchingTest.php
- [ ] T129 Review and update API documentation in specs/002-enhanced-user-models/contracts/openapi.yaml
- [ ] T130 [P] Implement structured JSON logging with contextual metadata (user ID, tenant ID, request ID, timestamps) in app/Logging/StructuredLogger.php
- [ ] T131 [P] Expose performance metrics (response times, query durations, error rates) for monitoring in app/Http/Middleware/PerformanceMetricsMiddleware.php
- [ ] T132 [P] Implement distributed tracing with correlation IDs in app/Http/Middleware/TracingMiddleware.php
- [ ] T133 [P] Integrate APM tools and configure dashboards for operational visibility (configure Laravel Telescope or similar)
- [ ] T134 [P] Create feature test for structured logging in tests/Feature/Logging/StructuredLoggingTest.php
- [ ] T135 [P] Create feature test for performance metrics in tests/Feature/Monitoring/PerformanceMetricsTest.php
- [ ] T136 [P] Implement per-enterprise rate limiting middleware in app/Http/Middleware/RateLimitByTenant.php
- [ ] T137 [P] Add tenant-configurable rate limiting quota fields (requests_per_minute, requests_per_hour) to Enterprise model in app/Models/Enterprise.php
- [ ] T137.5 [P] Configure rate limiting quotas per enterprise with system defaults (1000 requests/minute, 10000 requests/hour) in config/rate-limiting.php
- [ ] T137.6 [P] Implement tenant-configurable rate limiting logic using Enterprise quota fields in app/Http/Middleware/RateLimitByTenant.php
- [ ] T138 [P] Implement HTTP 429 error response with Retry-After header and JSON error body in app/Exceptions/Handler.php
- [ ] T139 [P] Create feature test for rate limiting in tests/Feature/RateLimiting/RateLimitingTest.php
- [ ] T140 [P] Create feature test for HTTP 429 error response format in tests/Feature/RateLimiting/RateLimitErrorResponseTest.php
- [ ] T140.5 [P] Create feature test for tenant-configurable rate limiting quotas in tests/Feature/RateLimiting/TenantConfigurableQuotasTest.php
- [ ] T141 [P] Implement GDPR data export endpoint (JSON primary, CSV optional) including bio content in app/Http/Controllers/Compliance/DataExportController.php
- [ ] T142 [P] Implement data deletion (right to be forgotten) with anonymization strategy in app/Http/Controllers/Compliance/DataDeletionController.php
- [ ] T142.5 [P] Implement user data anonymization (replace identifying info with anonymized data, preserve relationships) in app/Services/UserAnonymizationService.php
- [ ] T143 [P] Implement audit logging for all user actions, data access, and modifications in app/Observers/AuditLogObserver.php
- [ ] T144 [P] Create audit log model and migration in app/Models/AuditLog.php and database/migrations/
- [ ] T144.5 [P] Add tenant-configurable audit log retention period field (default 7 years) to Enterprise model in app/Models/Enterprise.php
- [ ] T144.6 [P] Create scheduled artisan command for audit log cleanup (preserve records within retention period) in app/Console/Commands/CleanupAuditLogs.php
- [ ] T145 [P] Implement data classification and tagging for sensitive information in app/Models/DataClassification.php
- [ ] T146 [P] Create feature test for GDPR data export including bio in tests/Feature/Compliance/GdprDataExportTest.php
- [ ] T147 [P] Create feature test for data deletion with anonymization in tests/Feature/Compliance/DataDeletionTest.php
- [ ] T148 [P] Create feature test for audit logging in tests/Feature/Compliance/AuditLoggingTest.php
- [ ] T148.5 [P] Create feature test for audit log retention and cleanup in tests/Feature/Compliance/AuditLogRetentionTest.php
- [ ] T225 [P] Create Architectural Decision Records (ADRs) document in docs/adr/ documenting key decisions (ULID vs integer, STI vs separate tables, subdomain tenancy, custom Livewire 4 Islands chat implementation, optional CQRS refactor paths)
- [ ] T226 [P] Document architectural quality metrics (coupling, cohesion, complexity) and success criteria in plan.md
- [ ] T227 [P] Create test plan document in docs/test-plan.md with test scope, objectives, types, coverage requirements, and exit criteria
- [ ] T228 [P] Document TDD workflow with specific steps (Red-Green-Refactor) in docs/tdd-workflow.md
- [ ] T229 [P] Document test quality requirements (test organization, naming conventions, clarity standards) in docs/test-quality.md
- [ ] T230 [P] Document refactoring requirements and guidelines (refactor after green, maintain test coverage) in docs/refactoring-guidelines.md
- [ ] T231 [P] Document test maintenance requirements (update tests with code changes) in docs/test-maintenance.md
- [ ] T232 [P] Document code review process and quality gates in docs/code-review-process.md
- [ ] T233 [P] Document bug fix workflow (test-first bug fixes, regression testing) in docs/bug-fix-workflow.md
- [ ] T234 [P] Document refactoring workflow (maintain test coverage, backward compatibility) in docs/refactoring-workflow.md
- [ ] T235 [P] Document test flakiness handling procedures in docs/test-reliability.md
- [ ] T236 [P] Document performance test optimization guidelines in docs/performance-testing.md
- [ ] T237 [P] Create BDD feature files (.feature format) for all user stories in tests/Feature/BDD/
- [ ] T238 [P] Document BDD step definitions and reusable patterns in docs/bdd-step-definitions.md
- [ ] T239 [P] Document task acceptance criteria and completion checklist in docs/task-completion-checklist.md
- [ ] T240 [P] Document task quality standards and verification steps in docs/task-quality-standards.md
- [ ] T241 [P] Document process for handling blocked tasks and dependency failures in docs/task-dependency-management.md
- [ ] T242 [P] Document parallel task execution guidelines and conflict resolution in docs/parallel-execution.md
- [ ] T243 [P] Document component failure handling patterns and graceful degradation in docs/error-handling-patterns.md
- [ ] T244 [P] Document partial system degradation scenarios and recovery procedures in docs/reliability-patterns.md
- [ ] T245 [P] Document data format requirements (ULID format, translatable slug format) in docs/data-formats.md
- [ ] T246 [P] Document architectural terms and definitions (STI, ULID, BelongsToTenant) in docs/architecture-glossary.md
- [ ] T247 [P] Document infrastructure assumptions (PostgreSQL 18, horizontal scaling) in docs/infrastructure-assumptions.md
- [ ] T248 [P] Document external services dependencies (APM tools, observability stack) in docs/external-dependencies.md
- [ ] T249 [P] Document team skills assumptions and requirements in docs/team-skills.md
- [ ] T250 [P] Document third-party library dependencies and rationale in docs/third-party-dependencies.md
- [ ] T251 [P] Create design review checklist and process in docs/design-review-process.md
- [ ] T252 [P] Document code structure analysis process (Mago checks, architecture validation) in docs/code-structure-analysis.md
- [ ] T253 [P] Document test plan change management process in docs/test-plan-change-management.md
- [ ] T254 [P] Document test failure handling and troubleshooting procedures in docs/test-failure-handling.md
- [ ] T255 [P] Document test environment troubleshooting procedures in docs/test-environment-troubleshooting.md
- [ ] T256 [P] Document test data isolation and conflict resolution in docs/test-data-management.md
- [ ] T257 [P] Document CI/CD test execution optimization in docs/ci-cd-optimization.md
- [ ] T258 [P] Document security requirements for test data handling in docs/test-data-security.md
- [ ] T259 [P] Document empty/null data handling requirements in docs/empty-data-handling.md
- [ ] T260 [P] Document minimum/zero data scenarios in docs/minimum-data-scenarios.md
- [ ] T261 [P] Document invalid data format handling in docs/invalid-data-handling.md
- [ ] T262 [P] Document network failure handling procedures in docs/network-failure-handling.md
- [ ] T263 [P] Document timezone edge case handling in docs/timezone-handling.md
- [ ] T264 [P] Document locale/character encoding edge cases for translatable attributes in docs/locale-handling.md
- [ ] T265 [P] Document performance requirements for edge case scenarios in docs/edge-case-performance.md
- [ ] T266 [P] Document security requirements for edge case scenarios in docs/edge-case-security.md
- [ ] T267 [P] Document reliability requirements for edge case scenarios in docs/edge-case-reliability.md
- [ ] T268 [P] Document data integrity monitoring requirements (audit logs, constraint violation tracking) in docs/data-integrity-monitoring.md
- [ ] T269 [P] Document data source reliability assumptions in docs/data-source-assumptions.md
- [ ] T270 [P] Document data validation library dependencies in docs/data-validation-dependencies.md
- [ ] T271 [P] Document data consistency model assumptions (ACID transactions) in docs/data-consistency-assumptions.md
- [ ] T272 [P] Document database integrity feature dependencies in docs/database-integrity-dependencies.md
- [ ] T273 [P] Document performance requirements balanced with data integrity in docs/performance-data-integrity-balance.md
- [ ] T274 [P] Document availability requirements balanced with data integrity in docs/availability-data-integrity-balance.md
- [ ] T275 [P] Create comprehensive test plan with risk assessment in docs/comprehensive-test-plan.md
- [ ] T276 [P] Document regression testing strategy and test suite in docs/regression-testing-strategy.md
- [ ] T277 [P] Document release test process and exit criteria in docs/release-test-process.md
- [ ] T278 [P] Document update test process for feature updates in docs/update-test-process.md

---

## 10. Phase 8: User Story 4 - Presence Status and Monitoring (Priority: P2)

**Goal**: Users can see real-time presence status (Online, Offline, Away, Busy) of other users and teams, along with presence history, to understand availability and coordinate collaboration effectively.

**Independent Test**: Can be fully tested by setting user presence status, viewing presence of other users/teams, querying presence history, and verifying real-time updates. Delivers complete presence awareness capability.

### 10.1. Tests for User Story 4 ⚠️

> **NOTE: Write these tests FIRST, ensure they FAIL before implementation**

- [ ] T149 [P] [US4] Create feature test for presence status tracking in tests/Feature/Presence/PresenceStatusTest.php
- [ ] T150 [P] [US4] Create feature test for automatic presence transitions (login, inactivity timeout) in tests/Feature/Presence/AutomaticPresenceTransitionsTest.php
- [ ] T151 [P] [US4] Create feature test for explicit presence status changes in tests/Feature/Presence/ExplicitPresenceChangesTest.php
- [ ] T152 [P] [US4] Create feature test for presence history queries in tests/Feature/Presence/PresenceHistoryTest.php
- [ ] T153 [P] [US4] Create feature test for team presence aggregation in tests/Feature/Presence/TeamPresenceAggregationTest.php
- [ ] T154 [P] [US4] Create feature test for real-time presence updates in tests/Feature/Presence/RealTimePresenceUpdatesTest.php
- [ ] T155 [P] [US4] Create performance test for presence status updates (<2s for 95% of updates) validating SC-008 in tests/Feature/Performance/PresenceUpdatePerformanceTest.php

### 10.2. Implementation for User Story 4

- [ ] T156 [US4] Create migration to add presence columns (presence_status, last_seen_at) to users table in database/migrations/YYYY_MM_DD_HHMMSS_add_presence_to_users.php
- [ ] T157 [US4] Create migration to create presence_history table in database/migrations/YYYY_MM_DD_HHMMSS_create_presence_history_table.php
- [ ] T158 [P] [US4] Create HasPresence trait in app/Models/Traits/HasPresence.php
- [ ] T159 [P] [US4] Create PresenceHistory model in app/Models/Presence/PresenceHistory.php
- [ ] T160 [US4] Implement automatic presence status transitions (Online on login, Away after 5-minute inactivity, Offline on logout) in app/Models/User.php
- [ ] T161 [US4] Implement explicit presence status changes (Busy, Away) in app/Models/User.php
- [ ] T162 [US4] Implement presence history recording on status changes in app/Models/User.php
- [ ] T163 [US4] Implement team presence aggregation: count-based aggregation (count of online/away/offline members for direct team members only) AND overall presence indicator (aggregates presence of all sub-teams/descendants without counts, showing general availability status like "Mostly online", "Some away", "Mostly offline") in app/Models/Team.php
- [ ] T164 [US4] Create PresenceIndicator Livewire component in app/Livewire/Presence/PresenceIndicator.php
- [ ] T165 [US4] Create PresenceHistory Livewire component in app/Livewire/Presence/PresenceHistory.php
- [ ] T166 [US4] Implement real-time presence updates via WebSocket connections (Laravel Echo/Pusher) in app/Livewire/Presence/PresenceIndicator.php
- [ ] T166.5 [US4] Create Laravel broadcasting event for presence status changes in app/Events/PresenceStatusChanged.php
- [ ] T166.6 [US4] Configure WebSocket channel authorization for presence channels (per-channel subscription authorization) in app/Broadcasting/PresenceChannel.php
- [ ] T166.6.5 [US4] Implement per-message validation for presence updates (validate authorization for each presence update before delivery) in app/Events/PresenceStatusChanged.php
- [ ] T166.6.6 [US4] Implement WebSocket polling fallback mechanism (poll every 5-10 seconds when WebSocket unavailable) in app/Livewire/Presence/PresenceIndicator.php
- [ ] T166.6.7 [US4] Implement connection status indicator UI component in resources/views/livewire/presence/presence-indicator.blade.php
- [ ] T166.6.8 [US4] Implement automatic reconnection with exponential backoff for WebSocket connections in resources/js/app.js
- [ ] T166.6.9 [US4] Implement seamless transition back to WebSocket when connection is restored in app/Livewire/Presence/PresenceIndicator.php
- [ ] T166.7 [US4] Add tenant-configurable presence history retention period field (default 90 days) to Enterprise model in app/Models/Enterprise.php
- [ ] T166.8 [US4] Create scheduled artisan command for presence history cleanup (preserve records within retention period) in app/Console/Commands/CleanupPresenceHistory.php
- [ ] T167 [US4] Create Livewire view for PresenceIndicator in resources/views/livewire/presence/presence-indicator.blade.php
- [ ] T168 [US4] Create Livewire view for PresenceHistory in resources/views/livewire/presence/presence-history.blade.php
- [ ] T169 [US4] Add presence indicator to user profile pages in resources/views/pages/users/[ulid].blade.php
- [ ] T170 [US4] Add team presence aggregation to team pages in resources/views/pages/teams/[ulid].blade.php

**Checkpoint**: At this point, User Story 4 should be fully functional and testable independently. Users can see real-time presence status of other users and teams, query presence history, and presence updates occur automatically based on user activity.

---

## 11. Phase 9: User Story 5 - Follow Users and Teams (Priority: P3)

**Goal**: Users can follow other users and teams to receive updates, notifications, and maintain awareness of their activities and changes.

**Independent Test**: Can be fully tested by following/unfollowing users and teams, verifying follow relationships are persisted, and checking that followed entities appear in user's follow list. Delivers social connection and information curation capability.

### 11.1. Tests for User Story 5 ⚠️

> **NOTE: Write these tests FIRST, ensure they FAIL before implementation**

- [ ] T171 [P] [US5] Create feature test for following users in tests/Feature/Follow/FollowUserTest.php
- [ ] T172 [P] [US5] Create feature test for following teams in tests/Feature/Follow/FollowTeamTest.php
- [ ] T173 [P] [US5] Create feature test for unfollowing users/teams in tests/Feature/Follow/UnfollowTest.php
- [ ] T174 [P] [US5] Create feature test for follow list queries in tests/Feature/Follow/FollowListTest.php
- [ ] T175 [P] [US5] Create feature test for self-follow prevention in tests/Feature/Follow/SelfFollowPreventionTest.php
- [ ] T176 [P] [US5] Create feature test for team follow permission validation in tests/Feature/Follow/TeamFollowPermissionTest.php
- [ ] T177 [P] [US5] Create feature test for automatic follow cleanup on access loss in tests/Feature/Follow/AutoCleanupFollowTest.php
- [ ] T178 [P] [US5] Create feature test for automatic follow cleanup on entity deletion/archival in tests/Feature/Follow/FollowCleanupOnDeletionTest.php

### 11.2. Implementation for User Story 5

- [ ] T179 [US5] Create migration to create user_follows_user pivot table in database/migrations/YYYY_MM_DD_HHMMSS_create_user_follows_user_table.php
- [ ] T180 [US5] Create migration to create user_follows_team pivot table in database/migrations/YYYY_MM_DD_HHMMSS_create_user_follows_team_table.php
- [ ] T181 [US5] Implement followedUsers and followers relationships in app/Models/User.php
- [ ] T182 [US5] Implement followedTeams relationship in app/Models/User.php
- [ ] T183 [US5] Implement followers relationship in app/Models/Team.php
- [ ] T184 [US5] Implement follow user logic with self-follow prevention in app/Models/User.php
- [ ] T185 [US5] Implement follow team logic with permission validation in app/Models/User.php
- [ ] T186 [US5] Implement unfollow logic in app/Models/User.php
- [ ] T187 [US5] Implement automatic follow cleanup when user loses team access in app/Observers/UserObserver.php or app/Models/User.php
- [ ] T188 [US5] Implement automatic follow cleanup when followed entity is deleted/archived in app/Observers/UserObserver.php and app/Observers/TeamObserver.php
- [ ] T188.5 [US5] Implement follow notification preferences (notification type flags, email preferences) in app/Models/User.php
- [ ] T188.6 [US5] Create NotificationPreferences Livewire component for configuring follow notifications in app/Livewire/Follow/NotificationPreferences.php
- [ ] T188.7 [US5] Create Livewire view for notification preferences in resources/views/livewire/follow/notification-preferences.blade.php
- [ ] T188.8 [US5] Implement in-app follow notification delivery via WebSocket channels in app/Events/FollowNotificationEvent.php
- [ ] T188.9 [US5] Implement optional email follow notifications based on user preferences in app/Notifications/FollowNotification.php
- [ ] T188.10 [US5] Create feature test for follow notification preferences in tests/Feature/Follow/FollowNotificationPreferencesTest.php
- [ ] T188.11 [US5] Create feature test for follow notification delivery (WebSocket and email) in tests/Feature/Follow/FollowNotificationDeliveryTest.php
- [ ] T188.12 [US5] Implement time-based throttling for follow notifications (batch notifications within tenant-configurable time window, default 1-5 minutes) in app/Services/FollowNotificationService.php
- [ ] T188.13 [US5] Add tenant-configurable follow notification throttling window field (default 1-5 minutes) to Enterprise model in app/Models/Enterprise.php
- [ ] T188.14 [US5] Implement notification summary delivery when throttling window expires in app/Services/FollowNotificationService.php
- [ ] T188.15 [US5] Create feature test for follow notification throttling in tests/Feature/Follow/FollowNotificationThrottlingTest.php
- [ ] T188.16 [US5] Configure WebSocket channel authorization for follow notification channels (per-channel subscription authorization) in app/Broadcasting/FollowNotificationChannel.php
- [ ] T188.17 [US5] Implement per-message validation for follow notifications (validate authorization for each notification before delivery) in app/Events/FollowNotificationEvent.php
- [ ] T188.18 [US5] Implement WebSocket polling fallback for follow notifications (poll every 5-10 seconds when WebSocket unavailable) in app/Livewire/Follow/NotificationPreferences.php
- [ ] T189 [US5] Create FollowUser Livewire component in app/Livewire/Follow/FollowUser.php
- [ ] T190 [US5] Create FollowTeam Livewire component in app/Livewire/Follow/FollowTeam.php
- [ ] T191 [US5] Create FollowList Livewire component in app/Livewire/Follow/FollowList.php
- [ ] T192 [US5] Create Livewire view for FollowUser in resources/views/livewire/follow/follow-user.blade.php
- [ ] T193 [US5] Create Livewire view for FollowTeam in resources/views/livewire/follow/follow-team.blade.php
- [ ] T194 [US5] Create Livewire view for FollowList in resources/views/livewire/follow/follow-list.blade.php
- [ ] T195 [US5] Add follow button to user profile pages in resources/views/pages/users/[ulid].blade.php
- [ ] T196 [US5] Add follow button to team pages in resources/views/pages/teams/[ulid].blade.php
- [ ] T197 [US5] Create Folio page for follow list in resources/views/pages/follows/index.blade.php

**Checkpoint**: At this point, User Story 5 should be fully functional and testable independently. Users can follow/unfollow other users and teams, view their follow list, and follow relationships are automatically cleaned up when access is lost or entities are deleted.

---

## 12. Phase 10: User Story 6 - Online Chat Capabilities (Priority: P2)

**Goal**: Users can send and receive real-time chat messages with other users and teams to communicate and collaborate effectively within the platform.

**Independent Test**: Can be fully tested by initiating conversations with users and teams, sending/receiving messages, viewing conversation history, and verifying read receipts and unread counts. Delivers complete chat communication capability.

### 12.1. Tests for User Story 6 ⚠️

> **NOTE: Write these tests FIRST, ensure they FAIL before implementation**

- [ ] T198 [P] [US6] Create feature test for one-on-one chat conversations in tests/Feature/Chat/OneOnOneChatTest.php
- [ ] T199 [P] [US6] Create feature test for team chat conversations in tests/Feature/Chat/TeamChatTest.php
- [ ] T200 [P] [US6] Create feature test for read receipts tracking in tests/Feature/Chat/ReadReceiptsTest.php
- [ ] T201 [P] [US6] Create feature test for unread message counts in tests/Feature/Chat/UnreadCountsTest.php
- [ ] T202 [P] [US6] Create feature test for chat conversation history in tests/Feature/Chat/ChatHistoryTest.php
- [ ] T203 [P] [US6] Create feature test for real-time message delivery in tests/Feature/Chat/RealTimeDeliveryTest.php
- [ ] T204 [P] [US6] Create feature test for chat access validation (prevent messages to users/teams without access) in tests/Feature/Chat/ChatAccessValidationTest.php
- [ ] T205 [P] [US6] Create feature test for tenant isolation in chat conversations in tests/Feature/Chat/ChatTenantIsolationTest.php
- [ ] T206 [P] [US6] Create performance test for chat message delivery (<1s for 95% of messages) validating SC-009 in tests/Feature/Performance/ChatDeliveryPerformanceTest.php
- [ ] T207 [P] [US6] Create load test for concurrent chat conversations (100 concurrent per enterprise) validating SC-010 in tests/Feature/Performance/ConcurrentChatLoadTest.php

### 12.2. Implementation for User Story 6

**Note**: WireChat package is incompatible with Livewire 4. Custom chat implementation using Livewire 4 Islands, Laravel Reverb Presence Channels, and custom database schema. See `research/wirechat.md` for architectural blueprint.

- [ ] T208 [US6] Create migration for conversations table (custom chat schema) in database/migrations/YYYY_MM_DD_HHMMSS_create_conversations_table.php
- [ ] T209 [US6] Create migration for participants table (custom chat schema) in database/migrations/YYYY_MM_DD_HHMMSS_create_participants_table.php
- [ ] T210 [US6] Create migration for messages table (custom chat schema with reactions JSON, replies foreign key, edits timestamp) in database/migrations/YYYY_MM_DD_HHMMSS_create_messages_table.php
- [ ] T211 [US6] Create Conversation model (custom implementation) in app/Models/Chat/Conversation.php
- [ ] T212 [P] [US6] Create Participant model (custom implementation) in app/Models/Chat/Participant.php
- [ ] T213 [US6] Create Message model (custom implementation) in app/Models/Chat/Message.php
- [ ] T214 [US6] Implement tenant isolation for chat conversations (ensure conversations respect enterprise boundaries) in app/Models/Chat/Conversation.php
- [ ] T215 [US6] Create ChatRoom Livewire 4 Island component in app/Livewire/Chat/ChatRoom.php
- [ ] T216 [US6] Create ChatList Livewire 4 component in app/Livewire/Chat/ChatList.php
- [ ] T217 [US6] Create ChatWidget Livewire 4 Island component for embedding chat in pages in app/Livewire/Chat/ChatWidget.php
- [ ] T218 [US6] Implement Laravel Reverb Presence Channel setup for chat conversations in app/Broadcasting/ChatChannel.php
- [ ] T219 [US6] Implement "Start Chat" button on user profile pages in resources/views/pages/users/[ulid].blade.php
- [ ] T220 [US6] Implement "Chat with Team" button on team pages in resources/views/pages/teams/[ulid].blade.php
- [ ] T221 [US6] Implement chat access validation (prevent messages when sender loses access) in app/Livewire/Chat/ChatRoom.php
- [ ] T222 [US6] Configure real-time message delivery via Laravel Reverb WebSocket connections in app/Livewire/Chat/ChatRoom.php
- [ ] T222.5 [US6] Create Laravel broadcasting event for chat messages in app/Events/ChatMessageSent.php
- [ ] T222.6 [US6] Configure Laravel Reverb Presence Channel authorization for chat channels (per-channel subscription authorization) in app/Broadcasting/ChatChannel.php
- [ ] T222.6.5 [US6] Implement per-message validation for chat messages (validate authorization for each message before delivery) in app/Events/ChatMessageSent.php
- [ ] T222.6.6 [US6] Implement WebSocket polling fallback for chat messages (poll every 5-10 seconds when WebSocket unavailable) in app/Livewire/Chat/ChatRoom.php
- [ ] T222.6.7 [US6] Implement typing indicators using Reverb client whispers (no database storage) in resources/views/livewire/chat/chat-room.blade.php
- [ ] T222.6.8 [US6] Implement follow-based chat authorization (users can only chat with followed users/teams) in app/Policies/ChatPolicy.php
- [ ] T222.6.9 [US6] Implement chat message character limit validation (tenant-configurable default 10,000 characters, system maximum 50,000 characters) in app/Http/Requests/SendChatMessageRequest.php or app/Livewire/Chat/ChatRoom.php
- [ ] T222.6.10 [US6] Add tenant-configurable chat message character limit field (default 10,000 characters) to Enterprise model in app/Models/Enterprise.php
- [ ] T222.6.11 [US6] Create feature test for chat message size limit validation in tests/Feature/Chat/ChatMessageSizeLimitTest.php
- [ ] T222.6.12 [US6] Implement reactions support (JSON column on messages table) in app/Models/Chat/Message.php
- [ ] T222.6.13 [US6] Implement reply support (reply_to_id foreign key on messages table) in app/Models/Chat/Message.php
- [ ] T222.6.14 [US6] Implement message edit support (edited_at timestamp) in app/Models/Chat/Message.php
- [ ] T222.7 [US6] Add tenant-configurable chat history retention period field (default 1 year) to Enterprise model in app/Models/Enterprise.php
- [ ] T222.8 [US6] Create scheduled artisan command for chat history cleanup (preserve messages within retention period) in app/Console/Commands/CleanupChatHistory.php
- [ ] T222.9 [US6] Create feature test for chat history retention and cleanup in tests/Feature/Chat/ChatHistoryRetentionTest.php
- [ ] T223 [US6] Create Folio page for chat interface in resources/views/pages/chat/index.blade.php
- [ ] T224 [US6] Add chat widget (Livewire 4 Island) to navigation/layout in resources/views/components/navbar.blade.php or similar

**Checkpoint**: At this point, User Story 6 should be fully functional and testable independently. Users can initiate one-on-one and team chat conversations using custom Livewire 4 Islands implementation, send/receive messages in real-time via Laravel Reverb Presence Channels, view conversation history, see read receipts and unread counts, use typing indicators, and leverage modern chat features (reactions, replies, edits).

---

## 13. Dependencies & Execution Order

### 13.1. Phase Dependencies

- **Setup (Phase 1)**: No dependencies - can start immediately
- **Foundational (Phase 2)**: Depends on Setup completion - BLOCKS all user stories
- **User Stories (Phase 3-5)**: All depend on Foundational phase completion
  - User stories can then proceed in parallel (if staffed)
  - Or sequentially in priority order (P1 → P2 → P3)
- **Multi-Tenancy (Phase 6)**: Depends on User Story 1 (Enterprise model) and Foundational
- **Polish (Phase 7)**: Depends on all desired user stories being complete
- **User Story 4 (Phase 8)**: Depends on Foundational phase completion - Can start after Phase 2
- **User Story 5 (Phase 9)**: Depends on Foundational phase completion - Can start after Phase 2
- **User Story 6 (Phase 10)**: Depends on Setup (Laravel Reverb configuration) and Foundational phase completion - Can start after Phase 1 and Phase 2

### 13.2. User Story Dependencies

- **User Story 1 (P1)**: Can start after Foundational (Phase 2) - No dependencies on other stories
- **User Story 2 (P2)**: Can start after Foundational (Phase 2) - Requires User model enhancements from US3 for context fields, but can be implemented in parallel
- **User Story 3 (P3)**: Can start after Foundational (Phase 2) - No dependencies on other stories
- **User Story 4 (P2)**: Can start after Foundational (Phase 2) - No dependencies on other stories
- **User Story 5 (P3)**: Can start after Foundational (Phase 2) - No dependencies on other stories
- **User Story 6 (P2)**: Can start after Setup (Phase 1 - Laravel Reverb configuration) and Foundational (Phase 2) - Requires Laravel Reverb WebSocket infrastructure
- **Multi-Tenancy (Phase 6)**: Requires User Story 1 (Enterprise model) - Can start after US1

### 13.3. Within Each User Story

- Tests (REQUIRED) MUST be written and FAIL before implementation
- Models before services/components
- Base models before child models
- Core implementation before integration
- Story complete before moving to next priority

### 13.4. Parallel Opportunities

- All Setup tasks marked [P] can run in parallel
- All Foundational tasks marked [P] can run in parallel (within Phase 2)
- Once Foundational phase completes, User Stories 1, 3, 4, and 5 can start in parallel
- User Story 2 can start after User model has context fields (from US3 or Phase 2)
- User Story 6 can start after Setup (Laravel Reverb) and Foundational phases complete
- All tests for a user story marked [P] can run in parallel
- Models within a story marked [P] can run in parallel
- Different user stories can be worked on in parallel by different team members (with coordination)

---

## 14. Parallel Example: User Story 1

```bash
# Launch all tests for User Story 1 together:
Task: "Create feature test for Enterprise creation in tests/Feature/Teams/EnterpriseCreationTest.php"
Task: "Create feature test for Organisation creation in tests/Feature/Teams/OrganisationCreationTest.php"
Task: "Create feature test for Division creation in tests/Feature/Teams/DivisionCreationTest.php"
Task: "Create feature test for Department creation in tests/Feature/Teams/DepartmentCreationTest.php"
Task: "Create feature test for Project creation in tests/Feature/Teams/ProjectCreationTest.php"

# Launch all models for User Story 1 together:
Task: "Create Enterprise model (implements TenantContract) in app/Models/Enterprise.php"
Task: "Create Organisation model in app/Models/Organisation.php"
Task: "Create Division model in app/Models/Division.php"
Task: "Create Department model in app/Models/Department.php"
Task: "Create Project model in app/Models/Project.php"
```

---

## 15. Implementation Strategy

### 15.1. MVP First (User Story 1 Only)

1. Complete Phase 1: Setup
2. Complete Phase 2: Foundational (CRITICAL - blocks all stories)
3. Complete Phase 3: User Story 1 (Team Hierarchy Management)
4. **STOP and VALIDATE**: Test User Story 1 independently
5. Deploy/demo if ready

### 15.2. Incremental Delivery

1. Complete Setup + Foundational → Foundation ready
2. Add User Story 1 → Test independently → Deploy/Demo (MVP!)
3. Add Multi-Tenancy Integration → Test independently → Deploy/Demo
4. Add User Story 2 → Test independently → Deploy/Demo
5. Add User Story 3 → Test independently → Deploy/Demo
6. Each story adds value without breaking previous stories

### 15.3. Parallel Team Strategy

With multiple developers:

1. Team completes Setup + Foundational together
2. Once Foundational is done:
   - Developer A: User Story 1 (Team Hierarchy)
   - Developer B: User Story 3 (Enhanced User Attributes) - can work in parallel
   - Developer C: Multi-Tenancy Integration (after US1 Enterprise model)
3. Once US1 and US3 are done:
   - Developer A: User Story 2 (Context Switching)
   - Developer B: Polish & Optimization
4. Stories complete and integrate independently

---

## 16. Task Summary

- **Total Tasks**: 380 (includes 16 optional CQRS refactor tasks: 8 for Teams, 8 for Users)
- **Phase 1 (Setup)**: 21 tasks (added Laravel Reverb WebSocket infrastructure, bio markdown packages, and configuration)
- **Phase 2 (Foundational)**: 33 tasks (added bio column migrations: T012.5, T013.5)
- **Phase 3 (User Story 1)**: 71 tasks (17 tests + 54 implementation, added bio support for teams, 8 optional CQRS refactor tasks)
- **Phase 4 (User Story 2)**: 17 tasks (6 tests + 11 implementation)
- **Phase 5 (User Story 3)**: 37 tasks (10 tests + 27 implementation, added bio support for users, 8 optional CQRS refactor tasks)
- **Phase 6 (Multi-Tenancy)**: 7 tasks (5 tests + 7 implementation)
- **Phase 7 (Polish)**: 95 tasks (34 original + 54 documentation/process tasks + 7 new retention/rate limiting tasks)
- **Phase 8 (User Story 4 - Presence)**: 32 tasks (7 tests + 25 implementation, added WebSocket infrastructure, polling fallback, per-message validation, overall presence indicator, and retention cleanup)
- **Phase 9 (User Story 5 - Follow)**: 43 tasks (8 tests + 35 implementation, added notification preferences, throttling mechanism, WebSocket delivery, polling fallback, and per-message validation)
- **Phase 10 (User Story 6 - Chat)**: 44 tasks (11 tests + 33 implementation, custom Livewire 4 Islands implementation, Laravel Reverb Presence Channels, reactions/replies/edits support, polling fallback, per-message validation, message size limits, and retention cleanup)

### 16.1. MVP Scope (User Story 1 Only)

- **MVP Tasks**: 123 tasks (Phases 1, 2, 3, includes 8 optional CQRS refactor tasks)
- **MVP Independent Test**: Create Enterprise, add full hierarchy (Organisation → Division → Department → Project), verify all validation rules, and edit/view team biographies with markdown support
- **MVP Deliverable**: Complete team hierarchy management with validation, state management, hierarchical slugs, and team biography support with markdown rendering and syntax highlighting

---

## 17. Notes

- [P] tasks = different files, no dependencies
- [Story] label maps task to specific user story for traceability
- [OPTIONAL CQRS] tasks = optional future refactoring work documented in research/ ADRs, not required for MVP
- Each user story should be independently completable and testable
- **CRITICAL**: Verify tests fail before implementing (TDD requirement)
- Commit after each task or logical group
- Stop at any checkpoint to validate story independently
- Avoid: vague tasks, same file conflicts, cross-story dependencies that break independence
- All code must pass PHPStan level 9, Laravel Pint, Rector, and Mago checks
- Maintain 99% PHP test coverage and 100% type coverage throughout
- **Chat Implementation**: Custom Livewire 4 Islands implementation (WireChat package incompatible with Livewire 4). See `research/wirechat.md` for architectural blueprint.
- **CQRS Refactor**: Optional future work documented in `research/adr-cqrs-refactor-team.md` and `research/adr-cqrs-refactor-user.md`. Can be performed incrementally if complexity warrants it.
