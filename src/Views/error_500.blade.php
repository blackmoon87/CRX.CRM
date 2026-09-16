@extends('layouts.auth')

@section('title', '500 - Server Error')

@section('content')
<div style="text-align: center; padding: 20px 10px;">
    <div style="font-size: 56px; font-weight: 800; color: #e11d48; line-height: 1; margin-bottom: 12px;">500</div>
    <h2 style="font-size: 20px; font-weight: 700; margin-bottom: 8px; color: #0f172a;">Application Error</h2>
    <p style="font-size: 14px; color: #64748b; margin-bottom: 24px;">
        {{ $message ?? 'An unexpected error occurred. Please try again or contact the administrator.' }}
    </p>
    <div style="display: flex; gap: 12px; justify-content: center;">
        <a href="/dashboard" class="btn btn-primary" style="display: inline-flex; align-items: center; justify-content: center; text-decoration: none; padding: 10px 20px; border-radius: 8px; font-weight: 600;">
            Return to Dashboard
        </a>
    </div>
</div>
@endsection
