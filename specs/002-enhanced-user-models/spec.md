# Feature Specification: Enhanced User and Team Models

**Feature Branch**: `002-enhanced-user-models`
**Created**: 2025-12-22
**Status**: Draft
**Input**: User description: "based on @docs/user-model-enhancements/050-enhanced-models create a complete, detailed specification"

**Updated**: 2025-12-22
**Update Input**: Additional requirements: presence status/monitoring/history for users and teams, follow functionality, WireChat integration

**Updated**: 2025-12-22
**Update Input**: Additional requirements: Bio attribute for User and Team models (longText, markdown format, Filament integration)

**Updated**: 2025-12-22
**Update Input**: Clarification session for bio requirements

## Clarifications

### Session 2025-12-22

- Q: What is the primary business goal and scope boundary for this feature? → A: Model full Enterprise/Organisation/Division/Department/Project hierarchy plus user context switching between organisations as the primary goal
- Q: Which entities and relationships are in scope for the data model? → A: Include all core entities (User, Enterprise, Organisation, Division, Department, Project) plus supporting entities (Domain, pivot tables)
- Q: How are user roles and permissions defined for team management and context switching? → A: Role-based permissions (Enterprise Admin, Organisation Admin, Team Executive, Regular User) with defined capabilities per role
- Q: How is multi-tenancy implemented for tenant isolation and identification? → A: Subdomain-based tenant identification (`{enterprise-slug}.example.com`) with automatic query scoping and explicit tenant context checks
- Q: How does context switching work and what data is scoped? → A: Explicit user action (select organisation), persists in database, scopes team queries, requires permission check
- Q: What business rules and validation constraints apply to hierarchy, naming, and leadership? → A: Strict hierarchy rules, unique names per parent+type within enterprise, executive/deputy constraints (different people, same enterprise)
- Q: How do state transitions and lifecycle management work for users and teams? → A: Explicit state transition rules with role-based permissions (Enterprise Admin can transition all states, Team Executive can activate/suspend their teams, automatic Draft→Pending→Active on approval)
- Q: How are errors handled and presented to users? → A: Clear specific error messages per validation type, inline field-level errors, optimistic locking for conflicts (show conflict warning, allow refresh/retry), actionable guidance in messages
- Q: What are the performance and scalability targets for this feature? → A: Moderate scale targets (100 enterprises, 10,000 teams per enterprise, 1,000 users per enterprise) with <500ms list queries, <200ms single operations, supports horizontal scaling
- Q: How is data migration and backward compatibility handled? → A: Data migration required for existing users (generate ULIDs, default states, initialize context), maintain backward compatibility with integer ID routes/APIs during transition, provide migration scripts
- Q: How should rate limiting/throttling be implemented for API endpoints and user actions? → A: Per-enterprise rate limiting (each enterprise has independent quota, protects tenants from each other)
- Q: What data protection and privacy requirements must the system support? → A: Full compliance suite (GDPR, CCPA, SOC 2, with audit logging and data classification)
- Q: What observability requirements must the system support for monitoring and debugging? → A: Full observability stack (structured logs, metrics, distributed tracing, APM integration, dashboards)
- Q: How should maximum hierarchy depth be enforced for team nesting? → A: Tenant-configurable soft limit (default 5 levels with warning), hard limit 10 levels (validation error)
- Q: How should bulk operations (creating/updating multiple teams) be supported? → A: Bulk create/update via API with validation, using DTOs (Data Transfer Objects) with fallback to array format
- Q: Should the system support moving/reparenting teams to different parents in the hierarchy? → A: Allow move with validation (prevent cycles, depth violations, cross-tenant moves, validate new parent type) and require explicit approval workflow for significant changes
- Q: What should happen when a rate limit is exceeded for an API request? → A: HTTP 429 with Retry-After header and JSON error body (includes quota info, reset time, retry guidance)
- Q: What defines a "significant" team move that requires approval workflow? → A: Configurable thresholds (descendant count, depth change, cross-organisation moves) with enterprise-level settings
- Q: What format should be used for GDPR data export (right to access/portability)? → A: JSON with optional CSV export (JSON primary, CSV for spreadsheet analysis)
- Q: How should bulk operations handle partial failures (some items succeed, some fail validation)? → A: Partial success with detailed per-item results (return array with success/error for each item, client handles retries)
- Q: Who can edit bio content for users and teams? → A: Role-based permissions: Users can edit their own bio; Team Executives can edit their team's bio; Enterprise/Organisation Admins can edit any bio in their scope
- Q: Should bio content be translatable (multi-language)? → A: Bio is single-language per user/team (not translatable), stored as plain markdown text
- Q: How should existing users/teams without bio data be handled during migration? → A: Set bio to null by default for all existing users/teams; no automatic content generation
- Q: Should there be practical size limits or warnings for bio content? → A: Soft limit (tenant-configurable, default 10,000 characters) with warning, plus hard limit (e.g., 50,000 characters) with validation error preventing longer content
- Q: Should bio content be included in GDPR data export requests? → A: Yes, bio content must be included in GDPR data export (right to access/portability) requests
- Q: How should real-time updates (presence status and chat messages) be delivered to the frontend? → A: WebSockets (e.g., Laravel Echo, Pusher) for true real-time bidirectional communication
- Q: Who can approve significant team moves that require approval workflow? → A: Multi-level approval: requires approval from both the source organisation admin and target organisation admin (if different)
- Q: What should happen to related data when a user requests GDPR deletion (right to be forgotten)? → A: Anonymize user data and preserve relationships with anonymized references (replace identifying information with anonymized data, preserve chat history, presence history, and follow relationships with anonymized references)
- Q: What updates or notifications should followers receive when following users or teams? → A: Configurable notifications: followers can configure which types of updates they want to receive (activity, presence, bio changes, etc.)
- Q: How long should presence history be retained for users? → A: Tenant-configurable retention period (default 90 days) with automatic cleanup of older records
- Q: How long should audit logs be retained? → A: Tenant-configurable retention period (default 7 years for SOC 2 compliance) with automatic cleanup of older records
- Q: How long should chat conversation history be retained? → A: Tenant-configurable retention period (default 1 year) with automatic cleanup of messages older than the retention period
- Q: What should be the default rate limiting quotas per enterprise? → A: Tenant-configurable quotas with system defaults (e.g., 1000 requests/minute, 10000 requests/hour) that can be adjusted based on enterprise needs
- Q: What should be the maximum batch size for bulk operations (creating/updating multiple teams)? → A: Tenant-configurable maximum batch size (default 500 items) per bulk operation request
- Q: How should follow notifications be delivered to users? → A: In-app notifications via WebSocket with optional email notifications that users can enable/disable per notification type
- Q: How should the system handle WebSocket connection failures or service unavailability for real-time features? → A: Graceful degradation with automatic reconnection (exponential backoff), fallback to polling (e.g., every 5-10 seconds) when WebSocket unavailable, and connection status indicator for users
- Q: When calculating team presence aggregation, should the system include only direct team members or also members of nested/descendant teams? → A: Direct members only for count-based aggregation (e.g., "5 members online, 3 away"), plus an additional "overall" presence indicator (without counts) that aggregates presence of all sub-teams to show general availability status
- Q: Should there be character or size limits for individual chat messages? → A: Tenant-configurable character limit (e.g., 10,000 characters default) that enterprises can adjust, with system maximum (e.g., 50,000 characters) to prevent abuse and manage storage
- Q: Should there be throttling or rate limiting for follow notifications to prevent spam? → A: Time-based throttling: batch notifications within a tenant-configurable time window (e.g., 1-5 minutes default), sending a summary when the window expires to prevent notification spam while keeping users informed
- Q: How should WebSocket channel authorization be implemented for presence, chat, and follow notifications? → A: Both per-channel subscription authorization AND per-message validation: check authorization when subscribing to channels to prevent unauthorized subscriptions, and validate authorization for each message/event before delivery to handle access changes during active sessions

