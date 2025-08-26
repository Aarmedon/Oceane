<script setup>
import { ref, computed, onMounted, nextTick, onBeforeUnmount, watch } from 'vue';
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import axios from 'axios';

const loading = ref(true);
const error = ref(null);
const data = ref(null);
const showGrid = ref(true);
const showNames = ref(true);
const containerSize = ref({ width: 900, height: 700 });
const containerRef = ref(null);
const selectedGalaxyId = ref(null);
const adminMode = ref(false);
const asCommanderId = ref(null);
const debug = ref(false);
const isProd = import.meta.env.PROD;

// View transform state
const scale = ref(1);
const translateX = ref(0);
const translateY = ref(0);
let isDragging = false;
let dragStartX = 0;
let dragStartY = 0;
let lastTranslateX = 0;
let lastTranslateY = 0;
let ro = null; // ResizeObserver

function dbg(...args) {
  if (debug.value) {
    // eslint-disable-next-line no-console
    console.log('[GalaxyMap]', ...args);
  }
}

const galaxy = computed(() => data.value?.galaxy || { size_x: 100, size_y: 100, name: '...' });
const systems = computed(() => data.value?.systems || []);
const fleets = computed(() => (data.value?.fleets || []).filter(f => !!f));
const visibleGalaxies = computed(() => data.value?.visible_galaxies || []);
const sectors = computed(() => data.value?.sectors || []);
const mode = computed(() => data.value?.mode || 'player');
const hasCentered = ref(false);
// Square viewport grid controls
const showSquareGrid = ref(true);
const showCoords = ref(true);
const showSystems = ref(true);
// Snap markers to cell centers (round screen px to integers to reduce subpixel blur)
const snapToCells = ref(true);
// Use CSS Grid cells instead of absolute positioning
const useCellGrid = ref(false);
const gridCols = ref(21); // desired number of columns across viewport (default 21)
const gridDelta = ref(0.2); // tolerance to avoid a last half row

// Debug: restrict logs to specific cells only
// Accepts an array of [x,y], {x,y}, or "x,y" strings. Use window.GalaxyMapDebug.setCells(...)
const debugCells = ref([]);
const debugCellsSet = computed(() => new Set((debugCells.value || []).map((c) => {
  if (Array.isArray(c) && c.length >= 2) return `${Math.floor(c[0])},${Math.floor(c[1])}`;
  if (c && typeof c === 'object' && 'x' in c && 'y' in c) return `${Math.floor(c.x)},${Math.floor(c.y)}`;
  if (typeof c === 'string') return c.trim();
  return '';
}).filter(Boolean)));
function setDebugCells(cells) {
  const out = [];
  if (typeof cells === 'string') {
    const parts = cells.split(/[;\|]/);
    for (const p of parts) {
      const [xs, ys] = p.split(',').map(s => s.trim());
      const x = Number(xs); const y = Number(ys);
      if (Number.isFinite(x) && Number.isFinite(y)) out.push([Math.floor(x), Math.floor(y)]);
    }
  } else if (Array.isArray(cells)) {
    for (const it of cells) {
      if (Array.isArray(it) && it.length >= 2) {
        const x = Math.floor(it[0]); const y = Math.floor(it[1]);
        if (Number.isFinite(x) && Number.isFinite(y)) out.push([x, y]);
      } else if (it && typeof it === 'object' && 'x' in it && 'y' in it) {
        const x = Math.floor(it.x); const y = Math.floor(it.y);
        if (Number.isFinite(x) && Number.isFinite(y)) out.push([x, y]);
      } else if (typeof it === 'string') {
        const [xs, ys] = it.split(',').map(s => s.trim());
        const x = Number(xs); const y = Number(ys);
        if (Number.isFinite(x) && Number.isFinite(y)) out.push([Math.floor(x), Math.floor(y)]);
      }
    }
  }
  debugCells.value = out;
  if (debug.value) {
    nextTick(() => {
      try { logAllSystemsBoxes('setCells'); } catch (e) { /* ignore */ }
    });
  }
}
onMounted(() => {
  if (typeof window !== 'undefined') {
    window.GalaxyMapDebug = window.GalaxyMapDebug || {};
    window.GalaxyMapDebug.setCells = setDebugCells;
    window.GalaxyMapDebug.clearCells = () => setDebugCells([]);
  }
});
onBeforeUnmount(() => {
  if (typeof window !== 'undefined' && window.GalaxyMapDebug) {
    delete window.GalaxyMapDebug.setCells;
    delete window.GalaxyMapDebug.clearCells;
  }
});

function measureContainer() {
  if (!containerRef.value) return;
  const rect = containerRef.value.getBoundingClientRect();
  containerSize.value = { width: Math.floor(rect.width), height: Math.floor(rect.height) };
  dbg('measureContainer', containerSize.value);
}

function computeViewportBasis() {
  const w = containerSize.value.width || 1;
  const h = containerSize.value.height || 1;
  const sx = galaxy.value.size_x || 0;
  const sy = galaxy.value.size_y || 0;
  if (!w || !h || !sx || !sy) return { base: 1, xOff: 0, yOff: 0 };
  const base = Math.min(w / sx, h / sy); // isotropic pixels-per-world to keep squares square
  const xOff = (w - sx * base) / 2;
  const yOff = (h - sy * base) / 2;
  return { base, xOff, yOff };
}

function worldToScreen(x, y) {
  const { base, xOff, yOff } = computeViewportBasis();
  return { x: x * base + xOff, y: y * base + yOff };
}

// Pixels-per-world: base (pre-zoom) and on-screen (with current zoom)
const pxPerWorldBase = computed(() => {
  const { base } = computeViewportBasis();
  return (base || 0);
});
const pxPerWorldScreen = computed(() => pxPerWorldBase.value * (scale.value || 1));

// Marker sizes
// - Base world-anchored sizes (used for some elements)
const systemSizePx = computed(() => pxPerWorldBase.value * 0.35); // ~35% of cell
const fleetSizePx = computed(() => pxPerWorldBase.value * 0.48);   // ~48% of cell
// - Screen-anchored system sprite size equals 80% of the on-screen cell size
//   to keep some breathing room inside the grid cell.
const systemSizeScreenPx = computed(() => {
  const ratio = 0.8; // 80% of cell
  const size = Math.floor(pxPerWorldScreen.value * ratio);
  return Math.max(1, size);
});

// Cell anchor system: each cell has a fixed anchor point at its center
// Returns world coordinates (pre-transform) for positioning within the transformed container
function getCellAnchorPoint(cellX, cellY) {
  // World coordinates of the cell center (anchor point)
  const worldX = cellX + 0.5;
  const worldY = cellY + 0.5;
  // Use worldToScreen to get the pre-transform position
  const { base, xOff, yOff } = computeViewportBasis();
  return {
    x: worldX * base + xOff,
    y: worldY * base + yOff
  };
}

// Generic translate to center on the (x,y) anchor
function markerTranslateStyle() {
  return { transform: 'translate3d(-50%, -50%, 0)', transformOrigin: '0 0', willChange: 'transform' };
}

// System marker box (circle) - centered within the cell container
function systemMarkerStyle() {
  return {
    position: 'absolute',
    left: '50%',
    top: '50%',
    ...markerTranslateStyle(),
    width: systemSizePx.value + 'px',
    height: systemSizePx.value + 'px',
  };
}

