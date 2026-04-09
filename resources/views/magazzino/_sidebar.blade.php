{{-- Sidebar Magazzino --}}
<div class="mes-sidebar-section">
    <div class="mes-sidebar-section-label">Magazzino</div>
    <a href="{{ route('magazzino.dashboard', ['op_token' => request('op_token')]) }}" class="mes-sidebar-item {{ request()->routeIs('magazzino.dashboard') ? 'active' : '' }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
        Dashboard
    </a>
    <a href="{{ route('magazzino.carico', ['op_token' => request('op_token')]) }}" class="mes-sidebar-item {{ request()->routeIs('magazzino.carico') ? 'active' : '' }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
        Registra Bolla
    </a>
    <a href="{{ route('magazzino.prelievo', ['op_token' => request('op_token')]) }}" class="mes-sidebar-item {{ request()->routeIs('magazzino.prelievo') ? 'active' : '' }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Prelievo
    </a>
    <a href="{{ route('magazzino.articoli', ['op_token' => request('op_token')]) }}" class="mes-sidebar-item {{ request()->routeIs('magazzino.articoli') ? 'active' : '' }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><rect x="4" y="3" width="16" height="6" rx="1"/></svg>
        Articoli
    </a>
    <a href="{{ route('magazzino.giacenze', ['op_token' => request('op_token')]) }}" class="mes-sidebar-item {{ request()->routeIs('magazzino.giacenze') ? 'active' : '' }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
        Giacenze
    </a>
<a href="{{ route('magazzino.movimenti', ['op_token' => request('op_token')]) }}" class="mes-sidebar-item {{ request()->routeIs('magazzino.movimenti') ? 'active' : '' }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
        Movimenti
    </a>
    <a href="{{ route('magazzino.fabbisogno', ['op_token' => request('op_token')]) }}" class="mes-sidebar-item {{ request()->routeIs('magazzino.fabbisogno') ? 'active' : '' }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H2v7l6.29 6.29c.94.94 2.48.94 3.42 0l4.58-4.58c.94-.94.94-2.48 0-3.42L9 5z"/><path d="M6 9h.01"/></svg>
        Fabbisogno
    </a>
    <a href="{{ route('magazzino.ordiniAcquisto', ['op_token' => request('op_token')]) }}" class="mes-sidebar-item {{ request()->routeIs('magazzino.ordiniAcquisto') ? 'active' : '' }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
        Ordini Acquisto
    </a>
    <a href="{{ route('magazzino.alert', ['op_token' => request('op_token')]) }}" class="mes-sidebar-item {{ request()->routeIs('magazzino.alert') ? 'active' : '' }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        Alert Soglia
    </a>
    <a href="{{ route('magazzino.scanner', ['op_token' => request('op_token')]) }}" class="mes-sidebar-item {{ request()->routeIs('magazzino.scanner') ? 'active' : '' }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
        Scanner QR
    </a>
</div>