## User Scenarios & Testing *(mandatory)*

**Primary Goal**: Model full Enterprise/Organisation/Division/Department/Project hierarchy plus user context switching between organisations as the primary goal. Enhanced user attributes (ULID, states, statuses) support this hierarchy and context management. Additionally, the system provides presence status tracking and history, follow functionality for users and teams, online chat capabilities to enable real-time collaboration and communication, and biography (bio) support in markdown format for users and teams to enable rich text descriptions and documentation.

**User Roles**: The system uses role-based permissions with the following roles:

- **Enterprise Admin**: Full access to create/manage all teams within their enterprise, assign executives/deputies, manage user access
- **Organisation Admin**: Can create/manage teams within their assigned organisation(s) and descendants, assign executives/deputies within their scope
- **Team Executive**: Can view and manage their assigned team(s) and descendants, assign deputies for their teams
- **Regular User**: Can view teams within their accessible organisations, switch context between accessible organisations, cannot create or modify teams

**Note**: This specification uses British English spelling ("Organisation") for consistency with Laravel conventions and the codebase.

---

### User Story 1 - Team Hierarchy Management (Priority: P1)

As an Enterprise Admin, I want to create and manage a complete organizational hierarchy (Enterprise → Organisation → Division → Department → Project) with markdown biographies so that I can model my business structure with proper validation, state management, hierarchical relationships, and rich text descriptions for each team.

**Why this priority**: This is the core functionality that enables all other features. Without the ability to create and manage teams, context switching and enhanced user attributes have no organizational structure to operate on. This delivers the foundational data model required for the entire feature, including biographical information for teams.

**Independent Test**: Can be fully tested by creating an Enterprise, adding Organisations, Divisions, Departments, and Projects through the hierarchy, verifying all validation rules (hierarchy constraints, unique names, executive/deputy constraints) are enforced, and editing/viewing bio content for teams. Delivers complete organizational structure modeling capability with biographical information.

**Acceptance Scenarios**:

1. **Given** I am an Enterprise Admin, **When** I create an Enterprise, **Then** the Enterprise is created with ULID, translatable name/slug, and I am assigned as executive
2. **Given** I have an Enterprise, **When** I create an Organisation under it, **Then** the Organisation is created with hierarchical slug and parent relationship
3. **Given** I have an Organisation, **When** I create a Division under it, **Then** the Division is created with proper parent-child type validation
4. **Given** I attempt to create a Division directly under an Enterprise, **Then** the operation is rejected with a clear validation error
5. **Given** I create two teams with the same name but different parents, **Then** both teams are created successfully
6. **Given** I create two teams with the same name, same parent, and same type, **Then** the second creation is rejected with a unique name validation error
7. **Given** I assign the same person as executive and deputy, **Then** the operation is rejected with a clear validation error
8. **Given** I transition a team from Draft to Active, **Then** the state transition succeeds and the team becomes active
9. **Given** I attempt to transition a team directly from Draft to Suspended, **Then** the transition is rejected as invalid
10. **Given** I am a Team Executive, **When** I edit my assigned team's bio (or an Enterprise/Organisation Admin edits any team's bio in their scope), **Then** the bio is saved as markdown and displayed with syntax highlighting on the frontend