// Capital ring marker around cell center
function capitalMarkerStyle() {
  const s = systemSizePx.value + 6; // ring slightly larger than system dot
  return {
    position: 'absolute',
    left: '50%',
    top: '50%',
    ...markerTranslateStyle(),
    width: s + 'px',
    height: s + 'px',
    borderWidth: '2px',
  };
}

// Fleet triangle styles (stationary at system and moving between systems)
function fleetTriangleStyleStationary() {
  const half = (fleetSizePx.value / 2);
  const full = (fleetSizePx.value);
  return {
    position: 'absolute',
    left: '50%',
    top: '50%',
    ...markerTranslateStyle(),
    borderLeftWidth: half + 'px',
    borderRightWidth: half + 'px',
    borderBottomWidth: full + 'px',
    borderLeftColor: 'transparent',
    borderRightColor: 'transparent',
    borderStyle: 'solid',
  };
}
function fleetHeadingDeg(f) {
  // Backend provides heading in degrees (0=right, 90=down, 180=left, 270=up)
  // Return null when not provided
  const h = f && typeof f.heading === 'number' ? f.heading : null;
  return isFinite(h) ? h : null;
}
function fleetTriangleStyleMoving(f) {
  const base = fleetTriangleStyleStationary();
  const h = fleetHeadingDeg(f);
  // Our CSS triangle points UP by default; rotate by (heading + 90deg) to align 0deg=right
  const angle = h == null ? null : (h + 90);
  return {
    ...base,
    transform: angle == null ? base.transform : `${base.transform} rotate(${angle}deg)`,
    transformOrigin: '50% 50%'
  };
}

// Sprite-based markers are rendered via <img> with fallback to CSS dot

// System name label offset relative to marker size (scales with zoom)
function nameLabelStyle() {
  const sc = scale.value || 1;
  // Offset label by half of the on-screen sprite size + small margin
  const offset = Math.round(systemSizeScreenPx.value / 2) + 4;
  return {
    left: offset + 'px',
    top: '50%',
    // Keep label readable: counter-scale to neutralize map zoom
    transform: `translateY(-50%) scale(${1 / sc})`,
    transformOrigin: 'left center'
  };
}

// Auto-hide system names when zoomed too far out to avoid clutter
const labelVisibilityThreshold = ref(14); // min on-screen px-per-world to show names
const shouldShowNames = computed(() => showNames.value && (pxPerWorldScreen.value >= labelVisibilityThreshold.value));

// ---- Debug helpers for system boxes ----
const systemEls = new Map();
function setSystemEl(id, el, s) {
  if (!el) { systemEls.delete(id); return; }
  systemEls.set(id, el);
  if (debug.value) logSystemBox(el, s, 'mount');
}
function logSystemBox(el, s, reason = 'log') {
  try {
    const cont = containerRef.value ? containerRef.value.getBoundingClientRect() : null;
    const rect = el.getBoundingClientRect();
    const p0 = worldToScreen((s.x ?? 0), (s.y ?? 0));
    const sc = scale.value || 1;
    // Effective translate matches mapInnerStyle rounding
    const tx = Math.round(translateX.value || 0);
    const ty = Math.round(translateY.value || 0);
    const finalX = tx + sc * p0.x;
    const finalY = ty + sc * p0.y;
    // If snapping is active, mimic current rounding used by style
    const snappedX = snapToCells.value ? Math.round(sc * p0.x) / sc : p0.x;
    const snappedY = snapToCells.value ? Math.round(sc * p0.y) / sc : p0.y;
    const finalSnappedX = tx + sc * snappedX;
    const finalSnappedY = ty + sc * snappedY;
    const pageX = cont ? cont.left + finalSnappedX : null;
    const pageY = cont ? cont.top + finalSnappedY : null;
    const diffLeft = (pageX != null) ? (rect.left - pageX) : null;
    const diffTop = (pageY != null) ? (rect.top - pageY) : null;
    console.log('[GalaxyMap][SystemBox]', {
      reason,
      id: s.id, name: s.name, world: { x: s.x, y: s.y },
      containerRect: cont ? { left: cont.left, top: cont.top, width: cont.width, height: cont.height } : null,
      elementRect: { left: rect.left, top: rect.top, width: rect.width, height: rect.height },
      basePxPerWorld: pxPerWorldBase.value,
      scale: sc, translate: { x: tx, y: ty }, snapToCells: !!snapToCells.value,
      preTransform: { x: p0.x, y: p0.y },
      computedStyleLeftTop: { left: snappedX, top: snappedY },
      finalScreenPx: { x: finalX, y: finalY },
      finalScreenPxWithSnap: { x: finalSnappedX, y: finalSnappedY },
      pagePxWithSnap: { x: pageX, y: pageY },
      pageDiffFromRectTopLeft: { dx: diffLeft, dy: diffTop },
    });
  } catch (e) {
    // eslint-disable-next-line no-console
    console.log('[GalaxyMap][SystemBox] log error', e);
  }
}
function logAllSystemsBoxes(reason = 'transform-change') {
  try {
    const restrict = (debugCells.value || []).length > 0;
    const set = debugCellsSet.value;
    let count = 0;
    for (const s of (systems.value || [])) {
      if (restrict && !set.has(`${Math.floor(s.x)},${Math.floor(s.y)}`)) continue;
      const el = systemEls.get(s.id);
      if (el) {
        logSystemBox(el, s, reason);
        count++;
        if (count >= 10) break; // avoid spamming when many systems
      }
    }
  } catch (e) { /* ignore */ }
}

function debugLabelText(s) {
  const p0 = worldToScreen((s.x ?? 0), (s.y ?? 0));
  const sc = scale.value || 1;
  const tx = Math.round(translateX.value || 0);
  const ty = Math.round(translateY.value || 0);
  const sx = snapToCells.value ? Math.round(sc * p0.x) / sc : p0.x;
  const sy = snapToCells.value ? Math.round(sc * p0.y) / sc : p0.y;
  const fx = Math.round(tx + sc * sx);
  const fy = Math.round(ty + sc * sy);
  return `W(${s.x},${s.y}) pre(${p0.x.toFixed(1)},${p0.y.toFixed(1)}) S(${fx},${fy})`;
}

// Keep debug badge readable regardless of zoom
function debugBadgeStyle() {
  const sc = scale.value || 1;
  return {
    left: '6px',
    top: '6px',
    transform: `scale(${1 / sc})`,
    transformOrigin: 'top left'
  };
}

// Re-log a sample of systems whenever transform changes while debugging
watch([scale, translateX, translateY], () => {
  if (!debug.value) return;
  if ((debugCells.value || []).length === 0) return;
  nextTick(() => logAllSystemsBoxes('transform-change'));
});

