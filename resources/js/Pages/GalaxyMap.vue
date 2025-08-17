<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';

const loading = ref(true);
const error = ref(null);
const data = ref(null);
const showGrid = ref(true);
const showNames = ref(true);
const containerSize = ref({ width: 900, height: 700 });
const selectedGalaxyId = ref(null);

const galaxy = computed(() => data.value?.galaxy || { size_x: 100, size_y: 100, name: '...' });
const systems = computed(() => data.value?.systems || []);
const fleets = computed(() => data.value?.fleets || []);
const visibleGalaxies = computed(() => data.value?.visible_galaxies || []);
const mode = computed(() => data.value?.mode || 'player');

function systemPositionStyle(s) {
  const xPct = galaxy.value.size_x ? (s.x / galaxy.value.size_x) * 100 : 0;
  const yPct = galaxy.value.size_y ? (s.y / galaxy.value.size_y) * 100 : 0;
  return { left: xPct + '%', top: yPct + '%' };
}

async function fetchMap() {
  loading.value = true;
  error.value = null;
  try {
    const params = {};
    if (selectedGalaxyId.value) params.galaxy_id = selectedGalaxyId.value;
    const url = '/game/api/map';
    const resp = await axios.get(url, { params });
    data.value = resp.data;
    if (!selectedGalaxyId.value) selectedGalaxyId.value = resp.data?.galaxy?.id || null;
  } catch (e) {
    error.value = e?.response?.data?.error || e.message || 'Erreur';
  } finally {
    loading.value = false;
  }
}

onMounted(fetchMap);
</script>

<template>
  <Head title="Carte galactique" />
  <div class="p-4 space-y-4">
    <div class="flex items-center gap-2">
      <h1 class="text-xl font-semibold">Carte galactique (Vue)</h1>
      <span v-if="mode==='admin'" class="px-2 py-0.5 text-xs rounded bg-purple-100 text-purple-700">Admin</span>
    </div>

    <div class="flex items-center gap-3 text-sm">
      <label class="inline-flex items-center gap-1">
        <input type="checkbox" v-model="showGrid" />
        Grille
      </label>
      <label class="inline-flex items-center gap-1">
        <input type="checkbox" v-model="showNames" />
        Noms
      </label>
      <div class="inline-flex items-center gap-2" v-if="visibleGalaxies.length">
        <span>Galaxie:</span>
        <select v-model="selectedGalaxyId" @change="fetchMap" class="border rounded px-2 py-1">
          <option v-for="g in visibleGalaxies" :key="g.id" :value="g.id">{{ g.name }}</option>
        </select>
      </div>
    </div>

    <div v-if="error" class="text-red-600 text-sm">{{ error }}</div>
    <div v-if="loading" class="text-gray-600 text-sm">Chargement…</div>

    <div v-if="!loading && data" class="relative border rounded overflow-hidden bg-black" :style="{ width: containerSize.width + 'px', height: containerSize.height + 'px' }">
      <div v-if="showGrid" class="absolute inset-0 pointer-events-none" :style="{
        backgroundSize: '10% 10%',
        backgroundImage: 'linear-gradient(to right, rgba(255,255,255,0.06) 1px, transparent 1px), linear-gradient(to bottom, rgba(255,255,255,0.06) 1px, transparent 1px)'
      }"></div>

      <!-- Systems -->
      <div v-for="s in systems" :key="s.id" class="absolute" :style="systemPositionStyle(s)">
        <div :class="['w-2.5 h-2.5 rounded-full border transform -translate-x-1/2 -translate-y-1/2', {
          'bg-green-400 border-green-300': s.owner==='self',
          'bg-yellow-400 border-yellow-300': s.owner==='neutral',
          'bg-blue-400 border-blue-300': s.owner==='other'
        }]"></div>
        <div v-if="showNames" class="absolute left-2 top-0 text-white text-xs whitespace-nowrap">
          <a :href="'/game/star-system/' + s.id" class="hover:underline">{{ s.name }}</a>
        </div>
      </div>

      <!-- Fleets at system positions -->
      <div v-for="f in fleets" :key="f.id" v-if="f.system_id" class="absolute"
           :style="systemPositionStyle({ x: (systems.find(s=>s.id===f.system_id)?.x) || 0, y: (systems.find(s=>s.id===f.system_id)?.y) || 0 })">
        <div :class="['w-0 h-0 border-l-4 border-r-4 border-b-8 transform -translate-x-1/2 -translate-y-1/2', {
          'border-b-green-400': f.owner==='self',
          'border-b-red-400': f.owner==='other'
        }]"></div>
      </div>
    </div>

    <div v-if="data" class="text-xs text-gray-600">
      <div>Galaxie: {{ galaxy.name }} ({{ galaxy.size_x }}x{{ galaxy.size_y }})</div>
      <div>Systèmes: {{ systems.length }} | Flottes: {{ fleets.length }}</div>
    </div>
  </div>
</template>

<style scoped>
</style>
