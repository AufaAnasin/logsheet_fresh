<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Check, ChevronsUpDown, Ellipsis, SquarePen, Search } from 'lucide-vue-next';
import {
    Table,
    TableBody,
    TableCaption,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Combobox,
    ComboboxAnchor,
    ComboboxEmpty,
    ComboboxGroup,
    ComboboxInput,
    ComboboxItem,
    ComboboxItemIndicator,
    ComboboxList,
    ComboboxTrigger,
} from '@/components/ui/combobox';
import { cn } from '@/lib/utils';
import { ref, computed, watch } from 'vue';

interface Department {
    DepartmentID: number;
    name: string;
    desc: string | null;
}

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

interface LogConfiguration {
    id: number;
    DepartmentID: number;
    AreaID: number;
    frequency_type: string;
    schedule: string;
}

const props = defineProps<{
    departments: Department[];
    areas: Area[];
    logConfigurations: LogConfiguration[];
}>();

// Edit log configuration dialog state
const isEditLogConfigDialogOpen = ref(false);
const selectedLogConfig = ref<LogConfiguration | null>(null);

// Form for editing log configuration
const editLogConfigForm = useForm({
    frequency_type: 'daily',
    schedule: '["09:00"]' as string | undefined,
});

// Time options for schedule
const timeOptions = Array.from({ length: 24 }, (_, i) => ({
    value: `${String(i).padStart(2, '0')}:00`,
    label: `${String(i).padStart(2, '0')}:00`,
}));

// State for selected times
const selectedTimes = ref<{ value: string; label: string }[]>([]);

// Frequency options
const frequencyOptions = [
    { value: 'hourly', label: 'Hourly (24x/day)' },
    { value: 'twice_daily', label: 'Twice Daily (2x/day)' },
    { value: 'daily', label: 'Daily (1x/day)' },
];

// Selected frequency
const selectedFrequency = ref(frequencyOptions[2]); // Default to 'daily'

// Computed property to determine schedule constraints
const scheduleConstraints = computed(() => {
    switch (selectedFrequency.value.value) {
        case 'hourly':
            return { maxSelections: 0, disabled: true, message: 'Logs every hour (24x/day), no specific times needed.' };
        case 'twice_daily':
            return { maxSelections: 2, disabled: false, message: 'Select exactly 2 times per day.' };
        case 'daily':
            return { maxSelections: 1, disabled: false, message: 'Select 1 time per day.' };
        default:
            return { maxSelections: 1, disabled: false, message: '' };
    }
});

// Validation error for schedule
const scheduleError = ref<string | null>(null);

// Handle Combobox updates for multiple selections
const handleTimeSelection = (newTimes: { value: string; label: string }[]) => {
    console.log('Handling time selection:', newTimes);
    const maxSelections = scheduleConstraints.value.maxSelections;
    if (selectedFrequency.value.value !== 'hourly' && newTimes.length > maxSelections) {
        scheduleError.value = `Maximum ${maxSelections} time(s) allowed for ${selectedFrequency.value.label}.`;
        return;
    }
    selectedTimes.value = newTimes;
    scheduleError.value = null;
};

// Sync selectedTimes with form schedule
const updateSchedule = () => {
    try {
        editLogConfigForm.schedule = selectedFrequency.value.value === 'hourly'
            ? '[]'
            : JSON.stringify(selectedTimes.value.map(t => t.value));
        editLogConfigForm.errors.schedule = undefined;
        console.log('Updated schedule:', editLogConfigForm.schedule);
    } catch (e) {
        console.error('Error stringifying schedule:', e);
        editLogConfigForm.errors.schedule = 'Invalid schedule format';
        scheduleError.value = 'Invalid schedule format';
    }
};

// Watch selectedTimes to update schedule and validate
watch(selectedTimes, (newTimes) => {
    console.log('Selected times changed:', newTimes);
    const maxSelections = scheduleConstraints.value.maxSelections;
    if (selectedFrequency.value.value !== 'hourly' && newTimes.length > maxSelections) {
        selectedTimes.value = newTimes.slice(0, maxSelections);
        scheduleError.value = `Maximum ${maxSelections} time(s) allowed for ${selectedFrequency.value.label}.`;
    }
    updateSchedule();
}, { deep: true });

// Watch selectedFrequency to update form and reset times
watch(selectedFrequency, (newFrequency) => {
    console.log('Selected frequency changed:', newFrequency);
    editLogConfigForm.frequency_type = newFrequency.value;
    if (newFrequency.value === 'hourly') {
        selectedTimes.value = [];
    } else if (newFrequency.value === 'twice_daily' && selectedTimes.value.length > 2) {
        selectedTimes.value = selectedTimes.value.slice(0, 2);
    } else if (newFrequency.value === 'daily' && selectedTimes.value.length > 1) {
        selectedTimes.value = selectedTimes.value.slice(0, 1);
    }
    updateSchedule();
}, { deep: true });

