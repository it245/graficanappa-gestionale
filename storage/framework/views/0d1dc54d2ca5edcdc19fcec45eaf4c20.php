<?php $__env->startSection('content'); ?>
<style>
.azioni-cerchi {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-left: 20px;
}
.azioni-cerchi label {
    display: inline-flex;
    justify-content: center;
    align-items: center;
    width: 75px;
    height: 75px;
    border-radius: 50%;
    color: #fff;
    font-weight: bold;
    font-size: 12px;
    cursor: pointer;
    user-select: none;
}
.badge-avvia { background-color: #28a745; }
.badge-pausa { background-color: #ffc107; }
.badge-termina { background-color: #dc3545; }
.azioni-cerchi input[type="checkbox"] { display: none; }
.azioni-cerchi input[type="checkbox"]:checked + label {
    opacity: 0.7;
    box-shadow: inset 0 0 2px rgba(0,0,0,0.5);
}

/* Lampeggio tasto Avvia quando stato = 2 */
@keyframes lampeggio-avvia {
    0%, 100% { opacity: 1; background-color: #28a745; }
    50% { opacity: 0.3; background-color: #ff6600; }
}
.badge-avvia.lampeggia {
    animation: lampeggio-avvia 1s ease-in-out infinite;
}
</style>

<?php
    $operatore = request()->attributes->get('operatore') ?? auth('operatore')->user();
    $repartiOperatore = $operatore?->reparti?->pluck('id')->toArray() ?? [];
    $isSpedizione = $operatore?->reparti?->pluck('nome')->map(fn($n) => strtolower($n))->contains('spedizione');

    $ordineFasi = config('fasi_ordine');
    $getFaseOrdine = function($fase) use ($ordineFasi) {
        $nome = $fase->faseCatalogo->nome ?? '';
        return $ordineFasi[$nome] ?? $ordineFasi[strtolower($nome)] ?? 999;
    };
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Commessa <?php echo e($ordine->commessa); ?></h2>
    <div class="d-flex gap-2">
        <a href="<?php echo e(route('operatore.etichetta', $ordine->id)); ?>" class="btn btn-outline-dark d-flex align-items-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" class="me-1"><path d="M2 1a1 1 0 0 0-1 1v4.586a1 1 0 0 0 .293.707l7 7a1 1 0 0 0 1.414 0l4.586-4.586a1 1 0 0 0 0-1.414l-7-7A1 1 0 0 0 6.586 1H2zm4 3.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/></svg> Stampa Etichetta
        </a>
        <a href="<?php echo e($isSpedizione ? route('spedizione.dashboard') : route('operatore.dashboard')); ?>" class="btn btn-primary d-flex align-items-center">
            <img src="<?php echo e(asset('images/turn-left_15441589.png')); ?>" alt="Dashboard" style="width:20px; height:20px; margin-right:5px;">
            Dashboard
        </a>
    </div>
</div>

<?php
    $fasiGestibili = $ordine->fasi->filter(function($f) use ($repartiOperatore) {
        return in_array($f->faseCatalogo->reparto_id ?? null, $repartiOperatore);
    });
    $faseSelezionataId = (int) request('fase');
?>

<div class="container mt-3">
    <!-- Card info ordine -->
    <div class="card mb-3">
        <div class="card-body">
            <?php
                $desc = $ordine->descrizione ?? '';
                $cliente = $ordine->cliente_nome ?? '';
                $coloriCalc = \App\Helpers\DescrizioneParser::parseColori($desc, $cliente);
                $fustellaCalc = \App\Helpers\DescrizioneParser::parseFustella($desc, $cliente);
            ?>
            <p><strong>Cliente:</strong> <?php echo e($ordine->cliente_nome); ?></p>
            <p><strong>Descrizione:</strong> <?php echo e($ordine->descrizione); ?></p>
            <p><strong>Quantita totale:</strong> <?php echo e($ordine->qta_richiesta); ?> <?php echo e($ordine->um); ?></p>
            <p>
                <strong>Colori:</strong> <?php echo e($coloriCalc); ?>

                <?php if($fustellaCalc): ?>
                    &nbsp; <strong>Fustella:</strong> <?php echo e($fustellaCalc); ?>

                <?php endif; ?>
            </p>
            <div class="row mt-2 g-2">
                <div class="col-md-4">
                    <div class="border rounded p-2 h-100" style="background:#f8f9fa">
                        <strong class="d-block mb-1">Note Prestampa</strong>
                        <span class="<?php echo e($ordine->note_prestampa ? '' : 'text-muted'); ?>"><?php echo e($ordine->note_prestampa ?: '-'); ?></span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded p-2 h-100" style="background:#f8f9fa">
                        <strong class="d-block mb-1">Operatore Prestampa</strong>
                        <span class="<?php echo e($ordine->responsabile ? '' : 'text-muted'); ?>"><?php echo e($ordine->responsabile ?: '-'); ?></span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded p-2 h-100" style="background:#f8f9fa">
                        <strong class="d-block mb-1">Commento Produzione</strong>
                        <span class="<?php echo e($ordine->commento_produzione ? '' : 'text-muted'); ?>"><?php echo e($ordine->commento_produzione ?: '-'); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Fase selezionata (con pulsanti) + Anteprima affiancata -->
    <?php $__currentLoopData = $fasiGestibili; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $fase): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php if($fase->id === $faseSelezionataId): ?>
        <div class="row mb-3">
            <div class="<?php echo e(!empty($preview) ? 'col-md-8' : 'col-12'); ?>">
                <div class="card border-primary h-100" id="card-fase-<?php echo e($fase->id); ?>">
                    <div class="card-header bg-primary text-white">
                        <strong><?php echo e($fase->faseCatalogo->nome_display ?? '-'); ?></strong>
                        <?php $badgeBg = [0=>'bg-secondary',1=>'bg-info',2=>'bg-warning text-dark',3=>'bg-success']; ?>
                        <span class="badge <?php echo e($badgeBg[$fase->stato] ?? 'bg-dark'); ?> ms-2 fs-5" id="badge-fase-<?php echo e($fase->id); ?>"><?php echo e($fase->stato); ?></span>
                        <span class="ms-2" id="operatori-fase-<?php echo e($fase->id); ?>">
                            <?php $__currentLoopData = $fase->operatori; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $op): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <small class="badge bg-light text-dark"><?php echo e($op->nome); ?> (<?php echo e($op->pivot->data_inizio ? \Carbon\Carbon::parse($op->pivot->data_inizio)->format('d/m/Y H:i:s') : '-'); ?>)</small>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </span>
                    </div>
                    <div class="card-body border-bottom py-2">
                        <small class="text-muted"><?php echo e($fase->ordine_descrizione ?? $fase->ordine->descrizione ?? '-'); ?></small>
                    </div>
                    <div class="card-body d-flex align-items-start gap-3">
                        <div class="flex-grow-1">
                            <label for="note-fase-<?php echo e($fase->id); ?>"><strong>Note Operatore:</strong></label>
                            <textarea id="note-fase-<?php echo e($fase->id); ?>" class="form-control" rows="2"
                                      onblur="aggiornaCampo(<?php echo e($fase->id); ?>, 'note', this.value)"><?php echo e($fase->note ?? ''); ?></textarea>

                            
                            <div class="mt-3 border-top pt-2">
                                <label><strong>Info per fasi successive:</strong></label>
                                <?php
                                    $noteFS = $ordine->note_fasi_successive ?? '';
                                    $righeFS = $noteFS ? json_decode($noteFS, true) : [];
                                    if (!is_array($righeFS)) $righeFS = [];
                                ?>
                                <?php if(!empty($righeFS)): ?>
                                    <div class="mb-2" style="max-height:150px; overflow-y:auto; background:#f8f9fa; border-radius:4px; padding:8px; font-size:13px;">
                                        <?php $__currentLoopData = $righeFS; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $riga): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <div class="mb-1">
                                                <small class="text-muted"><?php echo e($riga['data'] ?? ''); ?></small>
                                                <strong><?php echo e($riga['reparto'] ?? ''); ?> - <?php echo e($riga['nome'] ?? ''); ?>:</strong>
                                                <?php echo e($riga['testo'] ?? ''); ?>

                                            </div>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </div>
                                <?php else: ?>
                                    <div class="mb-2 text-muted" style="font-size:13px;">Nessuna nota</div>
                                <?php endif; ?>
                                <div class="d-flex gap-2">
                                    <textarea id="nuova-nota-fs-<?php echo e($fase->id); ?>" class="form-control form-control-sm" rows="1"
                                              placeholder="Scrivi una nota per le fasi successive..."></textarea>
                                    <button type="button" class="btn btn-sm btn-outline-primary" style="white-space:nowrap"
                                            onclick="inviaNotaFS(<?php echo e($ordine->id); ?>, <?php echo e($fase->id); ?>)">Invia</button>
                                </div>
                            </div>
                        </div>
                        <div class="azioni-cerchi" id="azioni-fase-<?php echo e($fase->id); ?>">
                            
                            <input type="checkbox" id="avvia-<?php echo e($fase->id); ?>" onchange="aggiornaStato(<?php echo e($fase->id); ?>, 'avvia', this.checked)">
                            <label for="avvia-<?php echo e($fase->id); ?>" class="badge-avvia<?php echo e($fase->stato == 2 ? ' lampeggia' : ''); ?>"><?php echo e($fase->stato == 2 ? 'Avviato' : 'Avvia'); ?></label>

                            <input type="checkbox" id="pausa-<?php echo e($fase->id); ?>" onchange="gestisciPausa(<?php echo e($fase->id); ?>, this.checked)">
                            <label for="pausa-<?php echo e($fase->id); ?>" class="badge-pausa">Pausa</label>

                            <input type="checkbox" id="termina-<?php echo e($fase->id); ?>"
                                   data-qta-fase="<?php echo e($ordine->qta_richiesta ?? 0); ?>"
                                   data-fogli-buoni="<?php echo e($fase->fogli_buoni ?? 0); ?>"
                                   data-fogli-scarto="<?php echo e($fase->fogli_scarto ?? 0); ?>"
                                   data-qta-prod="<?php echo e($fase->qta_prod ?? 0); ?>"
                                   onchange="aggiornaStato(<?php echo e($fase->id); ?>, 'termina', this.checked)">
                            <label for="termina-<?php echo e($fase->id); ?>" class="badge-termina">Termina</label>

                            <?php if(!is_numeric($fase->stato)): ?>
                                <input type="checkbox" id="riprendi-<?php echo e($fase->id); ?>" onchange="riprendiFase(<?php echo e($fase->id); ?>, this.checked)">
                                <label for="riprendi-<?php echo e($fase->id); ?>" class="badge-avvia">Riprendi</label>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php if(!empty($preview)): ?>
            <div class="col-md-4">
                <div class="card p-3 text-center shadow-sm h-100 d-flex align-items-center justify-content-center">
                    <div class="fw-bold mb-2" style="font-size:13px;">Anteprima foglio di stampa</div>
                    <img src="data:<?php echo e($preview['mimeType']); ?>;base64,<?php echo e($preview['data']); ?>"
                         alt="Preview" style="max-width:100%; max-height:260px; border-radius:8px; cursor:pointer;"
                         onclick="window.open(this.src)">
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

    <!-- Altre fasi del tuo reparto (sola lettura) -->
    <?php
        $altreFasiMieNonSelezionate = $fasiGestibili->filter(fn($f) => $f->id !== $faseSelezionataId)->sortBy($getFaseOrdine)->values();
    ?>
    <?php if($altreFasiMieNonSelezionate->isNotEmpty()): ?>
    <h4>Altre fasi del reparto</h4>
    <table class="table table-sm table-bordered">
        <thead class="table-dark">
            <tr>
                <th>Fase</th>
                <th>Descrizione</th>
                <th>Stato</th>
                <th>Operatori</th>
                <th>Qta Prodotta</th>
                <th>Timeout</th>
            </tr>
        </thead>
        <tbody>
            <?php $__currentLoopData = $altreFasiMieNonSelezionate; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $fase): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr style="cursor:pointer" onclick="window.location='<?php echo e(route('commesse.show', $ordine->commessa)); ?>?fase=<?php echo e($fase->id); ?>'">
                <td><a href="<?php echo e(route('commesse.show', $ordine->commessa)); ?>?fase=<?php echo e($fase->id); ?>" style="color:#000; text-decoration:underline; font-weight:bold"><?php echo e($fase->faseCatalogo->nome_display ?? '-'); ?></a></td>
                <td><small><?php echo e(Str::limit($fase->ordine_descrizione ?? $fase->ordine->descrizione ?? '-', 60)); ?></small></td>
                <?php $sb = [0=>'#e9ecef',1=>'#cfe2ff',2=>'#fff3cd',3=>'#d1e7dd']; ?>
                <td id="stato-<?php echo e($fase->id); ?>" style="background:<?php echo e($sb[$fase->stato] ?? '#e9ecef'); ?>;font-weight:bold;text-align:center;"><?php echo e($fase->stato); ?></td>
                <td>
                    <?php $__currentLoopData = $fase->operatori; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $op): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php echo e($op->nome); ?> (<?php echo e($op->pivot->data_inizio ? \Carbon\Carbon::parse($op->pivot->data_inizio)->format('d/m/Y H:i:s') : '-'); ?>)<br>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </td>
                <td><?php echo e($fase->qta_prod ?? '-'); ?></td>
                <td><?php echo e($fase->timeout ?? '-'); ?></td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
    </table>
    <?php endif; ?>

    <!-- Fasi di altri reparti (sola lettura) -->
    <?php
        $altreFasi = $ordine->fasi->filter(function($f) use ($repartiOperatore) {
            return !in_array($f->faseCatalogo->reparto_id ?? null, $repartiOperatore);
        })->sortBy($getFaseOrdine)->values();
    ?>
    <?php if($altreFasi->count() > 0): ?>
    <h4>Altre fasi</h4>
    <table class="table table-sm table-bordered">
        <thead class="table-secondary">
            <tr>
                <th>Fase</th>
                <th>Stato</th>
                <th>Operatori</th>
            </tr>
        </thead>
        <tbody>
            <?php $__currentLoopData = $altreFasi; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $fase): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr>
                <td><?php echo e($fase->faseCatalogo->nome_display ?? '-'); ?></td>
                <?php $sb2 = [0=>'#e9ecef',1=>'#cfe2ff',2=>'#fff3cd',3=>'#d1e7dd']; ?>
                <td style="background:<?php echo e($sb2[$fase->stato] ?? '#e9ecef'); ?>;font-weight:bold;text-align:center;"><?php echo e($fase->stato); ?></td>
                <td>
                    <?php $__currentLoopData = $fase->operatori; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $op): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php echo e($op->nome); ?> (<?php echo e($op->pivot->data_inizio ? \Carbon\Carbon::parse($op->pivot->data_inizio)->format('d/m/Y H:i:s') : '-'); ?>)<br>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
    </table>
    <?php endif; ?>

    <!-- Prossime commesse -->
    <h4 class="mt-4">Prossime commesse</h4>
    <ul class="list-group">
        <?php $__currentLoopData = $prossime; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <li class="list-group-item">
                <a href="<?php echo e(route('commesse.show', $c->commessa)); ?>">
                    <?php echo e($c->commessa); ?> – <?php echo e($c->cliente_nome); ?>

                </a>
            </li>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </ul>