// Set zoom (scale) so that approximately `cols` world units fit across the viewport width.
function setColumnsAcrossView(cols) {
  const w = containerSize.value.width || 1;
  const h = containerSize.value.height || 1;
  const targetCols = Math.max(1, Math.round(Number(cols) || 21));
  const { base } = computeViewportBasis();
  const targetPxPerWorld = w / targetCols;
  const candidate = targetPxPerWorld / (base || 1);
  const newScale = clampScaleValue(isFinite(candidate) && candidate > 0 ? candidate : (scale.value || 1));
  // Anchor zoom to viewport center
  const cx = screenPxToWorldX(w / 2);
  const cy = screenPxToWorldY(h / 2);
  scale.value = newScale;
  const p = worldToScreen(cx, cy);
  translateX.value = (w / 2) - newScale * p.x;
  translateY.value = (h / 2) - newScale * p.y;
  clampTranslate();
  dbg('setColumnsAcrossView', { cols: targetCols, targetPxPerWorld, base, scale: scale.value });
}

function systemPositionStyle(s) {
  // Use the cell anchor system for consistent positioning
  // Position in pre-transform space (the container will handle zoom/pan)
  const anchor = getCellAnchorPoint(s.x, s.y);
  const cellSize = pxPerWorldBase.value;
  const sc = scale.value || 1;
  const ax = snapToCells.value ? Math.round(sc * anchor.x) / sc : anchor.x;
  const ay = snapToCells.value ? Math.round(sc * anchor.y) / sc : anchor.y;
  
  return {
    left: ax + 'px',
    top: ay + 'px',
    width: cellSize + 'px',
    height: cellSize + 'px',
    // Position the container so its center aligns with the anchor point
    transform: 'translate(-50%, -50%)',
    transformOrigin: 'center center'
  };
}

function fleetPositionStyleAt(x, y) {
  // For fleets, determine if we're positioning at a cell center or exact coordinates
  const isCell = Number.isInteger(x) && Number.isInteger(y);
  
  if (isCell) {
    // Fleet at a system - use cell anchor
    const anchor = getCellAnchorPoint(x, y);
    const sc = scale.value || 1;
    const ax = snapToCells.value ? Math.round(sc * anchor.x) / sc : anchor.x;
    const ay = snapToCells.value ? Math.round(sc * anchor.y) / sc : anchor.y;
    return {
      left: ax + 'px',
      top: ay + 'px',
      transform: 'translate(-50%, -50%)',
      transformOrigin: 'center center'
    };
  } else {
    // Fleet in transit - use exact world coordinates (pre-transform)
    const { base, xOff, yOff } = computeViewportBasis();
    const preTransformX = x * base + xOff;
    const preTransformY = y * base + yOff;
    return {
      left: preTransformX + 'px',
      top: preTransformY + 'px',
      width: fleetSizePx.value + 'px',
      height: fleetSizePx.value + 'px',
      transform: 'translate(-50%, -50%)',
      transformOrigin: 'center center'
    };
  }
}

// Capital helpers to ease visual identification
const capitalPos = computed(() => {
  const cx = data.value?.commander?.capital_x;
  const cy = data.value?.commander?.capital_y;
  return (cx != null && cy != null) ? { x: cx, y: cy } : null;
});
function isCapitalSystem(s) {
  const c = capitalPos.value;
  return !!c && s && s.x === c.x && s.y === c.y;
}


// Compute sector rectangle in screen space ensuring positive width/height
function sectorRect(sec) {
  const x1 = worldToScreen(sec.start_x, 0).x;
  const x2 = worldToScreen(sec.end_x, 0).x;
  const y1 = worldToScreen(0, sec.start_y).y;
  const y2 = worldToScreen(0, sec.end_y).y;
  const left = Math.min(x1, x2);
  const top = Math.min(y1, y2);
  const width = Math.abs(x2 - x1);
  const height = Math.abs(y2 - y1);
  return { left, top, width, height, right: left + width, bottom: top + height };
}

function topLineStyle(sec) {
  const r = sectorRect(sec);
  return { left: r.left + 'px', top: r.top + 'px', width: r.width + 'px', height: '1px' };
}
function bottomLineStyle(sec) {
  const r = sectorRect(sec);
  return { left: r.left + 'px', top: (r.top + r.height) + 'px', width: r.width + 'px', height: '1px' };
}
function leftLineStyle(sec) {
  const r = sectorRect(sec);
  return { left: r.left + 'px', top: r.top + 'px', width: '1px', height: r.height + 'px' };
}
function rightLineStyle(sec) {
  const r = sectorRect(sec);
  return { left: (r.left + r.width) + 'px', top: r.top + 'px', width: '1px', height: r.height + 'px' };
}
function sectorLabelStyle(sec) {
  const r = sectorRect(sec);
  const sc = scale.value || 1;
  return {
    left: (r.left + 5) + 'px',
    top: (r.top + 5) + 'px',
    // Do not let sector labels explode with zoom
    transform: `scale(${1 / sc})`,
    transformOrigin: 'top left'
  };
}

// --- Coordinate labels helpers ---
function coordLabelStyle(cellX, cellY) {
  const { base, xOff, yOff } = computeViewportBasis();
  const sc = scale.value || 1;
  
  // Position at exact cell center in world coordinates
  const worldX = cellX + 0.5;
  const worldY = cellY + 0.5;
  
  // Convert to pre-transform screen coordinates
  const preX = worldX * base + xOff;
  const preY = worldY * base + yOff;
  
  return {
    left: preX + 'px',
    top: preY + 'px',
    transform: `translate(-50%, -50%) scale(${1 / sc})`,
    transformOrigin: 'center center'
  };
}

// Screen-space coordinate labels (with zoom/pan applied)
function coordLabelScreenStyle(cellX, cellY) {
  // Use same logic as worldXToScreenPx/worldYToScreenPx
  const worldCenterX = cellX + 0.5;
  const worldCenterY = cellY + 0.5;
  
  const screenX = worldXToScreenPx(worldCenterX);
  const screenY = worldYToScreenPx(worldCenterY);
  const sc = scale.value || 1;
  
  // Adaptive scaling: smaller when zoomed out, larger when zoomed in
  // At scale 1: normal size (1.0)
  // At scale 0.1 (zoomed out): smaller (0.6) 
  // At scale 10 (zoomed in): larger (2.0)
  const labelScale = Math.max(0.6, Math.min(2.0, Math.sqrt(sc) * 0.8));
  
  return {
    left: screenX + 'px',
    top: screenY + 'px',
    transform: `translate(-50%, -50%) scale(${labelScale})`,
    transformOrigin: 'center center'
  };
}

// System positioning helper
function systemStyle(system) {
  const worldX = system.x + 0.5;
  const worldY = system.y + 0.5;
  
  const screenX = worldXToScreenPx(worldX);
  const screenY = worldYToScreenPx(worldY);
  // System size adapts to zoom but stays readable
  const systemSize = systemSizeScreenPx.value;
  
  return {
    left: screenX + 'px',
    top: screenY + 'px',
    width: systemSize + 'px',
    height: systemSize + 'px',
    transform: 'translate(-50%, -50%)',
    transformOrigin: 'center center'
  };
}

