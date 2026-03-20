<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Prinect API Explorer</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Inter', -apple-system, sans-serif; background: #0f172a; color: #e2e8f0; padding: 20px; }
    h1 { color: #38bdf8; margin-bottom: 20px; font-size: 22px; }
    h2 { color: #f59e0b; margin: 20px 0 10px; font-size: 16px; cursor: pointer; padding: 8px 12px; background: #1e293b; border-radius: 8px; border-left: 4px solid #f59e0b; }
    h2:hover { background: #334155; }
    h2 .badge { float: right; font-size: 11px; background: #334155; padding: 2px 8px; border-radius: 10px; color: #94a3b8; }
    h2.ok .badge { background: #065f46; color: #6ee7b7; }
    h2.err .badge { background: #7f1d1d; color: #fca5a5; }
    .section { display: none; margin: 0 0 10px 0; background: #1e293b; border-radius: 0 0 8px 8px; padding: 12px; overflow-x: auto; }
    .section.open { display: block; }
    table { border-collapse: collapse; font-size: 12px; width: 100%; }
    th { background: #334155; color: #f1f5f9; padding: 6px 10px; text-align: left; position: sticky; top: 0; }
    td { padding: 4px 10px; border-bottom: 1px solid #334155; color: #cbd5e1; max-width: 400px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    td:hover { white-space: normal; overflow: visible; background: #334155; }
    tr:hover td { background: rgba(56, 189, 248, 0.05); }
    .val-null { color: #64748b; font-style: italic; }
    .val-num { color: #34d399; }
    .val-str { color: #93c5fd; }
    .val-bool { color: #c084fc; }
    .val-obj { color: #fbbf24; cursor: pointer; text-decoration: underline; }
    pre { background: #0f172a; padding: 10px; border-radius: 6px; font-size: 11px; max-height: 300px; overflow: auto; color: #94a3b8; }
    .kpi { display: inline-block; background: #1e293b; border-radius: 8px; padding: 12px 20px; margin: 4px; border-left: 3px solid #38bdf8; }
    .kpi-val { font-size: 24px; font-weight: 700; color: #f1f5f9; }
    .kpi-lbl { font-size: 11px; color: #64748b; text-transform: uppercase; }
    .loading { color: #64748b; padding: 40px; text-align: center; }
    .filter { margin-bottom: 15px; }
    .filter input { background: #1e293b; border: 1px solid #334155; color: #f1f5f9; padding: 8px 14px; border-radius: 6px; width: 300px; font-size: 13px; }
</style>
</head>
<body>

<h1>Prinect API Explorer — XL106</h1>

<div class="filter">
    <input type="text" id="search" placeholder="Cerca in tutti gli endpoint..." oninput="filterSections(this.value)">
</div>

<div id="content" class="loading">Caricamento dati API...</div>

<script>
var apiData = null;

fetch('/prinect_api_dump.json?' + Date.now())
    .then(r => r.json())
    .then(data => {
        apiData = data;
        renderAll(data);
    })
    .catch(e => {
        document.getElementById('content').innerHTML = '<p style="color:#ef4444">Errore: ' + e.message + '<br>Lancia prima: <code>php esplora_prinect_json.php</code> sul server</p>';
    });

function renderAll(data) {
    var html = '';

    // KPI
    var acts = (data.device_activity_today?.activities || []).length;
    var plates = data.device_consumption?.plateChanges || 0;
    var jobsMod = (data.jobs_modified_2d?.jobs || []).length;
    var emps = (data.employees?.employees || []).length;

    html += '<div style="margin-bottom:20px">';
    html += kpi(acts, 'Attività oggi');
    html += kpi(plates, 'Cambi lastra oggi');
    html += kpi(jobsMod, 'Job modificati 2gg');
    html += kpi(emps, 'Operatori Prinect');
    html += '</div>';

    var sections = {
        'Device Status': data.device,
        'Device Activity (oggi)': data.device_activity_today,
        'Device Consumption': data.device_consumption,
        'Device Output': data.device_output,
        'Device Groups': data.devicegroup,
        'Job Detail': data.job,
        'Job Worksteps': data.job_worksteps,
        'Job Elements': data.job_elements,
        'Employees': data.employees,
        'Jobs Modified (2gg)': data.jobs_modified_2d,
        'Version': data.version
    };

    // Add workstep activities/ink
    Object.keys(data).forEach(function(k) {
        if (k.startsWith('ws_activity_')) sections['WS Activity: ' + k.replace('ws_activity_', '')] = data[k];
        if (k.startsWith('ws_ink_')) sections['WS Ink: ' + k.replace('ws_ink_', '')] = data[k];
    });

    Object.keys(sections).forEach(function(name) {
        var d = sections[name];
        var hasError = d && d._error;
        var cls = hasError ? 'err' : 'ok';
        var badge = hasError ? 'HTTP ' + d._error : estimateSize(d);
        html += '<h2 class="' + cls + '" onclick="toggle(this)">' + name + '<span class="badge">' + badge + '</span></h2>';
        html += '<div class="section">' + renderData(d) + '</div>';
    });

    document.getElementById('content').innerHTML = html;
}

function kpi(val, label) {
    return '<div class="kpi"><div class="kpi-val">' + val + '</div><div class="kpi-lbl">' + label + '</div></div>';
}

function renderData(data) {
    if (!data) return '<span class="val-null">null</span>';
    if (data._error) return '<span style="color:#ef4444">Errore: ' + data._error + '</span>';

    // Find the main array in the data
    var arrayKey = null;
    Object.keys(data).forEach(function(k) {
        if (Array.isArray(data[k]) && data[k].length > 0) arrayKey = k;
    });

    if (arrayKey) {
        return '<p style="color:#64748b;margin-bottom:8px">' + arrayKey + ': ' + data[arrayKey].length + ' items</p>' + renderTable(data[arrayKey]);
    }

    // Object
    return '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
}

function renderTable(arr) {
    if (!arr || arr.length === 0) return '<span class="val-null">vuoto</span>';
    var keys = Object.keys(arr[0]);
    var html = '<table><thead><tr>';
    keys.forEach(function(k) { html += '<th>' + k + '</th>'; });
    html += '</tr></thead><tbody>';
    arr.slice(0, 50).forEach(function(row) {
        html += '<tr>';
        keys.forEach(function(k) {
            html += '<td>' + formatVal(row[k]) + '</td>';
        });
        html += '</tr>';
    });
    if (arr.length > 50) html += '<tr><td colspan="' + keys.length + '" style="text-align:center;color:#64748b">... e altri ' + (arr.length - 50) + '</td></tr>';
    html += '</tbody></table>';
    return html;
}

function formatVal(v) {
    if (v === null || v === undefined) return '<span class="val-null">null</span>';
    if (typeof v === 'boolean') return '<span class="val-bool">' + v + '</span>';
    if (typeof v === 'number') return '<span class="val-num">' + v + '</span>';
    if (Array.isArray(v)) return '<span class="val-obj">[' + v.length + ' items]</span>';
    if (typeof v === 'object') return '<span class="val-obj">{...}</span>';
    return '<span class="val-str">' + String(v).substring(0, 100) + '</span>';
}

function toggle(h2) {
    var sec = h2.nextElementSibling;
    sec.classList.toggle('open');
}

function estimateSize(d) {
    var s = JSON.stringify(d).length;
    return s > 1024 ? Math.round(s/1024) + ' KB' : s + ' B';
}

function filterSections(q) {
    q = q.toLowerCase();
    document.querySelectorAll('h2').forEach(function(h2) {
        var sec = h2.nextElementSibling;
        var text = (h2.textContent + ' ' + sec.textContent).toLowerCase();
        var match = !q || text.includes(q);
        h2.style.display = match ? '' : 'none';
        sec.style.display = match && sec.classList.contains('open') ? 'block' : 'none';
    });
}
</script>
</body>
</html>
