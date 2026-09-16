@extends('layouts.main')

@section('content')
<div class="content-header" style="margin-bottom: 2rem;">
    <div>
        <h1 style="font-size: 1.6rem; font-weight: 800; color: var(--text-main); margin-bottom: 0.35rem;">
            📝 {{ __('app.settings.forms.title') }}
        </h1>
        <p style="color: var(--text-dim); font-size: 0.95rem;">
            {{ __('app.settings.forms.subtitle') }}
        </p>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2.5rem;">
    <!-- Create Form Card -->
    <div style="background: #FFFFFF; border: 1px solid var(--border); border-radius: 12px; box-shadow: var(--shadow-sm); padding: 1.75rem;">
        <h3 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 1.25rem; color: var(--text-main);">
            {{ __('app.settings.forms.create_new') }}
        </h3>

        <form method="POST" action="/settings/forms">
            @csrf

            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-dim); margin-bottom: 0.35rem;">
                    {{ __('app.settings.forms.form_name') }} *
                </label>
                <input type="text" name="name" required placeholder="e.g. Website Hero Form, Pricing Inquiries" style="width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--border); border-radius: 8px; font-size: 0.95rem;">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-dim); margin-bottom: 0.35rem;">
                        {{ __('app.settings.forms.header_title') }}
                    </label>
                    <input type="text" name="title" value="Get in Touch" style="width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--border); border-radius: 8px; font-size: 0.95rem;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-dim); margin-bottom: 0.35rem;">
                        {{ __('app.settings.forms.submit_btn') }}
                    </label>
                    <input type="text" name="button_text" value="Send Message" style="width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--border); border-radius: 8px; font-size: 0.95rem;">
                </div>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-dim); margin-bottom: 0.35rem;">
                    {{ __('app.settings.forms.success_message') }}
                </label>
                <input type="text" name="success_message" value="Thank you! Our executive team will reach out to you shortly." style="width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--border); border-radius: 8px; font-size: 0.95rem;">
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; font-weight: 700; padding: 0.75rem;">
                🚀 {{ __('app.settings.forms.generate') }}
            </button>
        </form>
    </div>

    <!-- Instructions / Value Proposition -->
    <div style="background: #F8FAFC; border: 1px solid var(--border); border-radius: 12px; box-shadow: var(--shadow-sm); padding: 1.75rem; display: flex; flex-direction: column; justify-content: space-between;">
        <div>
            <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--primary); letter-spacing: 0.05em; margin-bottom: 0.6rem;">
                🌐 Inbound Leads
            </div>
            <h3 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 1rem; color: var(--text-main);">
                {{ __('app.settings.forms.title') }}
            </h3>
            <p style="color: var(--text-dim); font-size: 0.9rem; line-height: 1.8;">
                {{ __('app.settings.forms.subtitle') }}
            </p>
        </div>

        <div style="background: #EFF6FF; border: 1px solid #BFDBFE; border-radius: 8px; padding: 1rem; margin-top: 1.5rem;">
            <span style="font-weight: 700; color: #1E40AF; font-size: 0.85rem;">🔒 CORS-Enabled Security:</span>
            <span style="color: #3B82F6; font-size: 0.82rem;"> Submissions accept standard AJAX or HTML POST requests from any domain.</span>
        </div>
    </div>
</div>

<!-- Forms List -->
<div style="background: #FFFFFF; border: 1px solid var(--border); border-radius: 12px; box-shadow: var(--shadow-sm); padding: 1.75rem;">
    <h3 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 1.25rem; color: var(--text-main);">
        {{ __('app.settings.forms.title') }} ({{ count($forms) }})
    </h3>

    @if(empty($forms))
        <div style="text-align: center; padding: 2.5rem; color: var(--text-dim); border: 1px dashed #E2E8F0; border-radius: 10px;">
            {{ __('app.common.no_records') }}
        </div>
    @else
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            @foreach($forms as $f)
                <div style="background: #F8FAFC; border: 1px solid var(--border); border-radius: 10px; padding: 1.25rem; display: flex; justify-content: space-between; align-items: center; gap: 1.5rem;">
                    <div>
                        <div style="display: flex; align-items: center; gap: 0.65rem; margin-bottom: 0.35rem;">
                            <span style="font-size: 1rem; font-weight: 700; color: var(--text-main);">{{ $f['name'] }}</span>
                            <span class="badge badge-success">{{ __('app.common.active') }}</span>
                        </div>
                        <div style="font-size: 0.82rem; color: var(--text-dim);">
                            Submissions: <strong style="color: var(--text-main);">{{ $f['submissions_count'] }} leads</strong> • Endpoint: <code>/api/v1/forms/{{ $f['uuid'] }}/submit</code>
                        </div>
                    </div>

                    <div style="display: flex; gap: 0.65rem;">
                        <button type="button" class="btn btn-secondary btn-sm" onclick="showEmbedModal('{{ $f['uuid'] }}', '{{ addslashes($f['name']) }}')">
                            📋 {{ __('app.settings.forms.embed_code') }}
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

<!-- Embed Modal -->
<div id="embed-modal" style="display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.5); backdrop-filter: blur(4px); z-index: 9999; align-items: center; justify-content: center; padding: 1rem;">
    <div style="background: #FFFFFF; border: 1px solid var(--border); border-radius: 16px; width: 100%; max-width: 620px; padding: 2rem; box-shadow: 0 25px 50px rgba(15,23,42,0.25);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
            <h3 id="modal-title" style="margin: 0; font-size: 1.2rem; font-weight: 800;">{{ __('app.settings.forms.embed_code') }}</h3>
            <button type="button" onclick="document.getElementById('embed-modal').style.display='none'" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-dim);">&times;</button>
        </div>

        <p style="font-size: 0.88rem; color: var(--text-dim); margin-bottom: 1rem;">
            {{ __('app.settings.forms.subtitle') }}
        </p>

        <textarea id="modal-code" rows="8" readonly style="width: 100%; font-family: monospace; font-size: 0.82rem; padding: 1rem; border: 1.5px solid var(--border); border-radius: 8px; background: #0F172A; color: #38BDF8; resize: none; margin-bottom: 1.25rem;"></textarea>

        <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
            <button type="button" class="btn btn-secondary" onclick="document.getElementById('embed-modal').style.display='none'">{{ __('app.common.close') }}</button>
            <button type="button" class="btn btn-primary" onclick="navigator.clipboard.writeText(document.getElementById('modal-code').value); this.textContent='✓ Copied!'; setTimeout(()=>this.textContent='{{ __('app.settings.forms.embed_code') }}', 2000);">
                {{ __('app.settings.forms.embed_code') }}
            </button>
        </div>
    </div>
</div>

<script>
function showEmbedModal(uuid, name) {
    const actionUrl = "{{ url('/api/v1/forms') }}/" + uuid + "/submit";
    const snippet = `<form method="POST" action="${actionUrl}">\n` +
        `  <input type="text" name="name" placeholder="Full Name" required />\n` +
        `  <input type="email" name="email" placeholder="Work Email" required />\n` +
        `  <input type="tel" name="phone" placeholder="Phone Number" />\n` +
        `  <input type="text" name="company" placeholder="Company Name" />\n` +
        `  <textarea name="message" placeholder="How can we help?"></textarea>\n` +
        `  <button type="submit">Submit</button>\n` +
        `</form>`;

    document.getElementById('modal-title').textContent = 'Embed: ' + name;
    document.getElementById('modal-code').value = snippet;
    document.getElementById('embed-modal').style.display = 'flex';
}
</script>
@endsection
