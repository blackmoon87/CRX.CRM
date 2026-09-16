@extends('layouts.main')

@section('content')
<div class="content-header" style="margin-bottom: 2rem;">
    <div>
        <h1 style="font-size: 1.6rem; font-weight: 800; color: var(--text-main); margin-bottom: 0.35rem;">
            📅 {{ __('app.settings.booking.title') }}
        </h1>
        <p style="color: var(--text-dim); font-size: 0.95rem;">
            {{ __('app.settings.booking.subtitle') }}
        </p>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 2rem;">
    <!-- Settings Form Card -->
    <div style="background: #FFFFFF; border: 1px solid var(--border); border-radius: 12px; box-shadow: var(--shadow-sm); padding: 2rem;">
        <h3 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 1.5rem; color: var(--text-main);">
            {{ __('app.settings.booking.preferences') }}
        </h3>

        <form method="POST" action="/settings/booking">
            @csrf

            <!-- Public Link Slug -->
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-size: 0.82rem; font-weight: 700; text-transform: uppercase; color: var(--text-dim); margin-bottom: 0.5rem;">
                    {{ __('app.settings.booking.slug') }} *
                </label>
                <div style="display: flex; align-items: center; border: 1px solid var(--border); border-radius: 8px; overflow: hidden; background: #F8FAFC;">
                    <span style="padding: 0.75rem 1rem; color: var(--text-dim); font-size: 0.9rem; font-weight: 600; border-right: 1px solid var(--border);">
                        {{ url('/book') }}/
                    </span>
                    <input type="text" name="slug" value="{{ $setting['slug'] }}" required style="flex: 1; border: none; padding: 0.75rem 1rem; font-size: 0.95rem; font-weight: 600; outline: none; background: #FFFFFF;">
                </div>
            </div>

            <!-- Meeting Title & Description -->
            <div style="margin-bottom: 1.25rem;">
                <label style="display: block; font-size: 0.82rem; font-weight: 700; text-transform: uppercase; color: var(--text-dim); margin-bottom: 0.5rem;">
                    {{ __('app.settings.booking.meeting_title') }} *
                </label>
                <input type="text" name="title" value="{{ $setting['title'] }}" required style="width: 100%; padding: 0.7rem 0.9rem; border: 1px solid var(--border); border-radius: 8px; font-size: 0.95rem;">
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-size: 0.82rem; font-weight: 700; text-transform: uppercase; color: var(--text-dim); margin-bottom: 0.5rem;">
                    {{ __('app.common.details') }}
                </label>
                <textarea name="description" rows="3" style="width: 100%; padding: 0.7rem 0.9rem; border: 1px solid var(--border); border-radius: 8px; font-size: 0.9rem; resize: vertical;">{{ $setting['description'] }}</textarea>
            </div>

            <!-- Duration & Working Hours -->
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; margin-bottom: 1.75rem;">
                <div>
                    <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-dim); margin-bottom: 0.4rem;">
                        {{ __('app.settings.booking.duration') }}
                    </label>
                    <select name="duration_minutes" style="width: 100%; padding: 0.65rem 0.85rem; border: 1px solid var(--border); border-radius: 8px; font-size: 0.9rem; font-weight: 600;">
                        <option value="15" {{ $setting['duration_minutes'] == 15 ? 'selected' : '' }}>15</option>
                        <option value="30" {{ $setting['duration_minutes'] == 30 ? 'selected' : '' }}>30</option>
                        <option value="45" {{ $setting['duration_minutes'] == 45 ? 'selected' : '' }}>45</option>
                        <option value="60" {{ $setting['duration_minutes'] == 60 ? 'selected' : '' }}>60</option>
                    </select>
                </div>

                <div>
                    <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-dim); margin-bottom: 0.4rem;">
                        Start
                    </label>
                    <input type="time" name="working_hours_start" value="{{ $setting['working_hours_start'] }}" style="width: 100%; padding: 0.65rem 0.85rem; border: 1px solid var(--border); border-radius: 8px; font-size: 0.9rem; font-weight: 600;">
                </div>

                <div>
                    <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-dim); margin-bottom: 0.4rem;">
                        End
                    </label>
                    <input type="time" name="working_hours_end" value="{{ $setting['working_hours_end'] }}" style="width: 100%; padding: 0.65rem 0.85rem; border: 1px solid var(--border); border-radius: 8px; font-size: 0.9rem; font-weight: 600;">
                </div>
            </div>

            <!-- Active Toggle -->
            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 2rem;">
                <input type="checkbox" id="is_active" name="is_active" value="1" {{ $setting['is_active'] ? 'checked' : '' }} style="width: 18px; height: 18px; cursor: pointer;">
                <label for="is_active" style="font-weight: 700; color: var(--text-main); cursor: pointer;">
                    {{ __('app.common.active') }}
                </label>
            </div>

            <button type="submit" class="btn btn-primary" style="padding: 0.85rem 1.75rem; font-size: 0.95rem; font-weight: 800;">
                💾 {{ __('app.common.save') }}
            </button>
        </form>
    </div>

    <!-- Live Preview & Share Card -->
    <div>
        <div style="background: #F8FAFC; border: 1px solid var(--border); border-radius: 12px; box-shadow: var(--shadow-sm); padding: 1.75rem; margin-bottom: 1.5rem;">
            <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--primary); letter-spacing: 0.05em; margin-bottom: 0.5rem;">
                🔗 {{ __('app.entities.quotes.copy_link') }}
            </div>
            <p style="font-size: 0.88rem; color: var(--text-dim); margin-bottom: 1.25rem;">
                {{ __('app.settings.booking.subtitle') }}
            </p>

            <div style="background: #FFFFFF; border: 1px solid var(--border); border-radius: 8px; padding: 0.75rem; font-family: monospace; font-size: 0.88rem; color: #0F172A; word-break: break-all; margin-bottom: 1rem;">
                {{ url('/book/' . $setting['slug']) }}
            </div>

            <div style="display: flex; gap: 0.75rem;">
                <button type="button" class="btn btn-secondary" onclick="navigator.clipboard.writeText('{{ url('/book/' . $setting['slug']) }}'); this.textContent = '✓ Copied!'; setTimeout(() => this.textContent = '📋 {{ __('app.entities.quotes.copy_link') }}', 2000);" style="flex: 1; font-size: 0.85rem;">
                    📋 {{ __('app.entities.quotes.copy_link') }}
                </button>
                <a href="/book/{{ $setting['slug'] }}" target="_blank" class="btn btn-primary" style="font-size: 0.85rem;">
                    ↗ {{ __('app.common.view') }}
                </a>
            </div>
        </div>

        <div style="background: #EFF6FF; border: 1.5px solid #BFDBFE; border-radius: 12px; padding: 1.25rem;">
            <div style="font-weight: 800; color: #1E40AF; margin-bottom: 0.4rem; font-size: 0.9rem;">
                ⚡ {{ __('app.settings.booking.title') }}
            </div>
            <p style="font-size: 0.82rem; color: #3B82F6; line-height: 1.6; margin: 0;">
                {{ __('app.settings.booking.subtitle') }}
            </p>
        </div>
    </div>
</div>
@endsection
