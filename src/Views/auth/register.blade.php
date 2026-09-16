@extends('layouts.auth')

@section('title', __('app.auth.sign_up'))

@section('content')
<form method="POST" action="/register">
    @csrf
    <div class="form-group">
        <label class="form-label" for="name">{{ __('app.auth.full_name') }}</label>
        <input type="text" id="name" name="name" class="form-control" required placeholder="Alex Mercer">
    </div>

    <div class="form-group">
        <label class="form-label" for="email">{{ __('app.auth.email_address') }}</label>
        <input type="email" id="email" name="email" class="form-control" required placeholder="alex@company.com">
    </div>

    <div class="form-group">
        <label class="form-label" for="workspace_name">{{ __('app.common.workspace') }}</label>
        <input type="text" id="workspace_name" name="workspace_name" class="form-control" required placeholder="Acme Global">
    </div>

    <div class="form-group">
        <label class="form-label" for="password">{{ __('app.auth.password') }}</label>
        <input type="password" id="password" name="password" class="form-control" required placeholder="••••••••">
    </div>

    <button type="submit" class="btn btn-primary" style="width:100%;margin-top:0.75rem;padding:0.7rem;">
        {{ __('app.auth.sign_up') }}
    </button>

    <div class="auth-footer">
        {{ __('app.auth.already_have_account') }} <a href="/login">{{ __('app.auth.sign_in') }}</a>
    </div>
</form>
@endsection