</div>

<!-- Modal Termina Fase -->
<div class="modal fade" id="modalTermina" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Termina Fase</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="terminaFaseId">
                <div class="mb-3">
                    <label class="form-label fw-bold">Qta prodotta <span class="text-danger">*</span></label>
                    <input type="number" id="terminaQtaProdotta" class="form-control" min="0" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Scarti</label>
                    <input type="number" id="terminaScarti" class="form-control" min="0" value="0">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                <button type="button" class="btn btn-danger fw-bold" onclick="confermaTermina()">Conferma e Termina</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Pausa -->
<div class="modal fade" id="modalPausa" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title">Pausa Fase</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="pausaFaseId">
                <div class="mb-3">
                    <label class="form-label fw-bold">Motivo della pausa</label>
                    <select id="pausaMotivoSelect" class="form-select" onchange="toggleAltroPausa()">
                        <option value="">-- Seleziona --</option>
                        <option>Attesa materiale</option>
                        <option>Problema macchina</option>
                        <option>Pranzo</option>
                        <option value="__altro__">Altro...</option>
                    </select>
                </div>
                <div class="mb-3" id="pausaAltroWrap" style="display:none;">
                    <label class="form-label fw-bold">Specifica motivo</label>
                    <input type="text" id="pausaAltroInput" class="form-control" placeholder="Scrivi il motivo...">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                <button type="button" class="btn btn-warning fw-bold" onclick="confermaPausa()">Conferma Pausa</button>
            </div>
        </div>
    </div>
