@push('styles')
<style>
    .galaxy-map-widget {
        background-color: #0a0e17;
        position: relative;
        width: 100%;
        height: 50vh;
        overflow: hidden;
        border-radius: 8px;
        border: 1px solid #2d3748;
    }
    .galaxy-map-widget.fullscreen {
        position: fixed !important;
        top: var(--nav-offset, 56px);
        left: 0;
        right: 0;
        bottom: 0;
        width: auto;
        height: auto;
        border-radius: 0;
        z-index: 1050; /* above content */
    }
    .galaxy-map-widget .star-system { position: absolute; cursor: default; transition: all 0.2s ease; }
    .galaxy-map-widget .star-system .star { width: 6px; height: 6px; border-radius: 50%; background-color: #fff; box-shadow: 0 0 8px rgba(255,255,255,0.6); }
    .galaxy-map-widget .star-system.owned .star { background-color: #48bb78; box-shadow: 0 0 8px rgba(72,187,120,0.7); }
    .galaxy-map-widget .star-system.enemy .star { background-color: #f56565; box-shadow: 0 0 8px rgba(245,101,101,0.7); }
    .galaxy-map-widget .star-system.neutral .star { background-color: #d69e2e; box-shadow: 0 0 8px rgba(214,158,46,0.7); }
    .galaxy-map-widget .star-system:hover { z-index: 5; transform: scale(1.4); }
    .galaxy-map-widget .legend { position: absolute; top: 8px; right: 8px; font-size: 11px; background: rgba(0,0,0,0.6); border-radius: 6px; padding: 6px 8px; color: #ddd; }
    .galaxy-map-widget .legend .item { display: flex; align-items: center; gap: 6px; margin-bottom: 4px; }
    .galaxy-map-widget .legend .dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; }
    .galaxy-map-widget .fleet { position: absolute; width: 10px; height: 10px; border-radius: 3px; background-color: #3182ce; transform: rotate(45deg); }
    .galaxy-map-widget .controls { position: absolute; top: 8px; left: 8px; z-index: 50; display: flex; gap: 6px; padding: 4px; border-radius: 6px; background: rgba(0,0,0,0.45); backdrop-filter: saturate(130%) blur(2px); }
    .galaxy-map-widget .controls .btn { background: #111827; color: #e5e7eb; border: 1px solid #374151; padding: 4px 8px; font-size: 12px; line-height: 1.2; border-radius: 6px; }
    .galaxy-map-widget .controls .btn:hover { background: #0f172a; color: #fff; border-color: #4b5563; }
    .galaxy-map-widget .stage { position: absolute; top: 0; left: 0; will-change: transform; }
    .galaxy-map-widget .star-system.capital .star { width: 10px; height: 10px; box-shadow: 0 0 12px rgba(72,187,120,0.9); border: 1px solid rgba(160,255,195,0.8); }
    /* Grid layer inside stage */
    .galaxy-map-widget .grid-line { position: absolute; background: rgba(255,255,255,0.16); }
    .galaxy-map-widget .grid-line.x { width: 1px; top: 0; bottom: 0; }
    .galaxy-map-widget .grid-line.y { height: 1px; left: 0; right: 0; }
    /* Rulers pinned to container */
    .galaxy-map-widget .ruler-x { position: absolute; top: 0; left: 0; right: 0; height: 20px; z-index: 5; pointer-events: none; background: linear-gradient(to bottom, rgba(0,0,0,0.35), rgba(0,0,0,0)); }
    .galaxy-map-widget .ruler-y { position: absolute; top: 0; bottom: 0; left: 0; width: 34px; z-index: 5; pointer-events: none; background: linear-gradient(to right, rgba(0,0,0,0.35), rgba(0,0,0,0)); }
    .galaxy-map-widget .ruler-x .tick { position: absolute; width: 1px; height: 6px; bottom: 0; background: rgba(255,255,255,0.5); }
    .galaxy-map-widget .ruler-y .tick { position: absolute; height: 1px; width: 6px; right: 0; background: rgba(255,255,255,0.5); }
    .galaxy-map-widget .ruler-x .label { position: absolute; font-size: 10px; color: #cbd5e1; transform: translateX(-50%); top: 2px; }
    .galaxy-map-widget .ruler-y .label { position: absolute; font-size: 10px; color: #cbd5e1; transform: translateY(-50%); left: 2px; }
    /* Loading overlay shown during pan/redraw */
    .galaxy-map-widget .loading-overlay { position: absolute; inset: 0; display: none; align-items: center; justify-content: center; background: rgba(0,0,0,0.18); z-index: 20; pointer-events: none; }
    .galaxy-map-widget .loading-overlay.show { display: flex; }
    .galaxy-map-widget .spinner { width: 18px; height: 18px; border: 2px solid rgba(255,255,255,0.35); border-top-color: #fff; border-radius: 50%; animation: spin 0.8s linear infinite; }
    @keyframes spin { to { transform: rotate(360deg); } }
    /* Edge indicators when reaching galaxy bounds */
    .galaxy-map-widget .edge { position: absolute; opacity: 0; transition: opacity 0.12s linear; pointer-events: none; z-index: 12; }
    .galaxy-map-widget .edge.show { opacity: 1; }
    .galaxy-map-widget .edge.left { left: 0; top: 0; bottom: 0; width: 12px; background: linear-gradient(to right, rgba(59,130,246,0.22), rgba(59,130,246,0)); }
    .galaxy-map-widget .edge.right { right: 0; top: 0; bottom: 0; width: 12px; background: linear-gradient(to left, rgba(59,130,246,0.22), rgba(59,130,246,0)); }
    .galaxy-map-widget .edge.top { left: 0; right: 0; top: 0; height: 12px; background: linear-gradient(to bottom, rgba(59,130,246,0.22), rgba(59,130,246,0)); }
    .galaxy-map-widget .edge.bottom { left: 0; right: 0; bottom: 0; height: 12px; background: linear-gradient(to top, rgba(59,130,246,0.22), rgba(59,130,246,0)); }
    /* Modeless draggable windows (global) */
    .gmw-window { position: fixed; top: 96px; left: 16px; min-width: 240px; max-width: 360px; background: #0b1220; color: #e5e7eb; border: 1px solid #334155; border-radius: 8px; box-shadow: 0 12px 32px rgba(0,0,0,0.55); z-index: 3000; }
    .gmw-window .gmw-header { cursor: move; user-select: none; padding: 8px 10px; display: flex; align-items: center; justify-content: space-between; gap: 8px; background: linear-gradient(to bottom, rgba(15,23,42,0.95), rgba(2,6,23,0.9)); border-bottom: 1px solid #1f2937; border-top-left-radius: 8px; border-top-right-radius: 8px; }
    .gmw-window .gmw-title { font-size: 12px; font-weight: 600; color: #f3f4f6; }
    .gmw-window .gmw-actions { display: flex; gap: 6px; }
    .gmw-window .gmw-actions .gmw-btn { background: #0f172a; color: #cbd5e1; border: 1px solid #334155; border-radius: 6px; padding: 2px 6px; font-size: 12px; line-height: 1; }
    .gmw-window .gmw-actions .gmw-btn:hover { background: #111827; }
    .gmw-window .gmw-body { padding: 10px; font-size: 13px; }
    .gmw-window .gmw-row { display: flex; gap: 8px; align-items: center; margin-bottom: 8px; }
    .gmw-window .gmw-row label { width: 18px; font-size: 12px; color: #cbd5e1; }
    .gmw-window input.gmw-num { width: 100px; background: #0a0f1a; color: #e5e7eb; border: 1px solid #334155; border-radius: 6px; padding: 4px 6px; font-size: 13px; }
    /* Step navigation pad */
    .gmw-window .gmw-pad-block { display: inline-block; padding: 8px; background: #0a0f1a; border: 1px solid #334155; border-radius: 8px; }
    .gmw-window .gmw-pad { --pad-size: 36px; --pad-gap: 6px; display: grid; grid-template-columns: repeat(3, var(--pad-size)); grid-auto-rows: var(--pad-size); gap: var(--pad-gap); align-items: stretch; justify-content: start; }
    .gmw-window .gmw-pad .dir { width: 100%; height: 100%; padding: 0; box-sizing: border-box; display: inline-flex; align-items: center; justify-content: center; background: #0f172a; color: #cbd5e1; border: 1px solid #334155; border-radius: 6px; cursor: pointer; }
    .gmw-window .gmw-pad .dir:hover { background: #111827; color: #fff; border-color: #4b5563; }
    .gmw-window .gmw-pad .gmw-step { width: 100%; height: 100%; box-sizing: border-box; text-align: center; background: #0a0f1a; color: #e5e7eb; border: 1px solid #334155; border-radius: 6px; }
    .gmw-window .gmw-pad .dir:focus, .gmw-window .gmw-pad .gmw-step:focus { outline: 2px solid #1d4ed8; outline-offset: 0; }
    .gmw-window .gmw-footer { display: flex; gap: 8px; justify-content: flex-end; margin-top: 4px; }
    .gmw-window .gmw-primary { background: #2563eb; color: #fff; border: 1px solid #1d4ed8; border-radius: 6px; padding: 4px 10px; font-size: 12px; }
    .gmw-window .gmw-primary:disabled { opacity: 0.6; cursor: not-allowed; }
</style>
@endpush

<div class="galaxy-map-widget" id="dashboardGalaxyMap">
    <div class="controls">
        <button type="button" class="btn btn-sm btn-dark" id="mapFullscreenToggle">
            <i class="fas fa-expand"></i> Plein écran
        </button>
        <button type="button" class="btn btn-sm btn-secondary" id="mapGotoButton" style="margin-left:6px;">
            Aller à…
        </button>
    </div>
    <div class="stage"></div>
    <div class="ruler-x"></div>
    <div class="ruler-y"></div>
    <div class="legend">
        <div class="item"><span class="dot" style="background:#48bb78"></span> Vos systèmes</div>
        <div class="item"><span class="dot" style="background:#f56565"></span> Systèmes ennemis</div>
        <div class="item"><span class="dot" style="background:#d69e2e"></span> Systèmes neutres</div>
    </div>
    <div class="loading-overlay" aria-hidden="true"><div class="spinner"></div></div>
    <div class="edge left" aria-hidden="true"></div>
    <div class="edge right" aria-hidden="true"></div>
    <div class="edge top" aria-hidden="true"></div>
    <div class="edge bottom" aria-hidden="true"></div>
</div>

@push('scripts')
<script>
    (function(){
        const onLoad = function() {
        const container = document.getElementById('dashboardGalaxyMap');
        if (!container) return;
        if (typeof console !== 'undefined' && console.log) {
            console.log('Galaxy map widget: script loaded and container found');
        }
        const stage = container.querySelector('.stage');
        const rulerX = container.querySelector('.ruler-x');
        const rulerY = container.querySelector('.ruler-y');
        const overlay = container.querySelector('.loading-overlay');
        const edgeL = container.querySelector('.edge.left');
        const edgeR = container.querySelector('.edge.right');
        const edgeT = container.querySelector('.edge.top');
        const edgeB = container.querySelector('.edge.bottom');

        function getSize() {
            return { width: container.clientWidth, height: container.clientHeight };
        }
        let { width, height } = getSize();
        // Star system navigation disabled; base URL removed
        // Admin/view-as support when this partial is embedded in admin views
        const adminMode = {!! isset($admin) && $admin ? 'true' : 'false' !!};
        const asCommanderId = {{ isset($asCommanderId) ? (int) $asCommanderId : 'null' }};
        const forcedGalaxyId = {{ isset($galaxyId) ? (int) $galaxyId : 'null' }};
        if (typeof console !== 'undefined' && console.log) {
            console.log('Galaxy map widget: init params', { adminMode, asCommanderId, forcedGalaxyId });
        }

        let mapData = null;
        function clearMap() { stage.innerHTML = ''; }
        // Fixed view window (columns x rows) with square cells
        let viewCols = 31, viewRows = 21;
        let cellSize = 10, offX = 0, offY = 0; // pixels
        let windowMinX = 0, windowMinY = 0;    // world coords
        let manualPan = false;                 // if true, do not auto re-center on capital
        let galaxySizeX = 0, galaxySizeY = 0;  // dynamic galaxy bounds for clamping
        let hideLoaderTimeout = null;
        // Allow overflow beyond right/bottom bounds during a drag if initial center starts beyond clamp
        let allowOverflowX = false, allowOverflowY = false;
        const stateKey = () => {
            // Persist viewport per current galaxy; fallback to forcedGalaxyId/default before data is loaded
            const galaxyKey = (mapData && mapData.galaxy && mapData.galaxy.id != null)
                ? mapData.galaxy.id
                : (forcedGalaxyId ?? 'default');
            const parts = [
                'gmw',
                adminMode ? 'adm' : 'ply',
                String(galaxyKey),
                String(asCommanderId ?? 'self')
            ];
            return parts.join(':');
        };
        function saveState() {
            try {
                const payload = { x: windowMinX, y: windowMinY, cols: viewCols, rows: viewRows, m: true };
                sessionStorage.setItem(stateKey(), JSON.stringify(payload));
                if (console && console.log) console.log('gmw:saveState', payload);
            } catch (_) {}
        }
        function restoreState() {
            try {
                const raw = sessionStorage.getItem(stateKey());
                if (!raw) return false;
                const s = JSON.parse(raw);
                if (Number.isFinite(s?.cols) && Number.isFinite(s?.rows)) {
                    viewCols = Math.max(1, Math.floor(s.cols));
                    viewRows = Math.max(1, Math.floor(s.rows));
                }
                if (Number.isFinite(s?.x) && Number.isFinite(s?.y)) {
                    windowMinX = s.x; windowMinY = s.y; manualPan = true;
                    // If saved window exceeds clamp, enable overflow so we don't snap to clampMax on first render
                    try {
                        const sizeX = Math.max(1, Math.round(mapData?.galaxy?.size_x ?? 1));
                        const sizeY = Math.max(1, Math.round(mapData?.galaxy?.size_y ?? 1));
                        const clampMaxX = Math.max(0, sizeX - viewCols);
                        const clampMaxY = Math.max(0, sizeY - viewRows);
                        allowOverflowX = windowMinX > clampMaxX;
                        allowOverflowY = windowMinY > clampMaxY;
                        if (console && console.log) console.log('gmw:restoreState', { windowMinX, windowMinY, manualPan, allowOverflowX, allowOverflowY, clampMaxX, clampMaxY });
                    } catch (_) {
                        if (console && console.log) console.log('gmw:restoreState (no clamp calc)');
                    }
                    return true;
                }
            } catch (_) {}
            return false;
        }
        function showLoading() {
            if (!overlay) return;
            overlay.classList.add('show');
            if (hideLoaderTimeout) { clearTimeout(hideLoaderTimeout); hideLoaderTimeout = null; }
        }
        function hideLoadingDebounced(delay = 120) {
            if (!overlay) return;
            if (hideLoaderTimeout) clearTimeout(hideLoaderTimeout);
            hideLoaderTimeout = setTimeout(() => overlay.classList.remove('show'), delay);
        }
        // Helpers for layout/zoom
        function clamp(n, min, max) { return Math.max(min, Math.min(max, n)); }
        function computeLayoutFor(cols, rows) {
            ({ width, height } = getSize());
            const leftPad = rulerY ? rulerY.clientWidth : 0;
            const topPad = rulerX ? rulerX.clientHeight : 0;
            const innerW = Math.max(0, width - leftPad);
            const innerH = Math.max(0, height - topPad);
            const cell = Math.floor(Math.min(innerW / cols, innerH / rows));
            const stageW = cell * cols;
            const stageH = cell * rows;
            const ox = leftPad + Math.floor((innerW - stageW) / 2);
            const oy = topPad + Math.floor((innerH - stageH) / 2);
            return { cell, offX: ox, offY: oy, stageW, stageH, innerW, innerH, leftPad, topPad };
        }

        // Recenter helper: go to world coords (cx, cy) and keep within bounds
        function recenterTo(cx, cy) {
            if (!mapData) return;
            const halfCols = Math.floor(viewCols / 2);
            const halfRows = Math.floor(viewRows / 2);
            // Ensure numbers
            const tx = Math.max(0, Math.floor(Number(cx)));
            const ty = Math.max(0, Math.floor(Number(cy)));
            const clampMaxX = Math.max(0, galaxySizeX - viewCols);
            const clampMaxY = Math.max(0, galaxySizeY - viewRows);
            manualPan = true;
            // Desired window without enforcing upper clamp: allow overflow to keep target centered
            const desiredWinX = Math.max(0, tx - halfCols);
            const desiredWinY = Math.max(0, ty - halfRows);
            allowOverflowX = desiredWinX > clampMaxX;
            allowOverflowY = desiredWinY > clampMaxY;
            windowMinX = desiredWinX;
            windowMinY = desiredWinY;
            if (console && console.log) console.log('gmw:goto', { tx, ty, clampMaxX, clampMaxY, windowMinX, windowMinY });
            saveState();
            showLoading();
            requestAnimationFrame(renderMap);
        }

        // Reload map data for a given galaxy id, then optionally run a callback
        function reloadMap(targetGalaxyId, after) {
            showLoading();
            let url = "{{ route('game.api.map') }}";
            const params = new URLSearchParams();
            if (adminMode) params.set('admin', '1');
            if (asCommanderId !== null) params.set('as_commander_id', String(asCommanderId));
            if (targetGalaxyId != null) params.set('galaxy_id', String(targetGalaxyId));
            const qs = params.toString();
            if (qs) url += `?${qs}`;
            if (typeof console !== 'undefined' && console.log) {
                console.log('Galaxy map widget: reloading URL', url);
            }
            fetch(url)
                .then(r => r.json())
                .then(data => {
                    mapData = data;
                    // Try to restore previous viewport for this galaxy before rendering
                    restoreState();
                    // First render to compute galaxy size and layout
                    renderMap();
                    // Notify any open modals to refresh their lists/options
                    try {
                        document.dispatchEvent(new CustomEvent('gmw:mapDataUpdated', { detail: { galaxyId: mapData?.galaxy?.id || null } }));
                    } catch (_) {}
                    if (typeof after === 'function') {
                        // Run after-load action (e.g., recenter to entered coords)
                        after();
                    }
                })
                .catch(err => {
                    console.error('Erreur rechargement carte:', err);
                });
        }

        // Lightweight modeless window manager (draggable)
        let gmwTopZ = 3000;
        let gmwCascade = 0;
        function spawnModelessWindow(title, renderBody, opts = {}) {
            const win = document.createElement('div');
            win.className = 'gmw-window';
            win.style.zIndex = String(++gmwTopZ);
            // cascade positions a bit
            const baseTop = 96 + (gmwCascade % 6) * 24;
            const baseLeft = 16 + (gmwCascade % 6) * 24;
            gmwCascade++;
            win.style.top = baseTop + 'px';
            win.style.left = baseLeft + 'px';

            const header = document.createElement('div');
            header.className = 'gmw-header';
            const hTitle = document.createElement('div');
            hTitle.className = 'gmw-title';
            hTitle.textContent = title || 'Fenêtre';
            const actions = document.createElement('div');
            actions.className = 'gmw-actions';
            const closeBtn = document.createElement('button');
            closeBtn.type = 'button';
            closeBtn.className = 'gmw-btn';
            closeBtn.textContent = 'Fermer';
            closeBtn.addEventListener('click', () => {
                win.remove();
                if (typeof opts.onClose === 'function') opts.onClose();
            });
            actions.appendChild(closeBtn);
            header.appendChild(hTitle);
            header.appendChild(actions);

            const body = document.createElement('div');
            body.className = 'gmw-body';

            win.appendChild(header);
            win.appendChild(body);
            document.body.appendChild(win);

            // Bring to front on mousedown
            win.addEventListener('mousedown', () => {
                win.style.zIndex = String(++gmwTopZ);
            });

            // Dragging
            let wDrag = false; let sx = 0, sy = 0; let startTop = 0, startLeft = 0;
            header.addEventListener('mousedown', (e) => {
                wDrag = true;
                sx = e.clientX; sy = e.clientY;
                startTop = win.offsetTop; startLeft = win.offsetLeft;
                e.preventDefault();
            });
            window.addEventListener('mousemove', (e) => {
                if (!wDrag) return;
                const dx = e.clientX - sx; const dy = e.clientY - sy;
                win.style.top = Math.max(0, startTop + dy) + 'px';
                win.style.left = Math.max(0, startLeft + dx) + 'px';
            });
            window.addEventListener('mouseup', () => { wDrag = false; });

            if (typeof renderBody === 'function') {
                renderBody(body, win);
            }
            return win;
        }

        let gotoWin = null;
        function openGotoWindow() {
            let onMapDataUpdatedHandler = null;
            return spawnModelessWindow('Aller aux coordonnées', (body, win) => {
                // Helper: build selectable lists from mapData
                const systemsById = {};
                if (mapData && Array.isArray(mapData.systems)) {
                    mapData.systems.forEach(s => { systemsById[s.id] = s; });
                }

                // Mode selector
                const modeRow = document.createElement('div'); modeRow.className = 'gmw-row';
                const modeLabel = document.createElement('label'); modeLabel.textContent = 'Mode';
                const modeSelect = document.createElement('select'); modeSelect.className = 'gmw-num'; modeSelect.style.width = 'auto';
                const optCoords = document.createElement('option'); optCoords.value = 'coords'; optCoords.textContent = 'Coordonnées';
                const optSystems = document.createElement('option'); optSystems.value = 'systems'; optSystems.textContent = 'Systèmes détectés';
                const optFleets = document.createElement('option'); optFleets.value = 'fleets'; optFleets.textContent = 'Flottes détectées';
                const optPad = document.createElement('option'); optPad.value = 'pad'; optPad.textContent = 'Navigation';
                modeSelect.appendChild(optCoords);
                modeSelect.appendChild(optPad);
                modeSelect.appendChild(optSystems);
                modeSelect.appendChild(optFleets);
                modeRow.appendChild(modeLabel); modeRow.appendChild(modeSelect);
                // Commander selectors are now placed inside per-mode filter sections (systems/fleets)

                // Coordinates section
                const coordsSec = document.createElement('div');
                const row1 = document.createElement('div'); row1.className = 'gmw-row';
                const labelX = document.createElement('label'); labelX.textContent = 'X';
                const inputX = document.createElement('input'); inputX.type = 'number'; inputX.className = 'gmw-num'; inputX.placeholder = 'ex: 462';
                row1.appendChild(labelX); row1.appendChild(inputX);
                const row2 = document.createElement('div'); row2.className = 'gmw-row';
                const labelY = document.createElement('label'); labelY.textContent = 'Y';
                const inputY = document.createElement('input'); inputY.type = 'number'; inputY.className = 'gmw-num'; inputY.placeholder = 'ex: 637';
                row2.appendChild(labelY); row2.appendChild(inputY);
                // Galaxy selector (coords mode only; hidden if only one visible galaxy)
                const galaxyRow = document.createElement('div'); galaxyRow.className = 'gmw-row';
                const galaxyLabel = document.createElement('label'); galaxyLabel.textContent = 'Gal';
                const galaxySelect = document.createElement('select'); galaxySelect.className = 'gmw-num'; galaxySelect.style.width = '100%';
                galaxyRow.appendChild(galaxyLabel); galaxyRow.appendChild(galaxySelect);
                // Step navigation pad (8 directions around a step input)
                const stepRow = document.createElement('div'); stepRow.className = 'gmw-row';
                const stepLabel = document.createElement('label'); stepLabel.textContent = 'Pas';
                const padWrap = document.createElement('div'); padWrap.style.flex = '1';
                padWrap.className = 'gmw-pad-block';
                const pad = document.createElement('div'); pad.className = 'gmw-pad';
                // Step input in center
                const stepInput = document.createElement('input');
                stepInput.type = 'number'; stepInput.min = '1'; stepInput.step = '1'; stepInput.value = '5';
                stepInput.placeholder = '1'; stepInput.className = 'gmw-num gmw-step';
                stepInput.addEventListener('input', () => {
                    const v = parseInt(stepInput.value, 10);
                    if (!Number.isFinite(v) || v < 1) stepInput.value = '1';
                });
                // Helper to move relative to current center
                const moveBy = (dx, dy) => {
                    let s = parseInt(stepInput.value, 10);
                    if (!Number.isFinite(s) || s < 1) s = 1;
                    const cx = windowMinX + Math.floor(viewCols / 2);
                    const cy = windowMinY + Math.floor(viewRows / 2);
                    recenterTo(cx + dx * s, cy + dy * s);
                };
                // Button factory
                const mkBtn = (txt, title, dx, dy) => {
                    const b = document.createElement('button');
                    b.type = 'button'; b.className = 'dir'; b.textContent = txt; if (title) b.title = title;
                    b.addEventListener('click', () => moveBy(dx, dy));
                    return b;
                };
                // Row 1: NW, N, NE
                pad.appendChild(mkBtn('↖', 'Nord-Ouest', -1, -1));
                pad.appendChild(mkBtn('↑', 'Nord', 0, -1));
                pad.appendChild(mkBtn('↗', 'Nord-Est', 1, -1));
                // Row 2: W, [step], E
                pad.appendChild(mkBtn('←', 'Ouest', -1, 0));
                pad.appendChild(stepInput);
                pad.appendChild(mkBtn('→', 'Est', 1, 0));
                // Row 3: SW, S, SE
                pad.appendChild(mkBtn('↙', 'Sud-Ouest', -1, 1));
                pad.appendChild(mkBtn('↓', 'Sud', 0, 1));
                pad.appendChild(mkBtn('↘', 'Sud-Est', 1, 1));
                padWrap.appendChild(pad);
                stepRow.appendChild(stepLabel); stepRow.appendChild(padWrap);
                coordsSec.appendChild(row1); coordsSec.appendChild(row2); coordsSec.appendChild(galaxyRow);
                // Navigation pad section (own category)
                const padSec = document.createElement('div');
                padSec.appendChild(stepRow);

                // Systems section
                const sysSec = document.createElement('div');
                const sysRow = document.createElement('div'); sysRow.className = 'gmw-row';
                const sysLabel = document.createElement('label'); sysLabel.textContent = 'Sys';
                const sysSelect = document.createElement('select'); sysSelect.className = 'gmw-num'; sysSelect.style.width = '100%';
                sysRow.appendChild(sysLabel); sysRow.appendChild(sysSelect);
                sysSec.appendChild(sysRow);
                // Systems filters (hidden by default)
                const sysFilterToggleRow = document.createElement('div'); sysFilterToggleRow.className = 'gmw-row';
                const sysFilterToggleLabel = document.createElement('label'); sysFilterToggleLabel.textContent = '';
                const sysFilterToggleBtn = document.createElement('button'); sysFilterToggleBtn.type = 'button'; sysFilterToggleBtn.className = 'gmw-btn'; sysFilterToggleBtn.textContent = 'Filtres';
                sysFilterToggleRow.appendChild(sysFilterToggleLabel); sysFilterToggleRow.appendChild(sysFilterToggleBtn);
                sysSec.appendChild(sysFilterToggleRow);
                const sysFilters = document.createElement('div'); sysFilters.style.display = 'none';
                // Commander filter for systems
                const sysCmdRow = document.createElement('div'); sysCmdRow.className = 'gmw-row';
                const sysCmdLabel = document.createElement('label'); sysCmdLabel.textContent = 'Cmdt';
                const sysCommanderSelect = document.createElement('select'); sysCommanderSelect.className = 'gmw-num'; sysCommanderSelect.style.width = '100%';
                sysCmdRow.appendChild(sysCmdLabel); sysCmdRow.appendChild(sysCommanderSelect);
                sysFilters.appendChild(sysCmdRow);
                sysSec.appendChild(sysFilters);

                // Fleets section
                const fltSec = document.createElement('div');
                const fltRow = document.createElement('div'); fltRow.className = 'gmw-row';
                const fltLabel = document.createElement('label'); fltLabel.textContent = 'Fl';
                const fltSelect = document.createElement('select'); fltSelect.className = 'gmw-num'; fltSelect.style.width = '100%';
                fltRow.appendChild(fltLabel); fltRow.appendChild(fltSelect);
                fltSec.appendChild(fltRow);

                // Fleets filters (toggleable)
                const fltFilterToggleRow = document.createElement('div'); fltFilterToggleRow.className = 'gmw-row';
                const fltFilterToggleLabel = document.createElement('label'); fltFilterToggleLabel.textContent = '';
                const fltFilterToggleBtn = document.createElement('button'); fltFilterToggleBtn.type = 'button'; fltFilterToggleBtn.className = 'gmw-btn'; fltFilterToggleBtn.textContent = 'Filtres';
                fltFilterToggleRow.appendChild(fltFilterToggleLabel); fltFilterToggleRow.appendChild(fltFilterToggleBtn);
                fltSec.appendChild(fltFilterToggleRow);
                const fltFilters = document.createElement('div'); fltFilters.style.display = 'none';
                // Commander filter for fleets
                const fltCmdRow = document.createElement('div'); fltCmdRow.className = 'gmw-row';
                const fltCmdLabel = document.createElement('label'); fltCmdLabel.textContent = 'Cmdt';
                const fltCommanderSelect = document.createElement('select'); fltCommanderSelect.className = 'gmw-num'; fltCommanderSelect.style.width = '100%';
                fltCmdRow.appendChild(fltCmdLabel); fltCmdRow.appendChild(fltCommanderSelect);
                fltFilters.appendChild(fltCmdRow);
                // Fleet size min/max
                const fltSizeRow = document.createElement('div'); fltSizeRow.className = 'gmw-row';
                const fltMinLabel = document.createElement('label'); fltMinLabel.textContent = 'Min';
                const fltMinInput = document.createElement('input'); fltMinInput.type = 'number'; fltMinInput.className = 'gmw-num'; fltMinInput.placeholder = '0'; fltMinInput.min = '0';
                const fltMaxLabel = document.createElement('label'); fltMaxLabel.textContent = 'Max';
                const fltMaxInput = document.createElement('input'); fltMaxInput.type = 'number'; fltMaxInput.className = 'gmw-num'; fltMaxInput.placeholder = '∞'; fltMaxInput.min = '0';
                fltSizeRow.appendChild(fltMinLabel); fltSizeRow.appendChild(fltMinInput);
                fltSizeRow.appendChild(fltMaxLabel); fltSizeRow.appendChild(fltMaxInput);
                fltFilters.appendChild(fltSizeRow);
                fltSec.appendChild(fltFilters);

                // Helpers for filters
                function getSelectedValues(selectEl) {
                    const vals = [];
                    for (const opt of Array.from(selectEl?.options || [])) { if (opt.selected) vals.push(opt.value); }
                    return vals;
                }
                function buildGalaxyOptions() {
                    galaxySelect.innerHTML = '';
                    const list = (mapData && Array.isArray(mapData.visible_galaxies)) ? mapData.visible_galaxies : [];
                    list.forEach(g => {
                        const o = document.createElement('option');
                        o.value = String(g.id);
                        o.textContent = g.name || ('Galaxie ' + g.id);
                        galaxySelect.appendChild(o);
                    });
                    const cur = mapData && mapData.galaxy && mapData.galaxy.id != null ? String(mapData.galaxy.id) : null;
                    if (cur !== null) galaxySelect.value = cur;
                }
                function buildCommanderOptionsFor(selectEl) {
                    if (!selectEl) return;
                    selectEl.innerHTML = '';
                    const addOpt = (val, label) => { const o = document.createElement('option'); o.value = val; o.textContent = label; selectEl.appendChild(o); };
                    // Default: show all
                    addOpt('all', 'Tous');
                    const systems = (mapData && Array.isArray(mapData.systems)) ? mapData.systems : [];
                    const fleets = (mapData && Array.isArray(mapData.fleets)) ? mapData.fleets : [];
                    const hasSelf = systems.some(s => s.owner === 'self') || fleets.some(f => f.owner === 'self') || !!(mapData && mapData.commander);
                    const hasNeutral = systems.some(s => s.owner === 'neutral') || fleets.some(f => f.owner === 'neutral');
                    const seen = new Map(); // id -> name
                    systems.forEach(s => {
                        if (s.owner_commander_id != null && s.owner !== 'self' && s.owner !== 'neutral' && s.owner_commander_name) {
                            const key = String(s.owner_commander_id);
                            if (!seen.has(key)) seen.set(key, s.owner_commander_name);
                        }
                    });
                    fleets.forEach(f => {
                        if (f.owner_commander_id != null && f.owner !== 'self' && f.owner_commander_name) {
                            const key = String(f.owner_commander_id);
                            if (!seen.has(key)) seen.set(key, f.owner_commander_name);
                        }
                    });
                    if (hasSelf) addOpt('self', 'Vous');
                    if (hasNeutral) addOpt('neutral', 'Neutre');
                    Array.from(seen.entries()).sort((a,b) => a[1].localeCompare(b[1]))
                        .forEach(([id, name]) => addOpt('cmd:' + id, name));
                    selectEl.value = 'all';
                }

                function systemPassesCommander(s) {
                    const sel = (sysCommanderSelect && sysCommanderSelect.value) || 'all';
                    if (sel === 'all') return true;
                    if (sel === 'self') return s.owner === 'self';
                    if (sel === 'neutral') return s.owner === 'neutral';
                    if (sel.startsWith('cmd:')) {
                        const id = parseInt(sel.slice(4), 10);
                        return Number.isFinite(id) && s.owner_commander_id === id;
                    }
                    return true;
                }

                // Populate systems list: own first, then others, sort by coords and apply filters
                function populateSystems() {
                    sysSelect.innerHTML = '';
                    const systems = (mapData && Array.isArray(mapData.systems)) ? mapData.systems.slice() : [];
                    const filtered = systems.filter(systemPassesCommander);
                    const own = filtered.filter(s => s.owner === 'self');
                    const others = filtered.filter(s => s.owner !== 'self');
                    const byCoord = (a, b) => (a.x - b.x) || (a.y - b.y);
                    own.sort(byCoord); others.sort(byCoord);
                    const ordered = own.concat(others);
                    if (!ordered.length) {
                        const opt = document.createElement('option'); opt.value = ''; opt.textContent = 'Aucun système';
                        sysSelect.appendChild(opt);
                        return 0;
                    }
                    ordered.forEach(s => {
                        const opt = document.createElement('option');
                        opt.value = `${s.x},${s.y}`;
                        const ownerTag = (s.owner === 'self') ? 'Vous' : (s.owner === 'neutral' ? 'Neutre' : (s.owner_commander_name || 'Autre'));
                        opt.textContent = `${s.name || 'Système'} — ${ownerTag} — X:${s.x} Y:${s.y}`;
                        sysSelect.appendChild(opt);
                    });
                    return ordered.length;
                }

                // Populate fleets list: own first, then others, sort by coords (use f.x/f.y or system coords) and apply filters
                function populateFleets() {
                    fltSelect.innerHTML = '';
                    const fleets = (mapData && Array.isArray(mapData.fleets)) ? mapData.fleets.slice() : [];
                    const withCoords = [];
                    fleets.forEach(f => {
                        let fx = Number.isFinite(f.x) ? f.x : null;
                        let fy = Number.isFinite(f.y) ? f.y : null;
                        if ((fx == null || fy == null) && f.system_id && systemsById[f.system_id]) {
                            fx = systemsById[f.system_id].x;
                            fy = systemsById[f.system_id].y;
                        }
                        if (Number.isFinite(fx) && Number.isFinite(fy)) {
                            withCoords.push({ f, x: fx, y: fy });
                        }
                    });
                    // Apply filters: commander (global) and size range
                    const minVal = fltMinInput.value !== '' ? parseInt(fltMinInput.value, 10) : null;
                    const maxVal = fltMaxInput.value !== '' ? parseInt(fltMaxInput.value, 10) : null;
                    const filtered = withCoords.filter(o => {
                        // Commander filter
                        const sel = (fltCommanderSelect && fltCommanderSelect.value) || 'all';
                        if (sel !== 'all') {
                            if (sel === 'self') {
                                if (o.f.owner !== 'self') return false;
                            } else if (sel === 'neutral') {
                                if (o.f.owner !== 'neutral') return false;
                            } else if (sel.startsWith('cmd:')) {
                                const id = parseInt(sel.slice(4), 10);
                                if (!(Number.isFinite(id) && o.f.owner_commander_id === id)) return false;
                            }
                        }
                        // Size filter
                        const size = Number.isFinite(o.f.size) ? o.f.size : 0;
                        if (minVal !== null && Number.isFinite(minVal) && size < minVal) return false;
                        if (maxVal !== null && Number.isFinite(maxVal) && size > maxVal) return false;
                        return true;
                    });
                    const own = filtered.filter(o => o.f.owner === 'self');
                    const others = filtered.filter(o => o.f.owner !== 'self');
                    const byCoord = (a, b) => (a.x - b.x) || (a.y - b.y);
                    own.sort(byCoord); others.sort(byCoord);
                    const ordered = own.concat(others);
                    if (!ordered.length) {
                        const opt = document.createElement('option'); opt.value = ''; opt.textContent = 'Aucune flotte';
                        fltSelect.appendChild(opt);
                        return 0;
                    }
                    ordered.forEach(o => {
                        const opt = document.createElement('option');
                        opt.value = `${o.x},${o.y}`;
                        const name = o.f.name || 'Flotte';
                        const ownerTag = (o.f.owner === 'self') ? 'Vous' : (o.f.owner_commander_name || 'Autre');
                        const sizeStr = Number.isFinite(o.f.size) ? ` — T:${o.f.size}` : '';
                        opt.textContent = `${name} — ${ownerTag}${sizeStr} — X:${o.x} Y:${o.y}`;
                        fltSelect.appendChild(opt);
                    });
                    return ordered.length;
                }

                // Footer with single primary button
                const footer = document.createElement('div'); footer.className = 'gmw-footer';
                const goBtn = document.createElement('button'); goBtn.type = 'button'; goBtn.className = 'gmw-primary'; goBtn.textContent = 'Centrer';
                footer.appendChild(goBtn);

                // Assemble body
                body.appendChild(modeRow);
                body.appendChild(coordsSec);
                body.appendChild(padSec);
                body.appendChild(sysSec);
                body.appendChild(fltSec);
                body.appendChild(footer);

                // Show/hide sections based on mode
                function updateModeUI() {
                    const mode = modeSelect.value;
                    coordsSec.style.display = (mode === 'coords') ? 'block' : 'none';
                    sysSec.style.display = (mode === 'systems') ? 'block' : 'none';
                    fltSec.style.display = (mode === 'fleets') ? 'block' : 'none';
                    padSec.style.display = (mode === 'pad') ? 'block' : 'none';
                    // Footer hidden in pad mode
                    footer.style.display = (mode === 'pad') ? 'none' : 'flex';
                    // Galaxy selector only when in coords mode and multiple galaxies visible
                    galaxyRow.style.display = (mode === 'coords' && mapData && Array.isArray(mapData.visible_galaxies) && mapData.visible_galaxies.length > 1) ? 'flex' : 'none';
                    // Enable button only when inputs are valid
                    if (mode === 'coords') {
                        const vx = parseInt(inputX.value, 10);
                        const vy = parseInt(inputY.value, 10);
                        goBtn.disabled = !(Number.isFinite(vx) && Number.isFinite(vy));
                    } else if (mode === 'systems') {
                        goBtn.disabled = !sysSelect.value;
                    } else if (mode === 'fleets') {
                        goBtn.disabled = !fltSelect.value;
                    } else if (mode === 'pad') {
                        goBtn.disabled = true;
                    }
                }

                // Initialize lists and mode
                buildGalaxyOptions();
                buildCommanderOptionsFor(sysCommanderSelect);
                buildCommanderOptionsFor(fltCommanderSelect);
                populateSystems();
                populateFleets();
                modeSelect.value = 'coords';
                updateModeUI();

                // Listeners
                modeSelect.addEventListener('change', updateModeUI);
                [inputX, inputY].forEach(inp => inp.addEventListener('input', updateModeUI));
                galaxySelect.addEventListener('change', () => {
                    // Immediate reload when switching galaxy in coordinates mode
                    if (modeSelect.value === 'coords') {
                        const currentGalaxyId = mapData && mapData.galaxy ? parseInt(mapData.galaxy.id, 10) : null;
                        const targetGalaxyId = galaxySelect && galaxySelect.value ? parseInt(galaxySelect.value, 10) : currentGalaxyId;
                        if (currentGalaxyId != null && targetGalaxyId != null && currentGalaxyId !== targetGalaxyId) {
                            reloadMap(targetGalaxyId, () => {
                                // After reload, if coords are valid, recenter to them; otherwise restored viewport is used
                                const vx = parseInt(inputX.value, 10);
                                const vy = parseInt(inputY.value, 10);
                                if (Number.isFinite(vx) && Number.isFinite(vy)) recenterTo(vx, vy);
                            });
                        }
                    }
                    updateModeUI();
                });
                sysSelect.addEventListener('change', updateModeUI);
                fltSelect.addEventListener('change', updateModeUI);
                // Filter toggles
                sysFilterToggleBtn.addEventListener('click', () => {
                    sysFilters.style.display = (sysFilters.style.display === 'none') ? 'block' : 'none';
                });
                fltFilterToggleBtn.addEventListener('click', () => {
                    fltFilters.style.display = (fltFilters.style.display === 'none') ? 'block' : 'none';
                });
                // Unified commander filter + fleet size
                sysCommanderSelect.addEventListener('change', () => { populateSystems(); updateModeUI(); });
                fltCommanderSelect.addEventListener('change', () => { populateFleets(); updateModeUI(); });
                fltMinInput.addEventListener('input', () => { populateFleets(); updateModeUI(); });
                fltMaxInput.addEventListener('input', () => { populateFleets(); updateModeUI(); });

                // Submit actions
                goBtn.addEventListener('click', () => {
                    const mode = modeSelect.value;
                    if (mode === 'coords') {
                        const vx = parseInt(inputX.value, 10);
                        const vy = parseInt(inputY.value, 10);
                        if (Number.isFinite(vx) && Number.isFinite(vy)) {
                            const currentGalaxyId = mapData && mapData.galaxy ? parseInt(mapData.galaxy.id, 10) : null;
                            const targetGalaxyId = galaxySelect && galaxySelect.value ? parseInt(galaxySelect.value, 10) : currentGalaxyId;
                            if (currentGalaxyId != null && targetGalaxyId != null && currentGalaxyId !== targetGalaxyId) {
                                reloadMap(targetGalaxyId, () => recenterTo(vx, vy));
                            } else {
                                recenterTo(vx, vy);
                            }
                        } else {
                            inputX.focus();
                        }
                    } else if (mode === 'systems') {
                        if (sysSelect.value) {
                            const [sx, sy] = sysSelect.value.split(',').map(n => parseInt(n, 10));
                            if (Number.isFinite(sx) && Number.isFinite(sy)) recenterTo(sx, sy);
                        }
                    } else if (mode === 'fleets') {
                        if (fltSelect.value) {
                            const [fx, fy] = fltSelect.value.split(',').map(n => parseInt(n, 10));
                            if (Number.isFinite(fx) && Number.isFinite(fy)) recenterTo(fx, fy);
                        }
                    }
                });

                // Submit on Enter for inputs
                [inputX, inputY].forEach(inp => inp.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') { goBtn.click(); }
                }));

                // Refresh modal content when map data is updated externally (e.g., galaxy switch)
                onMapDataUpdatedHandler = () => {
                    buildGalaxyOptions();
                    buildCommanderOptionsFor(sysCommanderSelect);
                    buildCommanderOptionsFor(fltCommanderSelect);
                    populateSystems();
                    populateFleets();
                    updateModeUI();
                };
                document.addEventListener('gmw:mapDataUpdated', onMapDataUpdatedHandler);
            }, { onClose: () => { if (onMapDataUpdatedHandler) document.removeEventListener('gmw:mapDataUpdated', onMapDataUpdatedHandler); gotoWin = null; } });
        }

        function toggleGotoWindow() {
            if (gotoWin && document.body.contains(gotoWin)) {
                gotoWin.remove();
                gotoWin = null;
            } else {
                gotoWin = openGotoWindow();
            }
        }

        function drawGrid() {
            const grid = document.createElement('div');
            // vertical grid lines (x)
            for (let i = 0; i <= viewCols; i++) {
                const line = document.createElement('div');
                line.className = 'grid-line x';
                line.style.left = (i * cellSize) + 'px';
                grid.appendChild(line);
            }
            // horizontal grid lines (y)
            for (let j = 0; j <= viewRows; j++) {
                const line = document.createElement('div');
                line.className = 'grid-line y';
                line.style.top = (j * cellSize) + 'px';
                grid.appendChild(line);
            }
            stage.appendChild(grid);
        }
        function drawRulers() {
            rulerX.innerHTML = '';
            rulerY.innerHTML = '';
            // choose label density based on cell size
            const labelEveryX = cellSize >= 30 ? 1 : (cellSize >= 18 ? 2 : (cellSize >= 12 ? 5 : Math.ceil(viewCols / 2)));
            const labelEveryY = cellSize >= 30 ? 1 : (cellSize >= 18 ? 2 : (cellSize >= 12 ? 5 : Math.ceil(viewRows / 2)));
            // X axis
            for (let i = 0; i <= viewCols; i++) {
                const px = offX + i * cellSize;
                if (px < 0 || px > width) continue;
                const t = document.createElement('div');
                t.className = 'tick';
                t.style.left = px + 'px';
                rulerX.appendChild(t);
                // Center labels within cells: place label at center of cell [i]
                // Skip last grid line (i === viewCols) to avoid labeling beyond last cell
                if ((i % labelEveryX === 0 || i === 0) && i < viewCols) {
                    const lbl = document.createElement('div');
                    lbl.className = 'label';
                    lbl.style.left = (px + cellSize / 2) + 'px';
                    lbl.textContent = Math.round(windowMinX + i);
                    rulerX.appendChild(lbl);
                }
            }
            // Y axis
            for (let j = 0; j <= viewRows; j++) {
                const py = offY + j * cellSize;
                if (py < 0 || py > height) continue;
                const t = document.createElement('div');
                t.className = 'tick';
                t.style.top = py + 'px';
                rulerY.appendChild(t);
                // Center labels within cells: place label at center of cell [j]
                // Skip last grid line (j === viewRows) to avoid labeling beyond last cell
                if ((j % labelEveryY === 0 || j === 0) && j < viewRows) {
                    const lbl = document.createElement('div');
                    lbl.className = 'label';
                    lbl.style.top = (py + cellSize / 2) + 'px';
                    lbl.textContent = Math.round(windowMinY + j);
                    rulerY.appendChild(lbl);
                }
            }
        }
        function renderMap() {
            if (typeof console !== 'undefined' && console.log) {
                console.log('renderMap() start', { hasMapData: !!mapData });
            }
            if (!mapData) return;
            const defaultSizeX = Math.max(1, Math.round(mapData.galaxy.size_x || 100));
            const defaultSizeY = Math.max(1, Math.round(mapData.galaxy.size_y || 100));
            ({ width, height } = getSize());

            // Build fast lookup and find capital coords
            const systemsById = {};
            (mapData.systems || []).forEach(sys => { systemsById[sys.id] = sys; });
            const capIdFromApi = (mapData.commander && mapData.commander.capital_system_id != null)
                ? parseInt(mapData.commander.capital_system_id)
                : null;
            const capApiX = (mapData.commander && mapData.commander.capital_x != null)
                ? parseInt(mapData.commander.capital_x)
                : null;
            const capApiY = (mapData.commander && mapData.commander.capital_y != null)
                ? parseInt(mapData.commander.capital_y)
                : null;
            let capX = null, capY = null;
            let centerReason = 'unknown';
            if (adminMode && (asCommanderId === null || Number.isNaN(asCommanderId))) {
                // Admin free-view: center to galaxy midpoint regardless of commander presence
                capX = Math.floor(defaultSizeX / 2);
                capY = Math.floor(defaultSizeY / 2);
                centerReason = 'admin_midpoint';
            } else if (capApiX !== null && capApiY !== null) {
                capX = capApiX; capY = capApiY; centerReason = 'capital_coords';
            } else if (capIdFromApi && systemsById[capIdFromApi]) {
                capX = systemsById[capIdFromApi].x;
                capY = systemsById[capIdFromApi].y;
                centerReason = 'capital_system';
            } else {
                const owned = (mapData.systems || []).find(s => s.owner === 'self');
                if (owned) {
                    capX = owned.x; capY = owned.y; centerReason = 'owned_system';
                } else {
                    // If player has own fleets, try to center on the first one's coords (or its system coords)
                    const ownFleet = (mapData.fleets || []).find(f => f && f.owner === 'self');
                    if (ownFleet) {
                        let fx = null, fy = null;
                        if (Number.isFinite(ownFleet.x) && Number.isFinite(ownFleet.y)) { fx = ownFleet.x; fy = ownFleet.y; }
                        else if (ownFleet.system_id && systemsById[ownFleet.system_id]) { fx = systemsById[ownFleet.system_id].x; fy = systemsById[ownFleet.system_id].y; }
                        if (fx != null && fy != null) { capX = fx; capY = fy; centerReason = 'own_fleet'; }
                    }
                    // If still nothing, pick first visible system (if any)
                    if (capX == null && (mapData.systems || []).length) {
                        capX = mapData.systems[0].x; capY = mapData.systems[0].y; centerReason = 'first_visible_system';
                    }
                    // Last resort: galaxy midpoint
                    if (capX == null) {
                        capX = Math.floor(defaultSizeX / 2);
                        capY = Math.floor(defaultSizeY / 2);
                        centerReason = 'galaxy_midpoint';
                    }
                }
            }
            if (typeof console !== 'undefined' && console.log) {
                console.log('centerChoice', { centerReason, capX, capY });
            }
            // Compute dynamic galaxy size from data so we don't clamp off-screen when DB size is smaller than coordinates
            let maxX = defaultSizeX - 1;
            let maxY = defaultSizeY - 1;
            (mapData.systems || []).forEach(s => {
                if (Number.isFinite(s.x)) maxX = Math.max(maxX, s.x);
                if (Number.isFinite(s.y)) maxY = Math.max(maxY, s.y);
            });
            (mapData.fleets || []).forEach(f => {
                let fx = null, fy = null;
                if (Number.isFinite(f.x) && Number.isFinite(f.y)) { fx = f.x; fy = f.y; }
                else if (f.system_id && systemsById[f.system_id]) { fx = systemsById[f.system_id].x; fy = systemsById[f.system_id].y; }
                if (fx != null) maxX = Math.max(maxX, fx);
                if (fy != null) maxY = Math.max(maxY, fy);
            });
            if (capApiX !== null && capApiY !== null) { maxX = Math.max(maxX, capApiX); maxY = Math.max(maxY, capApiY); }
            const sizeX = Math.max(defaultSizeX, maxX + 1);
            const sizeY = Math.max(defaultSizeY, maxY + 1);
            galaxySizeX = sizeX; galaxySizeY = sizeY;

            // Debug diagnostics for centering
            if (typeof console !== 'undefined' && console.log) {
                console.log('Map center source', {
                    fromApi: { capIdFromApi, capApiX, capApiY },
                    chosen: { capX, capY },
                    windowBeforeClamp: { x0: Math.floor(capX) - Math.floor(viewCols/2), y0: Math.floor(capY) - Math.floor(viewRows/2) },
                    size: { sizeX, sizeY }
                });
            }

            // Compute window around capital (31x21).
            // Only clamp to 0 (no negative). Allow overflow past galaxy size to keep the capital centered near edges.
            const halfCols = Math.floor(viewCols / 2);
            const halfRows = Math.floor(viewRows / 2);
            if (manualPan) {
                // Respect manual pan; clamp using the true galaxy size to avoid being limited by current visibility
                const clampMaxX = Math.max(0, galaxySizeX - viewCols);
                const clampMaxY = Math.max(0, galaxySizeY - viewRows);
                // Recompute overflow policy based on current viewport vs clamp.
                // This prevents snapping back when toggling fullscreen or resizing after exploring near edges.
                allowOverflowX = windowMinX > clampMaxX;
                allowOverflowY = windowMinY > clampMaxY;
                // Always prevent negative; only enforce upper clamp if not in overflow mode.
                windowMinX = Math.max(0, allowOverflowX ? windowMinX : Math.min(windowMinX, clampMaxX));
                windowMinY = Math.max(0, allowOverflowY ? windowMinY : Math.min(windowMinY, clampMaxY));
                if (typeof console !== 'undefined' && console.log) {
                    console.log('clampPolicy', { manualPan, allowOverflowX, allowOverflowY, clampMaxX, clampMaxY, windowMinX, windowMinY });
                }
            } else {
                windowMinX = Math.max(0, Math.floor(capX) - halfCols);
                windowMinY = Math.max(0, Math.floor(capY) - halfRows);
            }
            if (typeof console !== 'undefined' && console.log) {
                const dx = Math.round(capX - windowMinX);
                const dy = Math.round(capY - windowMinY);
                console.log('windowAfterClamp', { windowMinX, windowMinY, halfCols, halfRows, dx, dy });
            }

            // Player-side debug: capital coords, chosen center, user_id and commander_id
            if (!adminMode && typeof console !== 'undefined' && console.log && mapData && mapData.commander) {
                const effectiveCenterX = windowMinX + halfCols;
                const effectiveCenterY = windowMinY + halfRows;
                console.log('Galaxy map (player) debug', {
                    user_id: mapData.commander.user_id,
                    commander_id: mapData.commander.id,
                    capital_from_api: { id: capIdFromApi, x: capApiX, y: capApiY },
                    chosen_center: { x: capX, y: capY },
                    effective_center: { x: effectiveCenterX, y: effectiveCenterY }
                });
            }

            // Square cells, center stage in the visible plot area (exclude rulers)
            const leftPad = rulerY ? rulerY.clientWidth : 0;   // ~34px
            const topPad = rulerX ? rulerX.clientHeight : 0;   // ~20px
            const innerW = Math.max(0, width - leftPad);
            const innerH = Math.max(0, height - topPad);
            cellSize = Math.floor(Math.min(innerW / viewCols, innerH / viewRows));
            const stageW = cellSize * viewCols;
            const stageH = cellSize * viewRows;
            offX = leftPad + Math.floor((innerW - stageW) / 2);
            offY = topPad + Math.floor((innerH - stageH) / 2);
            if (typeof console !== 'undefined' && console.log) {
                console.log('stageLayout', { leftPad, topPad, innerW, innerH, stageW, stageH, offX, offY, cellSize });
            }

            clearMap();
            stage.style.width = stageW + 'px';
            stage.style.height = stageH + 'px';
            stage.style.transform = `translate(${offX}px, ${offY}px)`;

            // Grid first
            drawGrid();
            // Decide which system id to highlight as capital
            const highlightCapId = (capIdFromApi && !Number.isNaN(capIdFromApi)) ? capIdFromApi : null;

            // Systems within window
            (mapData.systems || []).forEach(sys => {
                if (sys.x < windowMinX || sys.x >= windowMinX + viewCols || sys.y < windowMinY || sys.y >= windowMinY + viewRows) return;
                const px = (sys.x - windowMinX) * cellSize;
                const py = (sys.y - windowMinY) * cellSize;
                const el = document.createElement('div');
                const owner = (sys.owner === 'self') ? 'owned' : (sys.owner === 'neutral' ? 'neutral' : 'enemy');
                el.className = 'star-system ' + owner;
                if (highlightCapId && sys.id === highlightCapId) el.classList.add('capital');
                el.style.left = (px - 3 + cellSize / 2) + 'px';
                el.style.top = (py - 3 + cellSize / 2) + 'px';
                el.title = sys.name;
                el.setAttribute('data-id', sys.id);
                // Clicks on star systems are intentionally disabled (no navigation)
                const star = document.createElement('div');
                star.className = 'star';
                el.appendChild(star);
                stage.appendChild(el);
            });

            // Fleets near their system if inside window
            (mapData.fleets || []).forEach(fleet => {
                if (!fleet.system_id || !systemsById[fleet.system_id]) return;
                const sys = systemsById[fleet.system_id];
                if (sys.x < windowMinX || sys.x >= windowMinX + viewCols || sys.y < windowMinY || sys.y >= windowMinY + viewRows) return;
                const px = (sys.x - windowMinX) * cellSize;
                const py = (sys.y - windowMinY) * cellSize;
                const f = document.createElement('div');
                f.className = 'fleet';
                f.style.left = (px + cellSize / 2 + 6) + 'px';
                f.style.top = (py + cellSize / 2 - 5) + 'px';
                f.title = fleet.name || 'Flotte';
                stage.appendChild(f);
            });

            // Rulers after layout
            drawRulers();
            // Edge indicators at bounds (based on true galaxy extents)
            const atLeft = windowMinX <= 0;
            const atTop = windowMinY <= 0;
            const atRight = windowMinX >= Math.max(0, galaxySizeX - viewCols);
            const atBottom = windowMinY >= Math.max(0, galaxySizeY - viewRows);
            if (edgeL) edgeL.classList.toggle('show', atLeft);
            if (edgeR) edgeR.classList.toggle('show', atRight);
            if (edgeT) edgeT.classList.toggle('show', atTop);
            if (edgeB) edgeB.classList.toggle('show', atBottom);

            // Hide loader shortly after render completes
            hideLoadingDebounced();
        }

        {
            let url = "{{ route('game.api.map') }}";
            const params = new URLSearchParams();
            if (adminMode) params.set('admin', '1');
            if (asCommanderId !== null) params.set('as_commander_id', String(asCommanderId));
            if (forcedGalaxyId !== null) params.set('galaxy_id', String(forcedGalaxyId));
            const qs = params.toString();
            if (qs) url += `?${qs}`;
            if (typeof console !== 'undefined' && console.log) {
                console.log('Galaxy map widget: fetching URL', url);
            }
            fetch(url)
            .then(r => {
                if (typeof console !== 'undefined' && console.log) {
                    console.log('Galaxy map widget: fetch status', r.status);
                }
                return r.json();
            })
            .then(data => {
                if (typeof console !== 'undefined' && console.log) {
                    console.log('Galaxy map widget: data received', data);
                }
                mapData = data;
                // Try to restore previous viewport before first render
                restoreState();
                renderMap();
            })
            .catch(err => {
                console.error('Erreur chargement carte:', err);
            });
        }

        // Drag-to-pan interaction
        let isDragging = false;
        let didDrag = false;
        let dragStartX = 0, dragStartY = 0;
        let windowStartX = 0, windowStartY = 0;
        let lastAppliedDx = 0, lastAppliedDy = 0; // in cells
        function applyPan(dxPx, dyPx) {
            if (!mapData) return;
            if (cellSize <= 0) return;
            const dxCells = Math.round(dxPx / cellSize);
            const dyCells = Math.round(dyPx / cellSize);
            if (dxCells !== 0 || dyCells !== 0) didDrag = true;
            if (dxCells === lastAppliedDx && dyCells === lastAppliedDy) return;
            lastAppliedDx = dxCells; lastAppliedDy = dyCells;
            const candidateX = windowStartX - dxCells;
            const candidateY = windowStartY - dyCells;
            const clampMaxX = Math.max(0, galaxySizeX - viewCols);
            const clampMaxY = Math.max(0, galaxySizeY - viewRows);
            // Enable overflow when user drags beyond the right/bottom clamp
            allowOverflowX = candidateX > clampMaxX;
            allowOverflowY = candidateY > clampMaxY;
            // Never allow negative left/top
            windowMinX = Math.max(0, candidateX);
            windowMinY = Math.max(0, candidateY);
            showLoading();
            // Re-render on next frame
            requestAnimationFrame(renderMap);
        }
        container.addEventListener('mousedown', (e) => {
            if (e.button !== 0) return; // left button only
            isDragging = true;
            manualPan = true;
            didDrag = false;
            dragStartX = e.clientX; dragStartY = e.clientY;
            windowStartX = windowMinX; windowStartY = windowMinY;
            lastAppliedDx = 0; lastAppliedDy = 0;
            // Determine if current window starts beyond right/bottom clamp; if yes, allow overflow during this drag session
            const clampMaxX = Math.max(0, galaxySizeX - viewCols);
            const clampMaxY = Math.max(0, galaxySizeY - viewRows);
            allowOverflowX = windowMinX > clampMaxX;
            allowOverflowY = windowMinY > clampMaxY;
            // Defer loader: only show once movement reaches at least 1 cell in applyPan()
            e.preventDefault();
        });
        window.addEventListener('mousemove', (e) => {
            if (!isDragging) return;
            applyPan(e.clientX - dragStartX, e.clientY - dragStartY);
        });
        function endDrag() {
            if (!isDragging) return;
            isDragging = false;
            hideLoadingDebounced();
            // Keep didDrag true briefly to swallow click events after drag
            setTimeout(() => { didDrag = false; }, 150);
            // Reset overflow policy after drag ends
            allowOverflowX = false;
            allowOverflowY = false;
            saveState();
        }
        window.addEventListener('mouseup', endDrag);
        container.addEventListener('mouseleave', endDrag);

        // Mouse wheel zoom (anchor at mouse pointer)
        const MIN_COLS = 11, MIN_ROWS = 7;
        const MAX_COLS = 91, MAX_ROWS = 63;
        function handleWheel(e) {
            if (!mapData) return;
            // Prevent page scroll while zooming the map
            e.preventDefault();
            const dir = Math.sign(e.deltaY);
            const factor = dir > 0 ? 1.15 : (1 / 1.15);
            const proposedCols = clamp(Math.round(viewCols * factor), MIN_COLS, MAX_COLS);
            const proposedRows = clamp(Math.round(viewRows * factor), MIN_ROWS, MAX_ROWS);
            if (proposedCols === viewCols && proposedRows === viewRows) return;

            const rect = container.getBoundingClientRect();
            const mouseX = e.clientX - rect.left;
            const mouseY = e.clientY - rect.top;

            // Current stage bounds
            const stageWCur = cellSize * viewCols;
            const stageHCur = cellSize * viewRows;
            const inStage = (mouseX >= offX && mouseX <= offX + stageWCur && mouseY >= offY && mouseY <= offY + stageHCur);
            const anchorWorldX = inStage ? (windowMinX + (mouseX - offX) / Math.max(1, cellSize)) : (windowMinX + viewCols / 2);
            const anchorWorldY = inStage ? (windowMinY + (mouseY - offY) / Math.max(1, cellSize)) : (windowMinY + viewRows / 2);

            // Compute new layout for proposed view window
            const newLayout = computeLayoutFor(proposedCols, proposedRows);
            const newCell = Math.max(1, newLayout.cell);
            const newOffX = newLayout.offX;
            const newOffY = newLayout.offY;

            // Determine new window origin to keep anchor stable
            const anchorPixelX = inStage ? mouseX : (newOffX + (proposedCols * newCell) / 2);
            const anchorPixelY = inStage ? mouseY : (newOffY + (proposedRows * newCell) / 2);
            let newWinX = Math.floor(anchorWorldX - (anchorPixelX - newOffX) / newCell);
            let newWinY = Math.floor(anchorWorldY - (anchorPixelY - newOffY) / newCell);
            newWinX = Math.max(0, newWinX);
            newWinY = Math.max(0, newWinY);

            // Clamp policy against true galaxy size for the new view window
            const clampMaxX = Math.max(0, galaxySizeX - proposedCols);
            const clampMaxY = Math.max(0, galaxySizeY - proposedRows);
            allowOverflowX = newWinX > clampMaxX;
            allowOverflowY = newWinY > clampMaxY;

            // Commit zoom + viewport
            manualPan = true;
            viewCols = proposedCols;
            viewRows = proposedRows;
            windowMinX = newWinX;
            windowMinY = newWinY;

            showLoading();
            saveState();
            requestAnimationFrame(renderMap);
        }
        container.addEventListener('wheel', handleWheel, { passive: false });

        // Cell info modals (one modal per world cell; multiple different cells allowed)
        const openCellWindows = new Map(); // key: "x,y" -> window element
        function toggleCellWindowAt(wx, wy) {
            const key = wx + ',' + wy;
            if (openCellWindows.has(key)) {
                const existing = openCellWindows.get(key);
                if (existing && existing.remove) existing.remove();
                openCellWindows.delete(key);
                return;
            }
            const win = spawnModelessWindow(`Case (${wx}, ${wy})`, (body) => {
                // Coordinates
                const coords = document.createElement('div');
                coords.textContent = `Coordonnées: X=${wx}, Y=${wy}`;
                body.appendChild(coords);

                // Lookup system (if any)
                let sys = null;
                if (mapData && Array.isArray(mapData.systems)) {
                    sys = mapData.systems.find(s => Number(s.x) === Number(wx) && Number(s.y) === Number(wy)) || null;
                }
                const sysWrap = document.createElement('div');
                sysWrap.style.marginTop = '6px';
                if (sys) {
                    const ownerMap = { self: 'Vous', neutral: 'Neutre' };
                    const ownerTxt = ownerMap[sys.owner] || 'Autre';
                    sysWrap.innerHTML = `<div><strong>Système:</strong> ${sys.name || 'Système'} (ID: ${sys.id})</div>` +
                        `<div><strong>Propriétaire:</strong> ${ownerTxt}</div>`;
                } else {
                    sysWrap.innerHTML = '<div><strong>Système:</strong> Aucun</div>';
                }
                body.appendChild(sysWrap);

                // Fleets present on this cell (linked to system)
                const fleetsWrap = document.createElement('div');
                fleetsWrap.style.marginTop = '6px';
                const title = document.createElement('div');
                title.innerHTML = '<strong>Flottes:</strong>';
                fleetsWrap.appendChild(title);
                if (sys && mapData && Array.isArray(mapData.fleets)) {
                    const fleetsHere = mapData.fleets.filter(f => String(f.system_id) == String(sys.id));
                    if (fleetsHere.length === 0) {
                        const empty = document.createElement('div');
                        empty.textContent = 'Aucune';
                        fleetsWrap.appendChild(empty);
                    } else {
                        const ul = document.createElement('ul');
                        ul.style.margin = '4px 0 0 16px';
                        fleetsHere.forEach(f => {
                            const li = document.createElement('li');
                            const who = (f.owner === 'self') ? 'Vous' : (f.owner_commander_name || 'Autre');
                            const size = (f.size != null) ? `, taille: ${f.size}` : '';
                            const fname = f.name ? `, ${f.name}` : '';
                            li.textContent = `${who}${size}${fname}`;
                            ul.appendChild(li);
                        });
                        fleetsWrap.appendChild(ul);
                    }
                } else {
                    const empty = document.createElement('div');
                    empty.textContent = 'Aucune';
                    fleetsWrap.appendChild(empty);
                }
                body.appendChild(fleetsWrap);
            }, {
                onClose: () => {
                    const k = wx + ',' + wy;
                    if (openCellWindows.has(k)) openCellWindows.delete(k);
                }
            });
            openCellWindows.set(key, win);
        }

        // Click anywhere on the stage to toggle the cell modal
        container.addEventListener('click', (e) => {
            if (!mapData) return;
            if (didDrag) { e.preventDefault(); return; }
            if (cellSize <= 0) return;
            const rect = container.getBoundingClientRect();
            const mouseX = e.clientX - rect.left;
            const mouseY = e.clientY - rect.top;
            const stageW = cellSize * viewCols;
            const stageH = cellSize * viewRows;
            const inStage = (mouseX >= offX && mouseX < offX + stageW && mouseY >= offY && mouseY < offY + stageH);
            if (!inStage) return;
            const cx = Math.floor((mouseX - offX) / cellSize);
            const cy = Math.floor((mouseY - offY) / cellSize);
            const wx = windowMinX + cx;
            const wy = windowMinY + cy;
            toggleCellWindowAt(wx, wy);
        });

        // Fullscreen toggle
        const fsBtn = document.getElementById('mapFullscreenToggle');
        const gotoBtn = document.getElementById('mapGotoButton');
        let isFs = false;
        function setNavOffset() {
            const nav = document.querySelector('nav.navbar');
            const offset = nav ? nav.offsetHeight : 56;
            container.style.setProperty('--nav-offset', offset + 'px');
        }
        // Prevent map drag when interacting with controls
        const controls = container.querySelector('.controls');
        if (controls) {
            controls.addEventListener('mousedown', (e) => { e.stopPropagation(); });
            controls.addEventListener('touchstart', (e) => { e.stopPropagation(); }, { passive: true });
        }
        fsBtn.addEventListener('click', (ev) => {
            if (ev) ev.stopPropagation();
            isFs = !isFs;
            if (isFs) {
                setNavOffset();
                container.classList.add('fullscreen');
                document.body.style.overflow = 'hidden';
            } else {
                container.classList.remove('fullscreen');
                document.body.style.overflow = '';
            }
            // Re-render after layout change
            requestAnimationFrame(renderMap);
        });
        if (gotoBtn) {
            gotoBtn.addEventListener('click', (ev) => { ev.stopPropagation(); toggleGotoWindow(); });
        }

        // Re-render on window resize
        window.addEventListener('resize', () => {
            if (isFs) setNavOffset();
            renderMap();
        });
        // Ensure we never stay "stuck" in fullscreen after navigation away/back
        window.addEventListener('pagehide', () => {
            container.classList.remove('fullscreen');
            document.body.style.overflow = '';
            isFs = false;
        });
        window.addEventListener('pageshow', () => {
            // Restore state (if any) and re-render when page is shown from bfcache
            restoreState();
            renderMap();
        });
        };
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', onLoad);
        } else {
            onLoad();
        }
    })();
    function getElementByIdSafe(id) { return document.getElementById(id); }
</script>
@endpush
