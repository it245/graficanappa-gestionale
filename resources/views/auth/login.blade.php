@extends('layouts.app')

@section('content')
<style>
    body { background: linear-gradient(135deg, #f0f4f8 0%, #e2e8f0 100%); min-height: 100vh; }
    .login-wrap { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Inter, Roboto, sans-serif; }
    .login-card {
        background: #fff;
        border: none;
        border-radius: 16px;
        box-shadow: 0 2px 8px rgba(0,0,0,.04), 0 12px 32px rgba(0,0,0,.12);
        padding: 40px;
        max-width: 420px;
        width: 100%;
    }
    .login-brand { text-align: center; margin-bottom: 28px; }
    .login-brand .logo {
        font-size: 22px; font-weight: 700; color: #1f2937; letter-spacing: -0.3px;
    }
    .login-brand .tag {
        font-size: 13px; color: #6b7280; margin-top: 4px; letter-spacing: 0.3px; text-transform: uppercase;
    }
    .login-card h2 {
        font-size: 18px; font-weight: 600; color: #111827; margin-bottom: 24px; text-align: center;
    }
    .login-card .form-label {
        display: block; font-size: 13px; font-weight: 500; color: #374151; margin-bottom: 6px;
    }
    .login-card .form-control {
        width: 100%; border: 1px solid #e5e7eb; border-radius: 8px; padding: 11px 14px; font-size: 15px;
        background: #fafbfc; transition: border-color .2s cubic-bezier(.4,0,.2,1), background .2s, box-shadow .2s;
    }
    .login-card .form-control:focus {
        outline: none; border-color: #3b82f6; background: #fff;
        box-shadow: 0 0 0 3px rgba(59,130,246,.1);
    }
    .login-card .form-control.is-invalid { border-color: #ef4444; background: #fef2f2; }
    .login-card .field-group { margin-bottom: 18px; position: relative; }
    .login-card .pw-toggle {
        position: absolute; right: 10px; top: 32px; background: none; border: none; cursor: pointer;
        color: #6b7280; font-size: 13px; padding: 6px 8px;
    }
    .login-card .pw-toggle:hover { color: #1f2937; }
    .login-card .invalid-feedback { display: block; color: #dc2626; font-size: 12px; margin-top: 4px; }
    .login-card .row-remember { display: flex; align-items: center; justify-content: space-between; margin-bottom: 22px; font-size: 13px; }
    .login-card .row-remember label { color: #4b5563; cursor: pointer; }
    .login-card .row-remember input { margin-right: 6px; }
    .login-card .row-remember a { color: #3b82f6; text-decoration: none; font-weight: 500; }
    .login-card .row-remember a:hover { text-decoration: underline; }
    .login-card .btn-primary {
        width: 100%; background: #3b82f6; border: none; border-radius: 8px; padding: 12px;
        font-weight: 600; font-size: 14px; letter-spacing: 0.3px; color: #fff;
        cursor: pointer; transition: all .2s cubic-bezier(.4,0,.2,1);
        box-shadow: 0 2px 4px rgba(59,130,246,.15);
    }
    .login-card .btn-primary:hover {
        background: #2563eb; box-shadow: 0 8px 16px rgba(59,130,246,.25); transform: translateY(-1px);
    }
    .login-card .btn-primary:disabled { opacity: .6; cursor: wait; }
</style>

<div class="login-wrap">
    <div class="login-card">
        <div class="login-brand">
            <div class="logo">GRAFICA NAPPA</div>
            <div class="tag">MES — Sistema Produzione</div>
        </div>

        <h2>{{ __('Accedi') }}</h2>

        <form method="POST" action="{{ route('login') }}" id="loginForm">
            @csrf

            <div class="field-group">
                <label for="email" class="form-label">{{ __('Email') }}</label>
                <input id="email" type="email"
                       class="form-control @error('email') is-invalid @enderror"
                       name="email" value="{{ old('email') }}" required
                       autocomplete="email" autofocus
                       placeholder="nome@graficanappa.com">
                @error('email')
                    <span class="invalid-feedback" role="alert">{{ $message }}</span>
                @enderror
            </div>

            <div class="field-group">
                <label for="password" class="form-label">{{ __('Password') }}</label>
                <input id="password" type="password"
                       class="form-control @error('password') is-invalid @enderror"
                       name="password" required autocomplete="current-password"
                       placeholder="••••••••">
                <button type="button" class="pw-toggle" onclick="togglePw()" aria-label="Mostra/nascondi password">👁</button>
                @error('password')
                    <span class="invalid-feedback" role="alert">{{ $message }}</span>
                @enderror
            </div>

            <div class="row-remember">
                <label>
                    <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                    {{ __('Ricordami') }}
                </label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}">{{ __('Password dimenticata?') }}</a>
                @endif
            </div>

            <button type="submit" class="btn-primary" id="loginBtn">{{ __('Accedi') }}</button>
        </form>
    </div>
</div>

<script>
function togglePw() {
    var p = document.getElementById('password');
    p.type = p.type === 'password' ? 'text' : 'password';
}
document.getElementById('loginForm').addEventListener('submit', function() {
    var b = document.getElementById('loginBtn');
    b.disabled = true; b.textContent = 'Accesso in corso...';
});
</script>
@endsection
