@extends('layouts.mes')

@section('title', 'Demo Mossa 37 — Flowchart')

@section('content')
<div style="max-width:1400px; margin:0 auto; padding:24px;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <h1 style="margin:0;">Demo Mossa 37 — Flowchart</h1>
        <div style="display:flex; gap:8px;">
            <button onclick="prevSlide()" class="btn btn-outline-secondary">◀ Prev</button>
            <span id="slideLabel" style="align-self:center; font-weight:600;">1 / 4</span>
            <button onclick="nextSlide()" class="btn btn-outline-secondary">Next ▶</button>
            <button onclick="toggleFullscreen()" class="btn btn-primary">⛶ Fullscreen</button>
        </div>
    </div>

    <div id="slideContainer" style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:24px; min-height:80vh; display:flex; flex-direction:column; align-items:center; justify-content:flex-start;">
        <h2 id="slideTitle" style="text-align:center; margin-bottom:16px;"></h2>
        <img id="slideImg" src="" alt="" style="max-width:100%; max-height:75vh; object-fit:contain;">
    </div>

    <div style="margin-top:16px; display:flex; gap:10px; justify-content:center;">
        <button onclick="goSlide(0)" class="btn btn-sm btn-outline-secondary">1. Priorità</button>
        <button onclick="goSlide(1)" class="btn btn-sm btn-outline-secondary">2. Propagazione</button>
        <button onclick="goSlide(2)" class="btn btn-sm btn-outline-secondary">3. Architettura</button>
        <button onclick="goSlide(3)" class="btn btn-sm btn-outline-secondary">4. Confronto</button>
    </div>
</div>

<script>
const slides = [
    { title: '1. Decisione Priorità (5 livelli)', img: '/demo/01_priorita.png' },
    { title: '2. Propagazione Fasi (event-driven)', img: '/demo/02_propagazione.png' },
    { title: '3. Architettura Sistema', img: '/demo/03_architettura.png' },
    { title: '4. Confronto Manuale vs Mossa 37', img: '/demo/04_confronto.png' },
];
let idx = 0;

function render() {
    document.getElementById('slideTitle').textContent = slides[idx].title;
    document.getElementById('slideImg').src = slides[idx].img;
    document.getElementById('slideImg').alt = slides[idx].title;
    document.getElementById('slideLabel').textContent = (idx + 1) + ' / ' + slides.length;
}
function nextSlide() { idx = (idx + 1) % slides.length; render(); }
function prevSlide() { idx = (idx - 1 + slides.length) % slides.length; render(); }
function goSlide(i) { idx = i; render(); }
function toggleFullscreen() {
    const el = document.getElementById('slideContainer');
    if (!document.fullscreenElement) el.requestFullscreen(); else document.exitFullscreen();
}
document.addEventListener('keydown', e => {
    if (e.key === 'ArrowRight' || e.key === ' ') nextSlide();
    if (e.key === 'ArrowLeft') prevSlide();
    if (e.key === 'f') toggleFullscreen();
});
render();
</script>
@endsection