// --- Rulers helpers (screen/world conversions and tick generation) ---
function worldXToScreenPx(wx) {
  const { base, xOff } = computeViewportBasis();
  const pre = wx * base + xOff;
  const tx = Math.round(translateX.value || 0);
  return tx + (scale.value || 1) * pre;
}
function worldYToScreenPx(wy) {
  const { base, yOff } = computeViewportBasis();
  const pre = wy * base + yOff;
  const ty = Math.round(translateY.value || 0);
  return ty + (scale.value || 1) * pre;
}
function screenPxToWorldX(spx) {
  const { base, xOff } = computeViewportBasis();
  const tx = Math.round(translateX.value || 0);
  const pre = (spx - tx) / (scale.value || 1);
  return base ? (pre - xOff) / base : 0;
}
function screenPxToWorldY(spy) {
  const { base, yOff } = computeViewportBasis();
  const ty = Math.round(translateY.value || 0);
  const pre = (spy - ty) / (scale.value || 1);
  return base ? (pre - yOff) / base : 0;
}

// --- Zoom/Pan constraints helpers ---
function getScaleBounds() {
  const w = containerSize.value.width || 1;
  const h = containerSize.value.height || 1;
  const sx = Math.max(1, galaxy.value.size_x || 1);
  const sy = Math.max(1, galaxy.value.size_y || 1);
  const { base } = computeViewportBasis();
  if (!base || base <= 0) return { minScale: 1e-4, maxScale: 1e4 };
  // Max zoom-out: show at most 200 cells in either dimension AND not beyond galaxy extents
  const minByCells = Math.max(w / (base * 200), h / (base * 200));
  const minByGalaxy = Math.max(w / (base * sx), h / (base * sy));
  let minScale = Math.max(1e-4, minByCells, minByGalaxy);
  // Max zoom-in: show at least 3 cells in both dimensions
  const maxByCells = Math.min(w / (base * 3), h / (base * 3));
  let maxScale = Math.min(1e4, maxByCells);
  if (minScale > maxScale) {
    // If bounds cross (tiny galaxy/container), collapse to a single valid scale
    minScale = maxScale = minScale;
  }
  return { minScale, maxScale };
}

function clampScaleValue(s) {
  const { minScale, maxScale } = getScaleBounds();
  return Math.min(maxScale, Math.max(minScale, s));
}

function clampTranslate() {
  const w = containerSize.value.width || 0;
  const h = containerSize.value.height || 0;
  if (!w || !h) return;
  const { base, xOff, yOff } = computeViewportBasis();
  const sx = galaxy.value.size_x || 0;
  const sy = galaxy.value.size_y || 0;
  const sc = scale.value || 1;
  if (!base || !sc || !sx || !sy) return;
  // Keep visible world rect within [0..sx] x [0..sy]
  const txMin = w - sc * (xOff + sx * base);
  const txMax = -xOff * sc;
  if (txMin <= txMax) {
    translateX.value = Math.min(txMax, Math.max(txMin, translateX.value));
  } else {
    translateX.value = (txMin + txMax) / 2;
  }
  const tyMin = h - sc * (yOff + sy * base);
  const tyMax = -yOff * sc;
  if (tyMin <= tyMax) {
    translateY.value = Math.min(tyMax, Math.max(tyMin, translateY.value));
  } else {
    translateY.value = (tyMin + tyMax) / 2;
  }
}

function clampScaleAndTranslate() {
  scale.value = clampScaleValue(scale.value || 1);
  clampTranslate();
}
function niceStep(raw) {
  if (!isFinite(raw) || raw <= 0) return 1;
  const exp = Math.floor(Math.log10(raw));
  const f = raw / Math.pow(10, exp);
  let n;
  if (f <= 1) n = 1; else if (f <= 2) n = 2; else if (f <= 5) n = 5; else n = 10;
  return n * Math.pow(10, exp);
}

function niceIntStep(raw) {
  if (!isFinite(raw) || raw <= 1) return 1;
  const exp = Math.floor(Math.log10(raw));
  const base = Math.pow(10, exp);
  const f = raw / base;
  let n;
  if (f <= 1) n = 1; else if (f <= 2) n = 2; else if (f <= 5) n = 5; else n = 10;
  return Math.max(1, Math.round(n * base));
}

const xTicks = computed(() => {
  const w = containerSize.value.width || 0;
  if (!w || !galaxy.value.size_x) return [];
  const leftW = screenPxToWorldX(0);
  const rightW = screenPxToWorldX(w);
  const minW = Math.min(leftW, rightW);
  const maxW = Math.max(leftW, rightW);
  const range = maxW - minW;
  if (!isFinite(range) || range <= 0) return [];
  const step = niceIntStep(range / 8);
  // Place ticks at cell centers: grid lines are integers; centers are always at k + 0.5
  const firstCenter = Math.ceil((minW - 0.5) / step) * step + 0.5;
  const lastCenter = Math.floor((maxW - 0.5) / step) * step + 0.5;
  const arr = [];
  for (let c = firstCenter; c <= lastCenter + 1e-9; c += step) {
    const px = worldXToScreenPx(c);
    // Label with the lower integer boundary of the cell
    arr.push({ px, label: String(Math.floor(c)) });
  }
  return arr;
});

const yTicks = computed(() => {
  const h = containerSize.value.height || 0;
  if (!h || !galaxy.value.size_y) return [];
  const topW = screenPxToWorldY(0);
  const bottomW = screenPxToWorldY(h);
  const minW = Math.min(topW, bottomW);
  const maxW = Math.max(topW, bottomW);
  const range = maxW - minW;
  if (!isFinite(range) || range <= 0) return [];
  const step = niceIntStep(range / 8);
  // Place ticks at cell centers: grid lines are integers; centers are always at k + 0.5
  const firstCenter = Math.ceil((minW - 0.5) / step) * step + 0.5;
  const lastCenter = Math.floor((maxW - 0.5) / step) * step + 0.5;
  const arr = [];
  for (let c = firstCenter; c <= lastCenter + 1e-9; c += step) {
    const px = worldYToScreenPx(c);
    // Label with the lower integer boundary of the cell
    arr.push({ px, label: String(Math.floor(c)) });
  }
  return arr;
});

// World-anchored coordinate grid: lines at integer multiples, same world step on X and Y
const viewportGrid = computed(() => {
  const w = containerSize.value.width || 0;
  const h = containerSize.value.height || 0;
  const sx = galaxy.value.size_x || 0;
  const sy = galaxy.value.size_y || 0;
  if (!w || !h || !sx || !sy) return { vertical: [], horizontal: [], cols: 0, rows: 0 };

  // Visible world window
  const wx0 = screenPxToWorldX(0);
  const wx1 = screenPxToWorldX(w);
  const wy0 = screenPxToWorldY(0);
  const wy1 = screenPxToWorldY(h);
  const xMin = Math.min(wx0, wx1);
  const xMax = Math.max(wx0, wx1);
  const yMin = Math.min(wy0, wy1);
  const yMax = Math.max(wy0, wy1);

  // Pixels-per-world (isotropic) including scale
  const { base } = computeViewportBasis();
  const pxPerWorld = base * (scale.value || 1);
  if (!isFinite(pxPerWorld) || pxPerWorld <= 0) return { vertical: [], horizontal: [], cols: 0, rows: 0 };

  // Integer coordinate grid: one line per integer when possible; otherwise skip with integer step
  const minSpacingPx = 10;
  const stepWorld = Math.max(1, Math.ceil(minSpacingPx / pxPerWorld));

  const vertical = [];
  const startX = Math.ceil(xMin / stepWorld) * stepWorld;
  for (let wx = startX; wx <= xMax + 1e-9; wx += stepWorld) {
    const px = worldXToScreenPx(wx);
    vertical.push(Math.round(px));
  }

  const horizontal = [];
  const startY = Math.ceil(yMin / stepWorld) * stepWorld;
  for (let wy = startY; wy <= yMax + 1e-9; wy += stepWorld) {
    const py = worldYToScreenPx(wy);
    horizontal.push(Math.round(py));
  }

  return { vertical, horizontal, cols: gridCols.value, rows: Math.max(0, horizontal.length - 1) };
});

