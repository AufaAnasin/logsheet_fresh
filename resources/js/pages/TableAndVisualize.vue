<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-4">
      <h1 class="text-2xl font-bold mb-4">Log Data for {{ props.component?.name ?? 'Component' }}</h1>

      <!-- Flash Messages -->
      <div v-if="flash.success" class="rounded-xl border border-green-200 bg-green-50 p-3 text-green-700 mb-4">
        {{ flash.success }}
      </div>
      <div v-if="flash.error" class="rounded-xl border border-red-200 bg-red-50 p-3 text-red-700 mb-4">
        {{ flash.error }}
      </div>
      <div v-if="!userDepartmentId && userRole !== 'SuperUser'"
        class="rounded-xl border border-yellow-200 bg-yellow-50 p-3 text-yellow-700 mb-4">
        No department assigned. Please contact an administrator to assign a department.
      </div>

      <!-- Filters and Search -->
      <div class="mb-4 flex flex-col sm:flex-row gap-4">
        <!-- Date Filter -->
        <div class="flex flex-col">
          <label for="date-filter" class="text-sm font-medium mb-1">Filter by Date</label>
          <select
            id="date-filter"
            v-model="dateFilter"
            @change="applyDateFilter"
            class="rounded-md border border-gray-300 p-2"
          >
            <option value="all">All</option>
            <option value="yearly">Yearly</option>
            <option value="monthly">Monthly</option>
            <option value="daily">Daily</option>
          </select>
        </div>
        <!-- Year Selector -->
        <div v-if="dateFilter === 'yearly'" class="flex flex-col">
          <label for="year" class="text-sm font-medium mb-1">Select Year</label>
          <select id="year" v-model="selectedYear" @change="applyDateFilter" class="rounded-md border border-gray-300 p-2">
            <option v-for="year in availableYears" :key="year" :value="year">{{ year }}</option>
          </select>
        </div>
        <!-- Month Selector -->
        <div v-if="dateFilter === 'monthly'" class="flex flex-col">
          <label for="month" class="text-sm font-medium mb-1">Select Month</label>
          <select id="month" v-model="selectedMonth" @change="applyDateFilter" class="rounded-md border border-gray-300 p-2">
            <option v-for="month in availableMonths" :key="month.value" :value="month.value">{{ month.label }}</option>
          </select>
        </div>
        <!-- Day Selector -->
        <div v-if="dateFilter === 'daily'" class="flex flex-col">
          <label for="day" class="text-sm font-medium mb-1">Select Date</label>
          <input
            id="day"
            type="date"
            v-model="selectedDay"
            @change="applyDateFilter"
            class="rounded-md border border-gray-300 p-2"
          />
        </div>
        <!-- Search Input -->
        <div class="flex flex-col">
          <label for="search" class="text-sm font-medium mb-1">Search Logs</label>
          <input
            id="search"
            v-model="searchQuery"
            placeholder="Search by value or operator..."
            class="rounded-md border border-gray-300 p-2"
          />
        </div>
      </div>

      <!-- Chart -->
      <div v-if="hasChartData" class="mb-6">
        <p class="text-lg font-semibold mb-2">Log Data Visualization for {{ props.component?.name ?? 'Component' }}</p>
        <div class="border border-gray-200 dark:border-gray-800 p-4 rounded-xl chart-container">
          <apexchart type="line" :options="chartOptions" :series="series" :height="300"></apexchart>
        </div>
      </div>
      <div v-else class="mb-6 rounded-xl border border-gray-200 dark:border-gray-800 p-3 text-center text-gray-500">
        No chart data available.
      </div>

      <!-- Log Table -->
      <div v-if="props.component" class="relative flex-1 rounded-xl border border-gray-200 dark:border-gray-800 p-3">
        <p class="text-lg font-semibold mb-2">Log Entries for {{ props.component.name }}</p>
        <Table>
          <TableCaption>A list of log entries for the selected component.</TableCaption>
          <TableHeader>
            <TableRow>
              <TableHead class="w-[100px]">Log ID</TableHead>
              <TableHead>Value</TableHead>
              <TableHead>Timestamp</TableHead>
              <TableHead>Operator</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-if="filteredLogs.length === 0">
              <TableCell colspan="4" class="text-center">No log entries available for this component.</TableCell>
            </TableRow>
            <TableRow v-else v-for="log in filteredLogs" :key="log.LogID">
              <TableCell class="font-medium">{{ log.LogID }}</TableCell>
              <TableCell><b>{{ log.LogValue ?? 'N/A' }}</b></TableCell>
              <TableCell>{{ log.LogTimestamp }}</TableCell>
              <TableCell>{{ log.OperatorName }}</TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </div>
      <div v-else class="rounded-xl border border-red-200 bg-red-50 p-3 text-red-700 text-center">
        {{ flash.error || 'Component not found or unauthorized.' }}
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed, reactive } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import {
  Table,
  TableBody,
  TableCaption,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import VueApexCharts from 'vue3-apexcharts';

// Define interfaces
interface Log {
  LogID: number;
  ComponentID: number;
  LogValue: number | null;
  LogTimestamp: string;
  OperatorName: string;
}

interface Component {
  ComponentID: number;
  name: string;
  AreaID: number;
  area_name: string;
  desc: string | null;
}

interface ChartData {
  xAxis: { type: string; data: string[] };
  series: { name: string; type: string; data: (number | null)[] }[];
}

interface Flash {
  success?: string | null;
  error?: string | null;
}

interface PageProps {
  title: string;
  description: string;
  flash?: Flash;
  errors?: Record<string, string>;
  component?: Component | null;
  logs?: Log[] | null;
  chart_data?: ChartData | null;
  user?: { DepartmentID: number | null; role: string | null };
}

// Props with nullable fields
const props = defineProps<{
  title: string;
  description: string;
  component: Component | null;
  logs: Log[] | null;
  chart_data: ChartData | null;
}>();

// Debug props
console.log('TableAndVisualize props:', JSON.stringify({
  component: props.component,
  logs: props.logs,
  chart_data: props.chart_data
}, null, 2));

// Get user and department ID from page props
const page = usePage<{ props: PageProps }>();
const user = computed(() => page.props.user ?? { DepartmentID: null, role: null });
const userDepartmentId = computed(() => user.value?.DepartmentID ?? null);
const userRole = computed(() => user.value?.role ?? null);

// Safe logs array
const safeLogs = computed(() => Array.isArray(props.logs) ? props.logs : []);

// Flash messages
const flash = computed(() => {
  const flashObj: Flash = page.props.flash ?? { success: null, error: null };
  return {
    success: flashObj.success ?? null,
    error: page.props.errors ? Object.values(page.props.errors).join(', ') ?? null : flashObj.error ?? null,
  };
});

// Breadcrumbs
const breadcrumbs = computed(() => [
  { title: 'Analytics', href: '/analytics' },
  { title: `Components for ${props.component?.area_name ?? 'Unknown Area'}`, href: `/analytics/components/${props.component?.AreaID ?? ''}` },
  { title: `Log Data for ${props.component?.name ?? 'Unknown Component'}`, href: '' },
]);

// Filter state
const dateFilter = ref<'all' | 'yearly' | 'monthly' | 'daily'>('all');
const selectedYear = ref<number>(new Date().getFullYear());
const selectedMonth = ref<number>(new Date().getMonth() + 1); // 1-12
const selectedDay = ref<string>(new Date().toISOString().split('T')[0]);
const searchQuery = ref<string>('');

// Available years and months
const availableYears = computed(() => {
  const years = new Set(safeLogs.value.map(log => {
    if (log.LogTimestamp && log.LogTimestamp !== 'N/A') {
      return new Date(log.LogTimestamp).getFullYear();
    }
    return null;
  }));
  return Array.from(years).filter(year => year !== null).sort((a, b) => b - a);
});

const availableMonths = computed(() => [
  { value: 1, label: 'January' },
  { value: 2, label: 'February' },
  { value: 3, label: 'March' },
  { value: 4, label: 'April' },
  { value: 5, label: 'May' },
  { value: 6, label: 'June' },
  { value: 7, label: 'July' },
  { value: 8, label: 'August' },
  { value: 9, label: 'September' },
  { value: 10, label: 'October' },
  { value: 11, label: 'November' },
  { value: 12, label: 'December' },
]);

// Filtered logs
const filteredLogs = computed(() => {
  let filtered = safeLogs.value;

  if (dateFilter.value !== 'all') {
    filtered = filtered.filter(log => {
      if (!log.LogTimestamp || log.LogTimestamp === 'N/A') return false;
      const date = new Date(log.LogTimestamp);
      if (dateFilter.value === 'yearly') {
        return date.getFullYear() === selectedYear.value;
      } else if (dateFilter.value === 'monthly') {
        return date.getFullYear() === selectedYear.value && date.getMonth() + 1 === selectedMonth.value;
      } else if (dateFilter.value === 'daily') {
        return date.toISOString().split('T')[0] === selectedDay.value;
      }
      return true;
    });
  }

  if (searchQuery.value.trim()) {
    const query = searchQuery.value.toLowerCase();
    filtered = filtered.filter(log =>
      String(log.LogValue ?? '').toLowerCase().includes(query) ||
      log.OperatorName.toLowerCase().includes(query)
    );
  }

  return filtered.sort((a, b) => {
    const dateA = a.LogTimestamp && a.LogTimestamp !== 'N/A' ? new Date(a.LogTimestamp).getTime() : 0;
    const dateB = b.LogTimestamp && b.LogTimestamp !== 'N/A' ? new Date(b.LogTimestamp).getTime() : 0;
    return dateB - dateA; // Sort descending
  });
});

// Check if chart data is available
const hasChartData = computed(() => {
  return filteredLogs.value.length > 0 && filteredLogs.value.some(log => log.LogValue !== null);
});

// Chart options
const chartOptions = reactive({
  chart: {
    id: 'log-data-chart',
    toolbar: { show: false },
    animations: { enabled: true, easing: 'easeinout', speed: 800 },
  },
  xaxis: {
    type: 'datetime',
    categories: computed(() => filteredLogs.value
      .map(log => log.LogTimestamp !== 'N/A' ? new Date(log.LogTimestamp).getTime() : null)
      .filter(timestamp => timestamp !== null) as number[]),
    title: { text: 'Timestamp' },
    labels: {
      rotate: -45,
      hideOverlappingLabels: true,
      formatter: (value: number) => {
        const date = new Date(value);
        const allSameDay = filteredLogs.value.every(log => {
          if (!log.LogTimestamp || log.LogTimestamp === 'N/A') return true;
          const logDate = new Date(log.LogTimestamp);
          return logDate.toDateString() === date.toDateString();
        });
        return allSameDay
          ? date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })
          : date.toLocaleString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
      },
    },
  },
  yaxis: {
    title: { text: 'Log Value' },
    min: 0,
  },
  title: {
    text: 'Log Data Trend',
    align: 'center',
    style: { fontSize: '16px' },
  },
  tooltip: {
    x: {
      formatter: (timestamp: number) => {
        const date = new Date(timestamp);
        const allSameDay = filteredLogs.value.every(log => {
          if (!log.LogTimestamp || log.LogTimestamp === 'N/A') return true;
          const logDate = new Date(log.LogTimestamp);
          return logDate.toDateString() === date.toDateString();
        });
        return allSameDay
          ? date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })
          : date.toLocaleString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
      },
    },
    y: { formatter: (value: number) => `${value} units` },
  },
  responsive: [
    {
      breakpoint: 640,
      options: {
        chart: { width: '100%' },
        xaxis: { labels: { rotate: 0 } },
      },
    },
  ],
});