</div>

<script>
const badgeBgMap = {0:'bg-secondary',1:'bg-info',2:'bg-warning text-dark',3:'bg-success'};

function updateBadge(faseId, stato) {
    const badge = document.getElementById('badge-fase-'+faseId);
    if (!badge) return;
    badge.className = 'badge ms-2 fs-5 ' + (badgeBgMap[stato] || 'bg-dark');
    badge.textContent = stato;
}

function updateButtons(faseId, nuovoStato) {
    const container = document.getElementById('azioni-fase-'+faseId);
    if (!container) return;

    // I 3 bottoni base sempre visibili
    var lampeggiaClass = (nuovoStato == 2) ? ' lampeggia' : '';
    let html =
        '<input type="checkbox" id="avvia-'+faseId+'" onchange="aggiornaStato('+faseId+', \'avvia\', this.checked)">' +
        '<label for="avvia-'+faseId+'" class="badge-avvia'+lampeggiaClass+'">'+(nuovoStato == 2 ? 'Avviato' : 'Avvia')+'</label>' +
        '<input type="checkbox" id="pausa-'+faseId+'" onchange="gestisciPausa('+faseId+', this.checked)">' +
        '<label for="pausa-'+faseId+'" class="badge-pausa">Pausa</label>' +
        '<input type="checkbox" id="termina-'+faseId+'" onchange="aggiornaStato('+faseId+', \'termina\', this.checked)">' +
        '<label for="termina-'+faseId+'" class="badge-termina">Termina</label>';

    // Aggiungi Riprendi se in pausa
    if (typeof nuovoStato === 'string' && isNaN(nuovoStato)) {
        html +=
            '<input type="checkbox" id="riprendi-'+faseId+'" onchange="riprendiFase('+faseId+', this.checked)">' +
            '<label for="riprendi-'+faseId+'" class="badge-avvia">Riprendi</label>';
    }

    container.innerHTML = html;
}