---

### User Story 2 - Context Switching (Priority: P2)

As a user with access to multiple organisations, I want to switch my working context between accessible organisations so that I can view and work with teams within my selected organisation scope.

**Why this priority**: Context switching enables users to work effectively across multiple organisational units within an enterprise. While the hierarchy (US1) provides the structure, context switching provides the user experience layer that makes multi-organisation access practical. This delivers the user-facing capability to navigate the organizational structure.

**Independent Test**: Can be fully tested by creating a user with access to multiple organisations, switching context between them, and verifying that team queries (Organisation, Division, Department, Project) are scoped to the current context. Delivers multi-organisation context management capability.

**Acceptance Scenarios**:

1. **Given** I am a user with access to Organisation A and Organisation B, **When** I switch my context to Organisation A, **Then** my current context is saved to the database and persists across sessions
2. **Given** my context is set to Organisation A, **When** I query teams, **Then** only teams within Organisation A's hierarchy are returned
3. **Given** I switch context to Organisation B, **When** I query teams again, **Then** only teams within Organisation B's hierarchy are returned
4. **Given** I log out and log back in, **When** the system restores my context, **Then** my last selected context (Organisation B) is restored
5. **Given** I lose access to my current context organisation, **When** I make a request, **Then** the system detects the invalid context, selects a new valid default, and informs me
6. **Given** I am a privileged user with bypass permissions, **When** I query teams without context scope, **Then** all teams within my enterprise are returned

---

### User Story 3 - Enhanced User Attributes (Priority: P3)

As a system administrator, I want users to have enhanced attributes (ULID for routes, translatable name/slug, state machine, status tracking, markdown bio) so that users can be uniquely identified, localized, managed through their lifecycle, and have rich biographical information.

**Why this priority**: Enhanced user attributes support the hierarchy and context management features. ULIDs provide URL-safe identifiers, translatable attributes enable internationalization, state/status management enables proper user lifecycle control, and bio support enables rich text descriptions. While not blocking for MVP, these enhancements improve user experience and system capabilities.

**Independent Test**: Can be fully tested by creating users with ULIDs, setting translatable attributes, transitioning states, verifying status tracking, and editing/viewing bio content in Filament and frontend. Delivers complete user enhancement capability including biographical information.

**Acceptance Scenarios**:

1. **Given** I create a new user, **When** the user is saved, **Then** a ULID is automatically generated and can be used for route binding
2. **Given** I set a user's name in English and French, **When** I retrieve the user, **Then** the appropriate translation is returned based on locale
3. **Given** a user is in Draft state, **When** an Enterprise Admin approves them, **Then** the user automatically transitions from Draft → Pending → Active
4. **Given** a user is Active, **When** I set their status to Away, **Then** the status is tracked and can be queried
5. **Given** I have existing users with integer IDs, **When** I run the migration command, **Then** ULIDs are generated and backward compatibility is maintained
6. **Given** I access a user route with an integer ID, **When** the route is processed, **Then** the user is found using backward compatibility lookup
7. **Given** I am a user, **When** I edit my own bio (or an Enterprise/Organisation Admin edits any user's bio, or a Team Executive edits their team's bio), **Then** the bio is saved as markdown and rendered with syntax highlighting on the frontend
8. **Given** I am viewing a user or team profile, **When** the bio contains markdown with code blocks, **Then** the code is highlighted with the Catppuccin Mocha theme
9. **Given** I enter malicious script tags in a bio field, **When** the bio is saved and displayed, **Then** the script tags are sanitized and removed to prevent XSS attacks

---

### User Story 4 - Presence Status and Monitoring (Priority: P2)

As a user, I want to see the real-time presence status (Online, Offline, Away, Busy) of other users and teams, along with presence history, so that I can understand availability and coordinate collaboration effectively.

**Why this priority**: Presence status enables real-time collaboration awareness, helping users know when colleagues are available for communication. This enhances the user experience for chat and coordination features. While not blocking for MVP, presence status significantly improves the utility of communication features.

**Independent Test**: Can be fully tested by setting user presence status, viewing presence of other users/teams, querying presence history, and verifying real-time updates. Delivers complete presence awareness capability.

**Acceptance Scenarios**:

1. **Given** I am an active user, **When** I log in, **Then** my presence status is automatically set to Online and visible to other users
2. **Given** I am Online, **When** I become inactive for 5 minutes, **Then** my presence status automatically transitions to Away
3. **Given** I am Online, **When** I explicitly set my status to Busy, **Then** my status changes to Busy and is visible to other users
4. **Given** I am viewing a user's profile, **When** I check their presence, **Then** I see their current status (Online/Offline/Away/Busy) and last seen timestamp
5. **Given** I am viewing a team, **When** I check team presence, **Then** I see count-based aggregated presence for direct members (e.g., "5 members online, 3 away") and an overall presence indicator for all sub-teams (e.g., "Mostly online")
6. **Given** I query presence history for a user, **When** I request the last 7 days, **Then** I receive a timeline of presence status changes with timestamps
7. **Given** a user's presence status changes, **When** other users viewing that user's presence, **Then** the status updates in real-time without page refresh

