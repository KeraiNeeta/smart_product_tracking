<form method="GET" action="{{ url()->current() }}" class="table-search">
    <label class="sr-only" for="table-search-{{ $searchId ?? 'main' }}">Search table records</label>
    <div class="table-search-control">
        <svg viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="10.8" cy="10.8" r="6.8"></circle>
            <path d="m16 16 4.5 4.5"></path>
        </svg>
        <input
            id="table-search-{{ $searchId ?? 'main' }}"
            type="search"
            name="search"
            value="{{ request()->query('search') }}"
            placeholder="{{ $placeholder ?? 'Search records…' }}"
            autocomplete="off"
        >
        @if (request()->filled('search'))
            <a class="table-search-clear" href="{{ url()->current() }}">Clear</a>
        @endif
        <button type="submit">Search</button>
    </div>
    @if (isset($rows) && method_exists($rows, 'total'))
        <span class="table-search-count">{{ number_format($rows->total()) }} {{ $rows->total() === 1 ? 'result' : 'results' }}</span>
    @else
        <span class="table-search-count">{{ $hint ?? 'Search all matching records' }}</span>
    @endif
</form>
