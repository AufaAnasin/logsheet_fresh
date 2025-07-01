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
        <div class="relative flex-1 rounded-xl border border-gray-200 dark:border-gray-800 p-3 mb-4 h-96">
          <VChart :option="chartOptions" autoresize />
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
import VChart from 'vue-echarts';
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
  Notes: string | null;
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
  series: { name: string; type: string; data: (number | null)[]; lineStyle: { color: string }; smooth: boolean; symbol: string; symbolSize: number }[];
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
  chart_data: ChartData;
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

// ECharts options
const chartOptions = computed(() => ({
  xAxis: {
    ...props.chart_data.xAxis,
    name: 'Date',
    axisLabel: {
      rotate: 45,
    },
  },
  yAxis: {
    type: 'value',
    name: 'Log Value',
    min: 0,
    axisLabel: {
      formatter: '{value}',
    },
  },
  series: props.chart_data.series,
  tooltip: {
    trigger: 'axis',
    formatter: (params: any) => {
      const data = params[0];
      return `${data.seriesName}<br/>${data.name}: ${data.value ?? 'N/A'}`;
    },
  },
  legend: {
    top: '5%',
  },
  title: {
    text: `Log Data for ${props.component?.name ?? 'Component'}`,
    left: 'center',
  },
  grid: {
    left: '10%',
    right: '10%',
    bottom: '20%',
  },
  dataZoom: [
    {
      type: 'slider',
      xAxisIndex: 0,
      start: 0,
      end: 100,
    },
    {
      type: 'inside',
      xAxisIndex: 0,
    },
  ],
}));

// Debug chart data
watch(() => props.chart_data, (newChartData) => {
  console.log('Chart Data:', newChartData);
}, { immediate: true });
</script>