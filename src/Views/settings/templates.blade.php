@extends('layouts.main')

@section('title', __('app.settings.templates.title'))

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('app.settings.templates.title') }}</h1>
        <p class="page-subtitle">{{ __('app.settings.templates.subtitle') }}</p>
    </div>
</div>

<div class="detail-layout" style="grid-template-columns: 380px 1fr; gap: 1.5rem;">
    <div>
        <div class="detail-card">
            <h3 style="font-size:1rem;font-weight:700;margin-bottom:1rem;border-bottom:1px solid var(--border);padding-bottom:0.5rem;">
                {{ __('app.settings.templates.create') }}
            </h3>
            <form method="POST" action="/settings/templates">
                @csrf
                <div class="form-group">
                    <label class="form-label" for="name">{{ __('app.settings.templates.snippet_title') }} *</label>
                    <input type="text" id="name" name="name" class="form-control" required placeholder="e.g. Post-Demo Follow-up">
                </div>

                <div class="form-group">
                    <label class="form-label" for="category">{{ __('app.settings.templates.category') }}</label>
                    <select id="category" name="category" class="form-control">
                        <option value="sales">Sales & Outreach</option>
                        <option value="follow_up">Meeting Follow-up</option>
                        <option value="closing">Closing & Proposals</option>
                        <option value="support">Customer Support</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="subject">{{ __('app.bulk.email_modal.subject') }} ({{ __('app.common.optional') }})</label>
                    <input type="text" id="subject" name="subject" class="form-control" placeholder="Quick question regarding @{{company_name}}">
                </div>

                <div class="form-group">
                    <label class="form-label" for="body">{{ __('app.bulk.email_modal.body') }} *</label>
                    <textarea id="body" name="body" class="form-control" rows="8" required placeholder="Hi @{{first_name}},&#10;&#10;Following up on our call regarding @{{deal_name}}..."></textarea>
                </div>

                <div style="background:var(--bg-main);border:1px solid var(--border);padding:0.75rem;margin-bottom:1rem;border-radius:var(--r-sm);font-size:0.75rem;">
                    <strong>Placeholders:</strong><br>
                    <code>@{{first_name}}</code>, <code>@{{last_name}}</code>, <code>@{{company_name}}</code>, <code>@{{deal_name}}</code>, <code>@{{deal_amount}}</code>, <code>@{{user_name}}</code>
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%;">
                    {{ __('app.common.save') }}
                </button>
            </form>
        </div>
    </div>

    <div>
        <div class="detail-card">
            <h3 style="font-size:1rem;font-weight:700;margin-bottom:1rem;">
                {{ __('app.settings.templates.title') }} ({{ count($templates) }})
            </h3>

            @if(empty($templates))
                <div style="text-align:center;padding:2rem;color:var(--text-dim);">
                    {{ __('app.common.no_records') }}
                </div>
            @else
                <div style="display:flex;flex-direction:column;gap:1rem;">
                    @foreach($templates as $tpl)
                        <div style="background:#ffffff;border:1px solid var(--border);box-shadow:var(--shadow-xs);padding:1rem;border-radius:var(--r-sm);">
                            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:0.5rem;">
                                <div>
                                    <span class="badge badge-primary" style="font-size:0.7rem;text-transform:uppercase;">{{ $tpl['category'] }}</span>
                                    <h4 style="font-size:1rem;font-weight:700;margin:0.25rem 0;color:var(--text-main);">{{ $tpl['name'] }}</h4>
                                    @if(!empty($tpl['subject']))
                                        <div style="font-size:0.8rem;color:var(--text-muted);"><strong>Subject:</strong> {{ $tpl['subject'] }}</div>
                                    @endif
                                </div>
                                <form method="POST" action="/settings/templates/{{ $tpl['id'] }}/delete" data-confirm="Are you sure you want to delete this snippet?" style="margin:0;">
                                    @csrf
                                    <button type="submit" class="btn btn-secondary btn-sm" style="color:var(--danger);">{{ __('app.common.delete') }}</button>
                                </form>
                            </div>
                            <pre style="background:var(--bg-surface);border:1px solid var(--border);padding:0.75rem;font-size:0.8rem;line-height:1.5;white-space:pre-wrap;font-family:inherit;margin:0;">{{ $tpl['body'] }}</pre>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
