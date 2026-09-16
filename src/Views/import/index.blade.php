@extends('layouts.main')

@section('title', __('app.import.title'))

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('app.import.title') }}</h1>
        <p class="page-subtitle">{{ __('app.import.subtitle') }}</p>
    </div>
</div>

<div class="detail-layout" style="grid-template-columns: 1fr 1fr; gap: 1.5rem;">
    {{-- Import Card --}}
    <div class="detail-card">
        <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:1.25rem;">
            <div style="width:42px;height:42px;background:var(--primary-subtle);color:var(--primary);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.25rem;border:1px solid var(--border);">
                📥
            </div>
            <div>
                <h3 style="font-size:1.1rem;font-weight:700;color:var(--text-main);margin:0;">{{ __('app.import.wizard') }}</h3>
                <span style="font-size:0.8rem;color:var(--text-dim);">CSV Import</span>
            </div>
        </div>

        <form method="POST" action="/import/preview" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label class="form-label" for="targetEntity">{{ __('app.import.select_target') }} *</label>
                <select id="targetEntity" name="entity" class="form-control" onchange="updateTemplateLink(this.value)">
                    <option value="companies" @selected($target === 'companies')>{{ __('app.entities.companies.title') }}</option>
                    <option value="people" @selected($target === 'people')>{{ __('app.entities.people.title') }}</option>
                    <option value="opportunities" @selected($target === 'opportunities')>{{ __('app.entities.opportunities.title') }}</option>
                    <option value="tasks" @selected($target === 'tasks')>{{ __('app.entities.tasks.title') }}</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="csvFile">{{ __('app.import.choose_file') }} (.csv) *</label>
                <input type="file" id="csvFile" name="csv_file" class="form-control" accept=".csv,text/csv" required style="padding:0.5rem;">
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%;font-size:0.95rem;padding:0.75rem;">
                {{ __('app.import.start_import') }} &rarr;
            </button>
        </form>
    </div>

    {{-- Live Export Card --}}
    <div class="detail-card">
        <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:1.25rem;">
            <div style="width:42px;height:42px;background:#ecfdf5;color:#059669;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.25rem;border:1px solid #d1fae5;">
                📤
            </div>
            <div>
                <h3 style="font-size:1.1rem;font-weight:700;color:var(--text-main);margin:0;">{{ __('app.common.export_csv') }}</h3>
                <span style="font-size:0.8rem;color:var(--text-dim);">Download latest workspace datasets</span>
            </div>
        </div>

        <div style="display:flex;flex-direction:column;gap:0.75rem;">
            <div style="display:flex;justify-content:space-between;align-items:center;padding:0.85rem 1rem;background:var(--bg-main);border:1px solid var(--border);border-radius:8px;">
                <div>
                    <div style="font-weight:600;color:var(--text-main);">🏢 {{ __('app.entities.companies.title') }}</div>
                </div>
                <a href="/export/companies" class="btn btn-secondary btn-sm" download>
                    {{ __('app.common.export_csv') }}
                </a>
            </div>

            <div style="display:flex;justify-content:space-between;align-items:center;padding:0.85rem 1rem;background:var(--bg-main);border:1px solid var(--border);border-radius:8px;">
                <div>
                    <div style="font-weight:600;color:var(--text-main);">👤 {{ __('app.entities.people.title') }}</div>
                </div>
                <a href="/export/people" class="btn btn-secondary btn-sm" download>
                    {{ __('app.common.export_csv') }}
                </a>
            </div>

            <div style="display:flex;justify-content:space-between;align-items:center;padding:0.85rem 1rem;background:var(--bg-main);border:1px solid var(--border);border-radius:8px;">
                <div>
                    <div style="font-weight:600;color:var(--text-main);">💼 {{ __('app.entities.opportunities.title') }}</div>
                </div>
                <a href="/export/opportunities" class="btn btn-secondary btn-sm" download>
                    {{ __('app.common.export_csv') }}
                </a>
            </div>

            <div style="display:flex;justify-content:space-between;align-items:center;padding:0.85rem 1rem;background:var(--bg-main);border:1px solid var(--border);border-radius:8px;">
                <div>
                    <div style="font-weight:600;color:var(--text-main);">✅ {{ __('app.entities.tasks.title') }}</div>
                </div>
                <a href="/export/tasks" class="btn btn-secondary btn-sm" download>
                    {{ __('app.common.export_csv') }}
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