// Chart series
const series = computed(() => [
  {
    name: props.component?.name ?? 'Component',
    data: filteredLogs.value
      .map(log => ({
        x: log.LogTimestamp !== 'N/A' ? new Date(log.LogTimestamp).getTime() : null,
        y: log.LogValue !== null ? log.LogValue : null,
      }))
      .filter(point => point.x !== null && point.y !== null),
  },
]);

// Apply date filter by updating URL query params
const applyDateFilter = () => {
  if (!props.component?.ComponentID) {
    console.error('ComponentID is missing, cannot apply date filter');
    return;
  }
  const query: Record<string, string | number> = {};
  if (dateFilter.value !== 'all') {
    query.filter = dateFilter.value;
    if (dateFilter.value === 'yearly') {
      query.year = selectedYear.value;
    } else if (dateFilter.value === 'monthly') {
      query.year = selectedYear.value;
      query.month = selectedMonth.value;
    } else if (dateFilter.value === 'daily') {
      query.day = selectedDay.value;
    }
  }
  console.log('Navigating to visualizeandtable with query:', query);
  router.get(route('visualizeandtable', { componentId: props.component.ComponentID }), query, { preserveState: true });
};
</script>

<style scoped>
.chart-container {
  position: relative;
  width: 100%;
}
.apexcharts-canvas {
  margin: 0 auto;
}
@media (max-width: 640px) {
  .apexcharts-canvas {
    width: 100% !important;
  }
}
</style>