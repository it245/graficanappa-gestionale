@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4>Dispositivi fidati</h4>
                <a href="{{ route('admin.dashboard') }}" class="btn btn-sm btn-outline-secondary">Torna alla Dashboard</a>
            </div>

            @if(session('success'))
                <div class="alert alert-success py-2">{{ session('success') }}</div>
            @endif

            <div class="card shadow">
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Dispositivo</th>
                                <th>IP primo accesso</th>
                                <th>Ultimo utilizzo</th>
                                <th>Registrato il</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($devices as $d)
                            <tr>
                                <td><strong>{{ $d->device_name ?? 'Sconosciuto' }}</strong></td>
                                <td class="text-muted">{{ $d->ip_first_use }}</td>
                                <td>{{ $d->last_used_at ? \Carbon\Carbon::parse($d->last_used_at)->format('d/m/Y H:i') : '-' }}</td>
                                <td>{{ \Carbon\Carbon::parse($d->created_at)->format('d/m/Y') }}</td>
                                <td>
                                    <form method="POST" action="{{ route('admin.2fa.revokeDevice', $d->id) }}" onsubmit="return confirm('Revocare questo dispositivo?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Revoca</button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center text-muted py-3">Nessun dispositivo fidato registrato</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
