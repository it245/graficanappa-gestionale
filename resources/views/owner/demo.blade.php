<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demo Mossa 37 — Flowchart</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; margin: 0; background: #f3f4f6; color: #111; }
        .wrap { max-width: 1600px; margin: 0 auto; padding: 24px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 12px; }
        h1 { margin: 0; font-size: 22px; }
        .controls { display: flex; gap: 8px; align-items: center; }
        button { padding: 8px 14px; border: 1px solid #d1d5db; background: #fff; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 500; }
        button:hover { background: #f9fafb; }
        button.primary { background: #2563eb; color: #fff; border-color: #2563eb; }
        button.primary:hover { background: #1d4ed8; }
        #slideContainer {
            background: #fff; border: 1px solid #e5e7eb; border-radius: 12px;
            padding: 20px; min-height: 75vh;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            overflow: auto;
        }
        #slideContainer:fullscreen {
            padding: 24px; min-height: 100vh; height: 100vh; background: #fff;
            border: none; border-radius: 0;
        }
        #slideTitle { text-align: center; margin: 0 0 12px 0; font-size: 22px; flex-shrink: 0; }
        #slideContainer:fullscreen #slideTitle { font-size: 28px; margin-bottom: 16px; }
        .img-wrap { flex: 1; width: 100%; display: flex; align-items: center; justify-content: center; overflow: auto; }
        #slideImg {
            max-width: 100%; max-height: 100%;
            width: auto; height: auto;
            object-fit: contain; cursor: zoom-in;
        }
        #slideImg.zoomed { max-width: none; max-height: none; cursor: zoom-out; }
        /* Fullscreen: priorità altezza viewport, scroll orizzontale se serve */
        #slideContainer:fullscreen .img-wrap { align-items: center; justify-content: flex-start; }
        #slideContainer:fullscreen #slideImg {
            max-width: none; max-height: none;
            height: 88vh; width: auto;
        }
        #slideContainer:fullscreen #slideImg.zoomed { height: auto; width: auto; }
        .nav-btns { margin-top: 14px; display: flex; gap: 8px; justify-content: center; flex-wrap: wrap; }
        .label { font-weight: 600; padding: 0 8px; min-width: 50px; text-align: center; }
        .hint { color: #6b7280; font-size: 12px; text-align: center; margin-top: 8px; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="header">
            <h1>Demo Mossa 37 — Flowchart</h1>
            <div class="controls">
                <button onclick="prevSlide()">◀ Prev</button>
                <span class="label" id="slideLabel">1 / 5</span>
                <button onclick="nextSlide()">Next ▶</button>
                <button class="primary" onclick="toggleFullscreen()">⛶ Fullscreen</button>
            </div>
        </div>

        <div id="slideContainer">
            <h2 id="slideTitle"></h2>
            <div class="img-wrap">
                <img id="slideImg" src="" alt="">
            </div>
        </div>

        <div class="nav-btns">
            <button onclick="goSlide(0)">0. Sintesi</button>
            <button onclick="goSlide(1)">1. Priorità</button>
            <button onclick="goSlide(2)">2. Propagazione</button>
            <button onclick="goSlide(3)">3. Architettura</button>
            <button onclick="goSlide(4)">4. Confronto</button>
        </div>
        <div class="hint">⌨️ Frecce/Spazio = naviga · F = fullscreen · Click immagine = zoom</div>
    </div>

    <script>
        const slides = [
            { title: '0. Come funziona Mossa 37 — Sintesi', img: '/demo-img/00_sintesi.png' },
            { title: '1. Decisione Priorità (5 livelli)', img: '/demo-img/01_priorita.png' },
            { title: '2. Propagazione Fasi (event-driven)', img: '/demo-img/02_propagazione.png' },
            { title: '3. Architettura Sistema', img: '/demo-img/03_architettura.png' },
            { title: '4. Confronto Manuale vs Mossa 37', img: '/demo-img/04_confronto.png' },
        ];
        let idx = 0;

        function render() {
            document.getElementById('slideTitle').textContent = slides[idx].title;
            const img = document.getElementById('slideImg');
            img.src = slides[idx].img;
            img.alt = slides[idx].title;
            img.classList.remove('zoomed');
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
            if (e.key === 'f' || e.key === 'F') toggleFullscreen();
            if (e.key === 'Escape' && document.fullscreenElement) document.exitFullscreen();
        });
        document.getElementById('slideImg').addEventListener('click', function(e) {
            this.classList.toggle('zoomed');
        });
        render();
    </script>
</body>
</html>