// Visible cells indices with performance-conscious spacing
const coordGrid = computed(() => {
  const w = containerSize.value.width || 0;
  const h = containerSize.value.height || 0;
  const sx = Math.max(0, (galaxy.value.size_x || 0));
  const sy = Math.max(0, (galaxy.value.size_y || 0));
  if (!w || !h || !sx || !sy) return { xs: [], ys: [] };

  const leftW = screenPxToWorldX(0);
  const rightW = screenPxToWorldX(w);
  const topW = screenPxToWorldY(0);
  const bottomW = screenPxToWorldY(h);
  let xMin = Math.max(0, Math.min(leftW, rightW));
  let xMax = Math.min(sx, Math.max(leftW, rightW));
  let yMin = Math.max(0, Math.min(topW, bottomW));
  let yMax = Math.min(sy, Math.max(topW, bottomW));
  if (!(xMax > xMin) || !(yMax > yMin)) return { xs: [], ys: [] };

  const minX = Math.max(1, Math.floor(xMin));
  const maxX = Math.min(sx - 1, Math.floor(xMax));
  const minY = Math.max(1, Math.floor(yMin));
  const maxY = Math.min(sy - 1, Math.floor(yMax));
  if (maxX < minX || maxY < minY) return { xs: [], ys: [] };

  // Performance limit: max 50x50 = 2500 labels
  const maxLabelsPerAxis = 50;
  const xRange = maxX - minX + 1;
  const yRange = maxY - minY + 1;
  const xStep = Math.max(1, Math.ceil(xRange / maxLabelsPerAxis));
  const yStep = Math.max(1, Math.ceil(yRange / maxLabelsPerAxis));

  const xs = [];
  const ys = [];
  for (let x = minX; x <= maxX; x += xStep) xs.push(x);
  for (let y = minY; y <= maxY; y += yStep) ys.push(y);

  return { xs, ys };
});

// Axis ticks for rulers
const xAxisTicks = computed(() => {
  const w = containerSize.value.width || 0;
  if (!w) return [];
  
  const leftW = screenPxToWorldX(0);
  const rightW = screenPxToWorldX(w);
  const xMin = Math.min(leftW, rightW);
  const xMax = Math.max(leftW, rightW);
  
  const ticks = [];
  const step = Math.max(1, Math.ceil((xMax - xMin) / 20)); // ~20 ticks max
  const startX = Math.ceil(xMin / step) * step;
  
  for (let x = startX; x <= xMax; x += step) {
    if (x >= 1) { // Exclude x=0
      const screenX = worldXToScreenPx(x + 0.5); // Center on cell
      if (screenX >= 0 && screenX <= w) {
        ticks.push({ coord: x, screenX });
      }
    }
  }
  return ticks;
});

const yAxisTicks = computed(() => {
  const h = containerSize.value.height || 0;
  if (!h) return [];
  
  const topW = screenPxToWorldY(32); // Account for top ruler height
  const bottomW = screenPxToWorldY(h);
  const yMin = Math.min(topW, bottomW);
  const yMax = Math.max(topW, bottomW);
  
  const ticks = [];
  const step = Math.max(1, Math.ceil((yMax - yMin) / 20)); // ~20 ticks max
  const startY = Math.ceil(yMin / step) * step;
  
  for (let y = startY; y <= yMax; y += step) {
    if (y >= 1) { // Exclude y=0
      const screenY = worldYToScreenPx(y + 0.5); // Center on cell
      if (screenY >= 32 && screenY <= h) { // Account for top ruler
        ticks.push({ coord: y, screenY });
      }
    }
  }
  return ticks;
});

// Filtered systems based on visibility rules
const visibleSystems = computed(() => {
  if (!showSystems.value) return [];
  
  const allSystems = systems.value || [];
  const isAdmin = mode.value === 'admin';
  
  // Admin sees all systems
  if (isAdmin) return allSystems;
  
  // Players: keep systems explicitly visible or owned by the viewer (relation 'self')
  return allSystems.filter(system => 
    system?.visible === true || system?.owner?.relation === 'self'
  );
});

// Fog-of-war (screen-anchored): compute set of visible cells and rectangles for hidden areas
const visibleCellsSet = computed(() => {
  const set = new Set();
  const vis = data.value?.visibility;
  if (vis?.cells && Array.isArray(vis.cells)) {
    for (const c of vis.cells) {
      if (Array.isArray(c) && c.length >= 2) {
        const x = Math.floor(c[0]);
        const y = Math.floor(c[1]);
        if (Number.isFinite(x) && Number.isFinite(y)) set.add(`${x},${y}`);
      }
    }
  }
  if (vis?.rects && Array.isArray(vis.rects)) {
    const sx = Math.max(1, galaxy.value.size_x || 1);
    const sy = Math.max(1, galaxy.value.size_y || 1);
    for (const r of vis.rects) {
      const x0 = Math.max(1, Math.floor(r?.x0 ?? NaN));
      const y0 = Math.max(1, Math.floor(r?.y0 ?? NaN));
      const x1 = Math.min(sx, Math.floor(r?.x1 ?? NaN));
      const y1 = Math.min(sy, Math.floor(r?.y1 ?? NaN));
      if (!Number.isFinite(x0) || !Number.isFinite(y0) || !Number.isFinite(x1) || !Number.isFinite(y1)) continue;
      for (let x = x0; x <= x1; x++) {
        for (let y = y0; y <= y1; y++) {
          set.add(`${x},${y}`);
        }
      }
    }
  }
  // Fallback: derive visibility from systems when backend provided none
  if (set.size === 0) {
    const all = systems.value || [];
    const isAdmin = (mode.value === 'admin');
    for (const s of all) {
      const rel = s?.owner?.relation;
      const sysVisible = isAdmin || s?.visible === true || rel === 'self';
      if (!sysVisible) continue;
      const x = Math.floor(s?.x ?? NaN);
      const y = Math.floor(s?.y ?? NaN);
      if (Number.isFinite(x) && Number.isFinite(y)) set.add(`${x},${y}`);
    }
  }
  return set;
});

