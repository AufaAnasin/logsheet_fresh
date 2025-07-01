<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-4">
      <h1 class="text-2xl font-bold mb-4">{{ props.title }}</h1>
      <p class="text-gray-600 mb-4">{{ props.description }}</p>

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

      <!-- Component Details and Chart -->
      <div v-if="props.component">
        <h2 class="text-xl font-semibold mb-4">Log Data for {{ props.component.name }}</h2>
        <div v-if="(props.chart_data?.xAxis?.data ?? []).length > 0" class="relative flex-1 rounded-xl border border-gray-200 dark:border-gray-800 p-3 mb-4 h-96">
          <Bar :chart-data="chartData" :options="chartOptions" />
        </div>
        <div v-else class="rounded-xl border border-yellow-200 bg-yellow-50 p-3 text-yellow-700 mb-4">
          No chart data available for this component.
        </div>
        <div class="relative flex-1 rounded-xl border border-gray-200 dark:border-gray-800 p-3">
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
              <TableRow v-if="safeLogs.length === 0">
                <TableCell colspan="6" class="text-center">No log entries available for this component.</TableCell>
              </TableRow>
              <TableRow v-else v-for="log in safeLogs" :key="log.LogID">
                <TableCell class="font-medium">{{ log.LogID }}</TableCell>
                <TableCell><b>{{ log.LogValue ?? 'N/A' }}</b></TableCell>
                <TableCell>{{ log.LogTimestamp }}</TableCell>
                <TableCell>{{ log.OperatorName }}</TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>
      </div>

      <!-- Fallback for No Component -->
      <div v-else class="rounded-xl border border-red-200 bg-red-50 p-3 text-red-700 text-center">
        {{ flash.error || 'Component not found or unauthorized.' }}
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import {
  Table,
  TableBody,
  TableCaption,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { computed, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';

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
  chart_data?: ChartData;
  user?: { DepartmentID: number | null; role: string | null };
}

// Props with nullable fields
const props = defineProps<{
  title: string;
  description: string;
  component: Component | null;
  logs: Log[] | null;
  chart_data: ChartData | null; // Allow null to handle edge cases
}>();

// Get user and department ID from page props
const page = usePage<{ props: PageProps }>();
const user = computed(() => page.props.user);
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

// Chart data for Bar chart with all individual values
const chartData = computed(() => {
  if (!props.chart_data || !props.chart_data.xAxis.data.length) {
    return {
      labels: ['No Data'],
      datasets: [{ label: props.component?.name ?? 'Component', backgroundColor: '#3b82f6', data: [0] }],
    };
  }
  return {
    labels: props.chart_data.xAxis.data,
    datasets: props.chart_data.series.map(series => ({
      label: series.name,
      backgroundColor: '#3b82f6',
      data: series.data,
    })),
  };
});

// Chart options
const chartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  scales: {
    x: {
      title: { display: true, text: 'Timestamp' }, // Updated to reflect individual timestamps
    },
    y: {
      title: { display: true, text: 'Log Value' },
      beginAtZero: true,
    },
  },
  plugins: {
    legend: { position: 'top' },
    title: {
      display: true,
      text: `Log Data for ${props.component?.name ?? 'Component'}`,
      align: 'center',
    },
    tooltip: {
      callbacks: {
        label: (context) => `${context.label}: ${context.raw ?? 'N/A'}`,
      },
    },
  },
}));

// Debug chart data
watch(() => props.chart_data, (newChartData) => {
  console.log('Chart Data:', newChartData);
}, { immediate: true });
</script>