# research `wirechat/wirechat`

## 1. prompt

consider using wirecxhat as a starting point and customising/developing further, later

## 2. response

Using **WireChat** as a foundation to eventually "take over" is a smart strategy for Laravel 12. It saves you ~40 hours of boilerplate (database schema, read receipts, unread counts) while letting you "eject" from the defaults gradually.

Here is the strategic roadmap for using WireChat as a scaffold, ensuring you don't get locked in.

### 2.1. The Strategy: "Extend, Don't Fork"

The mistake most developers make is editing the files in `/vendor` or forking the repository immediately. **Do not do this.**

WireChat is designed to be extended. You can replace specific logic parts while keeping the core updates flowing.

| Level of Control | Method | What you change |
| --- | --- | --- |
| **Level 1 (Visuals)** | `vendor:publish` | You copy the Blade views to your `resources` folder and change the HTML/Tailwind. |
| **Level 2 (Logic)** | **Class Extension** | You create your own Livewire component (e.g., `MyChatList`) that extends WireChat’s base class. |
| **Level 3 (Total)** | **Schema Takeover** | You stop using the package's UI entirely but keep using its database tables and Models. |

---

### 2.2. How to Execute This (Step-by-Step)

#### 2.2.1. Phase 1: Rapid Implementation (The "Buy" Phase)

Install it normally to get the database tables and basic UI running.

```bash
composer require namu/wirechat
php artisan wirechat:install
php artisan migrate

```

* **Goal:** Get chat working in your app in 10 minutes.
* **Tip:** Use the `<livewire:wirechat.chats widget="true" />` component to embed it in your existing layout rather than using its full-page route.

#### 2.2.2. Phase 2: Visual Takeover (The "Customize" Phase)

When the default design inevitably doesn't match your Figma file, publish the views:

```bash
php artisan vendor:publish --tag=wirechat-views

```

* **Result:** You now have `resources/views/vendor/wirechat`.
* **Action:** Edit these files directly. You can rip out the Tailwind classes and replace them with your own. Since WireChat is "headless-capable," removing UI elements won't break the logic.

#### 2.2.3. Phase 3: Logic Extension (The "Develop" Phase)

This is the most critical part for your request. Eventually, you will want to change *behavior* (e.g., "Users can't message unless they are friends" or "Admins see a different chat list").

Instead of hacking the package, create your own component that **extends** theirs.

**Run this:**

```bash
php artisan make:livewire CustomChatList

```

**Edit `app/Livewire/CustomChatList.php`:**

```php
<?php

namespace App\Livewire;

use Wirechat\Livewire\Chats\Chats as WireChatList;

// EXTEND the original class, don't just copy it
class CustomChatList extends WireChatList
{
    // Override only the method you want to change
    public function getConversations()
    {
        // Your custom logic, e.g., filter by 'friends' only
        return auth()->user()->conversations()->where('type', 'friend')->get();
    }
}

```

Now, in your Blade file, use `<livewire:custom-chat-list />` instead of the default one.

---

### 2.3. The "Lock-in" Risks (Read Before Proceeding)

While this is a good starting point, be aware of the technical debt you are accepting:

* **Tailwind Dependency:** WireChat is heavy on Tailwind. If you try to switch to Bootstrap or plain CSS later, you will have to rewrite every single view file.
* **Database Schema:** WireChat uses a polymorphic relationship structure.
* *Good:* It's flexible (Users can chat with Teams).
* *Bad:* Migrating data *out* of polymorphic tables to a simpler schema later is painful. **Check the `wirechat_conversations` and `wirechat_messages` tables early** to ensure you are comfortable with that structure.



### 2.4. Summary Verdict

**Yes, do it.**

1. **Start:** Install WireChat to get the database schema and "Unread Count" logic for free.
2. **Customize:** Publish views immediately to own the UI.
3. **Extend:** Use Class Inheritance (as shown above) to tweak logic.

**Would you like me to write a migration script that adds a "custom_status" column to the WireChat conversation table, so you can see how to add fields to their schema?**

## 3. prompt

yes

## 4. response

Here is the practical roadmap for modifying the WireChat database schema without breaking future updates.

### 4.1. Step 1: Create the Migration

You need to add your custom column to the table WireChat created. Do not edit their migration files; create a new one that runs *after* theirs.

**Run this command:**

```bash
php artisan make:migration add_custom_status_to_conversations_table --table=wirechat_conversations

```

**Edit the migration file:**

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wirechat_conversations', function (Blueprint $table) {
            // We add 'nullable' so existing chats don't break
            $table->string('custom_status')->nullable()->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('wirechat_conversations', function (Blueprint $table) {
            $table->dropColumn('custom_status');
        });
    }
};

```

---

### 4.2. Step 2: The "Gotcha" (Mass Assignment)

By default, the `Conversation` model inside the `vendor` folder doesn't know this new column exists. If you try to do `Conversation::create(['custom_status' => 'archived'])`, it will likely be ignored because it isn't in the `$fillable` array of the package's model.

You have two solutions. **Solution B** is the robust "Developer" way.

#### 4.2.1. Solution A: The Quick Fix (Force Fill)

If you only update this column occasionally, just use `forceFill`. It bypasses the `$fillable` protection.

```php
$conversation = WireChat\Models\Conversation::find(1);
$conversation->forceFill(['custom_status' => 'priority'])->save();

```

#### 4.2.2. Solution B: The "Takeover" (Recommended)

You should extend their model to make this a first-class citizen in your app.

1. **Create your own model:**
`app/Models/Chat/Conversation.php`
```php
namespace App\Models\Chat;

use Namu\WireChat\Models\Conversation as BaseConversation;

class Conversation extends BaseConversation
{
    // Merge your new column with their existing fillables
    public function getFillable()
    {
        return array_merge(parent::getFillable(), ['custom_status']);
    }

    // You can now add your own relationships too!
    public function project()
    {
        return $this->belongsTo(\App\Models\Project::class);
    }
}

```


2. **Tell Laravel to use YOUR model:**
Most modern packages (including WireChat) allow you to swap classes via the config file.
Open `config/wirechat.php` (publish it if you haven't) and look for the `models` section:
```php
'models' => [
    'conversation' => \App\Models\Chat\Conversation::class, // Point to your new class
    'message' => \Namu\WireChat\Models\Message::class,
],

```


*(Note: If the package version you are using does not have a config option for model swapping, you can bind it in your `AppServiceProvider`'s `register` method: `$this->app->bind(\Namu\WireChat\Models\Conversation::class, \App\Models\Chat\Conversation::class);`)*

### 4.3. Step 3: Using it in the UI

Now you can use this field in your custom Livewire components (or the published views) to change the UI logic.

**Example: In your Blade View**

```blade
@foreach($conversations as $conversation)
    <div class="chat-item {{ $conversation->custom_status === 'priority' ? 'bg-red-50 border-red-500' : '' }}">
        {{ $conversation->getDisplayName() }}
    </div>
@endforeach

```

### 4.4. Strategic Summary

You have now:

1. **Modified the database** safely (via a separate migration).
2. **Extended the logic** safely (via inheritance).
3. **Preserved the package core** (so when WireChat updates to fix a security bug, you can still run `composer update` without losing your changes).

**Would you like me to explain how to customize the "Message Sent" event so you can trigger a custom notification (like an SMS or email) whenever a chat message is sent?**
