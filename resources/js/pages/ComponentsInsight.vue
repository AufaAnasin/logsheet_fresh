<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-4">
      <h1 class="text-2xl font-bold mb-4">Components Insights</h1>

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
        <!-- Year/Month/Day Selectors -->
        <div v-if="dateFilter === 'yearly'" class="flex flex-col">
          <label for="year" class="text-sm font-medium mb-1">Select Year</label>
          <select id="year" v-model="selectedYear" @change="applyDateFilter" class="rounded-md border border-gray-300 p-2">
            <option v-for="year in availableYears" :key="year" :value="year">{{ year }}</option>
          </select>
        </div>
        <div v-if="dateFilter === 'monthly'" class="flex flex-col">
          <label for="month" class="text-sm font-medium mb-1">Select Month</label>
          <select id="month" v-model="selectedMonth" @change="applyDateFilter" class="rounded-md border border-gray-300 p-2">
            <option v-for="month in availableMonths" :key="month.value" :value="month.value">{{ month.label }}</option>
          </select>
        </div>
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
        <!-- Chart Type Selector -->
        <div class="flex flex-col">
          <label for="chart-type" class="text-sm font-medium mb-1">Chart Type</label>
          <select
            id="chart-type"
            v-model="chartType"
            class="rounded-md border border-gray-300 p-2"
          >
            <option value="line">Line</option>
            <option value="bar">Bar</option>
          </select>
        </div>
      </div>

      <!-- Chart -->
      <div v-if="chartConfig.labels.length > 0 && chartConfig.datasets[0].data.length > 0" class="mb-6">
        <p class="text-lg font-semibold mb-2">Log Data Visualization for {{ props.component?.name ?? 'Component' }}</p>
        <div class="border border-gray-200 dark:border-gray-800 p-4 rounded-xl chart-container">
          <component :is="chartComponent" :data="chartConfig" :options="chartOptions" />
        </div>
      </div>
      <div v-else class="mb-6 rounded-xl border border-gray-200 dark:border-gray-800 p-3 text-center text-gray-500">
        No chart data available.
      </div>

      <!-- Log Table -->
      <div class="relative flex-1 rounded-xl border border-gray-200 dark:border-gray-800 p-3">
        <p class="text-lg font-semibold mb-2">Log Data for {{ areaName }}</p>
        <Table>
          <TableCaption>Log data for the selected component.</TableCaption>
          <TableHeader>
            <TableRow>
              <TableHead class="w-[100px]">Log ID</TableHead>
              <TableHead>Value</TableHead>
              <TableHead>Timestamp (WIB)</TableHead>
              <TableHead>Operator</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-if="filteredLogs.length === 0">
              <TableCell colspan="4" class="text-center">
                {{ props.component ? 'No logs available for this component.' : 'Component not found or unauthorized.' }}
              </TableCell>
            </TableRow>
            <TableRow v-else v-for="log in filteredLogs" :key="log.LogID">
              <TableCell class="font-medium">{{ log.LogID }}</TableCell>
              <TableCell>{{ log.LogValue }}</TableCell>
              <TableCell>{{ formatTimestamp(log.LogTimestamp) }}</TableCell>
              <TableCell>{{ log.OperatorName }}</TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import {
  Table,
  TableBody,
  TableCaption,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { computed, ref } from 'vue';
import { formatInTimeZone } from 'date-fns-tz';
import { Bar, Line } from 'vue-chartjs';
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  BarElement,
  LineElement,
  PointElement,
  CategoryScale,
  LinearScale,
} from 'chart.js';

// Register Chart.js components
ChartJS.register(Title, Tooltip, Legend, BarElement, LineElement, PointElement, CategoryScale, LinearScale);

// Define interfaces
interface Log {
  LogID: number;
  ComponentID: number;
  LogValue: number;
  LogTimestamp: string | null;
  OperatorName: string;
}

interface Component {
  ComponentID: number;
  name: string;
  AreaID: number;
  area_name: string | null;
  desc: string | null;
}

interface Flash {
  success?: string | null;
  error?: string | null;
}

interface ChartData {
  xAxis: { type: string; data: string[] };
  series: { name: string; type: string; data: number[] }[];
}

interface PageProps {
  flash?: Flash;
  errors?: Record<string, string>;
  component?: Component | null;
  logs?: Log[] | null;
  chart_data?: ChartData;
  user?: { DepartmentID: number | null; role: string | null };
}

// Props with nullable arrays
const props = defineProps<{
  component: Component | null;
  logs: Log[] | null;
  chart_data: ChartData;
}>();

// Debug chart_data
console.log('chart_data:', JSON.stringify(props.chart_data, null, 2));