---

### User Story 5 - Follow Users and Teams (Priority: P3)

As a user, I want to follow other users and teams with configurable notifications so that I can receive updates and maintain awareness of their activities and changes based on my preferences.

**Why this priority**: Follow functionality enables users to curate their information feed and stay informed about relevant people and teams. This supports collaboration and information discovery. While not critical for MVP, it enhances user engagement and information flow.

**Independent Test**: Can be fully tested by following/unfollowing users and teams, verifying follow relationships are persisted, and checking that followed entities appear in user's follow list. Delivers social connection and information curation capability.

**Acceptance Scenarios**:

1. **Given** I am viewing another user's profile, **When** I click "Follow", **Then** I am added as a follower and the follow relationship is saved
2. **Given** I am viewing a team, **When** I click "Follow Team", **Then** I am added as a team follower and the relationship is saved
3. **Given** I am following a user, **When** I view my follow list, **Then** that user appears in my followed users list
4. **Given** I am following a team, **When** I view my follow list, **Then** that team appears in my followed teams list
5. **Given** I am following a user, **When** I click "Unfollow", **Then** the follow relationship is removed and the user no longer appears in my follow list
6. **Given** I am following a team, **When** I lose access to that team (e.g., removed from organisation), **Then** the follow relationship is automatically removed
7. **Given** I am viewing a user's profile, **When** I check their followers, **Then** I see the count of followers (if permissions allow) but not necessarily the full list

---

### User Story 6 - Online Chat Capabilities (Priority: P2)

As a user, I want to send and receive real-time chat messages with other users and teams so that I can communicate and collaborate effectively within the platform.

**Why this priority**: Online chat provides real-time communication capabilities essential for collaboration. Using WireChat as a foundation accelerates implementation while maintaining extensibility. This delivers core communication functionality that enhances user engagement.

**Independent Test**: Can be fully tested by initiating conversations with users and teams, sending/receiving messages, viewing conversation history, and verifying read receipts and unread counts. Delivers complete chat communication capability.

**Acceptance Scenarios**:

1. **Given** I am viewing another user's profile, **When** I click "Start Chat", **Then** a conversation is created and I can send messages
2. **Given** I am viewing a team, **When** I click "Chat with Team", **Then** a team conversation is created and I can send messages to team members
3. **Given** I send a message in a conversation, **When** the message is delivered, **Then** the recipient sees the message in real-time and read receipts are tracked
4. **Given** I have unread messages, **When** I view my conversations list, **Then** I see unread count badges indicating messages I haven't read
5. **Given** I am in a conversation, **When** I view the conversation history, **Then** I see all previous messages with timestamps and sender information
6. **Given** I receive a message, **When** I am online, **Then** I receive a real-time notification and the conversation appears in my list
7. **Given** I am in a team conversation, **When** I send a message, **Then** all team members with access receive the message

### Edge Cases

- What happens when a team is moved to a new parent that would violate the maximum hierarchy depth?
  **Expected**: The move is rejected with a clear validation error and the original hierarchy is preserved. If the move would exceed the soft limit, a warning is shown and approval is required. If it would exceed the hard limit, the move is rejected.
- What happens when a user attempts to move a team that would create a cycle (team becomes its own ancestor)?
  **Expected**: The move is rejected with a clear validation error: "Cannot move team to this location as it would create a circular reference."
- What happens when a significant team move (e.g., team with many descendants) is requested?
  **Expected**: The system requires explicit approval workflow, notifies affected stakeholders (executives, deputies), and requires approval from both the source organisation admin and target organisation admin (if different) before the move is executed. For moves within the same organisation, approval from the organisation admin is required.
- How does the system handle deletion or deactivation of an executive or deputy who is referenced by multiple teams?
  **Expected**: Deletion is blocked or requires explicit reassignment; deactivation requires reassignment or an explicit decision and teams remain in a valid state.
- What happens when a user loses access to an organisation that is currently stored as their active context?
  **Expected**: On next login or request, the system detects the invalid context, selects a new valid default context (or none), and informs the user.
- How does the system behave when an enterprise reaches the soft limit (default 5 levels) in its hierarchy and an additional nested team is requested?
  **Expected**: The operation succeeds but a warning is displayed recommending reorganization. If the hard limit (10 levels) is reached, the operation is rejected with a validation error and the user is prompted to reorganise or flatten the structure.
- What happens when a team name conflict is detected (same name, same parent, same type)?
  **Expected**: The operation is rejected with a clear validation error: "A team with the name '{name}' already exists in this context. Please choose a different name."
- What happens when executive and deputy are assigned as the same person?
  **Expected**: The operation is rejected with a clear validation error: "Executive and Deputy cannot be the same person."
- What happens when a user attempts to assign an executive/deputy from a different enterprise?
  **Expected**: The operation is rejected with a clear validation error: "Cross-tenant relationships are not allowed."
- What happens when two users attempt to modify the same team simultaneously?
  **Expected**: The system detects the conflict using optimistic locking, shows a warning message: "This team was modified by another user. Please refresh and try again.", and allows the user to refresh and retry with the latest data.
