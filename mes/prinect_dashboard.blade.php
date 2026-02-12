@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Dashboard Prinect</h2>

    @if(isset($devices['devices']) && count($devices['devices']) > 0)
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Stato</th>
                    <th>Attività</th>
                    <th>Velocità</th>
                    <th>Totale fogli</th>
                    <th>Job</th>
                    <th>Workstep</th>
                </tr>
            </thead>
            <tbody>
                @foreach($devices['devices'] as $device)
                    <tr>
                        <td>{{ $device['id'] }}</td>
                        <td>{{ $device['name'] }}</td>
                        <td>{{ $device['deviceStatus']['status'] ?? '-' }}</td>
                        <td>{{ $device['deviceStatus']['activity'] ?? '-' }}</td>
                        <td>{{ $device['deviceStatus']['speed'] ?? '-' }}</td>
                        <td>{{ $device['deviceStatus']['totalizer'] ?? '-' }}</td>
                        <td>{{ $device['deviceStatus']['workstep']['job']['name'] ?? '-' }}</td>
                        <td>{{ $device['deviceStatus']['workstep']['name'] ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p>Nessun dispositivo trovato.</p>
    @endif
</div>
@endsection