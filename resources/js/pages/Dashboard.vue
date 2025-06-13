<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { CirclePlus, Check, ChevronsUpDown, Ellipsis, SquarePen, X, Search } from 'lucide-vue-next';
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
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
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
import { ref, reactive, computed, watch } from 'vue';

// Define interfaces
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
    area_name?: string; // Added to match backend response
    desc: string | null;
}

// Receive props
const props = defineProps<{
    departments: Department[];
    areas: Area[];
    components?: Component[];
}>();

// Reactive collections
const localDepartments = reactive(props.departments.slice());
const localAreas = reactive(props.areas.slice());
const localComponents = reactive(props.components ? props.components.slice() : []);

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

// Computed property for selected department name
const selectedDepartmentName = computed(() => {
    if (selectedDepartmentID.value) {
        const department = localDepartments.find(
            (dep) => dep.DepartmentID.toString() === selectedDepartmentID.value
        );
        return department?.name || 'Select department...';
    }
    return 'Select department...';
});

// Form for adding new department
const createDepartmentForm = useForm({
    name: '',
    desc: '',
});

// Handle create department form submission
const submitDepartment = () => {
    createDepartmentForm.post('/departments', {
        preserveState: true,
        onSuccess: (response) => {
            createDepartmentForm.reset();
            alert('Department created successfully!');
            const newDepartment = response.props.departments.find(
                (dep: Department) => !localDepartments.some((d) => d.DepartmentID === dep.DepartmentID)
            );
            if (newDepartment) {
                localDepartments.push(newDepartment);
            }
        },
        onError: (errors) => {
            console.error(errors);
            const errorMessage = errors.name || 'Failed to create department. Check the form inputs.';
            alert(errorMessage);
        },
    });
};

// Edit department dialog state
const isEditDepartmentDialogOpen = ref(false);
const selectedDepartment = ref<Department | null>(null);

// Form for editing department
const editDepartmentForm = useForm({
    name: '',
    desc: '',
});

// Open edit department dialog
const openEditDepartmentDialog = (department: Department) => {
    selectedDepartment.value = department;
    editDepartmentForm.reset();
    editDepartmentForm.clearErrors();
    editDepartmentForm.name = department.name;
    editDepartmentForm.desc = department.desc || '';
    isEditDepartmentDialogOpen.value = true;
};

// Handle edit department form submission
const updateDepartment = () => {
    if (!selectedDepartment.value) return;
    editDepartmentForm.put(`/departments/${selectedDepartment.value.DepartmentID}`, {
        preserveState: true,
        onSuccess: (response) => {
            alert('Department updated successfully!');
            isEditDepartmentDialogOpen.value = false;
            const updatedDepartment = response.props.departments.find(
                (dep: Department) => dep.DepartmentID === selectedDepartment.value!.DepartmentID
            );
            if (updatedDepartment) {
                const index = localDepartments.findIndex(
                    (dep) => dep.DepartmentID === updatedDepartment.DepartmentID
                );
                if (index !== -1) {
                    localDepartments[index] = updatedDepartment;
                }
            }
        },
        onError: (errors) => {
            console.error(errors);
            const errorMessage = errors.name || 'Failed to update department. Check the form inputs.';
            alert(errorMessage);
        },
    });
};

// Delete department dialog state
const isDeleteDepartmentDialogOpen = ref(false);
const departmentToDelete = ref<Department | null>(null);

// Form for deleting department
const deleteDepartmentForm = useForm({});

// Open delete department dialog
const openDeleteDepartmentDialog = (department: Department) => {
    departmentToDelete.value = department;
    isDeleteDepartmentDialogOpen.value = true;
};

// Handle delete department confirmation
const deleteDepartment = () => {
    if (!departmentToDelete.value) return;
    deleteDepartmentForm.delete(`/departments/${departmentToDelete.value.DepartmentID}`, {
        preserveState: true,
        onSuccess: () => {
            alert('Department deleted successfully!');
            isDeleteDepartmentDialogOpen.value = false;
            const index = localDepartments.findIndex(
                (dep) => dep.DepartmentID === departmentToDelete.value!.DepartmentID
            );
            if (index !== -1) {
                localDepartments.splice(index, 1);
            }
            departmentToDelete.value = null;
        },
        onError: (errors) => {
            console.error(errors);
            alert('Failed to delete department.');
            isDeleteDepartmentDialogOpen.value = false;
        },
    });
};