- What happens when a validation error occurs during team creation or update?
  **Expected**: The system displays specific, actionable error messages inline at the relevant fields (e.g., "A team with the name 'Sales' already exists in this context. Please choose a different name." next to the name field).
- What happens when a user's presence status is queried but they have never logged in?
  **Expected**: The system returns "Offline" status with no last seen timestamp, or indicates the user has never been active.
- What happens when presence history is requested for a time period with no data?
  **Expected**: The system returns an empty history array with appropriate messaging indicating no presence data for the requested period.
- What happens when a user attempts to follow themselves?
  **Expected**: The operation is rejected with a clear validation error: "You cannot follow yourself."
- What happens when a user attempts to follow a team they don't have access to?
  **Expected**: The operation is rejected with a clear validation error: "You do not have permission to follow this team."
- What happens when a followed user or team is deleted or archived?
  **Expected**: The follow relationship is automatically removed, and the user is notified (if applicable) that the followed entity is no longer available.
- What happens when a chat conversation is created but one participant loses access to the other user or team?
  **Expected**: The conversation is marked as restricted, messages cannot be sent, and participants are notified of the access change.
- What happens when a user sends a message to a team conversation but loses team access mid-conversation?
  **Expected**: The message is rejected with an error, and the user is removed from the conversation participants list.
- What happens when a user attempts to send a chat message that exceeds the tenant-configurable character limit?
  **Expected**: The message is rejected with a clear validation error: "Message exceeds maximum length of {limit} characters. Please shorten your message."
- What happens when a followed user or team has multiple activity updates (e.g., presence changes, bio updates) in quick succession?
  **Expected**: System batches notifications within the tenant-configurable time window (e.g., 1-5 minutes) and sends a summary notification when the window expires (e.g., "User X had 3 updates in the last 5 minutes"), preventing notification spam while keeping users informed.
- What happens when a user loses access to a team or user while actively subscribed to their WebSocket channel (presence, chat, or notifications)?
  **Expected**: System validates authorization for each message/event before delivery, preventing delivery of messages to users who have lost access, and automatically unsubscribes the user from the channel when access is revoked.
- What happens when bio content contains malicious script tags or XSS attempts?
  **Expected**: All script tags and unsafe HTML are sanitized and removed by `stevebauman/purify` before storage and rendering, preventing XSS attacks while preserving valid markdown formatting.
- What happens when bio content exceeds the tenant-configurable soft limit (default 10,000 characters)?
  **Expected**: System displays a warning message but allows the user to continue editing and save the content.
- What happens when bio content exceeds the hard limit (e.g., 50,000 characters)?
  **Expected**: System rejects the save operation with a validation error: "Bio content exceeds maximum length of {limit} characters. Please shorten your bio."
- What happens when code blocks in bio contain unsupported languages for syntax highlighting?
  **Expected**: Code blocks are still displayed with basic formatting, and `highlight.js` attempts auto-detection or falls back to plain text formatting.
- What happens when bio is edited in Filament admin panel and saved, then viewed on frontend immediately?
  **Expected**: The updated bio is immediately available on frontend, and if Livewire navigation is used, syntax highlighting is automatically re-applied via `livewire:navigated` event listener.
- What happens when a user requests GDPR deletion (right to be forgotten)?
  **Expected**: System anonymizes user identifying information (replacing name, email, bio with anonymized data such as "Deleted User #123"), preserves related data (chat history, presence history, follow relationships, team memberships) with anonymized references, and maintains audit trails. The user record remains in the database but is no longer personally identifiable.
- What happens when WebSocket connections fail or the WebSocket service is unavailable?
  **Expected**: System automatically attempts reconnection with exponential backoff, falls back to polling (e.g., every 5-10 seconds) for real-time updates (presence, chat, notifications) when WebSocket unavailable, displays connection status indicator to users, and seamlessly transitions back to WebSocket when connection is restored.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: System MUST identify tenants via subdomain pattern (`{enterprise-slug}.example.com`) and map subdomain to Enterprise entity
- **FR-002**: System MUST automatically scope all tenant-related queries to the current tenant (Enterprise) using BelongsToTenant trait
- **FR-003**: System MUST prevent cross-tenant data access and relationships, rejecting operations that would violate tenant isolation
- **FR-004**: System MUST support users belonging to multiple enterprises with explicit enterprise selection at login or via switch action
  - **Implementation Note**: Enterprise selection at login is handled automatically via subdomain-based tenant identification. Users accessing `{enterprise-slug}.example.com` are automatically associated with that enterprise. Multi-enterprise users can switch between enterprises by accessing different subdomains.
