@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow">
                <div class="card-header bg-dark text-white text-center">
                    <h5 class="mb-0">Verifica a due fattori</h5>
                </div>
                <div class="card-body p-4">
                    <p class="text-muted text-center mb-3">Inserisci il codice a 6 cifre dall'app Authenticator oppure un codice di recupero.</p>

                    @if($errors->any())
                        <div class="alert alert-danger py-2">{{ $errors->first() }}</div>
                    @endif

                    <form method="POST" action="{{ route('admin.2fa.verify') }}">
                        @csrf
                        <div class="mb-3">
                            <input type="text" name="code" class="form-control form-control-lg text-center"
                                   placeholder="000000" maxlength="20" autofocus autocomplete="one-time-code"
                                   style="font-size:1.5rem; letter-spacing:0.3em;">
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg w-100">Verifica</button>
                    </form>

                    <p class="text-muted text-center mt-3" style="font-size:0.85rem;">
                        Questo dispositivo verra ricordato per sempre dopo la verifica.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
