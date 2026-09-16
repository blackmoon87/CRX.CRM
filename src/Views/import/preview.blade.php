@extends('layouts.main')

@section('title', __('app.import.map_columns'))

@section('content')
@php
if (!function_exists('crxDetectSmartFieldType')) {
    function crxDetectSmartFieldType(string $header, ?string $sample): string {
        $h = strtolower(trim($header));
        $s = trim((string)$sample);

        // Email
        if (str_contains($h, 'email') || str_contains($h, 'mail') || filter_var($s, FILTER_VALIDATE_EMAIL)) {
            return 'email';
        }
        // Phone
        if (preg_match('/(phone|mobile|tel|cell|fax)/i', $h) || (preg_match('/^\+?[0-9\-\(\)\s\.]{7,20}$/', $s) && !is_numeric($s))) {
            return 'phone';
        }
        // Website / URL / Social
        if (preg_match('/(url|website|link|web|site|linkedin|twitter|github|facebook|instagram)/i', $h) || preg_match('/^https?:\/\//i', $s)) {
            return 'url';
        }
        // Date
        if (preg_match('/(date|birthday|dob|due|anniversary|deadline|schedule)/i', $h) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $s) || preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $s)) {
            return 'date';
        }
        // Currency
        if (preg_match('/(price|cost|salary|revenue|budget|currency|fee|spend|arr|mrr|value|amount)/i', $h) || str_starts_with($s, '$') || str_starts_with($s, '€') || str_starts_with($s, '£')) {
            return 'currency';
        }
        // Rating
        if (preg_match('/(rating|score|stars|grade)/i', $h) && is_numeric($s) && (float)$s <= 10) {
            return 'rating';
        }
        // Boolean
        if (preg_match('/^(is_|has_|can_)/i', $h) || preg_match('/(flag|active|vip|newsletter|optin|subscribed|verified)/i', $h) || in_array(strtolower($s), ['true', 'false', 'yes', 'no', '1', '0', 'y', 'n'], true)) {
            return 'boolean';
        }
        // Textarea
        if (preg_match('/(note|notes|comment|comments|desc|description|bio|details|summary|address|message)/i', $h) || mb_strlen($s) > 60) {
            return 'textarea';
        }
        // Number
        if (preg_match('/(count|qty|quantity|number|num|age|headcount|size)/i', $h) || (is_numeric($s) && !str_starts_with($s, '0'))) {
            return 'number';
        }

        return 'text';
    }
}

$cfTypes = [
    'text'         => 'Text (Single line)',
    'textarea'     => 'Text Area (Multi-line notes)',
    'number'       => 'Number (Integer/Decimal)',
    'currency'     => 'Currency ($)',
    'date'         => 'Date (YYYY-MM-DD)',
    'boolean'      => 'Boolean (Yes / No)',
    'select'       => 'Dropdown Select (Auto-discover options)',
    'multi_select' => 'Multi-Select Options',
    'email'        => 'Email Address',
    'phone'        => 'Phone Number',
    'url'          => 'Website / Profile URL',
    'rating'       => 'Rating (1-5 Stars)',
];
@endphp

<div class="page-header" style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:1rem;">
    <div>
        <h1 class="page-title">{{ __('app.import.step_map', ['entity' => ucfirst($entity)]) }}</h1>
        <p class="page-subtitle">{{ __('app.import.map_subtitle', ['count' => $totalRows]) }}</p>
    </div>
    <div style="display:flex; align-items:center; gap:0.75rem; flex-wrap:wrap;">
        <a href="/settings/custom-fields" target="_blank" class="btn btn-secondary btn-sm" style="font-size:0.8rem;">
            ⚙️ {{ __('app.settings.custom_fields.title') }} ↗
        </a>
        <a href="/import?entity={{ $entity }}" class="btn btn-secondary btn-sm" style="font-size:0.8rem;">
            &larr; {{ __('app.common.cancel') }}
        </a>
    </div>
</div>

