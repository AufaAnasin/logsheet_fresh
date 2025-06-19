<script setup lang="ts">
  import AppLayout from '@/layouts/AppLayout.vue';
  import { Head, usePage, router } from '@inertiajs/vue3';
  import {
      Select,
      SelectContent,
      SelectItem,
      SelectTrigger,
      SelectValue,
  } from '@/components/ui/select';
  import { ref, watch } from 'vue';
  import { Button } from '@/components/ui/button';
  import { Input } from '@/components/ui/input';
  import { Label } from '@/components/ui/label';

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

  interface PageProps {
      areas?: Area[] | null;
      components?: Component[] | null;
  }

  const breadcrumbs = [
      { title: 'Log Data', href: '/logdata' },
  ];

  // Access props and flash messages
  const page = usePage<{ props: PageProps }>();
  const areas: Area[] = Array.isArray(page.props.areas) ? page.props.areas : [];
  const components: Component[] = Array.isArray(page.props.components) ? page.props.components : [];
  const flash = (page.props.flash || {}) as { success?: string; error?: string };

  // State for selected area and log inputs
  const selectedAreaId = ref<number | null>(null);
  const logInputs = ref<Record<number, string>>({});

  // Watch for area selection change and filter components
  watch(selectedAreaId, (newAreaId) => {
      if (newAreaId) {
          const filteredComponents = components.filter(comp => comp.AreaID === newAreaId);
          logInputs.value = filteredComponents.reduce((acc, comp) => {
              acc[comp.ComponentID] = '';
              return acc;
          }, {} as Record<number, string>);
      } else {
          logInputs.value = {};
      }
  });

  // Form submission
  const submitLogData = () => {
      if (!selectedAreaId.value) {
          alert('Please select an area.');
          return;
      }

      const logData = Object.entries(logInputs.value).map(([componentId, logMessage]) => ({
          component_id: parseInt(componentId),
          log_message: logMessage,
      }));

      router.post(route('logdata.store'), { // Use named route
          area_id: selectedAreaId.value,
          logs: logData,
      }, {
          onSuccess: () => {
              // Redirect handled by server, clear inputs here
              logInputs.value = {};
          },
          onError: (errors) => {
              alert('Failed to submit log data: ' + Object.values(errors).join(', '));
          },
      });
  };
  </script>

  <template>
      <Head title="Log Data" />

      <AppLayout :breadcrumbs="breadcrumbs">
          <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
              <!-- Flash Messages -->
              <div v-if="flash.success" class="rounded-xl border border-green-200 bg-green-50 p-3 text-green-700">
                  {{ flash.success }}
              </div>
              <div v-if="flash.error" class="rounded-xl border border-red-200 bg-red-50 p-3 text-red-700">
                  {{ flash.error }}
              </div>

              <div class="grid gap-2">
                  <label for="area-select" class="text-sm font-medium">Select Area</label>
                  <Select v-model="selectedAreaId">
                      <SelectTrigger id="area-select" class="w-full">
                          <SelectValue placeholder="Select an area..." />
                      </SelectTrigger>
                      <SelectContent>
                          <SelectItem v-for="area in areas" :key="area.AreaID" :value="area.AreaID">
                              {{ area.name }} ({{ area.department_name }})
                          </SelectItem>
                          <SelectItem v-if="areas.length === 0" value="no-areas" disabled>
                              No areas available
                          </SelectItem>
                      </SelectContent>
                  </Select>
              </div>

              <!-- Dynamic component inputs -->
              <div v-if="selectedAreaId" class="space-y-4">
                  <div v-for="component in components.filter(comp => comp.AreaID === selectedAreaId)" :key="component.ComponentID" class="grid gap-2">
                      <Label :for="`log-${component.ComponentID}`">{{ component.name }}</Label>
                      <Input
                          :id="`log-${component.ComponentID}`"
                          v-model="logInputs[component.ComponentID]"
                          placeholder="Enter log message..."
                          class="w-full"
                      />
                  </div>
                  <Button @click="submitLogData" :disabled="Object.values(logInputs).every(val => !val.trim())">
                      Submit Log Data
                  </Button>
              </div>
              <div v-else class="text-muted-foreground">
                  Please select an area to see components.
              </div>
          </div>
      </AppLayout>
  </template>