@extends('layouts.auth')

@section('title', __('app.auth.sign_in'))

@section('content')
<form method="POST" action="/login">
    @csrf
    <div class="form-group">
        <label class="form-label" for="email">{{ __('app.auth.email_address') }}</label>
        <input type="email" id="email" name="email" class="form-control" required placeholder="admin@crx.local" value="admin@crx.local">
    </div>

    <div class="form-group">
        <label class="form-label" for="password">{{ __('app.auth.password') }}</label>
        <input type="password" id="password" name="password" class="form-control" required placeholder="••••••••" value="password123">
    </div>

    <button type="submit" class="btn btn-primary" style="width:100%;margin-top:0.75rem;padding:0.7rem;">
        {{ __('app.auth.sign_in') }}
    </button>

    <div class="auth-footer">
        {{ __('app.auth.dont_have_account') }} <a href="/register">{{ __('app.auth.sign_up') }}</a>
    </div>
</form>
@endsection