// Form for adding new area
const createAreaForm = useForm({
    name: '',
    DepartmentID: '',
    desc: '',
});

// Combobox state for area department selection
const isAreaComboboxOpen = ref(false);
const selectedDepartmentID = ref('');

// Handle area form submission
const submitArea = () => {
    createAreaForm.post('/areas', {
        preserveState: true,
        onSuccess: (response) => {
            createAreaForm.reset();
            selectedDepartmentID.value = '';
            alert('Area created successfully!');
            const newArea = response.props.areas.find(
                (area: Area) => !localAreas.some((a) => a.AreaID === area.AreaID)
            );
            if (newArea) {
                localAreas.push(newArea);
            }
        },
        onError: (errors) => {
            console.error(errors);
            const errorMessage = errors.name || errors.DepartmentID || 'Failed to create area. Check the form inputs.';
            alert(errorMessage);
        },
    });
};

// Update DepartmentID when combobox selection changes
const updateDepartmentID = (departmentID: string) => {
    createAreaForm.DepartmentID = departmentID;
    editAreaForm.DepartmentID = departmentID; // Sync for edit form
    createAreaForm.errors.DepartmentID = null;
    editAreaForm.errors.DepartmentID = null;
    selectedDepartmentID.value = departmentID;
    isAreaComboboxOpen.value = false;
};

// Edit area dialog state
const isEditAreaDialogOpen = ref(false);
const selectedArea = ref<Area | null>(null);

// Form for editing area
const editAreaForm = useForm({
    name: '',
    DepartmentID: '',
    desc: '',
});

// Open edit area dialog
const openEditAreaDialog = (area: Area) => {
    selectedArea.value = area;
    editAreaForm.reset();
    editAreaForm.clearErrors();
    editAreaForm.name = area.name;
    editAreaForm.DepartmentID = area.DepartmentID.toString();
    editAreaForm.desc = area.desc || '';
    selectedDepartmentID.value = area.DepartmentID.toString();
    isEditAreaDialogOpen.value = true;
};

// Handle edit area form submission
const updateArea = () => {
    if (!selectedArea.value) return;
    editAreaForm.put(`/areas/${selectedArea.value.AreaID}`, {
        preserveState: true,
        onSuccess: (response) => {
            alert('Area updated successfully!');
            isEditAreaDialogOpen.value = false;
            const updatedArea = response.props.areas.find(
                (area: Area) => area.AreaID === selectedArea.value!.AreaID
            );
            if (updatedArea) {
                const index = localAreas.findIndex(
                    (area) => area.AreaID === updatedArea.AreaID
                );
                if (index !== -1) {
                    localAreas[index] = updatedArea;
                }
            }
            selectedDepartmentID.value = '';
        },
        onError: (errors) => {
            console.error(errors);
            const errorMessage = errors.name || errors.DepartmentID || 'Failed to update area. Check the form inputs.';
            alert(errorMessage);
        },
    });
};

// Delete area dialog state
const isDeleteAreaDialogOpen = ref(false);
const areaToDelete = ref<Area | null>(null);

// Form for deleting area
const deleteAreaForm = useForm({});

// Open delete area dialog
const openDeleteAreaDialog = (area: Area) => {
    areaToDelete.value = area;
    isDeleteAreaDialogOpen.value = true;
};

// Handle delete area confirmation
const deleteArea = () => {
    if (!areaToDelete.value) return;
    deleteAreaForm.delete(`/areas/${areaToDelete.value.AreaID}`, {
        preserveState: true,
        onSuccess: () => {
            alert('Area deleted successfully!');
            isDeleteAreaDialogOpen.value = false;
            const index = localAreas.findIndex(
                (area) => area.AreaID === areaToDelete.value!.AreaID
            );
            if (index !== -1) {
                localAreas.splice(index, 1);
            }
            areaToDelete.value = null;
        },
        onError: (errors) => {
            console.error(errors);
            alert('Failed to delete area.');
            isDeleteAreaDialogOpen.value = false;
        },
    });
};

// Form for adding new component
const createComponentForm = useForm({
    name: '',
    AreaID: '',
    desc: '',
});

// Combobox state for component area selection
const selectedComponentArea = ref<Area | null>(null);