// Build fog rectangles in SCREEN space by grouping contiguous hidden cells per row
const fogRectsScreen = computed(() => {
  // No fog in admin mode
  if (mode.value === 'admin') return [];
  const w = containerSize.value.width || 0;
  const h = containerSize.value.height || 0;
  const sx = Math.max(1, galaxy.value.size_x || 1);
  const sy = Math.max(1, galaxy.value.size_y || 1);
  if (!w || !h || !sx || !sy) return [];

  // Visible world window
  const leftW = screenPxToWorldX(0);
  const rightW = screenPxToWorldX(w);
  const topW = screenPxToWorldY(0);
  const bottomW = screenPxToWorldY(h);
  const xMin = Math.max(1, Math.floor(Math.min(leftW, rightW)));
  const xMax = Math.min(sx, Math.floor(Math.max(leftW, rightW)));
  const yMin = Math.max(1, Math.floor(Math.min(topW, bottomW)));
  const yMax = Math.min(sy, Math.floor(Math.max(topW, bottomW)));
  if (!(xMax >= xMin) || !(yMax >= yMin)) return [];

  const rects = [];
  const vis = visibleCellsSet.value;
  for (let y = yMin; y <= yMax; y++) {
    let runStart = null;
    for (let x = xMin; x <= xMax; x++) {
      const isVisible = vis.has(`${x},${y}`);
      if (!isVisible) {
        if (runStart == null) runStart = x;
      } else if (runStart != null) {
        // close run [runStart..x-1]
        const l = worldXToScreenPx(runStart);
        const r = worldXToScreenPx(x);
        const t = worldYToScreenPx(y);
        const b = worldYToScreenPx(y + 1);
        rects.push({ left: Math.min(l, r), top: Math.min(t, b), width: Math.max(0, Math.abs(r - l)), height: Math.max(0, Math.abs(b - t)) });
        runStart = null;
      }
    }
    if (runStart != null) {
      const l = worldXToScreenPx(runStart);
      const r = worldXToScreenPx(xMax + 1);
      const t = worldYToScreenPx(y);
      const b = worldYToScreenPx(y + 1);
      rects.push({ left: Math.min(l, r), top: Math.min(t, b), width: Math.max(0, Math.abs(r - l)), height: Math.max(0, Math.abs(b - t)) });
    }
  }
  return rects;
});

function fogRectScreenStyle(r) {
  return {
    left: r.left + 'px',
    top: r.top + 'px',
    width: r.width + 'px',
    height: r.height + 'px',
    backgroundColor: 'rgba(210, 210, 210, 0.28)'
  };
}

function centerOnCoordinates(cx, cy) {
  const w = containerSize.value.width;
  const h = containerSize.value.height;
  const p = worldToScreen(cx + 0.5, cy + 0.5); // center of the cell
  const sc = (scale.value || 1);
  translateX.value = (w / 2) - sc * p.x;
  translateY.value = (h / 2) - sc * p.y;
  clampTranslate();
}

function centerOnCapital() {
  let cx = data.value?.commander?.capital_x;
  let cy = data.value?.commander?.capital_y;
  if (cx == null || cy == null) {
    cx = Math.floor((galaxy.value.size_x || 0) / 2);
    cy = Math.floor((galaxy.value.size_y || 0) / 2);
  }
  centerOnCoordinates(cx, cy);
  dbg('centerOnCapital', { cx, cy });
}

function getFleetPoints(owner) {
  const pts = [];
  const sys = systems.value || [];
  for (const f of (fleets.value || [])) {
    // v2: owner is an object with relation; keep backward-compat with string
    const rel = (f && f.owner && typeof f.owner === 'object') ? f.owner.relation : f?.owner;
    if (owner === 'self') {
      if (rel !== 'self') continue;
    } else {
      if (rel === 'self') continue;
    }
    if (f.system_id) {
      const s = sys.find(s => s.id === f.system_id);
      if (s) {
        pts.push({ x: s.x, y: s.y });
      } else if (f.x != null && f.y != null) {
        pts.push({ x: f.x, y: f.y });
      }
    } else if (f.x != null && f.y != null) {
      pts.push({ x: f.x, y: f.y });
    }
  }
  return pts;
}

function centerOnFleets(owner) {
  const pts = getFleetPoints(owner);
  if (!pts.length) {
    // No fleets of this owner visible; fallback to capital
    centerOnCapital();
    return;
  }
  let sx = 0, sy = 0;
  for (const p of pts) {
    sx += p.x; sy += p.y;
  }
  const cx = sx / pts.length;
  const cy = sy / pts.length;
  centerOnCoordinates(cx, cy);
}

function applyResetView() {
  // Reset but respect constraints
  scale.value = clampScaleValue(1);
  translateX.value = 0;
  translateY.value = 0;
  clampTranslate();
}

// Zoom keeping the given screen point (spx, spy) anchored
function zoomAt(spx, spy, factor) {
  const w = containerSize.value.width || 0;
  const h = containerSize.value.height || 0;
  if (!w || !h) return;
  // world position under the screen point before scaling
  const wx = screenPxToWorldX(spx);
  const wy = screenPxToWorldY(spy);
  // apply scale multiplicatively within dynamic bounds
  const newScale = clampScaleValue((scale.value || 1) * factor);
  scale.value = newScale;
  // adjust translate so that (wx, wy) stays at (spx, spy)
  const p = worldToScreen(wx, wy); // pre-transform position
  translateX.value = spx - newScale * p.x;
  translateY.value = spy - newScale * p.y;
  clampTranslate();
}

function zoomIn() {
  const w = containerSize.value.width || 0;
  const h = containerSize.value.height || 0;
  zoomAt(w / 2, h / 2, 1.2);
}
function zoomOut() {
  const w = containerSize.value.width || 0;
  const h = containerSize.value.height || 0;
  zoomAt(w / 2, h / 2, 1 / 1.2);
}
function resetView() {
  applyResetView();
}

function onMouseDown(e) {
  isDragging = true;
  dragStartX = e.clientX;
  dragStartY = e.clientY;
  lastTranslateX = translateX.value;
  lastTranslateY = translateY.value;
}
function onMouseMove(e) {
  if (!isDragging) return;
  translateX.value = lastTranslateX + (e.clientX - dragStartX);
  translateY.value = lastTranslateY + (e.clientY - dragStartY);
  clampTranslate();
}
function onMouseUp() {
  isDragging = false;
}

const mapInnerStyle = computed(() => ({
  transform: `translate(${Math.round(translateX.value)}px, ${Math.round(translateY.value)}px) scale(${scale.value})`,
  transformOrigin: '0 0'
}));

async function fetchMap() {
  loading.value = true;
  error.value = null;
  try {
    const params = {};
    if (selectedGalaxyId.value) params.galaxy_id = selectedGalaxyId.value;
    if (adminMode.value) {
      params.admin = 1;
      if (asCommanderId.value) params.as_commander_id = asCommanderId.value;
    }
    if (!isProd && debug.value) { params.debug = 1; }
    // Use v2 map API to get sprites and richer metadata
    const url = '/game/api/v2/map';
    dbg('fetchMap:start', { params });
    const resp = await axios.get(url, { params });
    data.value = resp.data;
    try {
      const s = Array.isArray(resp.data?.systems) ? resp.data.systems.length : 0;
      const f = Array.isArray(resp.data?.fleets) ? resp.data.fleets.length : 0;
      const c = Array.isArray(resp.data?.sectors) ? resp.data.sectors.length : 0;
      dbg('fetchMap:success', { mode: resp.data?.mode, galaxy: resp.data?.galaxy, counts: { systems: s, fleets: f, sectors: c } });
    } catch (ie) { /* ignore */ }
    hasCentered.value = false;
    if (!selectedGalaxyId.value) selectedGalaxyId.value = resp.data?.galaxy?.id || null;
  } catch (e) {
    error.value = e?.response?.data?.error || e.message || 'Erreur';
    dbg('fetchMap:error', error.value);
  } finally {
    loading.value = false;
    await nextTick();
    if (data.value && containerRef.value) {
      measureContainer();
      // Set default zoom: ~21 columns across the viewport, then center
      setColumnsAcrossView(gridCols.value || 21);
      centerOnCapital();
      hasCentered.value = true;
      dbg('fetchMap:finally', { containerRef: !!containerRef.value, size: containerSize.value, hasCentered: hasCentered.value });
      if (debug.value) nextTick(() => { logAllSystemsBoxes('after-center'); });
    } else {
      dbg('fetchMap:finally:no-container-or-data', { hasData: !!data.value, hasContainer: !!containerRef.value, size: containerSize.value });
    }
  }
}

