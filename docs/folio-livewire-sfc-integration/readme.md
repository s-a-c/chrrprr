# Laravel Folio & Livewire 4 SFC Integration

This document outlines the architecture and implementation details for integrating **Laravel Folio** with **Livewire 4 Single-File Components (SFCs)** using a unified layout system powered by **Flux UI**.

## Architecture Overview

The integration relies on a "Global Bridge" that intercepts Folio's rendering process and redirects it through Livewire's component instantiation pipeline. This ensures that SFCs are correctly booted, their `$this` context is bound, and they leverage Livewire's native page-rendering features.

### Request Flow

```mermaid
sequenceDiagram
    participant Browser
    participant Folio as Laravel Folio
    participant Bridge as Folio Bridge (renderUsing)
    participant LW as Livewire 4 SFC
    participant Layout as Unified Layout (layouts.app)

    Browser->>Folio: GET /chrrps
    Folio->>Folio: Match resources/views/pages/chrrps.blade.php
    Folio->>Bridge: Invoke renderUsing callback
    Bridge->>LW: app('livewire')->new('pages::chrrps')
    LW-->>Bridge: Component Instance
    Bridge-->>Folio: Invoke Component Instance
    Folio->>LW: Render SFC Component
    LW->>Layout: Use default layouts.app
    Layout-->>Browser: Total HTML (Flux UI + LW SFC)
```

## Key Components

### 1. The Global Bridge
Implemented in `app/Providers/FolioServiceProvider.php`, this bridge uses `Folio::renderUsing()` to automatically detect and render Livewire SFCs found in the `resources/views/pages` directory.

### 2. Unified Layout (`layouts.app`)
The standard Livewire 4 layout file was updated to include the full **Flux UI** sidebar structure. This allows SFCs to remain "clean" by inheriting the layout automatically without needing the `#[Layout]` attribute.

```mermaid
graph TD
    A["layouts.app (Unified)"] --> B["partials.head"]
    A --> C["Flux Sidebar"]
    A --> D["Flux Header (Mobile)"]
    A --> E["Flux Main"]
    E --> F["Livewire SFC Slot"]
    C --> G["App Logo"]
    C --> H["Nav List (Platform)"]
    C --> I["User Menu"]
```

## Setup & Configuration

- **Folio Path**: `resources/views/pages`
- **Component Namespace**: `pages::*`
- **Default Layout**: `resources/views/layouts/app.blade.php`

## History & Decisions

For a detailed breakdown of the implementation steps, previous attempts, and key design decisions, please refer to the following documents:

- [Implementation Plans](history/implementation_plans.md)
- [Walkthroughs](history/walkthroughs.md)
- [Conversation Transcript](history/conversation_transcript.md)
- [Full Code Reference](code_reference.md)
