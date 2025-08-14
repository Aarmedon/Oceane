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
    .galaxy-map-widget .star-system { position: absolute; cursor: pointer; transition: all 0.2s ease; }
    .galaxy-map-widget .star-system .star { width: 6px; height: 6px; border-radius: 50%; background-color: #fff; box-shadow: 0 0 8px rgba(255,255,255,0.6); }
    .galaxy-map-widget .star-system.owned .star { background-color: #48bb78; box-shadow: 0 0 8px rgba(72,187,120,0.7); }
    .galaxy-map-widget .star-system.enemy .star { background-color: #f56565; box-shadow: 0 0 8px rgba(245,101,101,0.7); }
    .galaxy-map-widget .star-system.neutral .star { background-color: #d69e2e; box-shadow: 0 0 8px rgba(214,158,46,0.7); }
    .galaxy-map-widget .star-system:hover { z-index: 5; transform: scale(1.4); }
    .galaxy-map-widget .legend { position: absolute; top: 8px; right: 8px; font-size: 11px; background: rgba(0,0,0,0.6); border-radius: 6px; padding: 6px 8px; color: #ddd; }
    .galaxy-map-widget .legend .item { display: flex; align-items: center; gap: 6px; margin-bottom: 4px; }
    .galaxy-map-widget .legend .dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; }
    .galaxy-map-widget .fleet { position: absolute; width: 10px; height: 10px; border-radius: 3px; background-color: #3182ce; transform: rotate(45deg); }
    .galaxy-map-widget .controls { position: absolute; top: 8px; left: 8px; z-index: 10; display: flex; gap: 6px; }
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
</style>
@endpush

<div class="galaxy-map-widget" id="dashboardGalaxyMap">
    <div class="controls">
        <button type="button" class="btn btn-sm btn-dark" id="mapFullscreenToggle">
            <i class="fas fa-expand"></i> Plein écran
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
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const container = document.getElementById('dashboardGalaxyMap');
        if (!container) return;
        const stage = container.querySelector('.stage');
        const rulerX = container.querySelector('.ruler-x');
        const rulerY = container.querySelector('.ruler-y');

        function getSize() {
            return { width: container.clientWidth, height: container.clientHeight };
        }
        let { width, height } = getSize();
        const starSystemBase = "{{ url('/game/star-system') }}";

        let mapData = null;
        function clearMap() { stage.innerHTML = ''; }
        // Fixed view window (columns x rows) with square cells
        const viewCols = 31, viewRows = 21;
        let cellSize = 10, offX = 0, offY = 0; // pixels
        let windowMinX = 0, windowMinY = 0;    // world coords
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
                if (i % labelEveryX === 0 || i === 0 || i === viewCols) {
                    const lbl = document.createElement('div');
                    lbl.className = 'label';
                    lbl.style.left = px + 'px';
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
                if (j % labelEveryY === 0 || j === 0 || j === viewRows) {
                    const lbl = document.createElement('div');
                    lbl.className = 'label';
                    lbl.style.top = py + 'px';
                    lbl.textContent = Math.round(windowMinY + j);
                    rulerY.appendChild(lbl);
                }
            }
        }
        function renderMap() {
            if (!mapData) return;
            const sizeX = Math.max(1, Math.round(mapData.galaxy.size_x || 100));
            const sizeY = Math.max(1, Math.round(mapData.galaxy.size_y || 100));
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
            let capX = 0, capY = 0;
            if (capApiX !== null && capApiY !== null) {
                capX = capApiX; capY = capApiY;
            } else if (capIdFromApi && systemsById[capIdFromApi]) {
                capX = systemsById[capIdFromApi].x;
                capY = systemsById[capIdFromApi].y;
            } else {
                const owned = (mapData.systems || []).find(s => s.owner === 'self');
                if (owned) {
                    capX = owned.x; capY = owned.y;
                } else if ((mapData.systems || []).length) {
                    capX = mapData.systems[0].x;
                    capY = mapData.systems[0].y;
                }
            }
            // Debug diagnostics for centering
            if (typeof console !== 'undefined' && console.debug) {
                console.debug('Map center source', {
                    fromApi: { capIdFromApi, capApiX, capApiY },
                    chosen: { capX, capY },
                    windowBeforeClamp: { x0: Math.floor(capX) - Math.floor(viewCols/2), y0: Math.floor(capY) - Math.floor(viewRows/2) },
                    size: { sizeX, sizeY }
                });
            }

            // Compute window around capital (31x21), clamped to last valid index (half-open ranges)
            const halfCols = Math.floor(viewCols / 2);
            const halfRows = Math.floor(viewRows / 2);
            const lastX = sizeX - 1;
            const lastY = sizeY - 1;
            const maxMinX = Math.max(0, lastX - (viewCols - 1));
            const maxMinY = Math.max(0, lastY - (viewRows - 1));
            windowMinX = Math.max(0, Math.min(maxMinX, Math.floor(capX) - halfCols));
            windowMinY = Math.max(0, Math.min(maxMinY, Math.floor(capY) - halfRows));

            // Square cells, center stage in container
            cellSize = Math.floor(Math.min(width / viewCols, height / viewRows));
            const stageW = cellSize * viewCols;
            const stageH = cellSize * viewRows;
            offX = Math.floor((width - stageW) / 2);
            offY = Math.floor((height - stageH) / 2);

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
                el.addEventListener('click', () => { window.location.href = starSystemBase + '/' + sys.id; });
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
        }

        fetch("{{ route('game.api.map') }}")
            .then(r => r.json())
            .then(data => { mapData = data; renderMap(); })
            .catch(err => {
                console.error('Erreur chargement carte:', err);
            });

        // Fullscreen toggle
        const fsBtn = document.getElementById('mapFullscreenToggle');
        let isFs = false;
        function setNavOffset() {
            const nav = document.querySelector('nav.navbar');
            const offset = nav ? nav.offsetHeight : 56;
            container.style.setProperty('--nav-offset', offset + 'px');
        }
        fsBtn.addEventListener('click', () => {
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

        // Re-render on window resize
        window.addEventListener('resize', () => {
            if (isFs) setNavOffset();
            renderMap();
        });
    });
</script>
@endpush