function updateOperatori(faseId, operatori) {
    const container = document.getElementById('operatori-fase-'+faseId);
    if (!container || !operatori) return;
    container.innerHTML = operatori.map(function(op) {
        return '<small class="badge bg-light text-dark">' + op.nome + ' (' + op.data_inizio + ')</small>';
    }).join(' ');
}

function aggiornaStato(faseId, azione, checked){
    if(!checked) return;
    if(azione === 'termina'){
        apriModalTermina(faseId);
        return;
    }

    let route = '<?php echo e(route("produzione.avvia")); ?>';

    fetch(route, {
        method:'POST',
        headers:{'X-CSRF-TOKEN':csrfToken(),'Content-Type':'application/json'},
        body:JSON.stringify({fase_id:faseId})
    })
    .then(res=>res.json())
    .then(data=>{
        if(data.success){
            updateBadge(faseId, 2);
            updateButtons(faseId, 2);
            if(data.operatori) updateOperatori(faseId, data.operatori);
        } else {
            alert('Errore: ' + (data.messaggio || 'operazione fallita'));
        }
    })
    .catch(err=>console.error('Errore:', err));
}

function apriModalTermina(faseId) {
    var cb = document.getElementById('termina-'+faseId);
    var qtaFase = cb ? cb.getAttribute('data-qta-fase') : 0;
    var fogliBuoni = parseInt(cb ? cb.getAttribute('data-fogli-buoni') : 0) || 0;
    var fogliScarto = parseInt(cb ? cb.getAttribute('data-fogli-scarto') : 0) || 0;
    var qtaProd = parseInt(cb ? cb.getAttribute('data-qta-prod') : 0) || 0;

    document.getElementById('terminaFaseId').value = faseId;

    // Pre-fill: fogli_buoni se > 0, altrimenti qta_prod se > 0, altrimenti vuoto
    var prefillQta = fogliBuoni > 0 ? fogliBuoni : (qtaProd > 0 ? qtaProd : '');
    document.getElementById('terminaQtaProdotta').value = prefillQta;

    // Pre-fill scarti da fogli_scarto se > 0
    document.getElementById('terminaScarti').value = fogliScarto > 0 ? fogliScarto : 0;

    new bootstrap.Modal(document.getElementById('modalTermina')).show();
}

