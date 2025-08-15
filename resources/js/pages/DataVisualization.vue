```vue
<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted, watch } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Label } from '@/components/ui/label';
import { Alert, AlertDescription } from '@/components/ui/alert';
import FlatPickr from 'vue-flatpickr-component';
import 'flatpickr/dist/flatpickr.css';
import { VueDraggable } from 'vue-draggable-plus';
import { Bar, Line } from 'vue-chartjs';
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, LineElement, PointElement, CategoryScale, LinearScale, TimeScale } from 'chart.js';
import 'chartjs-adapter-date-fns';
import { saveAs } from 'file-saver';
import Papa from 'papaparse';

ChartJS.register(Title, Tooltip, Legend, BarElement, LineElement, PointElement, CategoryScale, LinearScale, TimeScale);

interface Area {
  AreaID: number;
  name: string;
  DepartmentID: number;
  department_name: string;
}

interface Component {
  ComponentID: number;
  name: string;
  AreaID: number;
  area_name: string | null;
  department_name: string | null;
}

interface LogData {
  component_id: number;
  component_name: string;
  timestamp: string;
  value: number;
  notes: string | null;
}

interface ChartConfig {
  id: number;
  type: 'bar' | 'line' | 'area';
  componentIds: number[];
  areaIds: number[];
  dateRange: [string, string];
  position: { x: number; y: number };
  size: { width: number; height: number };
}

interface PageProps {
  areas?: Area[] | null;
  components?: Component[] | null;
  flash?: { success?: string; error?: string };
  userRole?: string;
  userDepartmentId?: number;
}

const breadcrumbs = [{ title: 'Data Visualization', href: '/visualization' }];

const page = usePage<{ props: PageProps }>();
console.log('Raw page props:', page.props); // Log entire props to inspect data
const areas: Area[] = Array.isArray(page.props.areas) ? page.props.areas : [];
const components: Component[] = Array.isArray(page.props.components) ? page.props.components : [];
console.log('Initial areas:', areas);
console.log('Initial components:', components);
const flash = (page.props.flash || {}) as { success?: string; error?: string };
const userRole = page.props.userRole || 'Operator';
const userDepartmentId = page.props.userDepartmentId;

const selectedAreaIds = ref<number[]>([]);
const charts = ref<ChartConfig[]>([]);
const chartData = ref<Record<number, LogData[]>>({});
const loading = ref(false);
const error = ref<string | null>(null);
let chartIdCounter = 1;

const filteredComponents = computed(() => {
  console.log('Evaluating filteredComponents with selectedAreaIds:', selectedAreaIds.value);
  if (!components.length) {
    console.log('No components available in props');
    return [];
  }
  if (selectedAreaIds.value.length) {
    const filtered = components.filter(c => selectedAreaIds.value.includes(c.AreaID));
    console.log('Filtered components by selected areas:', filtered);
    return filtered.length ? filtered : components; // Fallback to all components if filter fails
  }
  const defaultFiltered = userRole === 'SuperUser' 
    ? components 
    : components.filter(c => {
        const area = areas.find(a => a.AreaID === c.AreaID);
        console.log('Checking component:', c.name, 'Area:', area?.name, 'Department match:', area?.department_name === (area ? area.department_name : null));
        return area && area.department_name === (area ? area.department_name : null);
      });
  console.log('Default filtered components:', defaultFiltered);
  return defaultFiltered;
});

// Force initial evaluation
onMounted(() => {
  console.log('Component mounted, forcing filteredComponents evaluation:', filteredComponents.value);
  const savedCharts = localStorage.getItem('visualization_charts');
  if (savedCharts) {
    charts.value = JSON.parse(savedCharts).map((chart: ChartConfig) => ({
      ...chart,
      componentIds: chart.componentIds.filter(id => components.some(c => c.ComponentID === id)),
      areaIds: chart.areaIds.filter(id => areas.some(a => a.AreaID === id)),
    }));
    chartIdCounter = Math.max(...charts.value.map(c => c.id), 0) + 1;
    fetchDataForCharts();
  }
  setInterval(() => fetchDataForCharts(), 60000);
});

const fetchDataForCharts = useDebounceFn(async () => {
  if (charts.value.length === 0 || charts.value.every(chart => chart.componentIds.length === 0 || chart.areaIds.length === 0)) {
    error.value = 'Please select at least one area and one component for each chart.';
    return;
  }
  loading.value = true;
  error.value = null;
  try {
    const payload = {
      charts: charts.value.map(chart => ({
        id: chart.id,
        component_ids: chart.componentIds,
        area_ids: chart.areaIds,
        date_range: chart.dateRange,
      })),
    };
    console.log('Payload sent to /visualization/data:', payload);
    const response = await fetch('/visualization/data', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
      },
      body: JSON.stringify(payload),
    });
    if (!response.ok) {
      const errorData = await response.json();
      throw new Error(errorData.error || `HTTP error ${response.status}`);
    }
    const data = await response.json();
    console.log('Fetched chart data:', data);
    if (Object.keys(data).length === 0 || Object.values(data).every(arr => arr.length === 0)) {
      error.value = 'No data available for the selected areas, components, and date range.';
    } else {
      chartData.value = data;
    }
  } catch (err) {
    error.value = `Failed to load chart data: ${err.message}`;
  } finally {
    loading.value = false;
  }
}, 300);

watch(charts, (newCharts) => {
  localStorage.setItem('visualization_charts', JSON.stringify(newCharts));
  fetchDataForCharts();
}, { deep: true });

watch(selectedAreaIds, () => {
  console.log('selectedAreaIds changed to:', selectedAreaIds.value);
  charts.value.forEach(chart => {
    chart.areaIds = selectedAreaIds.value;
    chart.componentIds = chart.componentIds.filter(id => filteredComponents.value.some(c => c.ComponentID === id));
  });
  fetchDataForCharts();
}, { deep: true });

const addChart = () => {
  if (charts.value.length >= 10) {
    error.value = 'Maximum 10 charts allowed.';
    return;
  }
  charts.value.push({
    id: chartIdCounter++,
    type: 'line',
    componentIds: [],
    areaIds: selectedAreaIds.value,
    dateRange: [
      new Date(Date.now() - 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
      new Date().toISOString().split('T')[0],
    ],
    position: { x: 0, y: charts.value.length * 300 },
    size: { width: 400, height: 300 },
  });
};

const removeChart = (chartId: number) => {
  charts.value = charts.value.filter(chart => chart.id !== chartId);
  delete chartData.value[chartId];
};

const exportChartAsPNG = (chartId: number) => {
  const canvas = document.getElementById(`chart-${chartId}`) as HTMLCanvasElement;
  if (canvas) {
    canvas.toBlob((blob) => {
      if (blob) saveAs(blob, `chart-${chartId}.png`);
    });
  }
};

const exportChartAsCSV = (chartId: number) => {
  const data = chartData.value[chartId] || [];
  const csv = Papa.unparse(data.map(d => ({
    Component: components.find(c => c.ComponentID === d.component_id)?.name || `Component ${d.component_id}`,
    Area: areas.find(a => a.AreaID === components.find(c => c.ComponentID === d.component_id)?.AreaID)?.name || 'Unknown',
    Timestamp: d.timestamp,
    Value: d.value,
    Notes: d.notes,
  })));
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8' });
  saveAs(blob, `chart-${chartId}-data.csv`);
};

const getChartOptions = (chart: ChartConfig) => {
  const data = chartData.value[chart.id] || [];
  console.log('Chart data for chart', chart.id, ':', data);
  if (!data.length) return { labels: [], datasets: [], options: { responsive: true, maintainAspectRatio: false } };
  const datasets = chart.componentIds.map(id => {
    const componentData = data.filter(d => d.component_id === id);
    return {
      label: components.find(c => c.ComponentID === id)?.name || `Component ${id}`,
      data: componentData.map(d => d.value),
      borderColor: `hsl(${Math.random() * 360}, 70%, 50%)`,
      backgroundColor: `hsl(${Math.random() * 360}, 70%, 50%, 0.2)`,
      fill: chart.type === 'area',
      tension: chart.type === 'line' ? 0.4 : 0,
    };
  });
  return {
    labels: [...new Set(data.map(d => d.timestamp))],
    datasets,
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        x: {
          type: 'time',
          time: { unit: 'day', displayFormats: { day: 'yyyy-MM-dd' } },
          title: { display: true, text: 'Date' },
        },
        y: { title: { display: true, text: 'Value' }, beginAtZero: true },
      },
      plugins: { legend: { position: 'top' }, tooltip: { mode: 'index', intersect: false } },
    },
  };
};

const getRandomColor = (opacity = 1) => {
  const r = Math.floor(Math.random() * 255);
  const g = Math.floor(Math.random() * 255);
  const b = Math.floor(Math.random() * 255);
  return `rgba(${r}, ${g}, ${b}, ${opacity})`;
};
</script>

<template>
  <Head title="Data Visualization" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="min-h-screen w-full bg-gray-50 dark:bg-gray-900 flex flex-col">
      <div class="sticky top-0 bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700 z-20 py-3 sm:py-4 px-3 sm:px-6">
        <div class="max-w-full mx-auto">
          <Label class="text-sm sm:text-base font-semibold text-gray-700 dark:text-gray-300">Dashboard Status</Label>
          <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-1">{{ loading ? 'Loading data...' : `${charts.length} chart(s) displayed` }}</p>
        </div>
      </div>

      <div class="flex-1 p-3 sm:p-6 overflow-auto">
        <div class="max-w-full mx-auto">
          <div class="mb-6 sm:mb-8">
            <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 dark:text-gray-100">Data Visualization</h1>
            <p class="mt-1 sm:mt-2 text-sm sm:text-base text-gray-600 dark:text-gray-400">Visualize log data across selected areas and components.</p>
          </div>

          <Alert v-if="flash.success" class="mb-4 sm:mb-6 bg-green-100 dark:bg-green-800 border-green-300 dark:border-green-600 text-green-800 dark:text-green-100 animate-fade-in">
            <AlertDescription class="text-sm sm:text-base">{{ flash.success }}</AlertDescription>
          </Alert>
          <Alert v-if="flash.error || error" variant="destructive" class="mb-4 sm:mb-6 bg-red-100 dark:bg-red-800 text-red-800 dark:text-red-400 animate-shake">
            <AlertDescription class="text-sm sm:text-base">{{ flash.error || error }}</AlertDescription>
          </Alert>

          <div class="mb-6 sm:mb-8">
            <div class="flex flex-col sm:flex-row gap-4">
              <div class="w-full sm:w-1/4">
                <Label for="area-select" class="text-sm sm:text-base font-semibold text-gray-700 dark:text-gray-300">Select Areas</Label>
                <Select v-model="selectedAreaIds" multiple :max="5" class="mt-2">
                  <SelectTrigger id="area-select" class="w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 text-sm sm:text-base">
                    <SelectValue placeholder="Choose areas..." :placeholder="selectedAreaIds.length ? `${selectedAreaIds.length} selected` : 'Choose areas...'" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem v-for="area in areas" :key="area.AreaID" :value="area.AreaID" :disabled="userRole !== 'SuperUser' && area.DepartmentID !== userDepartmentId">
                      {{ area.name }} ({{ area.department_name }})
                    </SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>
          </div>

          <div class="relative">
            <VueDraggable v-model="charts" item-key="id" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
              <template #item="{ element: chart }">
                <div
                  class="bg-white dark:bg-gray-800 p-4 sm:p-6 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 transition-all duration-200 hover:shadow-md"
                  :style="{ width: `${chart.size.width}px`, height: `${chart.size.height}px` }"
                >
                  <div class="flex justify-between items-center mb-3">
                    <h3 class="text-sm sm:text-base font-semibold text-gray-800 dark:text-gray-200">Chart {{ chart.id }}</h3>
                    <Button variant="ghost" size="sm" @click="removeChart(chart.id)" aria-label="Remove chart">✕</Button>
                  </div>
                  <div class="mb-4 space-y-2">
                    <Select v-model="chart.type" @update:model-value="fetchDataForCharts">
                      <SelectTrigger class="w-full border-gray-300 dark:border-gray-600 text-sm sm:text-base">
                        <SelectValue placeholder="Chart Type" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="line">Line</SelectItem>
                        <SelectItem value="bar">Bar</SelectItem>
                        <SelectItem value="area">Area</SelectItem>
                      </SelectContent>
                    </Select>
                    <Select v-model="chart.componentIds" multiple :max="10" @update:model-value="fetchDataForCharts">
                      <SelectTrigger class="w-full border-gray-300 dark:border-gray-600 text-sm sm:text-base">
                        <SelectValue placeholder="Select up to 10 components" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem v-for="comp in filteredComponents.value" :key="comp.ComponentID" :value="comp.ComponentID">
                          {{ comp.name }} ({{ comp.area_name }})
                        </SelectItem>
                      </SelectContent>
                    </Select>
                    <Select v-model="chart.areaIds" multiple :max="5" @update:model-value="fetchDataForCharts">
                      <SelectTrigger class="w-full border-gray-300 dark:border-gray-600 text-sm sm:text-base">
                        <SelectValue placeholder="Select up to 5 areas" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem v-for="area in areas" :key="area.AreaID" :value="area.AreaID" :disabled="userRole !== 'SuperUser' && area.DepartmentID !== userDepartmentId">
                          {{ area.name }}
                        </SelectItem>
                      </SelectContent>
                    </Select>
                    <FlatPickr
                      v-model="chart.dateRange"
                      :config="{ mode: 'range', dateFormat: 'Y-m-d' }"
                      class="w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 text-sm sm:text-base p-2 rounded"
                      placeholder="Select date range"
                      @on-change="fetchDataForCharts"
                    />
                  </div>
                  <div v-if="!chartData[chart.id]?.length" class="text-center text-gray-500 dark:text-gray-400 h-[250px] flex items-center justify-center">
                    No data available for the selected areas, components, and date range.
                  </div>
                  <component
                    v-else
                    :is="chart.type === 'area' ? Line : chart.type === 'line' ? Line : Bar"
                    :id="`chart-${chart.id}`"
                    :data="getChartOptions(chart)"
                    :options="getChartOptions(chart).options"
                    class="w-full h-[250px] sm:h-[300px]"
                  />
                  <div class="mt-3 flex gap-2">
                    <Button size="sm" variant="outline" @click="exportChartAsPNG(chart.id)" :disabled="!chartData[chart.id]?.length">Export PNG</Button>
                    <Button size="sm" variant="outline" @click="exportChartAsCSV(chart.id)" :disabled="!chartData[chart.id]?.length">Export CSV</Button>
                  </div>
                </div>
              </template>
            </VueDraggable>
          </div>
        </div>
      </div>

      <div class="sticky bottom-0 bg-gray-50 dark:bg-gray-900 py-3 sm:py-4 border-t border-gray-200 dark:border-gray-700 z-10">
        <div class="max-w-full mx-auto px-3 sm:px-6 flex flex-col sm:flex-row gap-2 sm:gap-4">
          <Button
            class="w-full sm:w-auto bg-blue-600 dark:bg-blue-500 hover:bg-blue-700 dark:hover:bg-blue-400 text-white font-semibold py-2 px-4 sm:px-6 rounded-lg transition-all duration-200 text-sm sm:text-base"
            :disabled="selectedAreaIds.length === 0"
            @click="addChart"
            aria-label="Add new chart"
          >
            Add Chart
          </Button>
          <Button
            variant="outline"
            class="w-full sm:w-auto border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-semibold py-2 px-4 sm:px-6 rounded-lg transition-all duration-200 text-sm sm:text-base"
            @click="charts = []; localStorage.removeItem('visualization_charts')"
            aria-label="Reset dashboard"
          >
            Reset Dashboard
          </Button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.min-h-screen { min-height: 100vh; }
.animate-fade-in { animation: fadeIn 0.3s ease-in; }
.animate-shake { animation: shake 0.5s cubic-bezier(0.36, 0.07, 0.19, 0.97) both; }
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes shake {
  10%, 90% { transform: translate3d(-1px, 0, 0); }
  20%, 80% { transform: translate3d(2px, 0, 0); }
  30%, 50%, 70% { transform: translate3d(-4px, 0, 0); }
  40%, 60% { transform: translate3d(4px, 0, 0); }
}
</style>