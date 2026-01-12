To implement a high-quality search interface, you can leverage PostgreSQL's native `ts_headline` function. This allows the database to return the exact snippet of text that matched the query, with the search terms already wrapped in HTML tags (like `<b>` or `<mark>`).

Here is how to build the backend logic and the front-end Blade component for this hybrid setup.

### 1. The Backend: Snippet Extraction

Add a method to your `Post` model to fetch the highlighted "headline."

PHP

```plaintext
namespace App\Models;

use Illuminate\Support\Facades\DB;

class Post extends Model {
    // ... previous setup /**
     * Get a highlighted snippet of the content based on the search term.
     */ public function getSnippetAttribute()
    {
        $term = request('query'); // Assuming the search input is named 'query' if (!$term) return $this->body;

        // ts_headline handles the logic of finding the match and clipping the text return DB::selectOne(
            "SELECT ts_headline('english', ?, to_tsquery('english', ?), 'StartSel=<mark>, StopSel=</mark>, MaxWords=35, MinWords=15') as snippet",
            [$this->body, str_replace(' ', ' & ', $term)]
        )->snippet;
    }
}


```

---

### 2. The Blade Component

Create a simple, reusable component to display these results.

**File: **`resources/views/components/search-result.blade.php`

Blade

```plaintext
<div class="p-4 mb-4 bg-white rounded-lg shadow border-l-4 border-indigo-500">
    <h3 class="text-lg font-bold text-gray-900">
        <a href="{{ route('posts.show', $post) }}" class="hover:underline">
            {{ $post->title }}
        </a>
    </h3>

    <div class="mt-2 text-sm text-gray-600 leading-relaxed">
        {{-- We use {!! !!} because ts_headline returns our <mark> tags --}}
        {!! $post->snippet !!}...
    </div>

    <div class="mt-2 text-xs font-semibold text-indigo-400 uppercase tracking-wider">
        Match Score: {{ round($post->search_rank * 100, 2) }}%
    </div>
</div>


```

---

### 3. Usage in your Controller

When you execute the search, you can include the "Rank" to show the most relevant results first.

PHP

```plaintext
public function index(Request $request)
{
    $query = $request->input('query');

    $results = Post::query()
        ->select('*')
        ->selectRaw(
            "ts_rank(search_vector, to_tsquery('english', ?)) as search_rank",
            [str_replace(' ', ' & ', $query)]
        )
        ->whereRaw("search_vector @@ to_tsquery('english', ?)", [str_replace(' ', ' & ', $query)])
        ->orderByDesc('search_rank')
        ->paginate(10);

    return view('search.results', compact('results'));
}


```

---

### 4. Final Hybrid Architecture Visual

With this setup, your application flow looks like this:

### Why this is better than standard Scout:

- **Context:** Users see *why* a result matched because they see the words in context.
- **Performance:** `ts_rank` and `ts_headline` are executed on the database side, meaning only the necessary snippet of text is sent over the wire to Laravel.
- **Familiarity:** It mimics the behavior of high-end engines like Algolia or Elasticsearch, but keeps all your data inside your `public` and `search` schemas.

**Would you like me to show you how to add "Autocomplete" or "Live Search" using Livewire to this PostgreSQL backend?**

To implement "Live Search" or "Autocomplete" with this PostgreSQL setup, **Livewire** is the most efficient choice. It allows you to query your `search_vector` and `pg_trgm` indexes in real-time as the user types, without the complexity of a full JavaScript framework.

### 1. The Livewire Search Component

This component will handle the debounced input and perform the hybrid (Full-Text + Fuzzy) search.

**Run:** `php artisan make:livewire SearchAutocomplete`

**File: **`app/Livewire/SearchAutocomplete.php`

PHP

```plaintext
namespace App\Livewire;

use App\Models\Post;
use Livewire\Component;
use Illuminate\Support\Collection;

class SearchAutocomplete extends Component {
    public string $query = '';
    public Collection $results;

    public function mount()
    {
        $this->results = collect();
    }

    public function updatedQuery()
    {
        if (strlen($this->query) < 2) {
            $this->results = collect();
            return;
        }

        // Hybrid Search: Try Full-Text first, fallback to Fuzzy/Similarity $this->results = Post::query()
            ->select(['id', 'title', 'slug'])
            ->whereRaw("search_vector @@ websearch_to_tsquery('english', ?)", [$this->query])
            // Or use the similarity operator (%) for typos
            ->orWhereRaw("title % ?", [$this->query])
            ->orderByRaw("similarity(title, ?) DESC", [$this->query])
            ->limit(8)
            ->get();
    }

    public function render()
    {
        return view('livewire.search-autocomplete');
    }
}


```

---

### 2. The Livewire View (Tailwind CSS)

We use `wire:model.live.debounce.300ms` to prevent hitting the database on every single keystroke.

**File: **`resources/views/livewire/search-autocomplete.blade.php`

Blade