// Sync AreaID with form
watch(selectedComponentArea, (newArea) => {
    createComponentForm.AreaID = newArea ? newArea.AreaID.toString() : '';
    createComponentForm.errors.AreaID = null;
});

// Handle component form submission
const submitComponent = () => {
    if (!createComponentForm.AreaID) {
        createComponentForm.errors.AreaID = 'Please select an area.';
        return;
    }
    createComponentForm.post('/components', {
        preserveState: true,
        onSuccess: (response) => {
            createComponentForm.reset();
            selectedComponentArea.value = null;
            alert('Component created successfully!');
            const newComponent = response.props.components.find(
                (comp: Component) => !localComponents.some((c) => c.ComponentID === comp.ComponentID)
            );
            if (newComponent) {
                localComponents.push(newComponent);
            }
        },
        onError: (errors) => {
            console.error(errors);
            const errorMessage = errors.name || errors.AreaID || 'Failed to create component. Check the form inputs.';
            alert(errorMessage);
        },
    });
};
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                <!-- Form for adding new department -->
                <div class="relative flex flex-col min-h-full rounded-xl border border-gray-200 dark:border-gray-800 p-3">
                    <p><b>Add Department</b></p>
                    <div class="grid w-full max-w-sm items-center gap-1.5 mt-2">
                        <Label for="department_name">Name</Label>
                        <Input id="department_name" type="text" placeholder="Put Department name ..." v-model="createDepartmentForm.name" @input="createDepartmentForm.errors.name = null" />
                        <span v-if="createDepartmentForm.errors.name" class="text-red-500 text-sm">{{ createDepartmentForm.errors.name }}</span>
                    </div>
                    <div class="grid w-full max-w-sm items-center gap-1.5 mt-2">
                        <Label for="department_description">Description</Label>
                        <Input id="department_description" type="text" placeholder="Input Department description ..." v-model="createDepartmentForm.desc" @input="createDepartmentForm.errors.desc = null" />
                        <span v-if="createDepartmentForm.errors.desc" class="text-red-500 text-sm">{{ createDepartmentForm.errors.desc }}</span>
                    </div>
                    <Button class="mt-auto w-full" @click="submitDepartment" :disabled="createDepartmentForm.processing">
                        <CirclePlus class="w-4 h-4 mr-2" />
                        {{ createDepartmentForm.processing ? 'Submitting...' : 'Submit Department' }}
                    </Button>
                </div>

                <!-- Form for adding new area -->
                <div class="relative flex flex-col min-h-full rounded-xl border border-gray-200 dark:border-gray-800 p-3">
                    <p><b>Create Area</b></p>
                    <div class="grid w-full max-w-sm items-center gap-1.5 mt-2">
                        <Label for="area_name">Area Name</Label>
                        <Input id="area_name" type="text" placeholder="Input Area name ..." v-model="createAreaForm.name" @input="createAreaForm.errors.name = null" />
                        <span v-if="createAreaForm.errors.name" class="text-red-500 text-sm">{{ createAreaForm.errors.name }}</span>
                    </div>
                    <div class="grid w-full max-w-sm items-center gap-1.5 mt-2">
                        <Label for="area_department">Department</Label>
                        <Popover v-model:open="isAreaComboboxOpen">
                            <PopoverTrigger as-child>
                                <Button variant="outline" role="combobox" :aria-expanded="isAreaComboboxOpen" class="w-full justify-between">
                                    {{ selectedDepartmentName }}
                                    <ChevronsUpDown class="ml-2 h-4 w-4 shrink-0 opacity-50" />
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent class="w-full p-0">
                                <Command>
                                    <CommandInput placeholder="Search department..." />
                                    <CommandEmpty>No department found.</CommandEmpty>
                                    <CommandList>
                                        <CommandGroup>
                                            <CommandItem v-for="department in localDepartments" :key="department.DepartmentID" :value="department.DepartmentID.toString()" @select="updateDepartmentID(department.DepartmentID.toString())">
                                                <Check :class="cn('mr-2 h-4 w-4', selectedDepartmentID === department.DepartmentID.toString() ? 'opacity-100' : 'opacity-0')" />
                                                {{ department.name }}
                                            </CommandItem>
                                        </CommandGroup>
                                    </CommandList>
                                </Command>
                            </PopoverContent>
                        </Popover>
                        <span v-if="createAreaForm.errors.DepartmentID" class="text-red-500 text-sm">{{ createAreaForm.errors.DepartmentID }}</span>
                    </div>
                    <div class="grid w-full max-w-sm items-center gap-1.5 mt-2">
                        <Label for="area_description">Description</Label>
                        <Input id="area_description" type="text" placeholder="Input Area description ..." v-model="createAreaForm.desc" @input="createAreaForm.errors.desc = null" />
                        <span v-if="createAreaForm.errors.desc" class="text-red-500 text-sm">{{ createAreaForm.errors.desc }}</span>
                    </div>
                    <Button class="mt-3 w-full" @click="submitArea" :disabled="createAreaForm.processing">
                        <CirclePlus class="w-4 h-4 mr-2" />
                        {{ createAreaForm.processing ? 'Submitting...' : 'Submit Area' }}
                    </Button>
                </div>

                <!-- Form for adding new component -->
                <div class="relative flex flex-col min-h-full rounded-xl border border-gray-200 dark:border-gray-800 p-3">
                    <p><b>Create Component</b></p>
                    <div class="grid w-full max-w-sm items-center gap-1.5 mt-2">
                        <Label for="component_name">Component Name</Label>
                        <Input id="component_name" type="text" placeholder="Put Component name ..." v-model="createComponentForm.name" @input="createComponentForm.errors.name = null" />
                        <span v-if="createComponentForm.errors.name" class="text-red-500 text-sm">{{ createComponentForm.errors.name }}</span>
                    </div>
                    <div class="grid w-full max-w-sm items-center gap-1.5 mt-2">
                        <Label for="component_area">Which Area?</Label>
                        <Combobox v-model="selectedComponentArea" class="w-full" by="AreaID">
                            <ComboboxAnchor as-child>
                                <ComboboxTrigger as-child>
                                    <Button variant="outline" class="justify-between w-full">
                                        {{ selectedComponentArea?.name ?? 'Select area...' }}
                                        <ChevronsUpDown class="ml-2 h-4 w-4 shrink-0 opacity-50" />
                                    </Button>
                                </ComboboxTrigger>
                            </ComboboxAnchor>
                            <ComboboxList>
                                <div class="relative w-full max-w-sm items-center">
                                    <ComboboxInput class="focus-visible:ring-0 border-0 border-b rounded-none h-10" placeholder="Search area..." />
                                    <span class="absolute start-0 inset-y-0 flex items-center justify-center px-3">
                                        <Search class="size-4 text-muted-foreground" />
                                    </span>
                                </div>
                                <ComboboxEmpty>No area found.</ComboboxEmpty>
                                <ComboboxGroup>
                                    <ComboboxItem v-for="area in localAreas" :key="area.AreaID" :value="area">
                                        {{ area.name }}
                                        <ComboboxItemIndicator>
                                            <Check :class="cn('ml-auto h-4 w-4', selectedComponentArea?.AreaID === area.AreaID ? 'opacity-100' : 'opacity-0')" />
                                        </ComboboxItemIndicator>
                                    </ComboboxItem>
                                </ComboboxGroup>
                            </ComboboxList>
                        </Combobox>
                        <span v-if="createComponentForm.errors.AreaID" class="text-red-500 text-sm">{{ createComponentForm.errors.AreaID }}</span>
                    </div>
                    <div class="grid w-full max-w-sm items-center gap-1.5 mt-2">
                        <Label for="component_description">Description</Label>
                        <Input id="component_description" type="text" placeholder="Put Component description ..." v-model="createComponentForm.desc" @input="createComponentForm.errors.desc = null" />
                        <span v-if="createComponentForm.errors.desc" class="text-red-500 text-sm">{{ createComponentForm.errors.desc }}</span>
                    </div>
                    <Button class="mt-3 w-full" @click="submitComponent" :disabled="createComponentForm.processing">
                        <CirclePlus class="w-4 h-4 mr-2" />
                        {{ createComponentForm.processing ? 'Submitting...' : 'Create Component' }}
                    </Button>
                </div>
            </div>

            <!-- Department table -->
            <div class="relative flex-1 rounded-xl border border-sidebar-border/2 dark:border-gray-800 p-3">
                <p><b>List All Departments</b></p>
                <Table>
                    <TableCaption>A list of departments.</TableCaption>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="w-[100px]">ID</TableHead>
                            <TableHead>Name</TableHead>
                            <TableHead>Description</TableHead>
                            <TableHead>Action</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="department in localDepartments" :key="department.DepartmentID">
                            <TableCell class="font-medium">{{ department.DepartmentID }}</TableCell>
                            <TableCell>{{ department.name }}</TableCell>
                            <TableCell>{{ department.desc || 'No description' }}</TableCell>
                            <TableCell>
                                <div class="flex items-center space-x-2">
                                    <DropdownMenu>
                                        <DropdownMenuTrigger>
                                            <Ellipsis />
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent>
                                            <DropdownMenuLabel>Select Action</DropdownMenuLabel>
                                            <DropdownMenuSeparator />
                                            <DropdownMenuItem @click="openEditDepartmentDialog(department)">
                                                <SquarePen class="w-4 h-4 mr-2" />Edit
                                            </DropdownMenuItem>
                                            <DropdownMenuItem @click="openDeleteDepartmentDialog(department)">
                                                <X class="w-4 h-4 mr-2" />Delete
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- Area table -->
            <div class="relative flex-1 rounded-xl border border-sidebar-border/2 dark:border-gray-800 p-3">
                <p><b>List All Areas</b></p>
                <Table>
                    <TableCaption>A list of areas.</TableCaption>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="w-[100px]">ID</TableHead>
                            <TableHead>Name</TableHead>
                            <TableHead>Department</TableHead>
                            <TableHead>Description</TableHead>
                            <TableHead>Action</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="area in localAreas" :key="area.AreaID">
                            <TableCell class="font-medium">{{ area.AreaID }}</TableCell>
                            <TableCell>{{ area.name }}</TableCell>
                            <TableCell>{{ area.department_name }}</TableCell>
                            <TableCell>{{ area.desc || 'No description' }}</TableCell>
                            <TableCell>
                                <div class="flex items-center space-x-2">
                                    <DropdownMenu>
                                        <DropdownMenuTrigger>
                                            <Ellipsis />
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent>
                                            <DropdownMenuLabel>Select Action</DropdownMenuLabel>
                                            <DropdownMenuSeparator />
                                            <DropdownMenuItem @click="openEditAreaDialog(area)">
                                                <SquarePen class="w-4 h-4 mr-2" />Edit
                                            </DropdownMenuItem>
                                            <DropdownMenuItem @click="openDeleteAreaDialog(area)">
                                                <X class="w-4 h-4 mr-2" />Delete
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- Edit Department Dialog -->
            <Dialog v-model:open="isEditDepartmentDialogOpen">
                <DialogContent class="sm:max-w-[425px]">
                    <DialogHeader>
                        <DialogTitle>Edit Department</DialogTitle>
                        <DialogDescription>
                            Make changes to the department details here. Click save when you're done.
                        </DialogDescription>
                    </DialogHeader>
                    <div class="grid gap-4 py-4">
                        <div class="grid grid-cols-4 items-center gap-4">
                            <Label for="edit_department_name" class="text-right">Name</Label>
                            <Input id="edit_department_name" v-model="editDepartmentForm.name" class="col-span-3" @input="editDepartmentForm.errors.name = null" />
                            <span v-if="editDepartmentForm.errors.name" class="text-red-500 text-sm col-start-2 col-span-3">{{ editDepartmentForm.errors.name }}</span>
                        </div>
                        <div class="grid grid-cols-4 items-center gap-4">
                            <Label for="edit_department_desc" class="text-right">Description</Label>
                            <Input id="edit_department_desc" v-model="editDepartmentForm.desc" class="col-span-3" @input="editDepartmentForm.errors.desc = null" />
                            <span v-if="editDepartmentForm.errors.desc" class="text-red-500 text-sm col-start-2 col-span-3">{{ editDepartmentForm.errors.desc }}</span>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" @click="isEditDepartmentDialogOpen = false">Cancel</Button>
                        <Button type="submit" @click="updateDepartment" :disabled="editDepartmentForm.processing">
                            {{ editDepartmentForm.processing ? 'Saving...' : 'Save Changes' }}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <!-- Delete Department Alert Dialog -->
            <AlertDialog v-model:open="isDeleteDepartmentDialogOpen">
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Are you sure?</AlertDialogTitle>
                        <AlertDialogDescription>
                            This action cannot be undone. This will permanently delete the department "{{ departmentToDelete?.name }}" from the database.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel @click="isDeleteDepartmentDialogOpen = false">Cancel</AlertDialogCancel>
                        <AlertDialogAction @click="deleteDepartment" :disabled="deleteDepartmentForm.processing">
                            {{ deleteDepartmentForm.processing ? 'Deleting...' : 'Delete' }}
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>

            <!-- Edit Area Dialog -->
            <Dialog v-model:open="isEditAreaDialogOpen">
                <DialogContent class="sm:max-w-[425px]">
                    <DialogHeader>
                        <DialogTitle>Edit Area</DialogTitle>
                        <DialogDescription>
                            Make changes to the area details here. Click save when you're done.
                        </DialogDescription>
                    </DialogHeader>
                    <div class="grid gap-4 py-4">
                        <div class="grid grid-cols-4 items-center gap-4">
                            <Label for="edit_area_name" class="text-right">Name</Label>
                            <Input id="edit_area_name" v-model="editAreaForm.name" class="col-span-3" @input="editAreaForm.errors.name = null" />
                            <span v-if="editAreaForm.errors.name" class="text-red-500 text-sm col-start-2 col-span-3">{{ editAreaForm.errors.name }}</span>
                        </div>
                        <div class="grid grid-cols-4 items-center gap-4">
                            <Label for="edit_area_department" class="text-right">Department</Label>
                            <div class="col-span-3">
                                <Popover v-model:open="isAreaComboboxOpen">
                                    <PopoverTrigger as-child>
                                        <Button variant="outline" role="combobox" :aria-expanded="isAreaComboboxOpen" class="w-full justify-between">
                                            {{ selectedDepartmentName }}
                                            <ChevronsUpDown class="ml-2 h-4 w-4 shrink-0 opacity-50" />
                                        </Button>
                                    </PopoverTrigger>
                                    <PopoverContent class="w-full p-0">
                                        <Command>
                                            <CommandInput placeholder="Search department..." />
                                            <CommandEmpty>No department found.</CommandEmpty>
                                            <CommandList>
                                                <CommandGroup>
                                                    <CommandItem v-for="department in localDepartments" :key="department.DepartmentID" :value="department.DepartmentID.toString()" @select="updateDepartmentID(department.DepartmentID.toString())">
                                                        <Check :class="cn('mr-2 h-4 w-4', selectedDepartmentID === department.DepartmentID.toString() ? 'opacity-100' : 'opacity-0')" />
                                                        {{ department.name }}
                                                    </CommandItem>
                                                </CommandGroup>
                                            </CommandList>
                                        </Command>
                                    </PopoverContent>
                                </Popover>
                                <span v-if="editAreaForm.errors.DepartmentID" class="text-red-500 text-sm">{{ editAreaForm.errors.DepartmentID }}</span>
                            </div>
                        </div>
                        <div class="grid grid-cols-4 items-center gap-4">
                            <Label for="edit_area_desc" class="text-right">Description</Label>
                            <Input id="edit_area_desc" v-model="editAreaForm.desc" class="col-span-3" @input="editAreaForm.errors.desc = null" />
                            <span v-if="editAreaForm.errors.desc" class="text-red-500 text-sm col-start-2 col-span-3">{{ editAreaForm.errors.desc }}</span>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" @click="isEditAreaDialogOpen = false">Cancel</Button>
                        <Button type="submit" @click="updateArea" :disabled="editAreaForm.processing">
                            {{ editAreaForm.processing ? 'Saving...' : 'Save Changes' }}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <!-- Delete Area Alert Dialog -->
            <AlertDialog v-model:open="isDeleteAreaDialogOpen">
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Are you sure?</AlertDialogTitle>
                        <AlertDialogDescription>
                            This action cannot be undone. This will permanently delete the area "{{ areaToDelete?.name }}" and its associated components from the database.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel @click="isDeleteAreaDialogOpen = false">Cancel</AlertDialogCancel>
                        <AlertDialogAction @click="deleteArea" :disabled="deleteAreaForm.processing">
                            {{ deleteAreaForm.processing ? 'Deleting...' : 'Delete' }}
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </div>
    </AppLayout>
</template>