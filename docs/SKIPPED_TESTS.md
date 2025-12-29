# Skipped Tests Documentation

This document tracks all skipped or incomplete tests in the codebase, including the reasons for skipping and references to the requirements/behaviors that should be demonstrated.

## Overview

Some tests are marked as skipped or incomplete due to technical limitations or pending implementation. This document ensures these gaps are tracked and can be addressed in the future.

---

## Team Component Tests

### 1. CreateTeamComponentTest::it('renders the create team page')

**Status:** Skipped
**Location:** `tests/Feature/Teams/CreateTeamComponentTest.php:15-19`

**Reason:**
Folio pages with anonymous Livewire components cannot be tested directly through HTTP routes in the test environment. Livewire's component resolution system cannot locate anonymous classes defined inline within Blade files.

**Technical Details:**
- Folio routes are file-based and require route registration in the test environment
- Livewire's `Livewire::test()` method expects component classes, not anonymous classes in Blade files
- The component path `pages::teams.create` or `pages.teams.create` cannot be resolved to the anonymous class

**Required Behavior:**
The test should verify that:
- The `/teams/create` route is accessible when authenticated
- The Livewire component `pages::teams.create` is rendered on the page
- The page displays the team creation form with appropriate fields

**Current Coverage:**
- ✅ Team creation functionality is tested through `CreateTeam` action tests
- ✅ Form validation is tested through `StoreTeamRequest` validation tests
- ✅ Component logic is tested indirectly through action integration tests

**Potential Solutions:**
1. Extract anonymous Livewire components to dedicated component classes
2. Configure Folio route discovery in test environment
3. Use browser testing (Pest v4) to test the full HTTP request/response cycle
4. Mock Folio route resolution in tests

**Related Files:**
- `resources/views/pages/teams/create.blade.php`
- `app/Actions/Teams/CreateTeam.php`
- `app/Http/Requests/StoreTeamRequest.php`

---

### 2. EditTeamComponentTest::it('renders the edit team page')

**Status:** Skipped
**Location:** `tests/Feature/Teams/EditTeamComponentTest.php:20-24`

**Reason:**
Same as above - Folio pages with anonymous Livewire components cannot be tested directly through HTTP routes in the test environment.

**Required Behavior:**
The test should verify that:
- The `/teams/{ulid}` route is accessible when authenticated
- The Livewire component `pages::teams.[ulid]` is rendered on the page
- The page displays the team edit form pre-populated with existing team data

**Current Coverage:**
- ✅ Team update functionality is tested through `UpdateTeam` action tests
- ✅ Form validation is tested through `UpdateTeamRequest` validation tests
- ✅ Data loading is tested through direct model queries

**Potential Solutions:**
Same as CreateTeamComponentTest above.

**Related Files:**
- `resources/views/pages/teams/[ulid].blade.php`
- `app/Actions/Teams/UpdateTeam.php`
- `app/Http/Requests/UpdateTeamRequest.php`

---

## Executive/Deputy Constraints Tests

### 3. ExecutiveDeputyConstraintsTest::replacement logic test

**Status:** Incomplete
**Location:** `tests/Feature/Teams/ExecutiveDeputyConstraintsTest.php:60`

**Reason:**
The test is marked as incomplete with the message "Replacement logic not yet tested". This suggests that the functionality for replacing executives/deputies may not be fully implemented or the test requirements are not yet clear.

**Required Behavior:**
The test should verify the behavior when replacing an executive or deputy:
- When a new executive is assigned, the previous executive should be removed
- When a new deputy is assigned, it should be added to the list (deputies can be multiple)
- Validation should prevent conflicts (e.g., executive cannot also be a deputy)
- Proper role assignment within team context

**Current Coverage:**
- ✅ Executive assignment is tested in `ManagesTeamRoles` trait
- ✅ Deputy assignment is tested in `ManagesTeamRoles` trait
- ✅ Conflict validation is tested in `validateExecutiveDeputyConstraints()`

**Potential Solutions:**
1. Review the replacement logic implementation in `ManagesTeamRoles::assignExecutive()`
2. Determine if replacement should be automatic or require explicit removal
3. Write comprehensive test cases for replacement scenarios

**Related Files:**
- `app/Models/Concerns/ManagesTeamRoles.php`
- `app/Models/Team.php`
- `tests/Feature/Teams/ExecutiveDeputyConstraintsTest.php`

---

## Test Coverage Summary

### What IS Tested

The following functionality is adequately covered through alternative test approaches:

1. **Team Creation**
   - ✅ `CreateTeam` action tests
   - ✅ `StoreTeamRequest` validation tests
   - ✅ Database persistence tests

2. **Team Updates**
   - ✅ `UpdateTeam` action tests
   - ✅ `UpdateTeamRequest` validation tests
   - ✅ Optimistic locking tests

3. **Component Logic**
   - ✅ Action integration tests verify component behavior
   - ✅ Form validation logic is tested separately

4. **Executive/Deputy Management**
   - ✅ Role assignment tests
   - ✅ Conflict validation tests
   - ✅ Permission checks

### What IS NOT Tested

1. **Full HTTP Request/Response Cycle**
   - Folio route resolution
   - Livewire component rendering in browser
   - Full page load and interaction

2. **Replacement Logic**
   - Automatic executive replacement
   - Deputy replacement scenarios

---

## Recommendations

### Short-term
1. Continue testing through actions and form requests (current approach)
2. Document the skipped tests clearly (this document)
3. Consider browser testing for critical user flows

### Medium-term
1. Extract anonymous Livewire components to dedicated classes if component testing becomes critical
2. Implement Folio route testing setup if needed for E2E testing
3. Complete the executive/deputy replacement logic test

### Long-term
1. Evaluate if browser testing (Pest v4) should be used for component tests
2. Consider if component extraction is worth the refactoring effort
3. Review if skipped tests represent actual gaps or acceptable trade-offs

---

## Maintenance

This document should be updated when:
- New tests are skipped or marked incomplete
- Skipped tests are re-enabled
- New solutions are implemented
- Requirements change

**Last Updated:** 2024-12-19
**Maintained By:** Development Team

