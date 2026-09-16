@if(!empty($duplicates) && count($duplicates) > 0)
@php
    $entityLabel = ($entityType === 'companies' ? 'Companies' : ($entityType === 'people' ? 'Contacts' : ucfirst($entityType) . 's'));
@endphp
<div class="duplicate-alert-banner" style="background:#FFFBEB; border:1px solid #FDE68A; border-left:4px solid #F59E0B; border-radius:10px; padding:12px 18px; margin-bottom:1.25rem; display:flex; justify-content:space-between; align-items:center; box-shadow:var(--shadow-xs);">
    <div style="display:flex; align-items:center; gap:12px;">
        <span style="font-size:1.3rem;">⚠️</span>
        <div>
            <strong style="color:#92400E; font-size:0.92rem;">
                {{ count($duplicates) }} Potential Duplicate {{ $entityLabel }} Detected
            </strong>
            <div style="font-size:0.8rem; color:#B45309; margin-top:2px;">
                Review and merge duplicate records to consolidate contacts, deals, notes, and activity history safely.
            </div>
        </div>
    </div>
    <button type="button" class="btn btn-primary btn-sm" onclick="openDuplicateModal()" style="background:linear-gradient(135deg, #d97706, #f59e0b); border-color:#d97706; box-shadow:0 2px 6px rgba(217, 119, 6, 0.28);">
        ⚡ Review & Merge ({{ count($duplicates) }})
    </button>
</div>

{{-- Modern Duplicate Reconciliation & Merge Modal --}}
<div id="duplicateModalOverlay" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(15,23,42,0.6); z-index:9999; backdrop-filter:blur(4px); align-items:center; justify-content:center;">
    <div style="background:#FFFFFF; border:1px solid var(--border); border-radius:16px; box-shadow:0 25px 50px -12px rgba(15, 23, 42, 0.25); width:90%; max-width:780px; max-height:85vh; overflow-y:auto; padding:1.75rem;">
        
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.25rem; border-bottom:1px solid var(--border); padding-bottom:0.75rem;">
            <div style="display:flex; align-items:center; gap:0.6rem;">
                <span style="font-size:1.3rem;">🔀</span>
                <h3 style="font-size:1.15rem; font-weight:700; color:var(--text-main); margin:0;">
                    Duplicate Reconciliation & Merge Studio
                </h3>
            </div>
            <button type="button" onclick="closeDuplicateModal()" style="background:none; border:none; font-size:1.25rem; font-weight:700; cursor:pointer; color:var(--text-dim);">✕</button>
        </div>

        <p style="font-size:0.85rem; color:var(--text-dim); margin-bottom:1.25rem;">
            Merging will transfer all linked deals, tasks, notes, and timeline interactions from the duplicate into the primary record. The duplicate will be safely archived to the Recycle Bin.
        </p>

        <div style="display:flex; flex-direction:column; gap:1.25rem;">
            @foreach($duplicates as $idx => $dup)
                @php
                    $p = $dup['primary'];
                    $d = $dup['duplicate'];
                    $isPerson = ($entityType === 'people');
                @endphp
                <div style="border:1px solid var(--border); border-radius:12px; padding:1.25rem; background:#F8FAFC; box-shadow:var(--shadow-xs);">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.75rem;">
                        <span style="font-size:0.75rem; font-weight:700; text-transform:uppercase; background:#FEF3C7; color:#92400E; padding:0.25rem 0.65rem; border-radius:6px; border:1px solid #FDE68A;">
                            Match: {{ $dup['match_reason'] }}
                        </span>
                        <span style="font-size:0.75rem; color:var(--text-dim); font-weight:600;">Pair #{{ $idx + 1 }}</span>
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1rem;">
                        <!-- Primary Record -->
                        <div style="background:#FFFFFF; border:1px solid #BBF7D0; border-left:3px solid #10B981; border-radius:8px; padding:0.85rem; box-shadow:var(--shadow-xs);">
                            <div style="font-size:0.7rem; font-weight:700; text-transform:uppercase; color:#047857; margin-bottom:0.35rem;">
                                ✅ Primary Record (To Keep)
                            </div>
                            <strong style="font-size:0.95rem; color:var(--text-main); display:block; margin-bottom:0.25rem;">
                                {{ $isPerson ? ($p['first_name'] . ' ' . ($p['last_name'] ?? '')) : $p['name'] }}
                            </strong>
                            <div style="font-size:0.8rem; color:var(--text-dim); line-height:1.4;">
                                @if($isPerson)
                                    <div>📧 {{ $p['email'] ?? 'No email' }}</div>
                                    <div>💼 {{ $p['job_title'] ?? 'No title' }}</div>
                                    <div>📞 {{ $p['phone'] ?? 'No phone' }}</div>
                                @else
                                    <div>🌐 {{ $p['domain'] ?? 'No domain' }}</div>
                                    <div>🏢 {{ $p['industry'] ?? 'No industry' }}</div>
                                    <div>💰 ${{ number_format((float)($p['annual_revenue'] ?? 0), 0) }}</div>
                                @endif
                            </div>
                        </div>

                        <!-- Duplicate Record -->
                        <div style="background:#FFFFFF; border:1px solid #FECACA; border-left:3px solid #EF4444; border-radius:8px; padding:0.85rem; box-shadow:var(--shadow-xs);">
                            <div style="font-size:0.7rem; font-weight:700; text-transform:uppercase; color:#B91C1C; margin-bottom:0.35rem;">
                                ⚠️ Duplicate Record (To Merge & Archive)
                            </div>
                            <strong style="font-size:0.95rem; color:var(--text-main); display:block; margin-bottom:0.25rem;">
                                {{ $isPerson ? ($d['first_name'] . ' ' . ($d['last_name'] ?? '')) : $d['name'] }}
                            </strong>
                            <div style="font-size:0.8rem; color:var(--text-dim); line-height:1.4;">
                                @if($isPerson)
                                    <div>📧 {{ $d['email'] ?? 'No email' }}</div>
                                    <div>💼 {{ $d['job_title'] ?? 'No title' }}</div>
                                    <div>📞 {{ $d['phone'] ?? 'No phone' }}</div>
                                @else
                                    <div>🌐 {{ $d['domain'] ?? 'No domain' }}</div>
                                    <div>🏢 {{ $d['industry'] ?? 'No industry' }}</div>
                                    <div>💰 ${{ number_format((float)($d['annual_revenue'] ?? 0), 0) }}</div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="/{{ $entityType }}/merge" style="margin:0; text-align:right;">
                        @csrf
                        <input type="hidden" name="primary_id" value="{{ $p['id'] }}">
                        <input type="hidden" name="duplicate_id" value="{{ $d['id'] }}">
                        <button type="submit" class="btn btn-primary btn-sm" data-confirm="Confirm merging duplicate into primary record? All associated deals and tasks will be consolidated.">
                            🔀 Consolidate & Merge Records
                        </button>
                    </form>
                </div>
            @endforeach
        </div>
    </div>
</div>

<script>
function openDuplicateModal() {
    const el = document.getElementById('duplicateModalOverlay');
    if (el) el.style.display = 'flex';
}
function closeDuplicateModal() {
    const el = document.getElementById('duplicateModalOverlay');
    if (el) el.style.display = 'none';
}
</script>
@endif