// Open edit log configuration dialog
const openEditLogConfigDialog = (component: Component) => {
    console.log('Opening edit log config dialog for component:', component);
    const logConfig = props.logConfigurations.find(config => config.AreaID === component.AreaID);
    const area = props.areas.find(a => a.AreaID === component.AreaID);
    selectedLogConfig.value = logConfig ? { ...logConfig } : {
        id: 0,
        DepartmentID: area?.DepartmentID || 0,
        AreaID: component.AreaID,
        frequency_type: 'daily',
        schedule: '["09:00"]',
    };

    console.log('Selected Log Config:', selectedLogConfig.value);

    editLogConfigForm.reset();
    editLogConfigForm.clearErrors();
    if (selectedLogConfig.value) {
        editLogConfigForm.frequency_type = selectedLogConfig.value.frequency_type;
        selectedFrequency.value = frequencyOptions.find(opt => opt.value === selectedLogConfig.value.frequency_type) || frequencyOptions[2];
    }

    try {
        const scheduleArray = JSON.parse(selectedLogConfig.value.schedule) as string[];
        selectedTimes.value = scheduleArray
            .filter(time => timeOptions.some(option => option.value === time))
            .map(time => ({ value: time, label: time }));
        console.log('Parsed schedule:', selectedTimes.value);
    } catch (e) {
        console.error('Error parsing schedule JSON:', e);
        selectedTimes.value = [{ value: '09:00', label: '09:00' }];
    }

    updateSchedule();
    isEditLogConfigDialogOpen.value = true;
    console.log('Dialog open state:', isEditLogConfigDialogOpen.value);
};

// Handle edit log configuration form submission
const updateLogConfig = () => {
    if (!selectedLogConfig.value) return;

    // Validate schedule based on frequency
    const maxSelections = scheduleConstraints.value.maxSelections;
    if (selectedFrequency.value.value !== 'hourly' && selectedTimes.value.length !== maxSelections) {
        scheduleError.value = `Please select exactly ${maxSelections} time(s) for ${selectedFrequency.value.label}.`;
        console.log('Validation failed:', scheduleError.value);
        return;
    }

    const url = selectedLogConfig.value.id
        ? `/log-configurations/${selectedLogConfig.value.id}`
        : `/log-configurations`;
    const method = selectedLogConfig.value.id ? 'put' : 'post';

    console.log('Submitting log config form:', {
        ...editLogConfigForm.data(),
        DepartmentID: selectedLogConfig.value.DepartmentID,
        AreaID: selectedLogConfig.value.AreaID,
    });

    editLogConfigForm[method](url, {
        preserveState: true,
        preserveScroll: true,
        data: {
            ...editLogConfigForm.data(),
            DepartmentID: selectedLogConfig.value.DepartmentID,
            AreaID: selectedLogConfig.value.AreaID,
        },
        onSuccess: () => {
            console.log('Log config saved successfully');
            isEditLogConfigDialogOpen.value = false;
            selectedLogConfig.value = null;
            selectedTimes.value = [];
            selectedFrequency.value = frequencyOptions[2]; // Reset to daily
            scheduleError.value = null;
        },
        onError: (errors) => {
            console.error('Error updating log configuration:', errors);
            if (errors.schedule) {
                scheduleError.value = errors.schedule;
            }
        },
    });
};

// Expose method for parent component
defineExpose({ openEditLogConfigDialog });
</script>