function confermaTermina() {
    var faseId = document.getElementById('terminaFaseId').value;
    var qtaProdotta = document.getElementById('terminaQtaProdotta').value;
    var scarti = document.getElementById('terminaScarti').value;

    if (qtaProdotta === '' || parseInt(qtaProdotta) <= 0) {
        alert('Inserire la quantita prodotta (deve essere maggiore di 0)');
        return;
    }

    bootstrap.Modal.getInstance(document.getElementById('modalTermina')).hide();

    fetch('<?php echo e(route("produzione.termina")); ?>', {
        method:'POST',
        headers:{'X-CSRF-TOKEN':csrfToken(),'Content-Type':'application/json'},
        body:JSON.stringify({fase_id: faseId, qta_prodotta: parseInt(qtaProdotta), scarti: parseInt(scarti) || 0})
    })
    .then(res=>res.json())
    .then(data=>{
        if(data.success){
            updateBadge(faseId, 3);
            updateButtons(faseId, 3);
            updateOperatori(faseId, []);
        } else {
            alert('Errore: ' + (data.messaggio || 'operazione fallita'));
            document.getElementById('termina-'+faseId).checked = false;
        }
    })
    .catch(err=>{
        console.error('Errore:', err);
        document.getElementById('termina-'+faseId).checked = false;
    });
}

