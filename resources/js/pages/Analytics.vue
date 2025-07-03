<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-4">
      <h1 class="text-2xl font-bold mb-4">Graph Data</h1>

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

      <!-- Area Table -->
      <div class="relative flex-1 rounded-xl border border-gray-200 dark:border-gray-800 p-3">
        <p class="text-lg font-semibold mb-2">List of Areas</p>
        <Table>
          <TableCaption>A list of areas for your department.</TableCaption>
          <TableHeader>
            <TableRow>
              <TableHead class="w-[100px]">ID</TableHead>
              <TableHead>Name</TableHead>
              <TableHead>Department</TableHead>
              <TableHead>Created At (WIB)</TableHead>
              <TableHead>Action</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-if="filteredAreas.length === 0">
              <TableCell colspan="5" class="text-center">
                {{ userDepartmentId ? 'No areas available for your department.' : 'No department assigned.' }}
              </TableCell>
            </TableRow>
            <TableRow v-else v-for="area in filteredAreas" :key="area.AreaID">
              <TableCell class="font-medium">{{ area.AreaID }}</TableCell>
              <TableCell>{{ area.name }}</TableCell>
              <TableCell>{{ area.department_name }}</TableCell>
              <TableCell>{{ formatTimestamp(area.created_at) }}</TableCell>
              <TableCell>
                <Button @click="navigateToComponents(area.AreaID)">
                  <MoveDiagonal class="w-4 h-4" />
                  See Components Insights
                </Button>
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { MoveDiagonal } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { router, usePage } from '@inertiajs/vue3';
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
import { computed } from 'vue';
import { formatInTimeZone } from 'date-fns-tz';

// Define interfaces
interface Area {
  AreaID: number;
  name: string;
  DepartmentID: number;
  department_name: string;
  desc: string | null;
  created_at: string | null; // Timestamp field (e.g., '2025-07-03 02:27:35')
}

interface Flash {
  success?: string | null;
  error?: string | null;
}

interface PageProps {
  flash?: Flash;
  errors?: Record<string, string>;
  areas?: Area[] | null;
  user?: { DepartmentID: number | null; role: string | null };
}

// Props with nullable arrays
const props = defineProps<{
  areas: Area[] | null;
}>();

// Get user and department ID from page props
const page = usePage<{ props: PageProps }>();
const user = computed(() => page.props.user ?? { DepartmentID: null, role: null });
const userDepartmentId = computed(() => user.value?.DepartmentID ?? null);
const userRole = computed(() => user.value?.role ?? null);

// Safe areas array
const safeAreas = computed(() => Array.isArray(props.areas) ? props.areas : []);

// Filter areas based on user's DepartmentID for non-SuperUsers
const filteredAreas = computed(() => {
  if (userRole.value === 'SuperUser') {
    return safeAreas.value;
  }
  return safeAreas.value.filter(area => area.DepartmentID === userDepartmentId.value);
});

// Flash messages
const flash = computed(() => {
  const flashObj: Flash = page.props.flash ?? { success: null, error: null };
  return {
    success: flashObj.success ?? null,
    error: page.props.errors ? Object.values(page.props.errors).join(', ') ?? null : flashObj.error ?? null,
  };
});

// Breadcrumbs
const breadcrumbs = [
  { title: 'Analytics', href: '/analytics' },
];

// Format timestamp to WIB using date-fns-tz
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

// Navigate to components insights page
const navigateToComponents = (areaId: number) => {
  console.log('Navigating to components for AreaID:', areaId);
  router.visit(`/analytics/components/${areaId}`);
};
</script>
