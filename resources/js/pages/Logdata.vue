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
  import { ref, watch, computed, onMounted } from 'vue';
  import { Button } from '@/components/ui/button';
  import { Input } from '@/components/ui/input';
  import { Label } from '@/components/ui/label';
  import { Alert, AlertDescription } from '@/components/ui/alert';
  import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
  import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
  import { Progress } from '@/components/ui/progress';

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
    userRole?: string | null;
    flash?: { success?: string; error?: string };
  }

  const breadcrumbs = [
    { title: 'Log Data', href: '/logdata' },
  ];

  const page = usePage<{ props: PageProps }>();
  const areas: Area[] = Array.isArray(page.props.areas) ? page.props.areas : [];
  const components: Component[] = Array.isArray(page.props.components) ? page.props.components : [];
  const flash = (page.props.flash || {}) as { success?: string; error?: string };

  const selectedAreaId = ref<number | null>(null);
  const logInputs = ref<Record<number, string>>({});
  const notesInputs = ref<Record<number, string>>({});
  const errors = ref<Record<number | 'global', { log?: string; notes?: string }>>({});
  const showConfirmDialog = ref(false);

  // Load cached data from localStorage on mount
  onMounted(() => {
    const cachedAreaId = localStorage.getItem('logdata_area_id');
    const cachedLogInputs = localStorage.getItem('logdata_log_inputs');
    const cachedNotesInputs = localStorage.getItem('logdata_notes_inputs');

    if (cachedAreaId && areas.some(area => area.AreaID === parseInt(cachedAreaId))) {
      selectedAreaId.value = parseInt(cachedAreaId);
    }

    if (cachedLogInputs) {
      const parsedLogInputs = JSON.parse(cachedLogInputs);
      if (selectedAreaId.value) {
        const validComponentIds = components
          .filter(comp => comp.AreaID === selectedAreaId.value)
          .map(comp => comp.ComponentID.toString());
        logInputs.value = Object.keys(parsedLogInputs)
          .filter(key => validComponentIds.includes(key))
          .reduce((acc, key) => {
            acc[key] = parsedLogInputs[key];
            return acc;
          }, {} as Record<number, string>);
      }
    }

    if (cachedNotesInputs) {
      const parsedNotesInputs = JSON.parse(cachedNotesInputs);
      if (selectedAreaId.value) {
        const validComponentIds = components
          .filter(comp => comp.AreaID === selectedAreaId.value)
          .map(comp => comp.ComponentID.toString());
        notesInputs.value = Object.keys(parsedNotesInputs)
          .filter(key => validComponentIds.includes(key))
          .reduce((acc, key) => {
            acc[key] = parsedNotesInputs[key];
            return acc;
          }, {} as Record<number, string>);
      }
    }
  });

  // Save form data to localStorage
  watch(selectedAreaId, (newAreaId) => {
    if (newAreaId) {
      localStorage.setItem('logdata_area_id', newAreaId.toString());
      const filteredComponents = components.filter(comp => comp.AreaID === newAreaId);
      logInputs.value = filteredComponents.reduce((acc, comp) => {
        acc[comp.ComponentID] = logInputs.value[comp.ComponentID] || '';
        return acc;
      }, {} as Record<number, string>);
      notesInputs.value = filteredComponents.reduce((acc, comp) => {
        acc[comp.ComponentID] = notesInputs.value[comp.ComponentID] || '';
        return acc;
      }, {} as Record<number, string>);
      errors.value = filteredComponents.reduce((acc, comp) => {
        acc[comp.ComponentID] = {};
        return acc;
      }, {} as Record<number, { log?: string; notes?: string }>);
    } else {
      localStorage.removeItem('logdata_area_id');
      logInputs.value = {};
      notesInputs.value = {};
      errors.value = {};
    }
    localStorage.setItem('logdata_log_inputs', JSON.stringify(logInputs.value));
    localStorage.setItem('logdata_notes_inputs', JSON.stringify(notesInputs.value));
  });

  watch([logInputs, notesInputs], () => {
    localStorage.setItem('logdata_log_inputs', JSON.stringify(logInputs.value));
    localStorage.setItem('logdata_notes_inputs', JSON.stringify(notesInputs.value));
  }, { deep: true });

  // Progress calculation
  const progress = computed(() => {
    if (!selectedAreaId.value) return 0;
    const totalFields = Object.keys(logInputs.value).length;
    const filledFields = Object.values(logInputs.value).filter(val => val.trim() !== '' && !isNaN(parseFloat(val))).length;
    return totalFields ? (filledFields / totalFields) * 100 : 0;
  });

  // Validate and format numeric input
  const handleNumericInput = (componentId: number, value: string) => {
    let formattedValue = value.replace(/^0+(?=\d)/, '').replace(/\.+/, '.');
    if (formattedValue === '' || /^-?\d*\.?\d*$/.test(formattedValue)) {
      logInputs.value[componentId] = formattedValue;
      errors.value[componentId].log = undefined;
    } else {
      errors.value[componentId].log = 'Enter a valid number (e.g., 42.5, -10)';
    }
  };

  // Validate notes input
  const handleNotesInput = (componentId: number, value: string) => {
    if (value.length <= 255) {
      notesInputs.value[componentId] = value;
      errors.value[componentId].notes = undefined;
    } else {
      notesInputs.value[componentId] = value.slice(0, 255);
      errors.value[componentId].notes = 'Notes cannot exceed 255 characters';
    }
  };

  // Character count for notes
  const getNotesLength = (componentId: number) => {
    return notesInputs.value[componentId]?.length || 0;
  };

  // Form validity check
  const isFormValid = computed(() => {
    if (!selectedAreaId.value) return false;
    const hasValidInput = Object.values(logInputs.value).some(val => val.trim() !== '' && !isNaN(parseFloat(val)));
    const hasErrors = Object.values(errors.value).some(err => err.log || err.notes);
    return hasValidInput && !hasErrors;
  });

  // Prepare log data for submission
  const logData = computed(() => {
    return Object.entries(logInputs.value)
      .filter(([, value]) => value.trim() !== '' && !isNaN(parseFloat(value)))
      .map(([componentId, logMessage]) => ({
        component_id: parseInt(componentId),
        log_message: logMessage.toString(),
        notes: notesInputs.value[componentId] || null,
      }));
  });

  // Handle form submission
  const submitLogData = () => {
    if (!selectedAreaId.value) {
      errors.value.global = { log: 'Please select an area' };
      return;
    }
    if (logData.value.length === 0) {
      errors.value.global = { log: 'Please enter at least one valid number' };
      return;
    }
    showConfirmDialog.value = true;
  };

  // Clear form and localStorage
  const clearForm = () => {
    selectedAreaId.value = null;
    logInputs.value = {};
    notesInputs.value = {};
    errors.value = {};
    localStorage.removeItem('logdata_area_id');
    localStorage.removeItem('logdata_log_inputs');
    localStorage.removeItem('logdata_notes_inputs');
  };

  // Confirm and submit
  const confirmSubmit = () => {
    router.post(route('logdata.store'), {
      area_id: selectedAreaId.value,
      logs: logData.value,
    }, {
      onSuccess: () => {
        clearForm();
        showConfirmDialog.value = false;
      },
      onError: (err) => {
        errors.value.global = { log: Object.values(err).join(', ') };
        showConfirmDialog.value = false;
      },
    });
  };
