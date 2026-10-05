<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Scheduling Logs (live)</title>
    <style>
        :root { --bg: #0f1115; --panel: #171a21; --text: #d6dae3; --muted: #8b93a7; --accent: #10a37f; --warn: #e0a83a; --err: #e5534b; }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--bg); color: var(--text); font: 13px/1.45 Consolas, "Courier New", monospace; }
        header {
            position: sticky; top: 0; z-index: 1; display: flex; flex-wrap: wrap; gap: 8px; align-items: center;
            padding: 10px 16px; background: var(--panel); border-bottom: 1px solid #262b36;
        }
        header h1 { font: 600 15px/1 system-ui, sans-serif; margin: 0 12px 0 0; }
        .dot { width: 9px; height: 9px; border-radius: 50%; background: var(--accent); display: inline-block; margin-right: 6px; }
        .dot.paused { background: var(--muted); }
        input, button, label {
            font: 13px system-ui, sans-serif; color: var(--text); background: #222733;
            border: 1px solid #323848; border-radius: 6px; padding: 5px 10px;
        }
        button { cursor: pointer; }
        label { display: inline-flex; gap: 6px; align-items: center; }
        #status { color: var(--muted); font: 12px system-ui, sans-serif; margin-left: auto; }
        #log { margin: 0; padding: 12px 16px 40px; white-space: pre-wrap; word-break: break-word; }
        .l-warn { color: var(--warn); }
        .l-err { color: var(--err); }
        .l-lpi { color: #7cc7ff; }
        .l-ok { color: #6fd39c; }
        .l-protect { color: #f0c674; }
        mark { background: #3b4a1f; color: inherit; }
    </style>
</head>
<body>
<header>
    <h1><span class="dot" id="dot"></span>Scheduling logs</h1>
    <input id="filter" type="search" placeholder="Filter (e.g. LPI, 1117958, LPI_PROTECT)">
    <button id="pause" type="button">Pause</button>
    <label><input id="follow" type="checkbox" checked> Auto-scroll</label>
    <button id="clear" type="button">Clear view</button>
    <span id="status">Connecting…</span>
</header>
<pre id="log"></pre>

<script>
    var TAIL_URL = "{{ route('logs.tail') }}";
    var MAX_LINES = 5000;

    var logEl = document.getElementById('log');
    var statusEl = document.getElementById('status');
    var filterEl = document.getElementById('filter');
    var followEl = document.getElementById('follow');
    var pauseBtn = document.getElementById('pause');
    var dot = document.getElementById('dot');

    var offset = -1;
    var lines = [];
    var partial = '';
    var paused = false;

    function lineClass(line) {
        if (/\.(ERROR|CRITICAL)|PARTIAL_BLOCKED/.test(line)) return 'l-err';
        if (/\.WARNING/.test(line)) return 'l-warn';
        if (/\[LPI_PROTECT\]/.test(line)) return 'l-protect';
        if (/\[LPI\]/.test(line)) return 'l-lpi';
        if (/✓|accepted/.test(line)) return 'l-ok';
        return '';
    }

    function escapeHtml(s) {
        return s.replace(/[&<>]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;' }[c]; });
    }

    function render() {
        var q = filterEl.value.trim().toLowerCase();
        var html = [];
        for (var i = 0; i < lines.length; i++) {
            var line = lines[i];
            if (q && line.toLowerCase().indexOf(q) === -1) continue;
            var text = escapeHtml(line);
            if (q) {
                var re = new RegExp(q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'gi');
                text = text.replace(re, function (m) { return '<mark>' + m + '</mark>'; });
            }
            var cls = lineClass(line);
            html.push(cls ? '<span class="' + cls + '">' + text + '</span>' : text);
        }
        logEl.innerHTML = html.join('\n');
        if (followEl.checked) window.scrollTo(0, document.body.scrollHeight);
    }

    function addText(text) {
        var parts = (partial + text).split('\n');
        partial = parts.pop();           // keep an unfinished last line for the next chunk
        for (var i = 0; i < parts.length; i++) {
            if (parts[i].length) lines.push(parts[i]);
        }
        if (lines.length > MAX_LINES) lines = lines.slice(lines.length - MAX_LINES);
    }

    function poll() {
        if (paused) return schedule();
        fetch(TAIL_URL + '?offset=' + offset, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(function (r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
            .then(function (res) {
                if (res.reset) { lines = []; partial = ''; }
                var grew = res.text && res.text.length;
                offset = res.offset;
                if (grew) addText(res.text);
                if (grew || res.reset) render();
                statusEl.textContent = 'Live · updated ' + new Date().toLocaleTimeString();
            })
            .catch(function (e) { statusEl.textContent = 'Reconnecting… (' + e.message + ')'; })
            .then(schedule);
    }

    function schedule() { setTimeout(poll, 2000); }

    pauseBtn.addEventListener('click', function () {
        paused = !paused;
        pauseBtn.textContent = paused ? 'Resume' : 'Pause';
        dot.classList.toggle('paused', paused);
        statusEl.textContent = paused ? 'Paused' : 'Resuming…';
    });
    document.getElementById('clear').addEventListener('click', function () { lines = []; render(); });
    filterEl.addEventListener('input', render);

    poll();
</script>
</body>
</html>
