<template>
    <AppLayout :breadcrumbs="breadcrumbs">
      <div class="p-4">
        <h2 class="text-2xl font-bold mb-6 text-gray-100 dark:text-gray-100">Area Overview</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
          <div v-for="area in areas" :key="area.AreaID" class="border border-gray-300 dark:border-gray-700 p-4 rounded-lg cursor-pointer hover:bg-gray-200 dark:hover:bg-gray-800 transition-colors" @click="selectArea(area.AreaID)" :class="{ 'bg-blue-200 dark:bg-blue-900': area.AreaID === selectedAreaId }">
            <div class="text-lg text-gray-800 dark:text-gray-200">{{ area.name }}</div>
            <div class="text-sm text-gray-600 dark:text-gray-400">({{ area.department_name }})</div>
          </div>
        </div>

        <div v-if="selectedAreaId" class="mt-6">
          <h3 class="text-xl font-semibold mb-4 text-gray-100 dark:text-gray-100">Components in {{ selectedAreaName }}</h3>
          <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
              <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-300">
                <tr>
                  <th scope="col" class="px-6 py-3">Component Name</th>
                  <th scope="col" class="px-6 py-3">Latest Value</th>
                  <th scope="col" class="px-6 py-3">Last Updated</th>
                  <th scope="col" class="px-6 py-3">Trend</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="component in components" :key="component.ComponentID" class="bg-white dark:bg-gray-800 border-b dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors" @click="selectComponent(component.ComponentID)">
                  <td class="px-6 py-4 font-medium text-gray-900 dark:text-white whitespace-nowrap">{{ component.name }}</td>
                  <td class="px-6 py-4">{{ latestValue(component.ComponentID) || 'N/A' }}</td>
                  <td class="px-6 py-4">{{ latestTimestamp(component.ComponentID) || 'N/A' }}</td>
                  <td class="px-6 py-4">
                    <span v-if="trend(component.ComponentID) > 0" class="text-green-500 dark:text-green-400">↑</span>
                    <span v-else-if="trend(component.ComponentID) < 0" class="text-red-500 dark:text-red-400">↓</span>
                    <span v-else class="text-gray-500 dark:text-gray-400">→</span>
                  </td>
                </tr>
              </tbody>
            </table>
            <pre v-if="Object.keys(componentLogs).length === 0" class="mt-4 text-red-500 dark:text-red-400">No log data available for selected area.</pre>
          </div>

          <!-- Modal for Component Details -->
          <div v-if="selectedComponentId" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg w-full max-w-md">
              <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Logs for {{ components.find(c => c.ComponentID === selectedComponentId)?.name }}</h4>
              <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-300">
                  <tr>
                    <th class="px-4 py-2">Timestamp</th>
                    <th class="px-4 py-2">Value</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(log, index) in componentLogs[selectedComponentId] || []" :key="index" class="border-b dark:border-gray-700">
                    <td class="px-4 py-2">{{ log.timestamp }}</td>
                    <td class="px-4 py-2">{{ log.value }}</td>
                  </tr>
                </tbody>
              </table>
              <button @click="selectedComponentId = null" class="mt-4 px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-800 dark:text-gray-200 rounded hover:bg-gray-400 dark:hover:bg-gray-500">Close</button>
            </div>
          </div>
        </div>
      </div>
    </AppLayout>
  </template>

  <script setup lang="ts">
  import AppLayout from '@/layouts/AppLayout.vue';
  import { usePage, router } from '@inertiajs/vue3';
  import { ref, onMounted } from 'vue';

  interface Area {
    AreaID: number;
    name: string;
    DepartmentID: number;
    department_name: string;
    desc: string | null;
  }

  interface Component {
    ComponentID: number;
    name: string;
    AreaID: number;
    area_name: string | null;
    desc: string | null;
  }

  interface LogData {
    timestamp: string;
    value: string;
  }

  interface PageProps {
    areas: Area[];
    components: Component[];
    componentLogs: Record<number, LogData[]>;
    selectedAreaId: number | null;
    userRole: string | null;
    [key: string]: unknown; // Add index signature to satisfy Inertia's PageProps constraint
  }

  // Explicitly type the usePage return value
  const { props } = usePage<PageProps>();
  const areas = props.areas;
  const components = props.components;
  const componentLogs = props.componentLogs;
  const selectedAreaId = ref(props.selectedAreaId);
  const selectedAreaName = ref(areas.length > 0 && selectedAreaId.value ? areas.find(a => a.AreaID === selectedAreaId.value)?.name || '' : '');
  const selectedComponentId = ref<number | null>(null);

  const breadcrumbs = [
    { title: 'Graph Data', href: '/graphdata' },
  ];

  const selectArea = (areaId: number) => {
    selectedAreaId.value = areaId;
    selectedAreaName.value = areas.find(a => a.AreaID === areaId)?.name || '';
    router.get('/graphdata', { area_id: areaId }, { preserveState: true, replace: true });
  };

  const selectComponent = (componentId: number) => {
    selectedComponentId.value = componentId;
    // No need for a full refresh; use existing data
  };

  const latestValue = (componentId: number) => {
    return componentLogs[componentId]?.[0]?.value || null;
  };

  const latestTimestamp = (componentId: number) => {
    return componentLogs[componentId]?.[0]?.timestamp || null;
  };

  const trend = (componentId: number) => {
    const logs = componentLogs[componentId] || [];
    if (logs.length < 2) return 0;
    const values = logs.map((log: LogData) => parseFloat(log.value) || 0);
    return values[0] - values[1]; // Simple trend: latest - previous
  };

  // Force initial data fetch if no selectedAreaId
  onMounted(() => {
    if (selectedAreaId.value === null && areas.length > 0) {
      selectArea(areas[0].AreaID);
    }
  });
  </script>