<template>
    <div class="relative flex-1 rounded-xl border border-sidebar-border/2 dark:border-gray-800 p-3">
        <p><b>List All Log Configurations</b></p>
        <Table>
            <TableCaption>A list of log configurations.</TableCaption>
            <TableHeader>
                <TableRow>
                    <TableHead class="w-[100px]">ID</TableHead>
                    <TableHead>Department</TableHead>
                    <TableHead>Area</TableHead>
                    <TableHead>Frequency Type</TableHead>
                    <TableHead>Schedule</TableHead>
                    <TableHead>Action</TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                <TableRow v-if="logConfigurations.length === 0">
                    <TableCell colspan="6" class="text-center">No log configurations available.</TableCell>
                </TableRow>
                <TableRow v-else v-for="logConfig in logConfigurations" :key="logConfig.id">
                    <TableCell class="font-medium">{{ logConfig.id }}</TableCell>
                    <TableCell>{{ departments.find(dep => dep.DepartmentID === logConfig.DepartmentID)?.name || 'N/A' }}</TableCell>
                    <TableCell>{{ areas.find(area => area.AreaID === logConfig.AreaID)?.name || 'N/A' }}</TableCell>
                    <TableCell>{{ frequencyOptions.find(opt => opt.value === logConfig.frequency_type)?.label || logConfig.frequency_type }}</TableCell>
                    <TableCell>{{ logConfig.frequency_type === 'hourly' ? 'Every hour' : JSON.parse(logConfig.schedule).join(', ') }}</TableCell>
                    <TableCell>
                        <div class="flex items-center space-x-2">
                            <DropdownMenu>
                                <DropdownMenuTrigger>
                                    <Ellipsis />
                                </DropdownMenuTrigger>
                                <DropdownMenuContent>
                                    <DropdownMenuLabel>Select Action</DropdownMenuLabel>
                                    <DropdownMenuSeparator />
                                    <DropdownMenuItem @click="openEditLogConfigDialog({ AreaID: logConfig.AreaID } as Component)">
                                        <SquarePen class="w-4 h-4 mr-2" />Edit
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    </TableCell>
                </TableRow>
            </TableBody>
        </Table>
    </div>

    <!-- Edit Log Configuration Dialog -->
    <Dialog v-model:open="isEditLogConfigDialogOpen">
        <DialogContent class="sm:max-w-[425px]">
            <DialogHeader>
                <DialogTitle>Edit Log Configuration</DialogTitle>
                <DialogDescription>
                    Configure logging frequency and schedule for this area. {{ scheduleConstraints.message }}
                </DialogDescription>
            </DialogHeader>
            <div class="grid gap-4 py-4">
                <!-- Frequency Type Combobox -->
                <div class="grid grid-cols-4 items-center gap-4">
                    <Label for="edit_log_config_frequency" class="text-right">Frequency</Label>
                    <div class="col-span-3">
                        <Combobox v-model="selectedFrequency" class="w-full" by="value">
                            <ComboboxAnchor as-child>
                                <ComboboxTrigger as-child>
                                    <Button variant="outline" class="w-full justify-between">
                                        {{ selectedFrequency.label || 'Select frequency...' }}
                                        <ChevronsUpDown class="ml-2 h-4 w-4 shrink-0 opacity-50" />
                                    </Button>
                                </ComboboxTrigger>
                            </ComboboxAnchor>
                            <ComboboxList>
                                <div class="relative w-full max-w-sm items-center">
                                    <ComboboxInput class="focus-visible:ring-0 border-0 border-b rounded-none h-10 w-full" placeholder="Search frequency..." />
                                    <span class="absolute start-0 inset-y-0 flex items-center justify-center px-3">
                                        <Search class="size-4 text-muted-foreground" />
                                    </span>
                                </div>
                                <ComboboxEmpty>No frequency found.</ComboboxEmpty>
                                <ComboboxGroup>
                                    <ComboboxItem v-for="option in frequencyOptions" :key="option.value" :value="option">
                                        {{ option.label }}
                                        <ComboboxItemIndicator>
                                            <Check :class="cn('ml-auto h-4 w-4', selectedFrequency.value === option.value ? 'opacity-100' : 'opacity-0')" />
                                        </ComboboxItemIndicator>
                                    </ComboboxItem>
                                </ComboboxGroup>
                            </ComboboxList>
                        </Combobox>
                        <span v-if="editLogConfigForm.errors.frequency_type" class="text-red-500 text-sm">
                            {{ editLogConfigForm.errors.frequency_type }}
                        </span>
                    </div>
                </div>

                <!-- Schedule Combobox -->
                <div class="grid grid-cols-4 items-center gap-4">
                    <Label for="edit_log_config_schedule" class="text-right">Schedule</Label>
                    <div class="col-span-3">
                        <Combobox v-model="selectedTimes" class="w-full" multiple by="value" :disabled="scheduleConstraints.disabled" @update:modelValue="handleTimeSelection">
                            <ComboboxAnchor as-child>
                                <ComboboxTrigger as-child>
                                    <Button variant="outline" class="w-full justify-between" :disabled="scheduleConstraints.disabled">
                                        {{ selectedTimes.length ? selectedTimes.map(t => t.label).join(', ') : 'Select times...' }}
                                        <ChevronsUpDown class="ml-2 h-4 w-4 shrink-0 opacity-50" />
                                    </Button>
                                </ComboboxTrigger>
                            </ComboboxAnchor>
                            <ComboboxList>
                                <div class="relative w-full max-w-sm items-center">
                                    <ComboboxInput class="focus-visible:ring-0 border-0 border-b rounded-none h-10 w-full" placeholder="Search or add time..." />
                                    <span class="absolute start-0 inset-y-0 flex items-center justify-center px-3">
                                        <Search class="size-4 text-muted-foreground" />
                                    </span>
                                </div>
                                <ComboboxEmpty>No time found.</ComboboxEmpty>
                                <ComboboxGroup>
                                    <ComboboxItem v-for="time in timeOptions" :key="time.value" :value="time" :disabled="scheduleConstraints.disabled">
                                        {{ time.label }}
                                        <ComboboxItemIndicator>
                                            <Check :class="cn('ml-auto h-4 w-4', selectedTimes.some(t => t.value === time.value) ? 'opacity-100' : 'opacity-0')" />
                                        </ComboboxItemIndicator>
                                    </ComboboxItem>
                                </ComboboxGroup>
                            </ComboboxList>
                        </Combobox>
                        <span v-if="scheduleError || editLogConfigForm.errors.schedule" class="text-red-500 text-sm">
                            {{ scheduleError || editLogConfigForm.errors.schedule }}
                        </span>
                    </div>
                </div>
            </div>
            <DialogFooter>
                <Button type="button" variant="outline" @click="isEditLogConfigDialogOpen = false">Cancel</Button>
                <Button type="submit" @click="updateLogConfig" :disabled="editLogConfigForm.processing || !!scheduleError">
                    {{ editLogConfigForm.processing ? 'Saving...' : 'Save Changes' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>