- **FR-005**: System MUST enforce tenant context checks for all data operations, ensuring users can only access data within their current enterprise context
- **FR-006**: System MUST provide explicit context switching mechanism (user selects organisation from UI) that requires permission validation
- **FR-007**: System MUST persist user's current context (organisation, optional division, optional department) in database, surviving sessions and logouts
- **FR-008**: System MUST scope all team-related queries (Organisation, Division, Department, Project) to user's current context using local scope (not global scope)
- **FR-009**: System MUST restore user's last valid context on login, or default to first accessible organisation if no saved context exists
- **FR-010**: System MUST allow privileged users to bypass context scoping for administrative/reporting purposes, guarded by appropriate permissions
- **FR-011**: System MUST enforce that only Enterprise can be root-level (parent_id = null), and all other team types require a parent
- **FR-011.5**: System MUST enforce tenant-configurable soft limit for hierarchy depth (default 5 levels, shows warning when exceeded) and hard limit of 10 levels (rejects creation with validation error)
- **FR-012**: System MUST enforce strict parent-child type constraints (Organisation can only have Enterprise as parent, Division can only have Organisation as parent, Department can only have Division as parent, Project can have any team type as parent)
- **FR-013**: System MUST enforce unique team names within the same parent AND same type within an enterprise graph, while allowing same names across different parents or different types
- **FR-014**: System MUST enforce that each team has a mandatory executive and optional deputy, and that executive and deputy cannot be the same person
- **FR-015**: System MUST enforce that executives and deputies assigned to a team belong to the same enterprise as the team
- **FR-016**: System MUST prevent deletion of a user who is currently acting as executive or deputy of any team unless all affected teams are reassigned first
- **FR-017**: System MUST enforce explicit state transition rules: User states (Draft→Pending→Active→Suspended→Archived), Team states (Draft→Active→Inactive→Archived)
- **FR-018**: System MUST allow Enterprise Admin to transition users and teams through all state transitions
- **FR-019**: System MUST allow Team Executive to activate or suspend their assigned teams (but not archive them)
- **FR-020**: System MUST automatically transition users from Draft→Pending→Active upon approval (by Enterprise Admin or designated approver)
- **FR-021**: System MUST prevent invalid state transitions (e.g., cannot transition directly from Draft to Suspended, must go through Active first)
- **FR-022**: System MUST provide clear, specific error messages for each validation failure type (hierarchy violations, name conflicts, executive/deputy constraints, cross-tenant violations)
- **FR-023**: System MUST display validation errors inline at the field level where applicable, with actionable guidance on how to resolve the error
- **FR-024**: System MUST handle concurrent modification conflicts using optimistic locking, showing a conflict warning and allowing users to refresh and retry the operation
- **FR-025**: System MUST provide user-friendly error messages that explain what went wrong and suggest corrective actions, avoiding technical jargon or stack traces in user-facing messages
- **FR-026**: System MUST provide data migration scripts to generate ULIDs for existing users, set default states (Active for existing active users, Draft for new users), initialize context fields, and set bio fields to null for existing users and teams (no automatic content generation)
- **FR-027**: System MUST maintain backward compatibility with existing integer ID-based routes and APIs during a transition period, supporting both ULID and integer ID lookups
- **FR-028**: System MUST allow gradual migration rollout, ensuring existing functionality continues to work while new ULID-based features are introduced
- **FR-029**: System MUST implement per-enterprise rate limiting for API endpoints and user actions, with each enterprise having an independent quota to protect tenants from each other. Quotas are tenant-configurable per enterprise, with system defaults (e.g., 1000 requests/minute, 10000 requests/hour) that can be adjusted based on enterprise needs.
- **FR-029.5**: System MUST return HTTP 429 (Too Many Requests) status code when rate limit is exceeded, including Retry-After header with seconds until quota resets and JSON error body with quota information (current usage, limit, reset time, retry guidance)
- **FR-030**: System MUST comply with GDPR, CCPA, and SOC 2 requirements including: data export (right to access/portability), data deletion (right to be forgotten), audit logging of all data access and modifications, and data classification for sensitive information
- **FR-030.6**: System MUST handle GDPR deletion requests by anonymizing user identifying information (replacing with anonymized data such as "Deleted User #123") while preserving related data (chat history, presence history, follow relationships, team memberships) with anonymized references to maintain data integrity and audit trails
- **FR-030.5**: System MUST provide GDPR data export in JSON format (primary, structured, machine-readable) with optional CSV export for spreadsheet analysis, including all user data, team memberships, bio content, and associated metadata
- **FR-031**: System MUST provide audit logs for all user actions, data access, modifications, and administrative operations with immutable timestamps and actor identification. Retention period is tenant-configurable per enterprise (default 7 years for SOC 2 compliance), with automatic cleanup of records older than the retention period.
- **FR-032**: System MUST support data classification and tagging to identify sensitive information (PII, financial data, etc.) for compliance reporting
- **FR-033**: System MUST provide structured logging (JSON format) for all operations with contextual metadata (user ID, tenant ID, request ID, timestamps)
- **FR-034**: System MUST expose performance metrics (response times, query durations, error rates) for monitoring and alerting
- **FR-035**: System MUST support distributed tracing for request flows across services/components with correlation IDs
- **FR-036**: System MUST integrate with APM (Application Performance Monitoring) tools and provide dashboards for operational visibility
- **FR-037**: System MUST support bulk create/update operations for teams via API endpoint that accepts array of team data, validates all items, and returns detailed per-item results (array with success/error status and validation details for each item, allowing partial success where some items succeed and others fail). Maximum batch size is tenant-configurable per enterprise (default 500 items per bulk operation request).
- **FR-038**: System MUST implement bulk operations using DTOs (Data Transfer Objects) for type safety and validation, with fallback support for array format for backward compatibility
- **FR-039**: System MUST support moving/reparenting teams to different parents in the hierarchy with validation that prevents: cycles (team cannot become its own ancestor), depth violations (would exceed soft/hard limits), cross-tenant moves, and invalid parent type constraints
- **FR-040**: System MUST require explicit approval workflow for significant team moves based on configurable enterprise-level thresholds (descendant count, depth change, cross-organisation moves) with notification to affected stakeholders. For cross-organisation moves, approval is required from both the source organisation admin and target organisation admin (if different). For moves within the same organisation, approval from the organisation admin is required.
- **FR-041**: System MUST track and display real-time presence status (Online, Offline, Away, Busy) for users, updating automatically based on user activity (login, inactivity timeout of 5 minutes, explicit status changes)
- **FR-042**: System MUST maintain presence history for users with timestamps of status changes, allowing queries for historical presence data over configurable time periods. Retention period is tenant-configurable per enterprise (default 90 days), with automatic cleanup of records older than the retention period.
- **FR-043**: System MUST provide aggregated presence information for teams: count-based aggregation (e.g., "5 members online, 3 away") for direct team members only, plus an "overall" presence indicator (without counts) that aggregates presence of all sub-teams/descendants to show general availability status. The overall indicator MUST be calculated based on percentage thresholds: "Mostly online" (>=70% of sub-teams have online members), "Some away" (30-69% have away/offline members), "Mostly offline" (>=70% of sub-teams have offline members), or "Mixed" (no clear majority).
- **FR-044**: System MUST update presence status in real-time for all users viewing another user's or team's presence without requiring page refresh using WebSocket connections (e.g., Laravel Echo, Pusher) for true real-time bidirectional communication, with automatic reconnection (exponential backoff) and polling fallback (e.g., every 5-10 seconds) when WebSocket unavailable. System MUST implement both per-channel subscription authorization (check authorization when subscribing to presence channels) AND per-message validation (validate authorization for each presence update before delivery) to handle access changes during active sessions.
- **FR-045**: System MUST allow users to follow other users, creating a follow relationship that persists and can be queried
- **FR-046**: System MUST allow users to follow teams, creating a follow relationship that persists and can be queried, subject to team access permissions
- **FR-047**: System MUST prevent users from following themselves (self-follow prevention)
- **FR-048**: System MUST automatically remove follow relationships when a user loses access to a followed team or when a followed user/team is deleted or archived
- **FR-049**: System MUST provide users with a list of their followed users and followed teams, queryable and filterable
- **FR-049.5**: System MUST allow followers to configure which types of updates/notifications they receive for followed users and teams (e.g., activity changes, presence status changes, bio updates), with per-follow relationship notification preferences. Notifications are delivered in-app via WebSocket connections (with polling fallback when WebSocket unavailable), with optional email notifications that users can enable/disable per notification type. System MUST implement time-based throttling for follow notifications, batching notifications within a tenant-configurable time window (e.g., 1-5 minutes default) and sending a summary when the window expires to prevent notification spam. System MUST implement both per-channel subscription authorization (check authorization when subscribing to notification channels) AND per-message validation (validate authorization for each notification before delivery) to handle access changes during active sessions.
- **FR-050**: System MUST use WireChat package (`wirechat/wirechat`) as the foundation for online chat capabilities, extending it through class inheritance and configuration rather than modifying vendor files
- **FR-051**: System MUST support one-on-one chat conversations between users within the same enterprise
- **FR-052**: System MUST support team chat conversations where messages are sent to all team members with appropriate access permissions
- **FR-053**: System MUST track read receipts for chat messages, indicating when messages have been read by recipients
- **FR-054**: System MUST maintain unread message counts per conversation, updating in real-time as messages are received and read via WebSocket connections
- **FR-055**: System MUST persist chat conversation history with messages, timestamps, and sender information, queryable for historical review. Retention period is tenant-configurable per enterprise (default 1 year), with automatic cleanup of messages older than the retention period.
- **FR-056**: System MUST deliver chat messages in real-time to online recipients without requiring page refresh using WebSocket connections (e.g., Laravel Echo, Pusher) for true real-time bidirectional communication, with automatic reconnection (exponential backoff) and polling fallback (e.g., every 5-10 seconds) when WebSocket unavailable. System MUST implement both per-channel subscription authorization (check authorization when subscribing to chat channels) AND per-message validation (validate authorization for each message before delivery) to handle access changes during active sessions.
- **FR-057**: System MUST prevent chat messages from being sent to users or teams the sender no longer has access to, rejecting with appropriate error messages
- **FR-057.5**: System MUST enforce a tenant-configurable character limit for chat messages (default 10,000 characters) that enterprises can adjust, with a system maximum (e.g., 50,000 characters) to prevent abuse and manage storage, rejecting messages that exceed the limit with a clear validation error
- **FR-058**: System MUST provide a `bio` attribute (longText, nullable, single-language, not translatable) for User model containing biography in markdown format
- **FR-059**: System MUST provide a `bio` attribute (longText, nullable, single-language, not translatable) for Team model containing biography in markdown format
- **FR-060**: System MUST sanitize bio content using `stevebauman/purify` before storage and rendering to prevent XSS attacks while preserving valid markdown and HTML elements (bold, italic, links, lists, code blocks)
- **FR-061**: System MUST parse and render bio markdown content to HTML on the frontend using `spatie/laravel-markdown` with server-side parsing disabled for code highlighting
- **FR-062**: System MUST apply client-side syntax highlighting to code blocks in bio content using `highlight.js` with Catppuccin Mocha theme, triggered on initial page load and Livewire navigation events
- **FR-063**: System MUST integrate bio editing capability into Filament admin panel using `MarkdownEditor` component for both User and Team resources
- **FR-063.5**: System MUST enforce role-based bio editing permissions: Users can edit their own bio; Team Executives can edit their assigned team's bio; Enterprise/Organisation Admins can edit any bio within their scope (user bio or team bio for teams they manage)
- **FR-064**: System MUST display bio content on user and team profile pages using unified Catppuccin Mocha theming with Tailwind Typography (prose) classes for consistent styling
- **FR-065**: System MUST handle empty/null bio gracefully, displaying appropriate placeholder message when no bio is available
- **FR-066**: System MUST enforce a tenant-configurable soft limit for bio content length (default 10,000 characters) with warning message when exceeded, allowing users to continue editing
- **FR-067**: System MUST enforce a hard limit for bio content length (e.g., 50,000 characters) with validation error preventing save when exceeded
- **FR-068**: System MUST handle WebSocket connection failures and service unavailability by automatically attempting reconnection with exponential backoff, falling back to polling (e.g., every 5-10 seconds) for real-time updates when WebSocket unavailable, displaying connection status indicator to users, and seamlessly transitioning back to WebSocket when connection is restored