// Reset checkbox when modal is dismissed without confirming
document.getElementById('modalTermina').addEventListener('hidden.bs.modal', function() {
    var faseId = document.getElementById('terminaFaseId').value;
    var cb = document.getElementById('termina-'+faseId);
    if (cb) cb.checked = false;
});

function gestisciPausa(faseId, checked){
    if(!checked) return;
    document.getElementById('pausaFaseId').value = faseId;
    document.getElementById('pausaMotivoSelect').value = '';
    document.getElementById('pausaAltroInput').value = '';
    document.getElementById('pausaAltroWrap').style.display = 'none';
    new bootstrap.Modal(document.getElementById('modalPausa')).show();
}

document.getElementById('modalPausa').addEventListener('hidden.bs.modal', function() {
    var faseId = document.getElementById('pausaFaseId').value;
    var cb = document.getElementById('pausa-'+faseId);
    if (cb) cb.checked = false;
});

function toggleAltroPausa() {
    document.getElementById('pausaAltroWrap').style.display =
        document.getElementById('pausaMotivoSelect').value === '__altro__' ? '' : 'none';
}

function confermaPausa() {
    var sel = document.getElementById('pausaMotivoSelect').value;
    var motivo = sel === '__altro__' ? (document.getElementById('pausaAltroInput').value.trim() || 'Altro') : sel;
    if (!motivo) { alert('Seleziona un motivo'); return; }
    var faseId = document.getElementById('pausaFaseId').value;
    bootstrap.Modal.getInstance(document.getElementById('modalPausa')).hide();

    fetch('<?php echo e(route("produzione.pausa")); ?>',{
        method:'POST',
        headers:{'X-CSRF-TOKEN':csrfToken(),'Content-Type':'application/json'},
        body:JSON.stringify({fase_id:faseId, motivo:motivo})
    })
    .then(res=>res.json())
    .then(data=>{
        if(data.success){
            updateBadge(faseId, data.nuovo_stato);
            updateButtons(faseId, data.nuovo_stato);
        } else {
            alert('Errore: ' + (data.messaggio || 'operazione fallita'));
        }
    })
    .catch(err=>console.error('Errore:', err));
}

