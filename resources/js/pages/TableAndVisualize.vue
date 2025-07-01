<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-4">
      <div class="p-2">
        <div class="mb-4">
          <apexchart type="line" :options="chartOptions" :series="series" :height="300"></apexchart>
        </div>
      </div>

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
import { ref, computed, reactive, watch } from 'vue';
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

// Chart options using reactive for nested properties
const chartOptions = reactive({
  chart: {
    id: 'log-data-chart',
    toolbar: {
      show: false,
    },
    animations: {
      enabled: true,
      easing: 'easeinout',
      speed: 800,
    },
  },
  xaxis: {
    type: 'datetime',
    categories: computed(() => safeLogs.value
      .map(log => log.LogTimestamp !== 'N/A' ? new Date(log.LogTimestamp).getTime() : null)
      .filter(timestamp => timestamp !== null) as number[]),
    title: {
      text: 'Timestamp',
    },
    labels: {
      rotate: -45,
      hideOverlappingLabels: true,
      formatter: (value: number) => {
        const date = new Date(value);
        const allSameDay = safeLogs.value.every(log => {
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
    title: {
      text: 'Log Value',
    },
    min: 0,
  },
  title: {
    text: 'Log Data Trend',
    align: 'center',
    style: {
      fontSize: '16px',
    },
  },
  tooltip: {
    x: {
      formatter: (timestamp: number) => {
        const date = new Date(timestamp);
        const allSameDay = safeLogs.value.every(log => {
          const logDate = new Date(log.LogTimestamp);
          return logDate.toDateString() === date.toDateString();
        });
        return allSameDay
          ? date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })
          : date.toLocaleString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
      },
    },
    y: {
      formatter: (value: number) => `${value} units`,
    },
  },
  responsive: [
    {
      breakpoint: 640,
      options: {
        chart: {
          width: '100%',
        },
        xaxis: {
          labels: {
            rotate: 0,
          },
        },
      },
    },
  ],
});

// Chart series
const series = ref([
  {
    name: computed(() => props.component?.name ?? 'Component'),
    data: computed(() => safeLogs.value
      .map(log => ({
        x: log.LogTimestamp !== 'N/A' ? new Date(log.LogTimestamp).getTime() : null,
        y: log.LogValue !== null ? log.LogValue : null,
      }))
      .filter(point => point.x !== null && point.y !== null)),
  },
]);

// Watch for logs updates
watch(() => props.logs, (newLogs) => {
  console.log('Logs Data:', newLogs);
}, { immediate: true });
</script>

<style scoped>
/* Ensure chart container adapts to content */
.apexcharts-canvas {
  margin: 0 auto;
}

/* Responsive adjustments */
@media (max-width: 640px) {
  .apexcharts-canvas {
    width: 100% !important;
  }
}
</style>