### Key Entities *(include if feature involves data)*

- **User**: Represents an authenticated person who may belong to one or more enterprises, act as executive or deputy for teams, and maintain a current working context. Key attributes: ULID (route identifier), translatable name/slug, state (Draft/Pending/Active/Suspended/Archived), status (Online/Offline/Away/Busy), tenant relationship, context (current organisation/division/department), presence status, last seen timestamp, presence history, bio (longText, markdown format, single-language, not translatable).
- **Enterprise**: Represents the root tenant for a business customer, owning a complete team hierarchy and domains. Key attributes: ULID, translatable name/description/slug, type discriminator, executive/deputy assignments, tenant relationship (self-referential), bio (longText, markdown format, single-language, not translatable).
- **Organisation**: Represents a major organisational unit within an enterprise (e.g., subsidiary or business unit) that sits directly under the enterprise. Key attributes: ULID, translatable name/description/slug, parent (Enterprise), executive/deputy, hierarchical slug, bio (longText, markdown format, single-language, not translatable).
- **Division**: Represents a functional grouping within an organisation (e.g., sales, engineering) that manages departments. Key attributes: ULID, translatable name/description/slug, parent (Organisation), executive/deputy, hierarchical slug, bio (longText, markdown format, single-language, not translatable).
- **Department**: Represents a more granular unit within a division that groups people and projects. Key attributes: ULID, translatable name/description/slug, parent (Division), executive/deputy, hierarchical slug, bio (longText, markdown format, single-language, not translatable).
- **Project**: Represents a non-root team that can be nested under any other team type and used to organise work. Key attributes: ULID, translatable name/description/slug, parent (any team type), executive/deputy, hierarchical slug, bio (longText, markdown format, single-language, not translatable).
- **Domain**: Represents a domain or subdomain associated with an enterprise for tenant identification and routing. Key attributes: domain name, enterprise relationship, primary flag, verification status.
- **User-Enterprise Pivot**: Many-to-many relationship table linking users to enterprises they belong to, with default enterprise flag.
- **User-Organisation Access Pivot**: Many-to-many relationship table controlling which organisations a user can access for context switching.
- **Presence History**: Historical record of user presence status changes with timestamps, queryable for presence analytics and history review.
- **Follow Relationship (User-User)**: Many-to-many relationship table linking users who follow other users, with timestamps and metadata.
- **Follow Relationship (User-Team)**: Many-to-many relationship table linking users who follow teams, with timestamps and metadata, subject to team access permissions.
- **Chat Conversation**: Represents a chat conversation between users or involving teams, using WireChat's polymorphic conversation structure. Key attributes: conversation type (user-to-user, team), participants, unread counts, last message timestamp.
- **Chat Message**: Represents an individual chat message within a conversation, using WireChat's message structure. Key attributes: message content, sender, timestamp, read receipts, conversation relationship.

