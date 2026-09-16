@extends('layouts.main')

@section('title', __('app.entities.tasks.create_title'))

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('app.entities.tasks.create_title') }}</h1>
        <p class="page-subtitle">{{ __('app.entities.tasks.create_sub') }}</p>
    </div>
    <div>
        <a href="/tasks" class="btn btn-secondary">&larr; {{ __('app.common.back') }}</a>
    </div>
</div>

<div class="detail-card" style="max-width:760px; background:#FFFFFF; border:1px solid var(--border); border-radius:12px; box-shadow:var(--shadow-sm); padding:1.75rem;">
    <form method="POST" action="/tasks">
        @csrf
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.25rem;">
            
            <!-- Title -->
            <div class="form-group" style="grid-column: span 2;">
                <label class="form-label" for="title" style="font-weight:700; color:var(--text-main);">{{ __('app.entities.tasks.task_title') }} *</label>
                <input type="text" id="title" name="title" class="form-control" required placeholder="e.g. Schedule Executive Onboarding Kickoff" style="font-size:1rem; font-weight:600;">
            </div>

            <!-- Priority -->
            <div class="form-group">
                <label class="form-label" for="priority" style="font-weight:700; color:var(--text-main);">{{ __('app.entities.tasks.priority') }}</label>
                <select id="priority" name="priority" class="form-control">
                    <option value="urgent">🔴 Urgent</option>
                    <option value="high">🟠 High</option>
                    <option value="medium" selected>🔵 Medium</option>
                    <option value="low">⚪ Low</option>
                </select>
            </div>

            <!-- Due Date -->
            <div class="form-group">
                <label class="form-label" for="due_date" style="font-weight:700; color:var(--text-main);">{{ __('app.entities.tasks.due_date') }}</label>
                <input type="date" id="due_date" name="due_date" class="form-control" value="{{ date('Y-m-d', strtotime('+1 day')) }}">
            </div>

            <!-- Associated Entity Type -->
            <div class="form-group">
                <label class="form-label" for="entity_type" style="font-weight:700; color:var(--text-main);">{{ __('app.entities.tasks.related_to') }}</label>
                <select id="entity_type" name="entity_type" class="form-control" onchange="updateEntityDropdown(this.value)">
                    <option value="">-- {{ __('app.common.none') ?? 'None' }} --</option>
                    <option value="opportunities">💼 {{ __('app.entities.opportunities.title') }}</option>
                    <option value="people">👤 {{ __('app.entities.people.title') }}</option>
                    <option value="companies">🏢 {{ __('app.entities.companies.title') }}</option>
                </select>
            </div>

            <!-- Associated Entity ID -->
            <div class="form-group">
                <label class="form-label" for="entity_id" style="font-weight:700; color:var(--text-main);">{{ __('app.common.select_all') }}</label>
                <select id="entity_id" name="entity_id" class="form-control">
                    <option value="">-- {{ __('app.common.none') ?? 'None' }} --</option>
                </select>
            </div>

            <!-- Description -->
            <div class="form-group" style="grid-column: span 2;">
                <label class="form-label" for="description" style="font-weight:700; color:var(--text-main);">{{ __('app.entities.tasks.instructions') }}</label>
                <textarea id="description" name="description" class="form-control" rows="4" placeholder="Add specific checklist items, call agenda, or meeting notes..."></textarea>
            </div>
        </div>

        {{-- Dynamic Custom Fields --}}
        @include('partials.custom_fields_form', ['customFields' => $customFields ?? [], 'values' => []])

        <div style="margin-top:1.5rem; display:flex; justify-content:flex-end; gap:0.75rem; border-top:1px solid var(--border); padding-top:1rem;">
            <a href="/tasks" class="btn btn-secondary">{{ __('app.common.cancel') }}</a>
            <button type="submit" class="btn btn-primary" style="padding:10px 24px;">
                ✅ {{ __('app.entities.tasks.add_new') }}
            </button>
        </div>
    </form>
</div>

<script>
const entityOptions = {
    opportunities: [
        @foreach($opportunities as $opp)
            { id: {{ $opp['id'] }}, name: "{{ addslashes($opp['name']) }} (${{ number_format((float)$opp['amount'], 0) }})" },
        @endforeach
    ],
    people: [
        @foreach($people as $person)
            { id: {{ $person['id'] }}, name: "{{ addslashes($person['first_name'] . ' ' . ($person['last_name'] ?? '')) }}" },
        @endforeach
    ],
    companies: [
        @foreach($companies as $company)
            { id: {{ $company['id'] }}, name: "{{ addslashes($company['name']) }}" },
        @endforeach
    ]
};

function updateEntityDropdown(type) {
    const select = document.getElementById('entity_id');
    select.innerHTML = '<option value="">-- {{ __('app.common.none') ?? 'None' }} --</option>';
    
    if (entityOptions[type]) {
        entityOptions[type].forEach(item => {
            const opt = document.createElement('option');
            opt.value = item.id;
            opt.textContent = item.name;
            select.appendChild(opt);
        });
    }
}
</script>
@endsection