function onResize() {
  measureContainer();
  clampScaleAndTranslate();
  dbg('window:resize', containerSize.value);
}

// Mouse wheel zoom anchored under cursor
function onWheel(e) {
  try {
    if (!containerRef.value) return;
    const rect = containerRef.value.getBoundingClientRect();
    const pad = showCoords.value ? 32 : 0; // top/left padding for rulers
    // Coordinates relative to the map content area (inside padding)
    let spx = e.clientX - rect.left - pad;
    let spy = e.clientY - rect.top - pad;
    // Clamp to viewport to avoid NaNs when over rulers
    const w = containerSize.value.width || 0;
    const h = containerSize.value.height || 0;
    spx = Math.max(0, Math.min(w, spx));
    spy = Math.max(0, Math.min(h, spy));

    const base = 1.1;
    const factor = Math.pow(base, -(e.deltaY || 0) / 100);
    zoomAt(spx, spy, factor);
  } catch (_) { /* ignore */ }
}

onMounted(() => {
  // Initialize selected galaxy from URL query if present
  try {
    const sp = new URLSearchParams(window.location.search);
    const gid = sp.get('galaxy_id');
    if (gid) {
      const parsed = parseInt(gid, 10);
      if (!isNaN(parsed)) selectedGalaxyId.value = parsed;
    }
    const admin = sp.get('admin');
    if (admin === '1' || admin === 'true') {
      adminMode.value = true;
    }
    const aci = sp.get('as_commander_id');
    if (aci) {
      const parsedAci = parseInt(aci, 10);
      if (!isNaN(parsedAci)) asCommanderId.value = parsedAci;
    }
    const dbgParam = sp.get('debug');
    if (!isProd && (dbgParam === '1' || dbgParam === 'true')) {
      debug.value = true;
    }
  } catch (e) {
    // ignore URL parsing errors
  }
  dbg('mounted', { selectedGalaxyId: selectedGalaxyId.value, adminMode: adminMode.value, asCommanderId: asCommanderId.value, debug: debug.value });
  fetchMap();
  window.addEventListener('resize', onResize);
  document.addEventListener('mouseup', onMouseUp);
  document.addEventListener('mousemove', onMouseMove);
  dbg('listeners:attached');
});
onBeforeUnmount(() => {
  window.removeEventListener('resize', onResize);
  document.removeEventListener('mouseup', onMouseUp);
  document.removeEventListener('mousemove', onMouseMove);
  try {
    if (ro && containerRef.value) ro.unobserve(containerRef.value);
    if (ro && ro.disconnect) ro.disconnect();
  } catch (e) { /* ignore */ }
});

// Keep galaxy_id in URL in sync for shareable links and back/forward nav
watch(selectedGalaxyId, (val) => {
  try {
    const url = new URL(window.location.href);
    if (val) {
      url.searchParams.set('galaxy_id', String(val));
    } else {
      url.searchParams.delete('galaxy_id');
    }
  window.history.replaceState({}, '', url);
} catch (e) {
  // ignore URL update errors
}
});

// Keep admin/as_commander_id in URL for shareable links
watch(adminMode, (val) => {
  try {
    const url = new URL(window.location.href);
    if (val) url.searchParams.set('admin', '1'); else url.searchParams.delete('admin');
    window.history.replaceState({}, '', url);
  } catch (e) { /* ignore */ }
});
watch(asCommanderId, (val) => {
  try {
    const url = new URL(window.location.href);
    if (adminMode.value && val) url.searchParams.set('as_commander_id', String(val));
    else url.searchParams.delete('as_commander_id');
    window.history.replaceState({}, '', url);
  } catch (e) { /* ignore */ }
});

// Observe container element mount/resize to keep containerSize in sync
watch(() => containerRef.value, (el, prev) => {
  try {
    if (ro == null && typeof ResizeObserver !== 'undefined') {
      ro = new ResizeObserver(() => measureContainer());
    }
    if (prev && ro) ro.unobserve(prev);
    if (el) {
      measureContainer();
      if (ro) ro.observe(el);
    }
  } catch (e) { /* ignore */ }
  dbg('containerRef:changed', { hasEl: !!el });
});

// Center once after first valid size
watch(containerSize, (sz) => {
  if (!hasCentered.value && sz.width > 0 && sz.height > 0 && data.value) {
    centerOnCapital();
    hasCentered.value = true;
  }
  dbg('containerSize:changed', sz);
  if (debug.value) nextTick(() => logAllSystemsBoxes('containerSize:changed'));
});

// Clamp columns input to a sensible range
watch(gridCols, (v) => {
  let n = Number(v);
  if (!isFinite(n)) n = 12;
  n = Math.round(n);
  n = Math.max(3, Math.min(200, n));
  if (n !== v) gridCols.value = n;
});

// Log when systems change and when debug toggles on
watch(systems, () => { if (debug.value) nextTick(() => logAllSystemsBoxes('systems:changed')); });
watch(debug, (v) => { if (v) nextTick(() => logAllSystemsBoxes('debug:enabled')); });

</script>

