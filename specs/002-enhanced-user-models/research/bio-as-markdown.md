# Markdown Bio with Livewire and Filament

---

<details><summary>Table of Contents</summary>

## Table of Contents

- [Markdown Bio with Livewire and Filament](#markdown-bio-with-livewire-and-filament)
  - [Table of Contents](#table-of-contents)
  - [1. Simple Example](#1-simple-example)
    - [1.1. Installation](#11-installation)
    - [1.2. Usage](#12-usage)
    - [1.3. Livewire](#13-livewire)
    - [1.4. Filament](#14-filament)
  - [2. Prompt](#2-prompt)
  - [3. Response](#3-response)
    - [3.1. **1. Architecture \& Reasoning**](#31-1-architecture--reasoning)
    - [3.2. **2. Installation (Bun \& Composer)**](#32-2-installation-bun--composer)
    - [3.3. **3. Configuration**](#33-3-configuration)
      - [3.3.1. **A. Tailwind CSS v4 (`resources/css/app.css`)**](#331-a-tailwind-css-v4-resourcescssappcss)
      - [3.3.2. **B. Client-Side Highlighting (`resources/js/app.js`)**](#332-b-client-side-highlighting-resourcesjsappjs)
      - [3.3.3. **C. Spatie Markdown Config**](#333-c-spatie-markdown-config)
    - [3.4. **4. Backend Implementation**](#34-4-backend-implementation)
      - [3.4.1. **A. Database Migration**](#341-a-database-migration)
      - [3.4.2. **B. Filament v5 Admin Resource**](#342-b-filament-v5-admin-resource)
    - [3.5. **5. Frontend Implementation (Folio + Livewire SFC)**](#35-5-frontend-implementation-folio--livewire-sfc)
    - [3.6. **6. "Gotchas" \& Areas for Vigilance**](#36-6-gotchas--areas-for-vigilance)
      - [3.6.1. **⚠️ 1. Security (XSS)**](#361-️-1-security-xss)
      - [3.6.2. **⚠️ 2. Tailwind 4 Plugin Loading**](#362-️-2-tailwind-4-plugin-loading)
      - [3.6.3. **⚠️ 3. Livewire 4 SPA Navigation**](#363-️-3-livewire-4-spa-navigation)
      - [3.6.4. **⚠️ 4. Spatie vs. Client Highlighting**](#364-️-4-spatie-vs-client-highlighting)
      - [3.6.5. **⚠️ 5. Filament Assets**](#365-️-5-filament-assets)
    - [**1. The Stack \& Strategy**](#1-the-stack--strategy)
    - [**2. Installation**](#2-installation)
    - [**3. Database \& Admin Setup**](#3-database--admin-setup)
    - [**4. Unified Theming (The Critical Step)**](#4-unified-theming-the-critical-step)
      - [**A. Frontend CSS (Flux + Bio)**](#a-frontend-css-flux--bio)
      - [**B. Admin CSS (Filament)**](#b-admin-css-filament)
    - [**5. Component Logic (Livewire SFC + Folio)**](#5-component-logic-livewire-sfc--folio)
    - [**6. Client-Side Highlighting Script**](#6-client-side-highlighting-script)
    - [**7. Configuration \& Build Checklist**](#7-configuration--build-checklist)
    - [**Summary of "The Fix"**](#summary-of-the-fix)
    - [**1. Database Migration**](#1-database-migration)
    - [**2. Filament Admin (Team Resource)**](#2-filament-admin-team-resource)
    - [**3. Frontend Page (Folio + Livewire SFC)**](#3-frontend-page-folio--livewire-sfc)
    - [**Why this "Just Works"**](#why-this-just-works)

</details>

---

## 1. Simple Example

This is a simple example of how to use Markdown in a Laravel application with Livewire and Filament.

### 1.1. Installation

```bash
composer require spatie/laravel-markdown

```

### 1.2. Usage

```php
use Spatie\LaravelMarkdown\MarkdownRenderer;

$markdown = new MarkdownRenderer();

echo $markdown->toHtml('# Hello World');

```

### 1.3. Livewire

```php
use Livewire\Component;
use Spatie\LaravelMarkdown\MarkdownRenderer;

class Bio extends Component
{
    public $bio;

    public function render()
    {
        $markdown = new MarkdownRenderer();

        return view('livewire.bio', [
            'bio' => $markdown->toHtml($this->bio),
        ]);
    }
}

```

### 1.4. Filament

```php
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BioResource extends Resource
{
    public static function form(Form $form): Form
    {

        return $form
                ->schema([
                    MarkdownEditor::make('bio')
                        ->required()
                        ->columnSpanFull(),
            ;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('bio')
                    ->markdown()
            ]);
    }
}

```

---

## 2. Prompt

if i want to add a "bio" column to my team and user models and allow for the column to contain markdown - what packages/tools are available to achieve this - using livewire v4 as frontend and filament v5 as admin panel
so, given:

- php 8.5
- laravel 12
- laravel/folio
- livewire 4 (w/ sfc)
- livewire/flux-pro
- filament 5
- tailwindcss 4
- spatie/laravel-markdown
- stevebauman/purify
- bun
- @catppuccin/tailwindcss
- @catppuccin/highlightjs
- @tailwindcss/typography
- highlight.js

please pull it all together into a coherent document, including reasoning and all installation, configuration, and implementation steps
highlight "gotchas" and areas requiring extra vigilance
i notice , we have not accomodated livewire/flux in our theming of the frontend with catppuccin
i believe flux is `zinc`-based as opposed to filament being `grey`-based

please pull it altogether in a single, thorough, complete implementation guide

## 3. Response

This document outlines the implementation of a **User Bio** feature with Markdown support, Syntax Highlighting, and Catppuccin Mocha theming.

It is tailored for a **Bleeding Edge** stack: **Laravel 12**, **Livewire 4**, **Filament 5**, **Tailwind 4**, and **Bun**.

---

### 3.1. **1. Architecture & Reasoning**

- **Storage:** `longText` is used to prevent truncation of lengthy Markdown bios.
- **Parsing:** `spatie/laravel-markdown` is used for robust, strictly typed Markdown-to-HTML conversion on the server.
- **Sanitization:** User input **must** be sanitized to prevent XSS. We will use `stevebauman/purify` before parsing or rely on strict CommonMark settings.
- **Highlighting:** We offload syntax highlighting to the **Client** (`highlight.js`) to avoid server-side Node.js dependencies (Shiki) and leverage Bun's frontend asset management.
- **Theming:** We achieve a "Unified UI" by applying **Catppuccin Mocha** to the Tailwind config (UI), the Typography plugin (Prose), and the Highlight.js styles (Code).
- **Routing:** **Laravel Folio** handles the route; **Livewire 4 Native SFC** handles the logic/view.

---

### 3.2. **2. Installation (Bun & Composer)**

Run these commands to install the required packages.

**Backend (Composer)**

```bash
# Markdown parser and HTML Sanitizer (Critical for user content)
composer require spatie/laravel-markdown stevebauman/purify

```

**Frontend (Bun)**

```bash
# Tailwind Plugins and Highlight.js assets
bun add -D @tailwindcss/typography @catppuccin/tailwindcss

# Highlighter and Theme
bun add highlight.js @catppuccin/highlightjs

```

---

### 3.3. **3. Configuration**

#### 3.3.1. **A. Tailwind CSS v4 (`resources/css/app.css`)**

In Tailwind 4, configuration moves from `tailwind.config.js` to CSS directives.

```css
@import "tailwindcss";

/* 1. Typography Plugin (Prose) */
@plugin "@tailwindcss/typography";

/* 2. Catppuccin Theme Plugin
   - prefix: 'ctp' prevents class collisions
   - defaultFlavour: 'mocha' aligns with your preference
*/
@plugin "@catppuccin/tailwindcss" {
    prefix: "ctp",
    defaultFlavour: "mocha"
};

/* 3. Highlight.js Theme
   - Imports the specific Mocha CSS for code blocks
*/
@import "@catppuccin/highlightjs/css/catppuccin-mocha.css";

/* 4. Global Overrides to glue it all together */
:root {
    @apply bg-ctp-base text-ctp-text;
}

/* Override Highlight.js background to match Catppuccin Base seamlessly */
.hljs {
    background-color: transparent !important; /* Let the container handle bg */
    padding: 0;
}

```

#### 3.3.2. **B. Client-Side Highlighting (`resources/js/app.js`)**

Livewire 4 acts as a Single Page Application (SPA). We must trigger highlighting on initial load **and** subsequent navigations.

```javascript
import hljs from 'highlight.js';

// Define the highlighter logic
const bootHighlighter = () => {
    document.querySelectorAll('pre code').forEach((el) => {
        hljs.highlightElement(el);
    });
};

// 1. Initial Load
document.addEventListener('DOMContentLoaded', bootHighlighter);

// 2. Livewire v4 Navigation (SPA Mode)
document.addEventListener('livewire:navigated', bootHighlighter);

```

#### 3.3.3. **C. Spatie Markdown Config**

Publish the config to ensure server-side highlighting is **disabled** (since we are doing it on the client).

```bash
php artisan vendor:publish --tag="markdown-config"

```

- In `config/markdown.php`, ensure `'code_highlighting' => ['enabled' => false]`.

---

### 3.4. **4. Backend Implementation**

#### 3.4.1. **A. Database Migration**

```php
// database/migrations/xxxx_add_bio_to_users.php
public function up(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->longText('bio')->nullable(); // longText for safety
    });
}

```

#### 3.4.2. **B. Filament v5 Admin Resource**

```php
// app/Filament/Resources/UserResource.php
use Filament\Forms\Components\MarkdownEditor;

public static function form(Form $form): Form
{
    return $form
        ->schema([
            MarkdownEditor::make('bio')
                ->label('Biography')
                ->columnSpanFull()
                // Optional: Restrict toolbar to prevent overly complex HTML
                ->toolbarButtons([
                    'bold', 'italic', 'link', 'bulletList', 'orderedList', 'codeBlock', 'undo', 'redo'
                ]),
        ]);
}

```

---

### 3.5. **5. Frontend Implementation (Folio + Livewire SFC)**

This file combines routing, logic, and the view.

**File:** `resources/views/pages/team/[User].blade.php`

```blade
<?php

declare(strict_types=1);

use Livewire\Component;
use Livewire\Attributes\Computed;
use Illuminate\Support\Str;
use Stevebauman\Purify\Facades\Purify;
use App\Models\User;

new class extends Component {
    public User $user;

    #[Computed]
    public function htmlBio(): string
    {
        // 1. Get raw content
        $raw = $this->user->bio ?? '';

        // 2. Sanitize FIRST (Security)
        // Removes <script>, onclick, etc., but keeps <b>, <i>, <pre>
        $sanitized = Purify::clean($raw);

        // 3. We return sanitized string; the view's x-markdown will parse it.
        return $sanitized;
    }
};
?>

<x-layouts.app :title="$user->name">
    <div class="max-w-4xl mx-auto py-12 px-6">

        {{-- Flux Header --}}
        <div class="mb-8 border-b border-ctp-surface1 pb-6">
            <flux:heading size="xl" class="text-ctp-mauve font-bold">
                {{ $user->name }}
            </flux:heading>
            <flux:subheading class="text-ctp-subtext0">
                {{ $user->email }}
            </flux:subheading>
        </div>

        {{--
            Bio Display
            - prose-slate: Base typography scale
            - prose-invert: Dark mode structure (if needed)
            - Custom Overrides: Force Catppuccin colors on specific elements
        --}}
        <div class="prose max-w-none
            text-ctp-text
            prose-headings:text-ctp-peach
            prose-a:text-ctp-blue hover:prose-a:text-ctp-sapphire
            prose-strong:text-ctp-red
            prose-code:text-ctp-teal
            prose-pre:bg-ctp-mantle prose-pre:border prose-pre:border-ctp-surface0
        ">
            {{-- Spatie Component parses the Markdown --}}
            <x-markdown>
                {!! $this->htmlBio !!}
            </x-markdown>
        </div>

        @if(empty($user->bio))
            <div class="rounded-lg bg-ctp-surface0 p-4 text-ctp-overlay1 italic">
                No biography available.
            </div>
        @endif
    </div>
</x-layouts.app>

```

---

### 3.6. **6. "Gotchas" & Areas for Vigilance**

#### 3.6.1. **⚠️ 1. Security (XSS)**

- **Risk:** Markdown is a superset of HTML. A user can type `<script>alert('hack')</script>` into the Markdown editor.
- **Mitigation:** Do not rely solely on `x-markdown` or `Str::markdown`. You **must** run `Purify::clean()` on the input before outputting it. The example above demonstrates this.

#### 3.6.2. **⚠️ 2. Tailwind 4 Plugin Loading**

- **Gotcha:** If you try to use `tailwind.config.js` to load plugins while using `@import "tailwindcss"`, they might be ignored or cause conflicts.
- **Fix:** Strict adherence to the `@plugin` syntax in CSS.

#### 3.6.3. **⚠️ 3. Livewire 4 SPA Navigation**

- **Gotcha:** Code blocks look perfect on refresh, but lose highlighting when clicking a link to another team member.
- **Reason:** Livewire 4 swaps the HTML body but doesn't re-run `DOMContentLoaded`.
- **Fix:** You must listen to `livewire:navigated` in your JS to re-trigger `hljs.highlightElement`.

#### 3.6.4. **⚠️ 4. Spatie vs. Client Highlighting**

- **Gotcha:** `spatie/laravel-markdown` attempts to use Shiki (Node.js) by default for code blocks. If your server environment doesn't have Node, this will crash.
- **Fix:** Explicitly disable server-side highlighting in `config/markdown.php` so the raw `<pre><code>` tags are sent to the browser for `highlight.js` to handle.

#### 3.6.5. **⚠️ 5. Filament Assets**

- **Gotcha:** Filament v5 is native TW4, but it runs in a separate "Panel" context.
- **Vigilance:** Ensure your frontend `app.css` (Catppuccin) doesn't accidentally bleed into or override Filament's admin styles if you are loading them on the same page (unlikely, but possible in custom implementations). Keep Admin and Frontend layouts distinct.

---

This is the definitive implementation guide for your **Bleeding Edge Stack**.

It unifies **Laravel 12**, **Livewire 4**, **Filament 5**, **Flux Pro**, and **Tailwind 4** under a single **Catppuccin Mocha** aesthetic.

This guide specifically addresses the "Zinc vs. Gray" conflict in Flux Pro to ensure a seamless color palette across your entire application.

---

### **1. The Stack & Strategy**

* **Core:** PHP 8.5, Laravel 12, Bun.
* **Frontend:** Livewire 4 (SFC), Flux Pro (UI), Folio (Routing).
* **Backend:** Filament 5 (Admin).
* **Theming:** Catppuccin Mocha (Unified).
* **Flux Pro:** Uses `zinc` by default. We will remap `zinc` to Catppuccin.
* **Filament:** Uses `gray` by default. We will remap `gray` to Catppuccin.
* **Code:** Uses `highlight.js` with the Catppuccin Mocha theme.



---

### **2. Installation**

**A. Backend Dependencies (Composer)**

```bash
composer require spatie/laravel-markdown stevebauman/purify

```

**B. Frontend Dependencies (Bun)**

```bash
# Core Tailwind & Typography
bun add -D @tailwindcss/typography

# Catppuccin Integration
bun add -D @catppuccin/tailwindcss @catppuccin/highlightjs

# Highlighter (Client-side)
bun add highlight.js

```

---

### **3. Database & Admin Setup**

**A. Database Migration**
Ensure your users have storage for the Markdown bio.

```php
// database/migrations/xxxx_xx_xx_add_bio_to_users.php
public function up(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->longText('bio')->nullable();
    });
}

```

**B. Filament Resource (Admin Panel)**
Enable Markdown editing in your Admin Panel.

```php
// app/Filament/Resources/UserResource.php
use Filament\Forms\Components\MarkdownEditor;

public static function form(Form $form): Form
{
    return $form->schema([
        MarkdownEditor::make('bio')
            ->label('Biography')
            ->columnSpanFull()
            ->toolbarButtons(['bold', 'italic', 'link', 'bulletList', 'orderedList', 'codeBlock', 'undo', 'redo']),
    ]);
}

```

---

### **4. Unified Theming (The Critical Step)**

This is where we solve the **Flux (Zinc)** vs. **Filament (Gray)** vs. **Catppuccin** conflict. We will configure two separate CSS files: one for the Frontend (Flux) and one for the Admin (Filament).

#### **A. Frontend CSS (Flux + Bio)**

**File:** `resources/css/app.css`

Flux defaults to `zinc` for its grays. In Tailwind 4, we override `zinc` to map directly to Catppuccin's palette.

```css
@import "tailwindcss";

/* 1. Plugins */
@plugin "@tailwindcss/typography";
@plugin "@catppuccin/tailwindcss" {
    prefix: "ctp",
    defaultFlavour: "mocha"
};

/* 2. Highlight.js Theme (Matches Code Blocks to UI) */
@import "@catppuccin/highlightjs/css/catppuccin-mocha.css";

/* 3. Theme Configuration */
@theme {
    /* FLUX OVERRIDE:
       Flux Pro uses 'zinc' for its neutrals. We hijack 'zinc'
       and force it to use Catppuccin colors.
    */
    --color-zinc-50:  #f9e2af; /* Yellow (Warm highlight) */
    --color-zinc-100: #cdd6f4; /* Text */
    --color-zinc-200: #bac2de; /* Subtext 1 */
    --color-zinc-300: #a6adc8; /* Subtext 0 */
    --color-zinc-400: #9399b2; /* Overlay 2 */
    --color-zinc-500: #7f849c; /* Overlay 1 */
    --color-zinc-600: #6c7086; /* Overlay 0 */
    --color-zinc-700: #585b70; /* Surface 2 */
    --color-zinc-800: #45475a; /* Surface 1 */
    --color-zinc-900: #313244; /* Surface 0 */
    --color-zinc-950: #1e1e2e; /* Base (Background) */

    /* Optional: Map 'white' to Base if you want a fully dark mode feel */
    --color-white: #1e1e2e;
}

/* 4. Global Reset */
:root {
    @apply bg-ctp-base text-ctp-text;
}

/* 5. Highlight.js Tweaks */
.hljs {
    background-color: transparent !important;
    padding: 0;
}

```

#### **B. Admin CSS (Filament)**

**File:** `resources/css/filament/admin/theme.css`

Filament uses `gray`. We perform a similar override but targeting `gray` variables.

```css
@import "tailwindcss";
@import "../../../vendor/filament/filament/resources/css/theme.css";

@plugin "@catppuccin/tailwindcss" {
    prefix: "ctp",
    defaultFlavour: "mocha"
};

@theme {
    /* FILAMENT OVERRIDE: Map 'gray' to Catppuccin */
    --color-gray-50:  #f9e2af;
    --color-gray-100: #cdd6f4;
    --color-gray-200: #bac2de;
    --color-gray-300: #a6adc8;
    --color-gray-400: #9399b2;
    --color-gray-500: #7f849c;
    --color-gray-600: #6c7086;
    --color-gray-700: #585b70;
    --color-gray-800: #45475a;
    --color-gray-900: #313244;
    --color-gray-950: #1e1e2e; /* Critical: Admin Background */

    /* PRIMARY BRANDING: Map to Catppuccin Mauve */
    --color-primary-50:  #f5e0dc;
    --color-primary-600: #cba6f7; /* Buttons/Links */
    --color-primary-950: #11111b;
}

```

---

### **5. Component Logic (Livewire SFC + Folio)**

**File:** `resources/views/pages/team/[User].blade.php`

This file handles the route `team/{id}`, parses the bio securely, highlights code (via JS), and styles the text using our new unified theme.

```blade
<?php

use Livewire\Component;
use Livewire\Attributes\Computed;
use Illuminate\Support\Str;
use Stevebauman\Purify\Facades\Purify;
use App\Models\User;

new class extends Component {
    public User $user;

    #[Computed]
    public function htmlBio(): string
    {
        // 1. Sanitize the raw markdown first (Security)
        $clean = Purify::clean($this->user->bio ?? '');

        // 2. Return safe string (Parsed by x-markdown in view)
        return $clean;
    }
};
?>

{{--
    Flux Layout Wrapper
    Assumes <x-layouts.app> uses Flux components internally.
--}}
<x-layouts.app :title="$user->name">
    <flux:main class="container mx-auto py-12 max-w-4xl">

        {{-- Header --}}
        <div class="mb-8 border-b border-zinc-700 pb-6">
            <flux:heading level="1" size="xl" class="text-ctp-mauve">
                {{ $user->name }}
            </flux:heading>
            <flux:subheading class="text-zinc-400">
                {{ $user->email }}
            </flux:subheading>
        </div>

        {{--
           Bio Content
           - prose-zinc: Uses our remapped 'zinc' colors automatically.
           - prose-invert: Ensures proper contrast in this dark theme.
           - Custom Overrides: Fine-tune colors using ctp utilities.
        --}}
        <div class="prose prose-zinc prose-invert max-w-none
            prose-headings:text-ctp-peach
            prose-a:text-ctp-blue hover:prose-a:text-ctp-sapphire
            prose-code:text-ctp-teal
            prose-pre:bg-zinc-900 prose-pre:border prose-pre:border-zinc-700
        ">
            <x-markdown>
                {!! $this->htmlBio !!}
            </x-markdown>
        </div>

        @if(empty($user->bio))
            <flux:card class="bg-zinc-800 border-zinc-700 italic text-zinc-400">
                No biography available.
            </flux:card>
        @endif

    </flux:main>
</x-layouts.app>

```

---

### **6. Client-Side Highlighting Script**

**File:** `resources/js/app.js`

Since we disabled server-side highlighting, we use this script to highlight code blocks on the client, respecting Livewire 4's SPA navigation.

```javascript
import hljs from 'highlight.js';

// Boot Highlighter
const highlight = () => {
    document.querySelectorAll('pre code').forEach((el) => {
        // Only highlight if not already processed
        if (!el.dataset.highlighted) {
            hljs.highlightElement(el);
            // Mark as processed (Livewire v4 helps here, but good practice)
            el.dataset.highlighted = 'yes';
        }
    });
};

// 1. Initial Load
document.addEventListener('DOMContentLoaded', highlight);

// 2. Livewire Navigation (SPA Mode)
document.addEventListener('livewire:navigated', highlight);

```

---

### **7. Configuration & Build Checklist**

1. **Vite Config (`vite.config.js`):**
Ensure both CSS files are in the input array.
```javascript
input: [
    'resources/css/app.css',
    'resources/js/app.js',
    'resources/css/filament/admin/theme.css' // Admin Theme
],

```


2. **Admin Provider (`AdminPanelProvider.php`):**
Register the theme.
```php
->viteTheme('resources/css/filament/admin/theme.css')

```


3. **Markdown Config (`config/markdown.php`):**
Disable server highlighting.
```php
'code_highlighting' => ['enabled' => false],

```


4. **Run Build:**
```bash
bun run dev

```



### **Summary of "The Fix"**

The key to making Flux Pro, Filament, and Catppuccin play nicely together is the **`@theme` override in CSS**.

* We told **Flux** (which asks for `zinc-950`) that `zinc-950` is actually Catppuccin Base (`#1e1e2e`).
* We told **Filament** (which asks for `gray-950`) that `gray-950` is actually Catppuccin Base (`#1e1e2e`).

Now, your Bio page, your Flux UI components, and your Filament Admin Dashboard are visually indistinguishable parts of a single, cohesive system.

This is a straightforward extension of the work we just did. Since the global configuration (CSS, Purifier, Highlighter) is already in place, you simply need to replicate the **Database**, **Admin**, and **Frontend** patterns for the `Team` model.

Here is the implementation guide for the `Team` model.

### **1. Database Migration**

Add the `bio` column to your `teams` table.

```bash
php artisan make:migration add_bio_to_teams_table

```

**File:** `database/migrations/xxxx_xx_xx_add_bio_to_teams_table.php`

```php
public function up(): void
{
    Schema::table('teams', function (Blueprint $table) {
        $table->longText('bio')->nullable();
    });
}

```

---

### **2. Filament Admin (Team Resource)**

Add the Markdown editor to your Team resource form.

**File:** `app/Filament/Resources/TeamResource.php`

```php
use Filament\Forms\Components\MarkdownEditor;

public static function form(Form $form): Form
{
    return $form->schema([
        // ... existing name fields ...

        MarkdownEditor::make('bio')
            ->label('Team Biography')
            ->columnSpanFull()
            ->toolbarButtons([
                'bold', 'italic', 'link', 'bulletList', 'orderedList', 'codeBlock', 'undo', 'redo'
            ]),
    ]);
}

```

---

### **3. Frontend Page (Folio + Livewire SFC)**

Create a new Folio page specifically for viewing a Team.
**Route:** `/teams/{id}` (or `/teams/{slug}` depending on your setup).

**File:** `resources/views/pages/teams/[Team].blade.php`

```blade
<?php

use Livewire\Component;
use Livewire\Attributes\Computed;
use Stevebauman\Purify\Facades\Purify;
use App\Models\Team;

new class extends Component {
    public Team $team;

    #[Computed]
    public function htmlBio(): string
    {
        return Purify::clean($this->team->bio ?? '');
    }
};
?>

<x-layouts.app :title="$team->name">
    <flux:main class="container mx-auto py-12 max-w-4xl">

        {{-- Team Header --}}
        <div class="mb-8 border-b border-zinc-700 pb-6">
            <flux:heading level="1" size="xl" class="text-ctp-mauve">
                {{ $team->name }}
            </flux:heading>
            <flux:subheading class="text-zinc-400">
                Managed by {{ $team->owner->name ?? 'Unknown' }}
            </flux:subheading>
        </div>

        {{--
            Bio Content
            Reuse the exact same prose classes as the User profile
            to ensure visual consistency.
        --}}
        <div class="prose prose-zinc prose-invert max-w-none
            prose-headings:text-ctp-peach
            prose-a:text-ctp-blue hover:prose-a:text-ctp-sapphire
            prose-code:text-ctp-teal
            prose-pre:bg-zinc-900 prose-pre:border prose-pre:border-zinc-700
        ">
            <x-markdown>
                {!! $this->htmlBio !!}
            </x-markdown>
        </div>

        @if(empty($team->bio))
            <flux:card class="bg-zinc-800 border-zinc-700 italic text-zinc-400">
                This team has not written a biography yet.
            </flux:card>
        @endif

    </flux:main>
</x-layouts.app>

```

### **Why this "Just Works"**

1. **Theming:** You already configured `app.css` to map `zinc` colors to Catppuccin. Since this new page uses `prose-zinc`, it will automatically inherit the Mocha theme without extra work.
2. **Highlighting:** Your `app.js` listens for `livewire:navigated`. Whether a user visits a User Profile or a Team Profile, the highlighter will trigger automatically.
3. **Security:** You are using the same `Purify::clean()` logic, ensuring that neither Teams nor Users can inject scripts.
