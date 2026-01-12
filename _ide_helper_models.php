<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * @property int $id
 * @property string $ulid
 * @property array<array-key, mixed> $name
 * @property array<array-key, mixed> $slug
 * @property \App\Enums\TeamType $type
 * @property int|null $parent_id
 * @property \App\States\Team\TeamState|null $state
 * @property \App\Enums\TeamStatus|null $status
 * @property string|null $tenant_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property array<array-key, mixed>|null $bio
 * @property int $lock_version
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Team> $children
 * @property-read int|null $children_count
 * @property-read string|null $bio_html
 * @property-read \App\Models\Team|null $parent
 * @property-read \App\Models\Enterprise|null $tenant
 * @property-read mixed $translations
 * @method static \Database\Factories\DepartmentFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department findSimilarSlugs(string $attribute, array $config, string $slug)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department inContext()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department orWhereNotState(string $column, $states)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department orWhereState(string $column, $states)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereBio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereJsonContainsLocale(string $column, string $locale, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereJsonContainsLocales(string $column, array $locales, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereLocale(string $column, string $locale)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereLocales(string $column, array $locales)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereLockVersion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereNotState(string $column, $states)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereTenantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereUlid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department withUniqueSlugConstraints(\Illuminate\Database\Eloquent\Model $model, string $attribute, array $config, string $slug)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department withoutContextScope()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department withoutTrashed()
 */
	final class Department extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $ulid
 * @property array<array-key, mixed> $name
 * @property array<array-key, mixed> $slug
 * @property \App\Enums\TeamType $type
 * @property int|null $parent_id
 * @property \App\States\Team\TeamState|null $state
 * @property \App\Enums\TeamStatus|null $status
 * @property string|null $tenant_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property array<array-key, mixed>|null $bio
 * @property int $lock_version
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Team> $children
 * @property-read int|null $children_count
 * @property-read string|null $bio_html
 * @property-read \App\Models\Team|null $parent
 * @property-read \App\Models\Enterprise|null $tenant
 * @property-read mixed $translations
 * @method static \Database\Factories\DivisionFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Division findSimilarSlugs(string $attribute, array $config, string $slug)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Division inContext()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Division newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Division newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Division onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Division orWhereNotState(string $column, $states)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Division orWhereState(string $column, $states)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Division query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Division whereBio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Division whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Division whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Division whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Division whereJsonContainsLocale(string $column, string $locale, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Division whereJsonContainsLocales(string $column, array $locales, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Division whereLocale(string $column, string $locale)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Division whereLocales(string $column, array $locales)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Division whereLockVersion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Division whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Division whereNotState(string $column, $states)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Division whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Division whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Division whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Division whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Division whereTenantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Division whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Division whereUlid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Division whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Division withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Division withUniqueSlugConstraints(\Illuminate\Database\Eloquent\Model $model, string $attribute, array $config, string $slug)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Division withoutContextScope()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Division withoutTrashed()
 */
	final class Division extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $domain
 * @property string $tenant_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Stancl\Tenancy\Database\Models\Tenant $tenant
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Domain newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Domain newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Domain query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Domain whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Domain whereDomain($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Domain whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Domain whereTenantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Domain whereUpdatedAt($value)
 */
	final class Domain extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $ulid
 * @property array<array-key, mixed> $name
 * @property array<array-key, mixed> $slug
 * @property \App\Enums\TeamType $type
 * @property int|null $parent_id
 * @property \App\States\Team\TeamState|null $state
 * @property \App\Enums\TeamStatus|null $status
 * @property string|null $tenant_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property array<array-key, mixed>|null $bio
 * @property int $lock_version
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Team> $children
 * @property-read int|null $children_count
 * @property-read string|null $bio_html
 * @property-read \App\Models\Team|null $parent
 * @property-read Enterprise|null $tenant
 * @property-read mixed $translations
 * @method static \Database\Factories\EnterpriseFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Enterprise findSimilarSlugs(string $attribute, array $config, string $slug)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Enterprise inContext()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Enterprise newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Enterprise newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Enterprise onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Enterprise orWhereNotState(string $column, $states)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Enterprise orWhereState(string $column, $states)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Enterprise query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Enterprise whereBio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Enterprise whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Enterprise whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Enterprise whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Enterprise whereJsonContainsLocale(string $column, string $locale, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Enterprise whereJsonContainsLocales(string $column, array $locales, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Enterprise whereLocale(string $column, string $locale)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Enterprise whereLocales(string $column, array $locales)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Enterprise whereLockVersion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Enterprise whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Enterprise whereNotState(string $column, $states)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Enterprise whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Enterprise whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Enterprise whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Enterprise whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Enterprise whereTenantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Enterprise whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Enterprise whereUlid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Enterprise whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Enterprise withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Enterprise withUniqueSlugConstraints(\Illuminate\Database\Eloquent\Model $model, string $attribute, array $config, string $slug)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Enterprise withoutContextScope()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Enterprise withoutTrashed()
 */
	final class Enterprise extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $ulid
 * @property array<array-key, mixed> $name
 * @property array<array-key, mixed> $slug
 * @property \App\Enums\TeamType $type
 * @property int|null $parent_id
 * @property \App\States\Team\TeamState|null $state
 * @property \App\Enums\TeamStatus|null $status
 * @property string|null $tenant_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property array<array-key, mixed>|null $bio
 * @property int $lock_version
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Team> $children
 * @property-read int|null $children_count
 * @property-read string|null $bio_html
 * @property-read \App\Models\Team|null $parent
 * @property-read \App\Models\Enterprise|null $tenant
 * @property-read mixed $translations
 * @method static \Database\Factories\OrganisationFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organisation findSimilarSlugs(string $attribute, array $config, string $slug)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organisation inContext()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organisation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organisation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organisation onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organisation orWhereNotState(string $column, $states)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organisation orWhereState(string $column, $states)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organisation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organisation whereBio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organisation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organisation whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organisation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organisation whereJsonContainsLocale(string $column, string $locale, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organisation whereJsonContainsLocales(string $column, array $locales, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organisation whereLocale(string $column, string $locale)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organisation whereLocales(string $column, array $locales)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organisation whereLockVersion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organisation whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organisation whereNotState(string $column, $states)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organisation whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organisation whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organisation whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organisation whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organisation whereTenantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organisation whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organisation whereUlid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organisation whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organisation withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organisation withUniqueSlugConstraints(\Illuminate\Database\Eloquent\Model $model, string $attribute, array $config, string $slug)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organisation withoutContextScope()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organisation withoutTrashed()
 */
	final class Organisation extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $ulid
 * @property array<array-key, mixed> $name
 * @property array<array-key, mixed> $slug
 * @property \App\Enums\TeamType $type
 * @property int|null $parent_id
 * @property \App\States\Team\TeamState|null $state
 * @property \App\Enums\TeamStatus|null $status
 * @property string|null $tenant_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property array<array-key, mixed>|null $bio
 * @property int $lock_version
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Team> $children
 * @property-read int|null $children_count
 * @property-read string|null $bio_html
 * @property-read \App\Models\Team|null $parent
 * @property-read \App\Models\Enterprise|null $tenant
 * @property-read mixed $translations
 * @method static \Database\Factories\ProjectFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project findSimilarSlugs(string $attribute, array $config, string $slug)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project inContext()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project orWhereNotState(string $column, $states)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project orWhereState(string $column, $states)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereBio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereJsonContainsLocale(string $column, string $locale, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereJsonContainsLocales(string $column, array $locales, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereLocale(string $column, string $locale)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereLocales(string $column, array $locales)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereLockVersion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereNotState(string $column, $states)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereTenantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereUlid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project withUniqueSlugConstraints(\Illuminate\Database\Eloquent\Model $model, string $attribute, array $config, string $slug)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project withoutContextScope()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project withoutTrashed()
 */
	final class Project extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int|null $team_id
 * @property string $name
 * @property string $guard_name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property bool $is_key
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role permission($permissions, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereGuardName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereIsKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereTeamId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role withoutPermission($permissions)
 */
	final class Role extends \Eloquent {}
}

namespace App\Models{
/**
 * @property self|null $parent
 * @property TeamType $type
 * @property TeamState $state
 * @property TeamStatus $status
 * @property int<0, max> $lock_version
 * @property int $id
 * @property string $ulid
 * @property array<array-key, mixed> $name
 * @property array<array-key, mixed> $slug
 * @property int|null $parent_id
 * @property string|null $tenant_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property array<array-key, mixed>|null $bio
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Team> $children
 * @property-read int|null $children_count
 * @property-read string|null $bio_html
 * @property-read \App\Models\Enterprise|null $tenant
 * @property-read mixed $translations
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team findSimilarSlugs(string $attribute, array $config, string $slug)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team inContext()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team orWhereNotState(string $column, $states)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team orWhereState(string $column, $states)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team whereBio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team whereJsonContainsLocale(string $column, string $locale, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team whereJsonContainsLocales(string $column, array $locales, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team whereLocale(string $column, string $locale)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team whereLocales(string $column, array $locales)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team whereLockVersion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team whereNotState(string $column, $states)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team whereTenantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team whereUlid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team withUniqueSlugConstraints(\Illuminate\Database\Eloquent\Model $model, string $attribute, array $config, string $slug)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team withoutContextScope()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team withoutTrashed()
 */
	class Team extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property string|null $two_factor_confirmed_at
 * @property string|null $ulid
 * @property \App\Enums\UserState|null $state
 * @property \App\Enums\UserStatus|null $status
 * @property array<array-key, mixed>|null $bio
 * @property int|null $tenant_id
 * @property int|null $current_context_id
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Organisation> $accessibleOrganisations
 * @property-read int|null $accessible_organisations_count
 * @property-read \App\Models\Organisation|null $currentContext
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Role> $roles
 * @property-read int|null $roles_count
 * @property-read \App\Models\Enterprise|null $tenant
 * @property-read mixed $translations
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User permission($permissions, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User role($roles, $guard = null, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereBio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCurrentContextId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereJsonContainsLocale(string $column, string $locale, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereJsonContainsLocales(string $column, array $locales, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereLocale(string $column, string $locale)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereLocales(string $column, array $locales)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTenantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTwoFactorConfirmedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTwoFactorRecoveryCodes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTwoFactorSecret($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUlid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutPermission($permissions)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutRole($roles, $guard = null)
 */
	final class User extends \Eloquent {}
}

