# Collections & Functional Programming Patterns Reference

This document serves as a quick reference guide for common collection patterns and functional programming idioms that can be applied throughout the codebase.

---

## Table of Contents

1. [Basic Transformations](#basic-transformations)
2. [Filtering & Searching](#filtering--searching)
3. [Aggregations](#aggregations)
4. [Recursive Operations](#recursive-operations)
5. [Conditional Operations](#conditional-operations)
6. [Array Manipulation](#array-manipulation)
7. [Error Handling](#error-handling)
8. [Performance Considerations](#performance-considerations)

---

## Basic Transformations

### Map: Transform Each Item

**Before:**
```php
$results = [];
foreach ($items as $item) {
    $results[] = $item->transform();
}
```

**After:**
```php
$results = collect($items)
    ->map(fn ($item) => $item->transform())
    ->all();
```

**With Keys:**
```php
$results = collect($items)
    ->mapWithKeys(fn ($item, $key) => [$key => $item->transform()])
    ->all();
```

---

### FlatMap: Transform and Flatten

**Before:**
```php
$results = [];
foreach ($teams as $team) {
    foreach ($team->members as $member) {
        $results[] = $member;
    }
}
```

**After:**
```php
$results = collect($teams)
    ->flatMap(fn ($team) => $team->members)
    ->all();
```

**With Transformation:**
```php
$results = collect($teams)
    ->flatMap(fn ($team) => $team->members->map(fn ($m) => $m->toArray()))
    ->all();
```

---

## Filtering & Searching

### Filter: Keep Matching Items

**Before:**
```php
$valid = [];
foreach ($items as $item) {
    if ($item->isValid()) {
        $valid[] = $item;
    }
}
```

**After:**
```php
$valid = collect($items)
    ->filter(fn ($item) => $item->isValid())
    ->values()
    ->all();
```

**Filter with Index:**
```php
$valid = collect($items)
    ->filter(fn ($item, $key) => $key % 2 === 0) // Keep even indices
    ->all();
```

---

### Reject: Remove Matching Items

**Before:**
```php
$active = [];
foreach ($users as $user) {
    if (!$user->isActive()) {
        continue;
    }
    $active[] = $user;
}
```

**After:**
```php
$active = collect($users)
    ->reject(fn ($user) => !$user->isActive())
    ->values()
    ->all();
```

---

### Contains: Check for Existence

**Before:**
```php
$found = false;
foreach ($items as $item) {
    if ($item->id === $targetId) {
        $found = true;
        break;
    }
}
```

**After:**
```php
$found = collect($items)->contains('id', $targetId);
// or with closure
$found = collect($items)->contains(fn ($item) => $item->id === $targetId);
```

---

### First: Get First Matching Item

**Before:**
```php
$found = null;
foreach ($items as $item) {
    if ($item->isValid()) {
        $found = $item;
        break;
    }
}
```

**After:**
```php
$found = collect($items)->first(fn ($item) => $item->isValid());
```

**With Default:**
```php
$found = collect($items)->first(fn ($item) => $item->isValid(), $default);
```

---

## Aggregations

### Sum: Calculate Total

**Before:**
```php
$total = 0;
foreach ($items as $item) {
    $total += $item->price;
}
```

**After:**
```php
$total = collect($items)->sum('price');
// or with closure
$total = collect($items)->sum(fn ($item) => $item->price);
```

**Recursive Sum:**
```php
// Count descendants recursively
$count = $children->sum(fn ($child) => 1 + $this->getDescendantCount($child));
```

---

### Count: Count Items

**Before:**
```php
$count = 0;
foreach ($items as $item) {
    if ($item->isActive()) {
        $count++;
    }
}
```

**After:**
```php
$count = collect($items)->filter(fn ($item) => $item->isActive())->count();
```

---

### Average: Calculate Mean

**Before:**
```php
$total = 0;
$count = 0;
foreach ($items as $item) {
    $total += $item->score;
    $count++;
}
$average = $count > 0 ? $total / $count : 0;
```

**After:**
```php
$average = collect($items)->avg('score') ?? 0;
```

---

### Min/Max: Find Extremes

**Before:**
```php
$max = null;
foreach ($items as $item) {
    if ($max === null || $item->value > $max) {
        $max = $item->value;
    }
}
```

**After:**
```php
$max = collect($items)->max('value');
$min = collect($items)->min('value');
```

---

### GroupBy: Group Items

**Before:**
```php
$grouped = [];
foreach ($items as $item) {
    $key = $item->category;
    if (!isset($grouped[$key])) {
        $grouped[$key] = [];
    }
    $grouped[$key][] = $item;
}
```

**After:**
```php
$grouped = collect($items)
    ->groupBy('category')
    ->toArray();
```

**With Closure:**
```php
$grouped = collect($items)
    ->groupBy(fn ($item) => $item->category . '-' . $item->status)
    ->toArray();
```

---

### Partition: Split into Two Groups

**Before:**
```php
$successes = [];
$failures = [];
foreach ($results as $result) {
    if ($result['success']) {
        $successes[] = $result;
    } else {
        $failures[] = $result;
    }
}
```

**After:**
```php
[$successes, $failures] = collect($results)->partition(
    fn ($result) => $result['success'] ?? false
);
```

---

## Recursive Operations

### Recursive Counting

**Before:**
```php
private function getDescendantCount(Team $team): int
{
    $count = 0;
    $children = $team->children()->get();

    foreach ($children as $child) {
        $count++;
        $count += $this->getDescendantCount($child);
    }

    return $count;
}
```

**After:**
```php
private function getDescendantCount(Team $team): int
{
    return $team->children()
        ->get()
        ->sum(fn (Team $child): int =>
            1 + $this->getDescendantCount($child)
        );
}
```

---

### Recursive Collection Building

**Before:**
```php
private function getAncestry(?int $parentId): array
{
    if (!$parentId) {
        return [];
    }

    $parent = Team::find($parentId);
    if (!$parent) {
        return [];
    }

    $ancestors = $this->getAncestry($parent->parent_id);
    array_unshift($ancestors, $parent);

    return $ancestors;
}
```

**After:**
```php
private function getAncestry(?int $parentId): Collection
{
    if (!$parentId) {
        return collect();
    }

    $parent = Team::find($parentId);
    if (!$parent) {
        return collect();
    }

    return $this->getAncestry($parent->parent_id)
        ->prepend($parent);
}
```

---

### Recursive Flattening

**Before:**
```php
private function getAllDescendants(Team $team): array
{
    $descendants = [];
    $children = $team->children()->get();

    foreach ($children as $child) {
        $descendants[] = $child;
        $descendants = array_merge($descendants, $this->getAllDescendants($child));
    }

    return $descendants;
}
```

**After:**
```php
private function getAllDescendants(Team $team): Collection
{
    $children = $team->children()->get();

    if ($children->isEmpty()) {
        return collect();
    }

    return $children->merge(
        $children->flatMap(fn ($child) => $this->getAllDescendants($child))
    );
}
```

---

## Conditional Operations

### When: Conditional Operations

**Before:**
```php
$result = [];
if ($condition) {
    $result = array_merge($result, $additionalItems);
}
```

**After:**
```php
$result = collect()
    ->when($condition, fn ($c) => $c->merge($additionalItems))
    ->all();
```

**With Else:**
```php
$result = collect()
    ->when($condition,
        fn ($c) => $c->merge($itemsA),
        fn ($c) => $c->merge($itemsB)
    )
    ->all();
```

---

### Unless: Inverted Conditional

**Before:**
```php
$result = [];
if (!$condition) {
    $result = array_merge($result, $items);
}
```

**After:**
```php
$result = collect()
    ->unless($condition, fn ($c) => $c->merge($items))
    ->all();
```

---

### Tap: Side Effects

**Before:**
```php
$collection = collect($items);
$collection->filter(...);
log($collection->count());
return $collection;
```

**After:**
```php
return collect($items)
    ->filter(...)
    ->tap(fn ($c) => log($c->count()));
```

---

## Array Manipulation

### Merge: Combine Arrays

**Before:**
```php
$result = [];
$result = array_merge($result, $array1);
$result = array_merge($result, $array2);
$result = array_unique($result);
```

**After:**
```php
$result = collect($array1)
    ->merge($array2)
    ->unique()
    ->values()
    ->all();
```

---

### Concat: Append Arrays

**Before:**
```php
$result = $array1;
foreach ($array2 as $item) {
    $result[] = $item;
}
```

**After:**
```php
$result = collect($array1)
    ->concat($array2)
    ->all();
```

---

### Unique: Remove Duplicates

**Before:**
```php
$unique = array_unique($items, SORT_REGULAR);
```

**After:**
```php
$unique = collect($items)
    ->unique()
    ->values()
    ->all();
```

**By Key:**
```php
$unique = collect($items)
    ->unique('id')
    ->values()
    ->all();
```

**By Closure:**
```php
$unique = collect($items)
    ->unique(fn ($item) => $item->category . $item->status)
    ->values()
    ->all();
```

---

### Sort: Order Items

**Before:**
```php
usort($items, fn ($a, $b) => $a->name <=> $b->name);
```

**After:**
```php
$sorted = collect($items)
    ->sortBy('name')
    ->values()
    ->all();
```

**Descending:**
```php
$sorted = collect($items)
    ->sortByDesc('name')
    ->values()
    ->all();
```

**By Closure:**
```php
$sorted = collect($items)
    ->sortBy(fn ($item) => $item->priority * 100 + $item->score)
    ->values()
    ->all();
```

---

### Chunk: Split into Groups

**Before:**
```php
$chunks = [];
$chunk = [];
foreach ($items as $item) {
    $chunk[] = $item;
    if (count($chunk) === $size) {
        $chunks[] = $chunk;
        $chunk = [];
    }
}
if (!empty($chunk)) {
    $chunks[] = $chunk;
}
```

**After:**
```php
$chunks = collect($items)
    ->chunk($size)
    ->map(fn ($chunk) => $chunk->values()->all())
    ->all();
```

---

## Error Handling

### Safe Mapping with Exceptions

**Before:**
```php
$results = [];
foreach ($items as $item) {
    try {
        $results[] = $this->process($item);
    } catch (Exception $e) {
        $results[] = ['error' => $e->getMessage()];
    }
}
```

**After:**
```php
$results = collect($items)
    ->map(function ($item) {
        try {
            return $this->process($item);
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    })
    ->all();
```

**With Helper Method:**
```php
private function processSafely($item): array
{
    try {
        return $this->process($item);
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

$results = collect($items)
    ->map(fn ($item) => $this->processSafely($item))
    ->all();
```

---

### Filter Out Failures

**Before:**
```php
$valid = [];
foreach ($items as $item) {
    try {
        $this->validate($item);
        $valid[] = $item;
    } catch (Exception $e) {
        // Skip invalid items
    }
}
```

**After:**
```php
$valid = collect($items)
    ->filter(function ($item) {
        try {
            $this->validate($item);
            return true;
        } catch (Exception $e) {
            return false;
        }
    })
    ->values()
    ->all();
```

---

## Performance Considerations

### Lazy Evaluation

Collections are evaluated lazily when possible, but be aware:

```php
// This executes the query immediately
$teams = Team::all();

// This is still a query builder (lazy)
$teams = Team::query()->get();

// This executes and creates collection
$teams = collect(Team::all());
```

### Eager Loading

Always use eager loading to prevent N+1 queries:

```php
// Bad: N+1 queries
$teams = Team::all();
foreach ($teams as $team) {
    $team->children; // Query for each team
}

// Good: Eager loading
$teams = Team::with('children')->get();
$teams->flatMap(fn ($team) => $team->children); // No additional queries
```

### Large Collections

For very large collections (> 10,000 items), consider:

1. **Chunking**: Process in batches
```php
Team::chunk(1000, function ($teams) {
    collect($teams)->each(fn ($team) => $this->process($team));
});
```

2. **Lazy Collections**: Use `LazyCollection` for memory efficiency
```php
Team::cursor()->chunk(1000)->each(function ($teams) {
    collect($teams)->each(fn ($team) => $this->process($team));
});
```

3. **Database Aggregations**: Use database functions when possible
```php
// Instead of
$total = collect($items)->sum('price');

// Use (if all items are from database)
$total = Item::sum('price');
```

---

## Common Anti-Patterns to Avoid

### ❌ Don't: Unnecessary Collection Wrapping

```php
// Bad: Unnecessary wrapping
$count = collect([$item])->count(); // Just use: count([$item]) or 1

// Good: Only wrap when needed
$count = collect($items)->count();
```

### ❌ Don't: Converting Back to Array Unnecessarily

```php
// Bad: Converting to array when collection is fine
$result = collect($items)->filter(...)->map(...)->all();
return $result; // If you're just returning, keep as collection

// Good: Return collection if that's what you need
return collect($items)->filter(...)->map(...);
```

### ❌ Don't: Over-Complicating Simple Operations

```php
// Bad: Over-engineered
$first = collect($items)->filter(fn ($i) => $i->id === $id)->first();

// Good: Simple and clear
$first = collect($items)->first(fn ($i) => $i->id === $id);
```

### ❌ Don't: Ignoring Database Capabilities

```php
// Bad: Loading all records to filter
$active = collect(User::all())->filter(fn ($u) => $u->isActive());

// Good: Use database query
$active = User::where('active', true)->get();
```

---

## Quick Reference Cheat Sheet

| Operation | Collection Method | Array Equivalent |
|-----------|------------------|-------------------|
| Transform each | `map()` | `array_map()` |
| Filter items | `filter()` | `array_filter()` |
| Find first match | `first()` | Loop with break |
| Check existence | `contains()` | `in_array()` |
| Count items | `count()` | `count()` |
| Sum values | `sum()` | Loop with += |
| Average | `avg()` | Loop with calculation |
| Group by key | `groupBy()` | Manual grouping |
| Split into two | `partition()` | Manual splitting |
| Merge arrays | `merge()` | `array_merge()` |
| Remove duplicates | `unique()` | `array_unique()` |
| Sort | `sortBy()` | `usort()` |
| Chunk | `chunk()` | Manual chunking |
| Flatten | `flatten()` | Manual flattening |

---

**Document Version**: 1.0
**Last Updated**: 2025-01-27