<form method="POST" action="/import/execute" id="importMappingForm">
    @csrf
    <input type="hidden" name="tmp_token" value="{{ $tmpToken }}">

    {{-- Toolbar & Quick Automation Bar --}}
    <div style="background:#ffffff; border:1px solid var(--border); border-radius:12px; box-shadow:var(--shadow-sm); padding:1rem 1.25rem; margin-bottom:1.5rem; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
        <div style="display:flex; align-items:center; gap:0.75rem; flex-wrap:wrap;">
            <span class="badge badge-primary" style="font-size:0.8rem; padding:5px 12px;">
                📊 {{ count($headers) }} CSV Columns
            </span>
            <span class="badge badge-secondary" style="font-size:0.8rem; padding:5px 12px; background:#f3e8ff; color:#7e22ce;">
                🏷️ {{ count($customFields) }} Existing Custom Fields
            </span>
            <span class="badge" style="font-size:0.8rem; padding:5px 12px; background:#ecfdf5; color:#047857;">
                ✨ {{ __('app.import.cf_types_covered') }}
            </span>
            <span style="font-size:0.82rem; color:var(--text-dim);">
                Map columns to standard CRM fields, existing custom fields, or create brand-new custom fields on the fly.
            </span>
        </div>

        <div style="display:flex; align-items:center; gap:0.5rem; flex-wrap:wrap;">
            <button type="button" class="btn btn-secondary btn-sm" onclick="autoMapSmartCustomFields()" style="font-size:0.82rem; font-weight:600; color:var(--primary); border-color:var(--primary-light);">
                ✨ {{ __('app.import.auto_map_cf') }}
            </button>
            <button type="button" class="btn btn-secondary btn-sm" onclick="resetMappings()" style="font-size:0.82rem;">
                ↺ {{ __('app.reports.reset_filters') }}
            </button>
        </div>
    </div>

    {{-- Column Mapping Grid --}}
    <div class="detail-card" style="margin-bottom:1.5rem;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.25rem; border-bottom:1px solid var(--border); padding-bottom:0.75rem; flex-wrap:wrap; gap:0.5rem;">
            <h3 style="font-size:1.1rem; font-weight:700; color:var(--text-main); margin:0;">
                {{ __('app.import.map_columns') }}
            </h3>
            <span style="font-size:0.8rem; color:var(--text-dim);">
                💡 Tip: Columns mapped to <strong>Dropdown Select</strong> will automatically extract unique options from your CSV rows!
            </span>
        </div>

        <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(320px, 1fr)); gap:1.25rem;">
            @foreach($headers as $idx => $header)
                @php
                    $cleanHeader     = strtolower(trim(preg_replace('/[^a-zA-Z0-9_]+/', '_', $header), '_'));
                    $headerNoCf      = preg_replace('/^cf_/', '', $cleanHeader);
                    $sampleVal       = $sampleRows[0][$idx] ?? '';
                    $smartDetectedCf = crxDetectSmartFieldType($header, $sampleVal);
                    $matchedField    = '__skip__';
                    $matchKind       = 'skipped';

                    // 1. Check existing custom fields first
                    foreach ($customFields as $cf) {
                        $cfCodeClean = strtolower(trim(preg_replace('/[^a-zA-Z0-9_]+/', '_', $cf['code']), '_'));
                        $cfNameClean = strtolower(trim(preg_replace('/[^a-zA-Z0-9_]+/', '_', $cf['name']), '_'));
                        if ($cleanHeader === "cf_{$cfCodeClean}" || $headerNoCf === $cfCodeClean || $headerNoCf === $cfNameClean) {
                            $matchedField = "cf_{$cf['code']}";
                            $matchKind    = 'custom';
                            break;
                        }
                    }

                    // 2. Check standard fields if not matched to custom field
                    if ($matchedField === '__skip__') {
                        foreach ($standardFields as $key => $label) {
                            $cleanKey   = strtolower(trim(preg_replace('/[^a-zA-Z0-9_]+/', '_', $key), '_'));
                            $cleanLabel = strtolower(trim(preg_replace('/[^a-zA-Z0-9_]+/', '_', $label), '_'));
                            if ($cleanHeader === $cleanKey || $headerNoCf === $cleanKey || str_contains($cleanLabel, $headerNoCf) || str_contains($headerNoCf, $cleanKey)) {
                                $matchedField = $key;
                                $matchKind    = 'standard';
                                break;
                            }
                        }
                    }

                    // 3. If CSV explicitly prefixed with cf_ or CF_, preselect smart auto-create
                    if ($matchedField === '__skip__' && str_starts_with($cleanHeader, 'cf_')) {
                        $matchedField = "__new_cf:{$smartDetectedCf}";
                        $matchKind    = 'new_cf';
                    }

                    $suggestedCfName = ucwords(str_replace(['_', 'cf '], ' ', $headerNoCf ?: $header));
                @endphp
                <div class="mapping-card" id="card_{{ $idx }}" 
                     data-smart-type="{{ $smartDetectedCf }}" 
                     data-header-clean="{{ $headerNoCf }}"
                     data-suggested-name="{{ $suggestedCfName }}"
                     style="background:var(--bg-main); border:1px solid {{ $matchKind === 'custom' ? '#c084fc' : ($matchKind === 'new_cf' ? '#34d399' : ($matchKind === 'standard' ? '#93c5fd' : 'var(--border)')) }}; border-radius:12px; padding:1.2rem; transition:all 0.15s ease; box-shadow:var(--shadow-xs);">
                    
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.6rem;">
                        <span class="badge badge-primary" style="font-family:monospace; font-size:0.75rem; font-weight:700;">
                            #{{ $idx + 1 }} CSV Col
                        </span>
                        <span id="badge_{{ $idx }}" class="badge" style="font-size:0.75rem; font-weight:600; {{ $matchKind === 'custom' ? 'background:#f3e8ff; color:#7e22ce;' : ($matchKind === 'new_cf' ? 'background:#ecfdf5; color:#047857;' : ($matchKind === 'standard' ? 'background:#eff6ff; color:#1d4ed8;' : 'background:#f1f5f9; color:#64748b;')) }}">
                            {{ $matchKind === 'custom' ? '🏷️ Custom Field' : ($matchKind === 'new_cf' ? '✨ New Field (' . ucfirst($smartDetectedCf) . ')' : ($matchKind === 'standard' ? '📋 Standard' : '🚫 Skipped')) }}
                        </span>
                    </div>

                    <div style="font-weight:700; font-size:0.95rem; color:var(--text-main); margin-bottom:0.5rem; word-break:break-all;">
                        "{{ $header }}"
                    </div>

                    <label class="form-label" style="font-size:0.75rem; color:var(--text-dim); margin-bottom:0.3rem;">
                        Maps to CRM Target Field:
                    </label>
                    <select name="map[{{ $idx }}]" id="select_{{ $idx }}" class="form-control mapping-select" style="font-size:0.85rem;" onchange="updateCardStatus({{ $idx }})">
                        <option value="__skip__">🚫 -- {{ __('app.import.skip_col') }} --</option>

                        <!-- Standard CRM Fields -->
                        <optgroup label="📋 {{ __('app.import.standard_fields') }}">
                            @foreach($standardFields as $fieldKey => $fieldLabel)
                                <option value="{{ $fieldKey }}" @selected($matchedField === $fieldKey)>
                                    {{ $fieldLabel }}
                                </option>
                            @endforeach
                        </optgroup>

                        <!-- Existing Workspace Custom Fields -->
                        @if(!empty($customFields))
                            <optgroup label="🏷️ {{ __('app.import.custom_fields') }} ({{ count($customFields) }})">
                                @foreach($customFields as $cf)
                                    <option value="cf_{{ $cf['code'] }}" @selected($matchedField === "cf_{$cf['code']}")>
                                        {{ $cf['name'] }} [cf_{{ $cf['code'] }}] ({{ ucfirst($cf['type']) }})
                                    </option>
                                @endforeach
                            </optgroup>
                        @endif

                        <!-- Auto-Create as New Custom Field (All 12 Custom Field Types Supported) -->
                        <optgroup label="✨ {{ __('app.import.quick_create_cf') }} (12 Types)">
                            @foreach($cfTypes as $cfTypeKey => $cfTypeLabel)
                                <option value="__new_cf:{{ $cfTypeKey }}" @selected($matchedField === "__new_cf:{$cfTypeKey}")>
                                    ➕ Create as Custom Field: {{ $cfTypeLabel }}
                                </option>
                            @endforeach
                        </optgroup>
                    </select>

                    {{-- Inline Dynamic Custom Field Configuration Drawer --}}
                    <div id="cf_box_{{ $idx }}" style="{{ str_starts_with($matchedField, '__new_cf') ? 'display:block;' : 'display:none;' }} margin-top:0.75rem; padding:0.75rem; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px;">
                        <div style="font-size:0.75rem; font-weight:700; color:#166534; margin-bottom:0.4rem; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:4px;">
                            <span>⚙️ {{ __('app.import.cf_settings') }}</span>
                            <span id="cf_code_badge_{{ $idx }}" style="font-family:monospace; font-size:0.72rem; color:#15803d; background:#dcfce7; padding:2px 6px; border-radius:4px;">
                                cf_{{ $headerNoCf ?: 'field' }}
                            </span>
                        </div>
                        <div style="display:flex; flex-direction:column; gap:0.4rem;">
                            <div>
                                <label style="font-size:0.7rem; color:#166534; font-weight:600; margin-bottom:2px; display:block;">
                                    Custom Field Name / Label:
                                </label>
                                <input type="text" name="cf_custom_name[{{ $idx }}]" id="cf_name_{{ $idx }}" 
                                       value="{{ $suggestedCfName }}" 
                                       class="form-control form-control-sm" 
                                       style="font-size:0.8rem; background:#ffffff; height:32px;" 
                                       placeholder="e.g. VIP Tier"
                                       oninput="updateCfCodePreview({{ $idx }})">
                            </div>
                            <div id="cf_auto_opt_hint_{{ $idx }}" style="font-size:0.72rem; color:#15803d; background:#dcfce7; padding:4px 8px; border-radius:6px; {{ in_array($matchedField, ['__new_cf:select', '__new_cf:multi_select']) ? 'display:block;' : 'display:none;' }}">
                                💡 <em>{{ __('app.import.cf_options_auto') }}</em>
                            </div>
                        </div>
                    </div>

                    <div style="font-size:0.75rem; color:var(--text-dim); margin-top:0.6rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; background:#ffffff; padding:4px 8px; border-radius:6px; border:1px solid var(--border);">
                        <strong>Sample:</strong> <em>{{ $sampleVal !== '' ? $sampleVal : '—' }}</em>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Sample Data Preview --}}
    <div class="detail-card" style="margin-bottom:1.5rem;">
        <h3 style="font-size:1.1rem; font-weight:700; color:var(--text-main); margin-bottom:1rem;">
            Sample Data Preview (First {{ count($sampleRows) }} Rows)
        </h3>
        <div class="table-container" style="border:1px solid var(--border); box-shadow:var(--shadow-xs); overflow-x:auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        @foreach($headers as $h)
                            <th>{{ $h }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($sampleRows as $row)
                        <tr>
                            @foreach($row as $cell)
                                <td>{{ $cell }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div style="display:flex; gap:1rem; align-items:center; flex-wrap:wrap;">
        <button type="submit" class="btn btn-primary" style="font-size:0.95rem; padding:0.75rem 2rem; font-weight:600;">
            🚀 {{ __('app.import.confirm_import', ['count' => $totalRows]) }} &rarr;
        </button>
        <a href="/import?entity={{ $entity }}" class="btn btn-secondary" style="font-size:0.95rem; padding:0.75rem 1.5rem;">
            {{ __('app.common.cancel') }}
        </a>
    </div>
</form>

<script>
function updateCardStatus(idx) {
    const sel = document.getElementById('select_' + idx);
    const badge = document.getElementById('badge_' + idx);
    const card = document.getElementById('card_' + idx);
    const cfBox = document.getElementById('cf_box_' + idx);
    const optHint = document.getElementById('cf_auto_opt_hint_' + idx);
    const val = sel.value;

    if (val.startsWith('cf_')) {
        badge.textContent = '🏷️ Custom Field';
        badge.style.background = '#f3e8ff';
        badge.style.color = '#7e22ce';
        card.style.borderColor = '#c084fc';
        if (cfBox) cfBox.style.display = 'none';
    } else if (val.startsWith('__new_cf:')) {
        const cfType = val.split(':')[1] || 'text';
        badge.textContent = '✨ New Field (' + cfType.charAt(0).toUpperCase() + cfType.slice(1) + ')';
        badge.style.background = '#ecfdf5';
        badge.style.color = '#047857';
        card.style.borderColor = '#34d399';
        if (cfBox) {
            cfBox.style.display = 'block';
            if (optHint) {
                optHint.style.display = (cfType === 'select' || cfType === 'multi_select') ? 'block' : 'none';
            }
        }
    } else if (val === '__skip__') {
        badge.textContent = '🚫 Skipped';
        badge.style.background = '#f1f5f9';
        badge.style.color = '#64748b';
        card.style.borderColor = 'var(--border)';
        if (cfBox) cfBox.style.display = 'none';
    } else {
        badge.textContent = '📋 Standard';
        badge.style.background = '#eff6ff';
        badge.style.color = '#1d4ed8';
        card.style.borderColor = '#93c5fd';
        if (cfBox) cfBox.style.display = 'none';
    }
}

function updateCfCodePreview(idx) {
    const nameInput = document.getElementById('cf_name_' + idx);
    const codeBadge = document.getElementById('cf_code_badge_' + idx);
    if (!nameInput || !codeBadge) return;

    let clean = nameInput.value.toLowerCase().trim().replace(/[^a-z0-9_]+/g, '_').replace(/^_+|_+$/g, '');
    if (!clean) clean = 'field';
    codeBadge.textContent = 'cf_' + clean;
}

function autoMapSmartCustomFields() {
    const selects = document.querySelectorAll('.mapping-select');
    selects.forEach((sel, idx) => {
        if (sel.value === '__skip__') {
            const card = document.getElementById('card_' + idx);
            const smartType = card.getAttribute('data-smart-type') || 'text';
            sel.value = '__new_cf:' + smartType;
            updateCardStatus(idx);
        }
    });
}

function resetMappings() {
    window.location.reload();
}
</script>
@endsection
