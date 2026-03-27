@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Configura autenticazione a due fattori</h5>
                </div>
                <div class="card-body p-4">
                    <p>Scansiona questo QR code con <strong>Google Authenticator</strong> o <strong>Microsoft Authenticator</strong>:</p>

                    <div class="text-center my-4">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ urlencode($qrUrl) }}" alt="QR Code 2FA" style="border:4px solid #333; border-radius:8px;">
                    </div>

                    <p class="text-muted" style="font-size:0.85rem;">Oppure inserisci manualmente questo codice nell'app:</p>
                    <div class="alert alert-secondary text-center" style="font-family:monospace; font-size:1.1rem; letter-spacing:0.15em;">
                        {{ $secret }}
                    </div>

                    @if($errors->any())
                        <div class="alert alert-danger py-2">{{ $errors->first() }}</div>
                    @endif

                    <form method="POST" action="{{ route('admin.2fa.confirm') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Inserisci il codice a 6 cifre per confermare:</label>
                            <input type="text" name="code" class="form-control form-control-lg text-center"
                                   placeholder="000000" maxlength="6" autofocus autocomplete="one-time-code"
                                   style="font-size:1.3rem; letter-spacing:0.3em;">
                        </div>
                        <button type="submit" class="btn btn-success btn-lg w-100">Attiva 2FA</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
