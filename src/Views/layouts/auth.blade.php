<!DOCTYPE html>
<html lang="{{ current_locale() }}" dir="{{ is_rtl() ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', __('app.auth.sign_in')) — {{ config('app.name', 'CRX CRM') }}</title>
    <link rel="stylesheet" href="{{ asset('css/reset.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: var(--bg);
            position: relative;
        }
        .auth-lang-switcher {
            position: absolute;
            top: 20px;
            right: 24px;
            display: flex;
            align-items: center;
            gap: 4px;
            background: #ffffff;
            padding: 3px 6px;
            border-radius: var(--r-sm);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-xs);
        }
        [dir="rtl"] .auth-lang-switcher {
            right: auto;
            left: 24px;
        }
        .auth-lang-link {
            text-decoration: none;
            font-size: 11.5px;
            font-weight: 600;
            color: var(--text-dim);
            padding: 3px 7px;
            border-radius: 5px;
            transition: all 0.12s ease;
        }
        .auth-lang-link.active {
            background: var(--primary);
            color: #ffffff;
        }
        .auth-card {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: var(--r);
            width: 100%;
            max-width: 440px;
            padding: 36px 32px;
            box-shadow: 0 20px 25px -5px rgba(15, 23, 42, 0.08), 0 8px 10px -6px rgba(15, 23, 42, 0.04);
        }
        .auth-header {
            text-align: center;
            margin-bottom: 24px;
        }
        .auth-logo {
            display: inline-flex;
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #4f46e5, #6366f1);
            border-radius: 12px;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: 800;
            color: #fff;
            margin-bottom: 12px;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.35);
        }
        .auth-footer {
            margin-top: 20px;
            text-align: center;
            font-size: 13px;
            color: var(--text-dim);
            font-weight: 500;
        }
        .auth-footer a {
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
        }
        .auth-footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-lang-switcher">
            <a href="/locale/en" class="auth-lang-link {{ current_locale() === 'en' ? 'active' : '' }}" title="English">🇺🇸 EN</a>
            <a href="/locale/ar" class="auth-lang-link {{ current_locale() === 'ar' ? 'active' : '' }}" title="العربية">🇸🇦 AR</a>
            <a href="/locale/fr" class="auth-lang-link {{ current_locale() === 'fr' ? 'active' : '' }}" title="Français">🇫🇷 FR</a>
        </div>
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-logo">C</div>
                <h1 style="font-size:1.35rem;font-weight:700;">{{ __('app.app_name') }}</h1>
                <p style="color:var(--text-muted);font-size:0.85rem;margin-top:0.25rem;">{{ __('app.tagline') }}</p>
            </div>

            @flash('error')
                <div class="alert alert-danger" style="margin-bottom:1.5rem;">
                    <span>{{ $flashMsg }}</span>
                </div>
            @endflash

            @flash('success')
                <div class="alert alert-success" style="margin-bottom:1.5rem;">
                    <span>{{ $flashMsg }}</span>
                </div>
            @endflash

            @yield('content')
        </div>
    </div>
    <script src="{{ asset('js/spartan-dialog.js') }}"></script>
</body>
</html>
