<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demo Mossa 37 — Flowchart</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; margin: 0; background: #f3f4f6; color: #111; }
        .wrap { max-width: 1400px; margin: 0 auto; padding: 24px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px; }
        h1 { margin: 0; font-size: 24px; }
        .controls { display: flex; gap: 8px; align-items: center; }
        button { padding: 8px 14px; border: 1px solid #d1d5db; background: #fff; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 500; }
        button:hover { background: #f9fafb; }
        button.primary { background: #2563eb; color: #fff; border-color: #2563eb; }
        button.primary:hover { background: #1d4ed8; }
        #slideContainer { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px; min-height: 80vh; display: flex; flex-direction: column; align-items: center; justify-content: flex-start; overflow: auto; }
        #slideContainer:fullscreen { padding: 40px; min-height: 100vh; background: #fff; }
        #slideTitle { text-align: center; margin: 0 0 16px 0; font-size: 22px; }
        #slideImg { max-width: 100%; height: auto; cursor: zoom-in; transition: transform 0.2s; }
        #slideImg.zoomed { max-width: none; transform: scale(1); cursor: zoom-out; }
        #slideContainer:fullscreen #slideImg { max-width: 100%; max-height: 90vh; }
        .nav-btns { margin-top: 16px; display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; }
        .label { font-weight: 600; padding: 0 8px; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="header">
            <h1>Demo Mossa 37 — Flowchart</h1>
            <div class="controls">
                <button onclick="prevSlide()">◀ Prev</button>
                <span class="label" id="slideLabel">1 / 4</span>
                <button onclick="nextSlide()">Next ▶</button>
                <button class="primary" onclick="toggleFullscreen()">⛶ Fullscreen</button>
            </div>
        </div>

        <div id="slideContainer">
            <h2 id="slideTitle"></h2>
            <img id="slideImg" src="" alt="">
        </div>

        <div class="nav-btns">
            <button onclick="goSlide(0)">1. Priorità</button>
            <button onclick="goSlide(1)">2. Propagazione</button>
            <button onclick="goSlide(2)">3. Architettura</button>
            <button onclick="goSlide(3)">4. Confronto</button>
        </div>
    </div>

    <script>
        const slides = [
            { title: '1. Decisione Priorità (5 livelli)', img: '/demo-img/01_priorita.png' },
            { title: '2. Propagazione Fasi (event-driven)', img: '/demo-img/02_propagazione.png' },
            { title: '3. Architettura Sistema', img: '/demo-img/03_architettura.png' },
            { title: '4. Confronto Manuale vs Mossa 37', img: '/demo-img/04_confronto.png' },
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
        document.getElementById('slideImg').addEventListener('click', function() {
            this.classList.toggle('zoomed');
        });
        render();
    </script>
</body>
</html>
