@php
    use App\Helpers\QueryHelper;

    $total = (int)($paginator['total'] ?? 0);
    $perPage = (int)($paginator['per_page'] ?? 15);
    $currentPage = (int)($paginator['current_page'] ?? 1);
    $lastPage = max(1, (int)($paginator['last_page'] ?? 1));

    $from = $total === 0 ? 0 : (($currentPage - 1) * $perPage) + 1;
    $to = min($total, $currentPage * $perPage);

    // Calculate window of pages to display
    $delta = 2;
    $range = [];
    for ($i = max(2, $currentPage - $delta); $i <= min($lastPage - 1, $currentPage + $delta); $i++) {
        $range[] = $i;
    }

    if ($currentPage - $delta > 2) {
        array_unshift($range, '...');
    }
    if ($currentPage + $delta < $lastPage - 1) {
        $range[] = '...';
    }

    array_unshift($range, 1);
    if ($lastPage > 1) {
        $range[] = $lastPage;
    }
@endphp

<div class="pagination-container" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-top:16px;padding:12px 16px;background:#ffffff;border:1px solid var(--border);box-shadow:var(--shadow-xs);border-radius:var(--r-sm);">
    {{-- Left: Record Summary Text & Per-Page Selector --}}
    <div style="display:flex;align-items:center;gap:16px;">
        <span style="font-size:0.85rem;color:var(--text-dim);font-weight:600;">
            {{ __('app.common.showing') }} <strong style="color:var(--text-main);font-weight:800;">{{ $from }}</strong> {{ __('app.common.to') }} <strong style="color:var(--text-main);font-weight:800;">{{ $to }}</strong> {{ __('app.common.of') }} <strong style="color:var(--primary);font-weight:800;">{{ number_format($total) }}</strong> {{ __('app.common.results') }}
        </span>

        <div style="display:flex;align-items:center;gap:6px;">
            <label for="perPageSelect" style="font-size:0.75rem;color:var(--text-dim);font-weight:700;text-transform:uppercase;">Rows:</label>
            <select id="perPageSelect" class="form-control" style="padding:4px 8px;font-size:0.8rem;height:auto;font-weight:700;width:auto;cursor:pointer;" onchange="location.href=this.value;">
                @foreach([10, 15, 25, 50, 100] as $option)
                    <option value="{{ QueryHelper::buildQuery(['per_page' => $option, 'page' => 1]) }}" @selected($perPage === $option)>
                        {{ $option }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Right: 3D Page Buttons --}}
    @if($lastPage > 1)
        <nav aria-label="Pagination Navigation" style="display:flex;align-items:center;gap:4px;">
            {{-- Previous Button --}}
            @if($currentPage > 1)
                <a href="{{ QueryHelper::pageUrl($currentPage - 1) }}" class="btn btn-secondary btn-sm" style="padding:4px 10px;font-size:0.8rem;display:inline-flex;align-items:center;gap:4px;">
                    ‹ Prev
                </a>
            @else
                <span class="btn btn-secondary btn-sm" style="padding:4px 10px;font-size:0.8rem;opacity:0.4;cursor:not-allowed;box-shadow:none;">
                    ‹ Prev
                </span>
            @endif

            {{-- Numbered Page Blocks --}}
            @foreach($range as $pageItem)
                @if($pageItem === '...')
                    <span style="padding:4px 8px;font-size:0.85rem;color:var(--text-dim);font-weight:700;">…</span>
                @elseif($pageItem === $currentPage)
                    <span class="btn btn-primary btn-sm" style="padding:4px 10px;font-size:0.8rem;font-weight:900;min-width:32px;text-align:center;">
                        {{ $pageItem }}
                    </span>
                @else
                    <a href="{{ QueryHelper::pageUrl((int)$pageItem) }}" class="btn btn-secondary btn-sm" style="padding:4px 10px;font-size:0.8rem;font-weight:700;min-width:32px;text-align:center;">
                        {{ $pageItem }}
                    </a>
                @endif
            @endforeach

            {{-- Next Button --}}
            @if($currentPage < $lastPage)
                <a href="{{ QueryHelper::pageUrl($currentPage + 1) }}" class="btn btn-secondary btn-sm" style="padding:4px 10px;font-size:0.8rem;display:inline-flex;align-items:center;gap:4px;">
                    Next ›
                </a>
            @else
                <span class="btn btn-secondary btn-sm" style="padding:4px 10px;font-size:0.8rem;opacity:0.4;cursor:not-allowed;box-shadow:none;">
                    Next ›
                </span>
            @endif
        </nav>
    @endif
</div>