function riprendiFase(faseId, checked){
    if(!checked) return;

    fetch('<?php echo e(route("produzione.riprendi")); ?>',{
        method:'POST',
        headers:{'X-CSRF-TOKEN':csrfToken(),'Content-Type':'application/json'},
        body:JSON.stringify({fase_id:faseId})
    })
    .then(res=>res.json())
    .then(data=>{
        if(data.success){
            updateBadge(faseId, 2);
            updateButtons(faseId, 2);
        } else {
            alert('Errore: ' + (data.messaggio || 'operazione fallita'));
            document.getElementById('riprendi-'+faseId).checked = false;
        }
    })
    .catch(err=>console.error('Errore:', err));
}

function aggiornaCampo(faseId, campo, valore){
    fetch('<?php echo e(route("produzione.aggiornaCampo")); ?>',{
        method:'POST',
        headers:{'X-CSRF-TOKEN':csrfToken(),'Content-Type':'application/json'},
        body:JSON.stringify({fase_id:faseId, campo:campo, valore:valore})
    })
    .then(res=>res.json())
    .then(data=>{
        if(!data.success) alert('Errore durante il salvataggio: '+data.messaggio);
    })
    .catch(err=>console.error('Errore:', err));
}

function inviaNotaFS(ordineId, faseId) {
    var textarea = document.getElementById('nuova-nota-fs-'+faseId);
    var testo = textarea.value.trim();
    if (!testo) { alert('Scrivi una nota prima di inviare'); return; }

    <?php
        $opNome = $operatore ? ($operatore->nome . ' ' . ($operatore->cognome ?? '')) : 'Operatore';
        $opReparto = $operatore?->reparti?->pluck('nome')->first() ?? 'N/D';
    ?>

    // Leggi note esistenti, aggiungi la nuova, salva
    var noteEsistenti = <?php echo json_encode($righeFS ?? [], 15, 512) ?>;
    noteEsistenti.push({
        data: new Date().toLocaleString('it-IT'),
        reparto: <?php echo json_encode($opReparto, 15, 512) ?>,
        nome: <?php echo json_encode(trim($opNome), 15, 512) ?>,
        testo: testo
    });

    fetch('<?php echo e(route("produzione.aggiornaOrdineCampo")); ?>', {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': csrfToken(), 'Content-Type': 'application/json'},
        body: JSON.stringify({ordine_id: ordineId, campo: 'note_fasi_successive', valore: JSON.stringify(noteEsistenti)})
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Errore: ' + (data.messaggio || JSON.stringify(data.errors)));
        }
    })
    .catch(err => console.error('Errore:', err));
}

</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Giovanni\graficanappa-gestionale\resources\views\commesse\show.blade.php ENDPATH**/ ?>