## Success Criteria *(mandatory)*

<!--
  ACTION REQUIRED: Define measurable success criteria.
  These must be technology-agnostic and measurable.
-->

### Measurable Outcomes

- **SC-001**: System MUST support up to 100 enterprises with complete team hierarchies and user management
- **SC-002**: System MUST support up to 10,000 teams per enterprise without performance degradation
- **SC-003**: System MUST support up to 1,000 users per enterprise with acceptable response times
- **SC-004**: System MUST return list queries (teams, users) within 500ms for 95% of requests under normal load
- **SC-005**: System MUST return single entity operations (fetch team by ULID, fetch user by ULID) within 200ms for 95% of requests
- **SC-006**: System MUST support horizontal scaling to accommodate growth beyond initial targets
- **SC-007**: System MUST maintain acceptable performance (response times within targets) when operating at 80% of maximum scale targets
- **SC-008**: System MUST update presence status changes within 2 seconds for 95% of status updates under normal load
- **SC-009**: System MUST deliver chat messages to online recipients within 1 second for 95% of messages under normal load
- **SC-010**: System MUST support up to 100 concurrent chat conversations per enterprise without performance degradation
- **SC-011**: System MUST render bio markdown content to HTML within 100ms for 95% of bio display requests under normal load
- **SC-012**: System MUST apply syntax highlighting to code blocks in bio content within 500ms after page load or Livewire navigation for 95% of requests