// Get user and department ID from page props
const page = usePage<{ props: PageProps }>();
const user = computed(() => page.props.user ?? ({ DepartmentID: null, role: null } as { DepartmentID: number | null; role: string | null }));
const userDepartmentId = computed(() => user.value.DepartmentID);
const userRole = computed(() => user.value.role);

// Safe logs array
const safeLogs = computed(() => Array.isArray(props.logs) ? props.logs : []);

// Area name for display
const areaName = computed(() => props.component?.area_name ?? 'Unknown Area');

// Flash messages
const flash = computed(() => {
  const flashObj: Flash = page.props.flash ?? { success: null, error: null };
  return {
    success: flashObj.success ?? null,
    error: page.props.errors ? Object.values(page.props.errors).join(', ') ?? null : flashObj.error ?? null,
  };
});

// Date filter state
const dateFilter = ref<'all' | 'yearly' | 'monthly' | 'daily'>('all');
const selectedYear = ref<number>(new Date().getFullYear());
const selectedMonth = ref<number>(new Date().getMonth() + 1); // 1-12
const selectedDay = ref<string>(new Date().toISOString().split('T')[0]);

// Search state
const searchQuery = ref<string>('');

// Chart type state
const chartType = ref<'bar' | 'line'>('line');

// Dynamic chart component
const chartComponent = computed(() => chartType.value === 'line' ? Line : Bar);

// Available years and months for dropdowns
const availableYears = computed(() => {
  const years = new Set(safeLogs.value.map(log => log.LogTimestamp ? new Date(log.LogTimestamp).getFullYear() : null));
  return Array.from(years).filter(year => year !== null).sort((a, b) => b - a);
});

const availableMonths = computed(() => {
  const months = [
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
  ];
  return months;
});

// Filtered logs based on date and search
const filteredLogs = computed(() => {
  let filtered = safeLogs.value;

  // Apply date filter (already filtered by backend, but ensure client-side consistency)
  if (dateFilter.value !== 'all') {
    filtered = filtered.filter(log => {
      if (!log.LogTimestamp) return false;
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

  // Apply search filter
  if (searchQuery.value.trim()) {
    const query = searchQuery.value.toLowerCase();
    filtered = filtered.filter(log =>
      String(log.LogValue).toLowerCase().includes(query) ||
      log.OperatorName.toLowerCase().includes(query)
    );
  }

  return filtered.sort((a, b) => {
    const dateA = a.LogTimestamp ? new Date(a.LogTimestamp).getTime() : 0;
    const dateB = b.LogTimestamp ? new Date(b.LogTimestamp).getTime() : 0;
    return dateB - dateA; // Sort descending
  });
});

// Format timestamp to WIB
const formatTimestamp = (timestamp: string | null) => {
  if (!timestamp) return 'N/A';
  try {
    const date = new Date(timestamp);
    if (isNaN(date.getTime())) return 'Invalid Date';
    return formatInTimeZone(date, 'Asia/Jakarta', 'd MMM yyyy, HH:mm:ss');
  } catch {
    return 'Invalid Date';
  }
};

// Chart configuration
const chartConfig = computed(() => ({
  labels: props.chart_data.xAxis.data.map(date => 
    date === 'No Data' ? date : formatInTimeZone(new Date(date), 'Asia/Jakarta', 'd MMM yyyy, HH:mm:ss')
  ),
  datasets: props.chart_data.series.map(series => ({
    label: series.name,
    data: series.data,
    backgroundColor: chartType.value === 'line' ? '#4f46e5' : 'rgba(79, 70, 229, 0.5)', // Adjust opacity for bar
    borderColor: '#4f46e5',
    borderWidth: 2,
    fill: chartType.value === 'line' ? false : true,
    tension: chartType.value === 'line' ? 0.4 : 0, // Smooth line
    pointRadius: chartType.value === 'line' ? 4 : 0,
  })),
}));

// Chart options for better rendering
const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  scales: {
    x: {
      title: {
        display: true,
        text: 'Timestamp (WIB)',
      },
    },
    y: {
      title: {
        display: true,
        text: 'Log Value',
      },
      beginAtZero: true,
    },
  },
  plugins: {
    legend: {
      display: true,
      position: 'top',
    },
    tooltip: {
      enabled: true,
    },
  },
};

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

// Breadcrumbs
const breadcrumbs = computed(() => [
  { title: 'Analytics', href: '/analytics' },
  { title: `Components for ${areaName.value}`, href: `/analytics/components/${props.component?.AreaID ?? ''}` },
]);
</script>

<style scoped>
.chart-container {
  position: relative;
  height: 400px;
  width: 100%;
}
</style>