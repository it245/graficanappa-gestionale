@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">2FA attivato con successo!</h5>
                </div>
                <div class="card-body p-4">
                    <div class="alert alert-warning">
                        <strong>Salva questi codici di recupero!</strong> Se perdi l'accesso all'app Authenticator, puoi usare uno di questi codici al posto del codice a 6 cifre. Ogni codice funziona una sola volta.
                    </div>

                    <div class="bg-light p-3 rounded text-center" style="font-family:monospace; font-size:1.1rem; line-height:2;">
                        @foreach($recoveryCodes as $code)
                            {{ $code }}<br>
                        @endforeach
                    </div>

                    <p class="text-muted mt-3" style="font-size:0.85rem;">
                        Consiglio: fotografa questa pagina o copia i codici in un posto sicuro.
                    </p>

                    <a href="{{ route('admin.dashboard') }}" class="btn btn-primary w-100 mt-3">Vai alla Dashboard</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