</script>

<template>
  <Head title="Log Data" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="min-h-screen w-full bg-gray-50 dark:bg-gray-900 flex flex-col">
      <!-- Sticky Progress Bar Header -->
      <div class="sticky top-0 bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700 z-20 py-3 sm:py-4 px-3 sm:px-6">
        <div class="max-w-full mx-auto">
          <Label class="text-sm sm:text-base font-semibold text-gray-700 dark:text-gray-300">Progress</Label>
          <Progress :value="progress" class="mt-2 bg-gray-200 dark:bg-gray-700" />
          <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-1">{{ Math.round(progress) }}% of components filled</p>
        </div>
      </div>

      <!-- Main Content -->
      <div class="flex-1 p-3 sm:p-6 overflow-auto">
        <div class="max-w-full mx-auto">
          <!-- Header -->
          <div class="mb-6 sm:mb-8">
            <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 dark:text-gray-100">Log Data Entry</h1>
            <p class="mt-1 sm:mt-2 text-sm sm:text-base text-gray-600 dark:text-gray-400">Select an area and enter log values with optional notes to record component data. Your progress is auto-saved.</p>
          </div>

          <!-- Flash Messages -->
          <Alert v-if="flash.success" class="mb-4 sm:mb-6 bg-green-100 dark:bg-green-800 border-green-300 dark:border-green-600 text-green-800 dark:text-green-100 animate-fade-in">
            <AlertDescription class="text-sm sm:text-base">{{ flash.success }}</AlertDescription>
          </Alert>
          <Alert v-if="flash.error || errors.global?.log" variant="destructive" class="mb-4 sm:mb-6 bg-red-100 dark:bg-red-800 text-red-800 dark:text-red-400 animate-shake">
            <AlertDescription class="text-sm sm:text-base">{{ flash.error || errors.global?.log }}</AlertDescription>
          </Alert>

          <!-- Area Selection -->
          <div class="mb-6 sm:mb-8">
            <Label for="area-select" class="text-sm sm:text-base font-semibold text-gray-700 dark:text-gray-300">Step 1: Select Area</Label>
            <Select v-model="selectedAreaId" class="mt-2">
              <SelectTrigger id="area-select" class="w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-blue-500 dark:focus:border-blue-400 transition-all duration-200 text-sm sm:text-base" aria-label="Select an area">
                <SelectValue placeholder="Choose an area..." />
              </SelectTrigger>
              <SelectContent class="bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 text-sm sm:text-base">
                <SelectItem v-for="area in areas" :key="area.AreaID" :value="area.AreaID">
                  {{ area.name }} ({{ area.department_name }})
                </SelectItem>
                <SelectItem v-if="areas.length === 0" value="no-areas" disabled>
                  No areas available
                </SelectItem>
              </SelectContent>
            </Select>
          </div>

          <!-- Component Inputs -->
          <div v-if="selectedAreaId" class="space-y-4 sm:space-y-6">
            <div class="bg-white dark:bg-gray-800 p-4 sm:p-6 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
              <h2 class="text-lg sm:text-xl font-semibold text-gray-800 dark:text-gray-200 mb-3 sm:mb-4">Step 2: Enter Component Data</h2>
              <div class="space-y-4">
                <div v-for="component in components.filter(comp => comp.AreaID === selectedAreaId)" :key="component.ComponentID" class="p-3 sm:p-4 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 transition-all duration-200 hover:shadow-md">
                  <div class="grid grid-cols-1 md:grid-cols-12 gap-3 sm:gap-4 items-start">
                    <!-- Component Name -->
                    <div class="md:col-span-3 flex items-center">
                      <Label :for="`log-${component.ComponentID}`" class="text-sm sm:text-base font-medium text-gray-700 dark:text-gray-300">{{ component.name }}</Label>
                      <TooltipProvider v-if="component.desc">
                        <Tooltip>
                          <TooltipTrigger>
                            <span class="ml-1 sm:ml-2 text-gray-400 dark:text-gray-500 cursor-help" aria-label="Component description">ⓘ</span>
                          </TooltipTrigger>
                          <TooltipContent class="bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 border-gray-300 dark:border-gray-600 text-sm sm:text-base">{{ component.desc }}</TooltipContent>
                        </Tooltip>
                      </TooltipProvider>
                    </div>
                    <!-- Log Value Input -->
                    <div class="md:col-span-4">
                      <Input
                        :id="`log-${component.ComponentID}`"
                        v-model="logInputs[component.ComponentID]"
                        type="text"
                        inputmode="numeric"
                        pattern="[0-9]*\.?[0-9]*"
                        placeholder="e.g., 42.5 or -10"
                        class="w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-blue-500 dark:focus:border-blue-400 transition-all duration-200 text-sm sm:text-base"
                        :aria-invalid="!!errors[component.ComponentID]?.log"
                        :aria-describedby="`log-error-${component.ComponentID}`"
                        @input="handleNumericInput(component.ComponentID, $event.target.value)"
                      />
                      <p v-if="errors[component.ComponentID]?.log" :id="`log-error-${component.ComponentID}`" class="text-xs sm:text-sm text-red-600 dark:text-red-400 mt-1 animate-fade-in">{{ errors[component.ComponentID].log }}</p>
                    </div>
                    <!-- Notes Input -->
                    <div class="md:col-span-5">
                      <div class="relative">
                        <Input
                          :id="`notes-${component.ComponentID}`"
                          v-model="notesInputs[component.ComponentID]"
                          type="text"
                          placeholder="Optional notes (max 255 chars)"
                          class="w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-blue-500 dark:focus:border-blue-400 transition-all duration-200 text-sm sm:text-base"
                          :aria-invalid="!!errors[component.ComponentID]?.notes"
                          :aria-describedby="`notes-error-${component.ComponentID}`"
                          @input="handleNotesInput(component.ComponentID, $event.target.value)"
                        />
                        <span class="absolute right-2 top-2 text-xs text-gray-500 dark:text-gray-400">{{ getNotesLength(component.ComponentID) }}/255</span>
                      </div>
                      <p v-if="errors[component.ComponentID]?.notes" :id="`notes-error-${component.ComponentID}`" class="text-xs sm:text-sm text-red-600 dark:text-red-400 mt-1 animate-fade-in">{{ errors[component.ComponentID].notes }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div v-else class="text-gray-500 dark:text-gray-400 text-center py-4 sm:py-6 text-sm sm:text-base">
            Please select an area to view and log components.
          </div>
        </div>
      </div>

      <!-- Sticky Submit Bar -->
      <div v-if="selectedAreaId" class="sticky bottom-0 bg-gray-50 dark:bg-gray-900 py-3 sm:py-4 border-t border-gray-200 dark:border-gray-700 z-10">
        <div class="max-w-full mx-auto px-3 sm:px-6 flex flex-col sm:flex-row gap-2 sm:gap-4">
          <Dialog v-model:open="showConfirmDialog">
            <DialogTrigger as-child>
              <Button
                :disabled="!isFormValid"
                class="w-full sm:w-auto bg-blue-600 dark:bg-blue-500 hover:bg-blue-700 dark:hover:bg-blue-400 text-white font-semibold py-2 px-4 sm:px-6 rounded-lg transition-all duration-200 text-sm sm:text-base"
                aria-label="Submit log data"
                @click="submitLogData"
              >
                Submit Log Data
              </Button>
            </DialogTrigger>
            <DialogContent class="bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 border-gray-300 dark:border-gray-600">
              <DialogHeader>
                <DialogTitle class="text-lg sm:text-xl">Confirm Log Submission</DialogTitle>
                <DialogDescription class="text-sm sm:text-base">
                  Review the data for {{ logData.length }} component(s) before submitting:
                  <ul class="mt-2 sm:mt-3 list-disc pl-5 space-y-2">
                    <li v-for="log in logData" :key="log.component_id" class="text-gray-700 dark:text-gray-300 text-sm sm:text-base">
                      <strong>{{ components.find(c => c.ComponentID === log.component_id)?.name }}</strong>:
                      Value: {{ log.log_message }},
                      Notes: {{ log.notes || 'None' }}
                    </li>
                  </ul>
                </DialogDescription>
              </DialogHeader>
              <DialogFooter>
                <Button variant="outline" @click="showConfirmDialog = false" aria-label="Cancel submission" class="border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm sm:text-base">Cancel</Button>
                <Button @click="confirmSubmit" class="bg-blue-600 dark:bg-blue-500 hover:bg-blue-700 dark:hover:bg-blue-400 text-white text-sm sm:text-base" aria-label="Confirm submission">Confirm</Button>
              </DialogFooter>
            </DialogContent>
          </Dialog>
          <Button
            variant="outline"
            class="w-full sm:w-auto border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-semibold py-2 px-4 sm:px-6 rounded-lg transition-all duration-200 text-sm sm:text-base"
            aria-label="Reset form"
            @click="clearForm"
          >
            Reset Form
          </Button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.min-h-screen {
  min-height: 100vh;
}
.animate-fade-in {
  animation: fadeIn 0.3s ease-in;
}
.animate-shake {
  animation: shake 0.5s cubic-bezier(0.36, 0.07, 0.19, 0.97) both;
}
@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}
@keyframes shake {
  10%, 90% { transform: translate3d(-1px, 0, 0); }
  20%, 80% { transform: translate3d(2px, 0, 0); }
  30%, 50%, 70% { transform: translate3d(-4px, 0, 0); }
  40%, 60% { transform: translate3d(4px, 0, 0); }
}
</style>