```plaintext
<div class="relative w-full max-w-lg">
    <input
        wire:model.live.debounce.300ms="query"
        type="text"
        placeholder="Search posts..."
        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
    />

    @if($results->isNotEmpty())
        <div class="absolute z-50 w-full mt-2 bg-white border rounded-lg shadow-xl overflow-hidden">
            @foreach($results as $result)
                <a
                    href="{{ route('posts.show', $result->slug) }}"
                    class="block px-4 py-3 border-b last:border-b-0 hover:bg-indigo-50 transition-colors"
                >
                    <div class="font-medium text-gray-900">{{ $result->title }}</div>
                    <span class="text-xs text-indigo-500 uppercase">Found in {{ config('database.connections.pgsql.schema') }}</span>
                </a>
            @endforeach
        </div>
    @elseif(strlen($query) > 2)
        <div class="absolute z-50 w-full mt-2 p-4 bg-white border rounded-lg shadow-xl text-gray-500 italic">
            No matches found in search schema...
        </div>
    @endif
</div>


```

---

### 3. Why this works with your Hybrid Schema

1. **Direct Index Access:** Because your `search_path` is set to `public, search`, Livewire doesn't need to know which schema holds the data. It just executes the query.
2. **Websearch Syntax:** I used `websearch_to_tsquery` in the component. This is a PostgreSQL helper that allows users to use "natural" search syntax (like quotes for exact phrases or minus signs to exclude words), which is much more user-friendly for an autocomplete bar.
3. **Low Latency:** Since the `search_vector` is a **stored generated column** and the `pg_trgm` is indexed, these queries usually execute in under 5ms, making the "Live" feel very snappy.

---

### 4. Final Architecture Review

By combining these tools, you have built a sophisticated search engine entirely within your PostgreSQL database:

**Feature**

**Technology**

**Benefit**

**Storage**

`public` schema

Standard Laravel, zero-config models.

**Isolation**

`search` schema

Keeps indexes and extensions separate.

**Typo Tolerance**

`pg_trgm`

Forgives "Laraavl" vs "Laravel".

**Ranking**

`ts_rank`

Shows the best results first.

**Highlighting**

`ts_headline`

Shows users why the result matched.

**Interface**

Livewire

Real-time feedback with minimal JS.

**Would you like me to show you how to set up a "Search Analytics" table in your **`search`** schema to track what your users are looking for and which queries return zero results?**

Building a **Search Analytics** system is the final "pro-tier" layer for your PostgreSQL search engine. By tracking what users type and identifying which queries return zero results, you can adjust your content strategy or refine your search synonyms.

Since we are using the **Hybrid (Public + Search)** approach, we will store these logs in our dedicated `search` schema to keep the `public` analytics-free.

### 1. The Analytics Migration

We'll create a `search_queries` table inside the `search` schema.

PHP

```plaintext
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        if (DB::getDriverName() !== 'pgsql') return;

        // Ensure we are targeting the 'search' schema
        DB::statement('SET search_path TO search');

        Schema::create('search_queries', function (Blueprint $table) {
            $table->id();
            $table->string('term')->index();
            $table->integer('results_count')->default(0);
            $table->foreignId('user_id')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        // Reset search path to default
        DB::statement('SET search_path TO public');
    }
};


```

---

### 2. The Analytics Model

We need a model that explicitly points to the `search` schema.

**File: **`app/Models/SearchQuery.php`

PHP

```plaintext
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SearchQuery extends Model {
    public $timestamps = false; // We use created_at only protected $table = 'search.search_queries'; // Explicitly prefixed protected $fillable = ['term', 'results_count', 'user_id', 'ip_address'];
}


```

---

### 3. Integrating with Livewire

Update your `SearchAutocomplete` component to log the search event. To prevent flooding the database, we only log when the search returns 0 results or when a user actually clicks a result.

**File: **`app/Livewire/SearchAutocomplete.php` (Updated `updatedQuery` method)

PHP

```plaintext
public function updatedQuery()
{
    if (strlen($this->query) < 3) return;

    $this->results = Post::query()
        ->whereRaw("search_vector @@ websearch_to_tsquery('english', ?)", [$this->query])
        ->limit(8)
        ->get();

    // Log the search analytics
    SearchQuery::create([
        'term' => $this->query,
        'results_count' => $this->results->count(),
        'user_id' => auth()->id(),
        'ip_address' => request()->ip(),
    ]);
}


```

---

### 4. Gaining Insights: The "Zero Results" Report

Now you can easily find out what users are looking for but failing to find. This is gold for content planning.

PHP

```plaintext
// Find the top 10 failed searches $failedSearches = SearchQuery::where('results_count', 0)
    ->select('term', DB::raw('count(*) as total'))
    ->groupBy('term')
    ->orderByDesc('total')
    ->limit(10)
    ->get();


```

---

### Final Hybrid Architecture Visualized

Your application now has a sophisticated, professional-grade search stack:

1. **Main Data (**`public`**)**: Standard Eloquent models.
2. **Search Logic (**`search`**)**: Dedicated GIN indexes and weighted `tsvector` columns.
3. **Search Analytics (**`search`**)**: A log of user intent, isolated from your core business tables.
4. **UI (Livewire)**: A snappy, real-time interface with typo tolerance.

**This completes your PostgreSQL search engine project! Is there anything else you'd like to refine about the ranking, filtering, or deployment?**