<template>
  <Head title="Carte galactique" />
  <AuthenticatedLayout>
    <div class="flex-1 min-h-0 flex flex-col bg-gray-950 text-gray-100 select-none">
    <header class="px-4 py-2 border-b border-gray-800 flex flex-wrap items-center gap-3">
      <div class="flex items-center gap-2 mr-4">
        <h1 class="text-lg font-semibold">Carte galactique</h1>
        <span v-if="mode==='admin'" class="px-2 py-0.5 text-xs rounded bg-purple-100 text-purple-700">Admin</span>
      </div>
      <label class="inline-flex items-center gap-1">
        <input type="checkbox" v-model="showGrid" />
        Grille
      </label>
      <label class="inline-flex items-center gap-1">
        <input type="checkbox" v-model="showNames" />
        Noms
      </label>
      <label class="inline-flex items-center gap-1">
        <input type="checkbox" v-model="showCoords" />
        Règles
      </label>
      <label class="inline-flex items-center gap-1">
        <input type="checkbox" v-model="showSystems" />
        Systèmes
      </label>
      <div class="inline-flex items-center gap-2">
        <label class="inline-flex items-center gap-1">
          <input type="checkbox" v-model="showSquareGrid" />
          Carrés
        </label>
        <label class="inline-flex items-center gap-1">
          <span>Cols:</span>
          <input type="number" min="3" max="200" v-model.number="gridCols" class="border rounded px-2 py-1 w-20 bg-gray-900" />
        </label>
        <label class="inline-flex items-center gap-1" v-if="adminMode">
          <input type="checkbox" v-model="snapToCells" />
          Snap
        </label>
      </div>
      <div class="inline-flex items-center gap-2" v-if="visibleGalaxies.length">
        <span>Galaxie:</span>
        <select v-model="selectedGalaxyId" @change="fetchMap" class="border rounded px-2 py-1 bg-gray-900">
          <option v-for="g in visibleGalaxies" :key="g.id" :value="g.id">{{ g.name }}</option>
        </select>
      </div>
      <div class="inline-flex items-center gap-2">
        <label class="inline-flex items-center gap-1">
          <input type="checkbox" v-model="adminMode" @change="fetchMap" />
          Mode MJ
        </label>
        <input type="number" min="1" v-model.number="asCommanderId" @change="fetchMap" :disabled="!adminMode" placeholder="as_commander_id" class="border rounded px-2 py-1 w-36 bg-gray-900" />
        <button class="border rounded px-2 py-1" @click="fetchMap">Rafraîchir</button>
      </div>
      <div class="inline-flex items-center gap-2">
        <span>Recentre:</span>
        <button class="border rounded px-2 py-1" @click="centerOnCapital">Capitale</button>
        <button class="border rounded px-2 py-1" @click="centerOnFleets('self')">Mes flottes</button>
        <button class="border rounded px-2 py-1" @click="centerOnFleets('other')">Flottes ennemies</button>
      </div>
      <div class="ml-auto flex items-center gap-3 text-xs">
        <span v-if="loading" class="text-gray-400">Chargement…</span>
        <span v-if="error" class="text-red-400">{{ error }}</span>
        <span v-if="data" class="hidden sm:inline text-gray-400">Galaxie: {{ galaxy.name }} — Sys: {{ systems.length }} | Flottes: {{ fleets.length }} | Secteurs: {{ sectors.length }}</span>
      </div>
    </header>

    <main class="flex-1 min-h-0 flex flex-col">
      <div v-if="!loading && data" ref="containerRef" class="relative w-full flex-1 min-h-0 border-t overflow-hidden bg-black cursor-grab"
           @mousedown="onMouseDown"
           @wheel.prevent="onWheel"
           :style="{ paddingTop: showCoords ? '32px' : '0', paddingLeft: showCoords ? '32px' : '0' }">

      <!-- Inner layer transformed by pan/zoom -->
      <div class="absolute inset-0" :style="mapInnerStyle">
        <!-- Sector Grid and labels -->
        <template v-if="showGrid">
          <template v-for="sec in sectors" :key="sec.id">
            <!-- Lines -->
            <div class="absolute bg-white/30 pointer-events-none" :style="topLineStyle(sec)"></div>
            <div class="absolute bg-white/30 pointer-events-none" :style="bottomLineStyle(sec)"></div>
            <div class="absolute bg-white/30 pointer-events-none" :style="leftLineStyle(sec)"></div>
            <div class="absolute bg-white/30 pointer-events-none" :style="rightLineStyle(sec)"></div>
            <!-- Label -->
            <div class="absolute text-gray-300/80 text-[10px] pointer-events-none" :style="sectorLabelStyle(sec)">{{ sec.name }}</div>
          </template>
        </template>
      </div>

      <!-- Grille carrée: lignes de la grille à l'écran -->
      <div v-if="showGrid && showSquareGrid" class="absolute inset-0 z-10 pointer-events-none">
        <!-- Lignes verticales -->
        <template v-for="x in viewportGrid.vertical" :key="'gx-' + x">
          <div class="absolute bg-white/15" :style="{ left: x + 'px', top: '0', width: '1px', height: '100%' }"></div>
        </template>
        <!-- Lignes horizontales -->
        <template v-for="y in viewportGrid.horizontal" :key="'gy-' + y">
          <div class="absolute bg-white/15" :style="{ left: '0', top: y + 'px', width: '100%', height: '1px' }"></div>
        </template>
      </div>

      <!-- Fog of war (screen-anchored, below systems, above grid) -->
      <div v-if="fogRectsScreen.length" class="absolute inset-0 z-12 pointer-events-none">
        <template v-for="r in fogRectsScreen" :key="`${r.left}-${r.top}-${r.width}-${r.height}`">
          <div class="absolute" :style="fogRectScreenStyle(r)"></div>
        </template>
      </div>

      <!-- Coordinate rulers (X axis on top, Y axis on left) -->
      <div v-if="showCoords" class="absolute inset-0 z-20 pointer-events-none">
        <!-- X axis ruler (top) -->
        <div class="absolute top-0 left-0 right-0 h-8 bg-gray-900/80 border-b border-gray-600">
          <template v-for="tick in xAxisTicks" :key="`x-${tick.coord}`">
            <div class="absolute text-white text-[12px] font-mono leading-none"
                 :style="{ left: tick.screenX + 'px', top: '2px', transform: 'translateX(-50%)' }">
              {{ tick.coord }}
            </div>
          </template>
        </div>
        <!-- Y axis ruler (left) -->
        <div class="absolute top-8 left-0 bottom-0 w-8 bg-gray-900/80 border-r border-gray-600">
          <template v-for="tick in yAxisTicks" :key="`y-${tick.coord}`">
            <div class="absolute text-white text-[12px] font-mono leading-none"
                 :style="{ left: '2px', top: (Math.round(tick.screenY) - 32) + 'px', transform: 'translateY(-50%)' }">
              {{ tick.coord }}
            </div>
          </template>
        </div>
      </div>

      <!-- Systems overlay -->
      <div v-if="showSystems" class="absolute inset-0 z-15 pointer-events-none">
        <template v-for="system in visibleSystems" :key="system.id">
          <div class="absolute cursor-pointer pointer-events-auto"
               :style="systemStyle(system)"
               :ref="el => setSystemEl(system.id, el, system)"
               :title="`Système ${system.name} (${system.x},${system.y})`">
            <!-- Sprite image when available -->
            <img v-if="system?.sprites?.map"
                 :src="system.sprites.map"
                 alt=""
                 class="block w-full h-full"
                 :style="{ imageRendering: 'pixelated', objectFit: 'contain' }" />
            <!-- Fallback circle marker -->
            <div v-else class="rounded-full border-2 border-white bg-blue-500/80 hover:bg-blue-400/90 transition-colors w-full h-full"></div>
            <!-- Optional system name label -->
            <div v-if="shouldShowNames" class="absolute text-white text-[10px] pointer-events-none" :style="nameLabelStyle()">
              {{ system.name }}
            </div>
          </div>
        </template>
      </div>

      <!-- Règles supprimées -->

      <!-- Légende supprimée -->

      <!-- Map Controls -->
      <div class="absolute bottom-2 right-2 z-20">
        <div class="inline-flex shadow rounded overflow-hidden">
          <button class="bg-gray-800/80 text-white text-sm px-3 py-1 hover:bg-gray-700" @click.stop="zoomIn">+
          </button>
          <button class="bg-gray-800/80 text-white text-sm px-3 py-1 hover:bg-gray-700" @click.stop="zoomOut">-
          </button>
          <button class="bg-gray-800/80 text-white text-sm px-3 py-1 hover:bg-gray-700" @click.stop="resetView">⟲
          </button>
        </div>
      </div>
      </div>
    </main>

    </div>
  </AuthenticatedLayout>
</template>

<style scoped>
</style>
