# Research `wirechat/wirechat` is incompatible with Livewire 4

---

<details><summary>Expand for Table of Contents</summary>

## Table of Contents

- [Research `wirechat/wirechat` is incompatible with Livewire 4](#research-wirechatwirechat-is-incompatible-with-livewire-4)
  - [Table of Contents](#table-of-contents)
  - [1. WireChat Incompatibility with Livewire 4](#1-wirechat-incompatibility-with-livewire-4)
    - [1.1. Should you fork WireChat?](#11-should-you-fork-wirechat)
    - [1.2. The "Livewire 4" Solution: Build Native with "Islands"](#12-the-livewire-4-solution-build-native-with-islands)
    - [1.3. The Blueprint: Building a "TALL 4" Chat](#13-the-blueprint-building-a-tall-4-chat)
      - [1.3.1. Step A: The Database (Use a Headless Package)](#131-step-a-the-database-use-a-headless-package)
      - [1.3.2. Step B: The Frontend (Livewire 4 Component)](#132-step-b-the-frontend-livewire-4-component)
    - [1.4. Summary: The "TALL 4" Stack for Chat](#14-summary-the-tall-4-stack-for-chat)
  - [2. wirechat alternatives](#2-wirechat-alternatives)
    - [2.1. The "Modern Chat" Architecture](#21-the-modern-chat-architecture)
    - [2.2. Why "Presence" \& "Following" Break Packages](#22-why-presence--following-break-packages)
    - [2.3. The "Modern" Database Schema (Reactions \& Replies)](#23-the-modern-database-schema-reactions--replies)
    - [2.4. Implementing "Presence" (The Livewire 4 Way)](#24-implementing-presence-the-livewire-4-way)
    - [2.5. Summary Recommendation](#25-summary-recommendation)

details>

---

## 1. WireChat Incompatibility with Livewire 4

- **Prompt: i am using TALL stack -- but wirechat/wirechat is not available for livewire 4**
- **Response:**

With **Livewire 4** (currently in Beta/Early Release as of late 2025), you are on the "bleeding edge." Most complex UI packages like WireChat—which rely heavily on Livewire 3's specific lifecycle hooks and component structure—will break because Livewire 4 introduces significant architectural changes (e.g., the new view-first system and changes to event listeners).

Since you are on the TALL stack and cannot use WireChat, here is your best path forward.

### 1.1. Should you fork WireChat?

**Absolutely not.**
Forking WireChat to make it compatible with Livewire 4 is not just a "quick fix." You would likely need to rewrite ~40% of the codebase to align with Livewire 4's new `Islands` architecture and deprecated hooks. You will end up owning a massive, complex codebase that you didn't write, with no upgrade path.

### 1.2. The "Livewire 4" Solution: Build Native with "Islands"

Livewire 4 introduces a feature called **Islands** (`wire:island`) that makes it the *perfect* tool for building chat apps from scratch—better than any package could offer in Livewire 3.

**Why L4 is better for chat:**

- **Islands:** You can wrap your chat widget in an "Island." When the chat updates (polls or receives a broadcast), **only** that island re-renders. The rest of your page (header, sidebar, content) is completely untouched. This solves the #1 performance issue with Livewire chat apps.
- **Reverb Native:** You don't need a package to handle the WebSocket connection; it's built-in.

### 1.3. The Blueprint: Building a "TALL 4" Chat

Since you can't use a package, here is the fastest way to build a robust chat in Livewire 4 without reinventing the wheel.

#### 1.3.1. Step A: The Database (Use a Headless Package)

Don't write the schema yourself if you don't want to. Use **Musonza Chat** strictly for the backend. It works with standard Models, so it doesn't care about Livewire versions.

```bash
composer require musonza/chat

```

*This gives you `conversations`, `messages`, and `participants` tables immediately.*

#### 1.3.2. Step B: The Frontend (Livewire 4 Component)

Create a single component: `Chat/Room.php`.

**The View (`resources/views/livewire/chat/room.blade.php`):**
In Livewire 4, use the new architecture to isolate the chat.

```blade
<div wire:island>

    <div class="h-96 overflow-y-auto flex flex-col space-y-2 p-4"
         id="chat-feed">
        @foreach($messages as $message)
            <div class="p-2 rounded-lg {{ $message->isMine() ? 'bg-blue-500 text-white self-end' : 'bg-gray-200' }}">
                {{ $message->body }}
            </div>
        @endforeach
    </div>

    <form wire:submit="sendMessage" class="p-4 border-t">
        <input
            wire:model="body"
            type="text"
            class="w-full border rounded px-3 py-2"
            placeholder="Type a message..."
        >
    </form>

</div>

@script
<script>
    // Listen for the event on the specific channel
    Echo.private('chat.{{ $conversationId }}')
        .listen('MessageSent', (e) => {
            // Trigger a refresh ONLY on the component
            $wire.$refresh();

            // Optional: Scroll to bottom
            setTimeout(() => {
                const feed = document.getElementById('chat-feed');
                feed.scrollTop = feed.scrollHeight;
            }, 100);
        });
</script>
@endscript

```

### 1.4. Summary: The "TALL 4" Stack for Chat

| Layer | Solution | Why? |
| --- | --- | --- |
| **UI** | **Livewire 4 (Custom)** | Use `wire:island` for high performance. |
| **Logic** | **Musonza Chat** | Handles the database schema/relationships (Version agnostic). |
| **Realtime** | **Laravel Reverb** | Native WebSocket server (No Pusher costs). |

**Recommendation:** Do not wait for WireChat to update. The changes in Livewire 4 are significant enough that building a simple, custom "Island" component will be faster, more performant, and 100% under your control.

[Building a Real-Time Chat Application in Laravel](https://www.youtube.com/watch?v=favkJtqija8)

This video series (from the creator of WireChat) is still highly relevant—even if you don't use the package, watching **Episode 1 & 2** will show you the exact logic needed to build the backend schema manually if you decide to skip Musonza.

---

## 2. wirechat alternatives

- **Prompt: consider using wirecxhat as a starting point and customising/developing further, latergiven modern chat sensibilities and my requirement for "presence" and "following", which are "custom" would a custom backend be better**

- **Response:**

Given your requirements for **"Presence"** (Online/Offline, "Active now") and **"Following"** (only friends can chat), a **Custom Backend is overwhelmingly better** than a headless package.

Existing packages (even headless ones) focus on **Storage** (saving messages). They do not solve **Connectivity** (Presence) or **Social Authorization** (Following). If you use a package, you will end up writing 80% of the code anyway to bridge these gaps, often fighting against the package's assumptions.

Here is the architectural blueprint for a modern, custom chat system in Laravel 12.

### 2.1. The "Modern Chat" Architecture

You need three distinct layers. Packages usually only give you Layer 3.

| Layer | Responsibility | Technology |
| --- | --- | --- |
| **1. Social Gate** | "Can A talk to B?" (Following Logic) | **Laravel Policy / Middleware** |
| **2. Connectivity** | "Is A online?" "Is A typing?" (Presence) | **Laravel Reverb (Presence Channels)** |
| **3. Storage** | "What did they say?" (History/Reactions) | **Custom Models (SQL)** |

---

### 2.2. Why "Presence" & "Following" Break Packages

**The "Presence" Problem:**

- **Packages:** Attempt to store "online status" in the database (e.g., `last_seen_at` column). This is slow, inaccurate (requires polling), and kills your database at scale.
- **The Modern Way:** You do **not** use the database for presence. You use **Reverb Presence Channels**. The "state" of who is online lives in the WebSocket server's memory, not your SQL table. No package gives you this out of the box because it requires frontend-backend coordination.

**The "Following" Problem:**

- **Packages:** Assume anyone can start a conversation, or they use complex "Permission" systems you have to learn.
- **The Modern Way:** You just write a simple check:

```php
if (! $user->isFollowing($recipient)) {
    abort(403, "You must follow this user to chat.");
}

```

Writing this natively is faster than reading a package's documentation on permissions.

---

### 2.3. The "Modern" Database Schema (Reactions & Replies)

To support "modern sensibilities" like Slack/WhatsApp (Reactions, Replies, Edits), you need a specific schema. Old packages typically use "pivot tables" which make these features painful.

**Recommended Schema:**

```php
// 1. Conversations (The "Room")
Schema::create('conversations', function (Blueprint $table) {
    $table->id();
    $table->boolean('is_group')->default(false);
    $table->timestamps();
});

// 2. Participants (Who is in the room + State)
Schema::create('participants', function (Blueprint $table) {
    $table->id();
    $table->foreignId('conversation_id');
    $table->foreignId('user_id');
    $table->timestamp('last_read_at')->nullable(); // For "Unread Counts"
    $table->boolean('is_muted')->default(false);   // Modern feature
    $table->unique(['conversation_id', 'user_id']);
});

// 3. Messages (The Content - Optimized for Modern Feeds)
Schema::create('messages', function (Blueprint $table) {
    $table->id();
    $table->foreignId('conversation_id')->index(); // Index for speed
    $table->foreignId('user_id');
    $table->text('body')->nullable(); // Nullable for attachment-only messages

    // REPLY SUPPORT
    $table->foreignId('reply_to_id')->nullable()->constrained('messages');

    // REACTION SUPPORT (JSON is better than a separate table for simple emojis)
    // Structure: {"❤️": [user_id_1, user_id_2], "👍": [user_id_3]}
    $table->json('reactions')->nullable();

    // EDIT SUPPORT
    $table->timestamp('edited_at')->nullable();

    $table->timestamps();
});

```

### 2.4. Implementing "Presence" (The Livewire 4 Way)

Since you are using TALL, you don't need a heavy frontend framework. Livewire 4 + Reverb handles this natively.

**The "Islands" Strategy:**

- **Why:** Chat is "high frequency." You don't want the whole page to re-render when a message comes in.
- **How:** Wrap the chat area in `wire:island`.

**Code Example: Presence & Typing**
Do not save "Typing..." to the database. Use **Client Whispers**.

```javascript
// In your Livewire 4 View (Alpine/JS)
Echo.join(`chat.${conversationId}`)
    .here((users) => {
        // "users" array contains everyone currently online
        // Update your UI to show green dots
    })
    .joining((user) => {
        // User came online -> Show Toast "User connected"
    })
    .leaving((user) => {
        // User went offline -> Grey out dot
    })
    .listenForWhisper('typing', (e) => {
        // Show "John is typing..." bubble
        // Remove it after 3 seconds via setTimeout
    });

// To broadcast typing (triggers no backend request!)
let typingTimer;
inputField.addEventListener('keydown', () => {
    clearTimeout(typingTimer);
    Echo.join(`chat.${conversationId}`).whisper('typing', { name: 'John' });
    typingTimer = setTimeout(() => { /* stop logic */ }, 1000);
});

```

### 2.5. Summary Recommendation

- **Do not use a package.** The "savings" are an illusion because you will spend weeks fighting the package to implement "Following" logic and "Presence" (which they don't support).
- **Build Custom:**

1. **Schema:** Use the 3-table schema above (JSON for reactions).
2. **Logic:** Use Laravel Policies for the "Following" check.
3. **Realtime:** Use Reverb Presence Channels for online status.

The following video is the most relevant resource for understanding how to specifically implement the "Presence" part using Laravel Reverb, which is the hardest part of your custom build:

[Laravel Reverb Presence Channels: Build a Live Online User Tracker!](https://www.youtube.com/watch?v=IwBd6ZF-WXo)

This video walks through the exact "Presence" logic (online/offline tracking) that generic database packages completely fail to provide.

---
