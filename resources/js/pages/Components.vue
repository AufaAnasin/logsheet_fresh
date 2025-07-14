<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-4">
      <h1 class="text-2xl font-bold mb-4">Components for {{ areaName }}</h1>

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

      <!-- PDF Download Button -->
      <div class="mb-4" v-if="props.area">
        <Button as="a" :href="route('generateAreaReport', { areaId: props.area.AreaID })" class="bg-blue-500 hover:bg-blue-700 text-white">
          Print All Logs
        </Button>
      </div>

      <!-- Components Table -->
      <div class="relative flex-1 rounded-xl border border-gray-200 dark:border-gray-800 p-3">
        <p class="text-lg font-semibold mb-2">List of Components for {{ areaName }}</p>
        <Table>
          <TableCaption>A list of components for the selected area.</TableCaption>
          <TableHeader>
            <TableRow>
              <TableHead class="w-[100px]">ID</TableHead>
              <TableHead>Name</TableHead>
              <TableHead>Description</TableHead>
              <TableHead>Action</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-if="filteredComponents.length === 0">
              <TableCell colspan="4" class="text-center">
                {{ props.area ? 'No components available for this area.' : 'Area not found or unauthorized.' }}
              </TableCell>
            </TableRow>
            <TableRow v-else v-for="component in filteredComponents" :key="component.ComponentID">
              <TableCell class="font-medium">{{ component.ComponentID }}</TableCell>
              <TableCell>{{ component.name }}</TableCell>
              <TableCell>{{ component.desc || 'N/A' }}</TableCell>
              <TableCell>
                <Button @click="navigateToVisualize(component.ComponentID)">
                  <MoveDiagonal class="w-4 h-4" />
                  View Logs
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

// Define interfaces
interface Component {
  ComponentID: number;
  name: string;
  AreaID: number;
  area_name: string | null;
  desc: string | null;
}

interface Area {
  AreaID: number;
  name: string;
  DepartmentID: number;
}

interface Flash {
  success?: string | null;
  error?: string | null;
}

interface PageProps {
  flash?: Flash;
  errors?: Record<string, string>;
  components?: Component[] | null;
  area?: Area | null;
  user?: { DepartmentID: number | null; role: string | null };
}

// Props with nullable arrays
const props = defineProps<{
  components: Component[] | null;
  area: Area | null;
}>();

// Debug props
console.log('Components props:', JSON.stringify({ components: props.components, area: props.area }, null, 2));

// Get user and department ID from page props
const page = usePage<{ props: PageProps }>();
const user = computed(() => page.props.user ?? { DepartmentID: null, role: null });
const userDepartmentId = computed(() => user.value?.DepartmentID ?? null);
const userRole = computed(() => user.value?.role ?? null);

// Safe components array
const filteredComponents = computed(() => Array.isArray(props.components) ? props.components : []);

// Area name for display
const areaName = computed(() => props.area?.name ?? 'Unknown Area');

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
  { title: `Components for ${areaName.value}`, href: `/analytics/components/${props.area?.AreaID ?? ''}` },
]);

// Navigate to visualize and table page
const navigateToVisualize = (componentId: number) => {
  console.log('Navigating to visualizeandtable for ComponentID:', componentId);
  router.visit(route('visualizeandtable', { componentId }));
};
</script>