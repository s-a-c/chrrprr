# Using Named Classes in Livewire 4 Single-File Components

## Question

Can Livewire 4 SFCs use **named classes** instead of **anonymous classes**?

## Answer

**Yes, technically PHP allows it, but Livewire's component resolution system cannot find them automatically.**

### Technical Feasibility

PHP allows you to define named classes in Blade files:

```php
<?php
namespace App\Livewire\Pages\Teams;

use Livewire\Component;

class IndexComponent extends Component {
    public function getTeamsProperty() {
        return Team::query()->inContext()->get();
    }
}
?>

<div>
    @foreach($this->teams as $team)
        ...
    @endforeach
</div>
```

### The Problem

However, **Livewire's component resolution system cannot automatically discover named classes defined in Blade files**. Here's why:

1. **Component Discovery**: Livewire discovers components by:
   - Scanning `component_locations` directories for files
   - Mapping file paths to component names (e.g., `pages/teams/index.blade.php` → `pages::teams.index`)
   - For SFCs, it expects an **anonymous class** (`new class extends Component`)

2. **Anonymous Class Pattern**: Livewire's SFC compiler specifically looks for the pattern:
   ```php
   new class extends Component { ... }
   ```
   This pattern triggers Livewire's component lifecycle and booting.

3. **Named Class Limitation**: When you define a named class in a Blade file:
   - The class is defined when the view is compiled
   - But Livewire doesn't know to look for it
   - `app('livewire')->exists('pages::teams.index')` will return `false`
   - `app('livewire')->new('pages::teams.index')` will fail

### Workaround: Manual Registration

You could manually register named classes, but this defeats the purpose of SFCs:

```php
// In a service provider
Livewire::component('pages::teams.index', \App\Livewire\Pages\Teams\IndexComponent::class);
```

This requires:
- Extracting the class to a separate file (defeats SFC purpose)
- Or manually registering each component (maintenance burden)

### Recommended Approach

**Stick with anonymous classes** and solve the booting problem instead:

1. **Option 1**: Extract to separate class files (Option 3 from remediation doc - **85% score**)
   - Create `app/Livewire/Pages/Teams/Index.php`
   - Use standard Livewire component pattern
   - Livewire can resolve by class name

2. **Option 2**: Fix the anonymous class booting (Option 1 from remediation doc - **75% score**)
   - Implement component class evaluation in `FolioServiceProvider`
   - Properly boot anonymous components
   - Maintains SFC pattern

### Why Anonymous Classes Are Used

Livewire 4 SFCs use anonymous classes because:

1. **View-First Philosophy**: The view file is the primary artifact
2. **Automatic Discovery**: Livewire automatically discovers and processes them
3. **No Manual Registration**: No need to register components manually
4. **Single File**: Everything in one place (class + view)

### Conclusion

While PHP technically allows named classes in Blade files, **Livewire's architecture doesn't support discovering them automatically**. The recommended solutions are:

- **Best**: Extract to named component classes in `app/Livewire/` (Option 3 - 85%)
- **Alternative**: Fix anonymous class booting (Option 1 - 75%)
- **Not Recommended**: Use named classes in Blade files (requires manual registration, defeats SFC purpose)

---

**See Also**: [Issue Analysis & Remediation](./issue-analysis-remediation.md)
