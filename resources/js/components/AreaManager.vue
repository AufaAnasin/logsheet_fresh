<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { CirclePlus, Check, ChevronsUpDown, Ellipsis, X } from 'lucide-vue-next';
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
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import { cn } from '@/lib/utils';
import { ref, computed } from 'vue';

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

const props = defineProps<{
    departments: Department[];
    areas: Area[];
    showTableOnly?: boolean;
}>();

// Form for adding new area
const createAreaForm = useForm({
    name: '',
    DepartmentID: undefined as number | undefined,
    desc: undefined as string | undefined,
});

// Combobox state for area department selection
const isAreaComboboxOpen = ref(false);
const selectedDepartmentID = ref<string | null>(null);

// Computed property for selected department name
const selectedDepartmentName = computed(() => {
    if (selectedDepartmentID.value) {
        const department = props.departments.find(
            (dep) => dep.DepartmentID.toString() === selectedDepartmentID.value
        );
        return department?.name || 'Select department...';
    }
    return 'Select department...';
});

// Utility to convert null to undefined
const nullToUndefined = (value: string | null): string | undefined => value ?? undefined;

// Handle area form submission
const submitArea = () => {
    if (!createAreaForm.DepartmentID) {
        createAreaForm.errors.DepartmentID = 'Please select a department.';
        return;
    }
    createAreaForm.post('/areas', {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            createAreaForm.reset();
            selectedDepartmentID.value = null;
        },
        onError: () => { },
    });
};

// Update DepartmentID when combobox selection changes
const updateDepartmentID = (departmentID: string) => {
    const id = parseInt(departmentID);
    createAreaForm.DepartmentID = id;
    editAreaForm.DepartmentID = id;
    createAreaForm.errors.DepartmentID = undefined;
    editAreaForm.errors.DepartmentID = undefined;
    selectedDepartmentID.value = departmentID;
    isAreaComboboxOpen.value = false;
};

// Edit area dialog state
const isEditAreaDialogOpen = ref(false);
const selectedArea = ref<Area | null>(null);

// Form for editing area
const editAreaForm = useForm({
    name: '',
    DepartmentID: undefined as number | undefined,
    desc: undefined as string | undefined,
});

// Open edit area dialog
const openEditAreaDialog = (area: Area) => {
    selectedArea.value = { ...area };
    editAreaForm.reset();
    editAreaForm.clearErrors();
    editAreaForm.name = area.name;
    editAreaForm.DepartmentID = area.DepartmentID;
    editAreaForm.desc = nullToUndefined(area.desc);
    selectedDepartmentID.value = area.DepartmentID.toString();
    isEditAreaDialogOpen.value = true;
};

// Handle edit area form submission
const updateArea = () => {
    if (!selectedArea.value || !editAreaForm.DepartmentID) return;
    editAreaForm.put(`/areas/${selectedArea.value.AreaID}`, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            isEditAreaDialogOpen.value = false;
            selectedDepartmentID.value = null;
        },
        onError: () => { },
    });
};

// Delete area dialog state
const isDeleteAreaDialogOpen = ref(false);
const areaToDelete = ref<Area | null>(null);

// Form for deleting area
const deleteAreaForm = useForm({});

// Open delete area dialog
const openDeleteAreaDialog = (area: Area) => {
    areaToDelete.value = { ...area };
    isDeleteAreaDialogOpen.value = true;
};

// Handle delete area confirmation
const deleteArea = () => {
    if (!areaToDelete.value) return;
    deleteAreaForm.delete(`/areas/${areaToDelete.value.AreaID}`, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            isDeleteAreaDialogOpen.value = false;
            areaToDelete.value = null;
        },
        onError: () => { },
    });
};
</script>

<template>
    <div v-if="!showTableOnly"
        class="relative flex flex-col min-h-full rounded-xl border border-gray-200 dark:border-gray-800 p-3">
        <p><b>Create Area</b></p>
        <div class="grid w-full max-w-sm items-center gap-1.5 mt-2">
            <Label for="area_name">Area Name</Label>
            <Input id="area_name" type="text" placeholder="Input Area name ..." v-model="createAreaForm.name"
                @input="createAreaForm.errors.name = undefined" />
            <span v-if="createAreaForm.errors.name" class="text-red-500 text-sm">{{ createAreaForm.errors.name }}</span>
        </div>
        <div class="grid w-full max-w-sm items-center gap-1.5 mt-2">
            <Label for="area_department">Department</Label>
            <Popover v-model:open="isAreaComboboxOpen">
                <PopoverTrigger as-child>
                    <Button variant="outline" role="combobox" :aria-expanded="isAreaComboboxOpen"
                        class="w-full justify-between">
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
                                <CommandItem v-for="department in departments" :key="department.DepartmentID"
                                    :value="department.DepartmentID.toString()"
                                    @select="updateDepartmentID(department.DepartmentID.toString())">
                                    <Check
                                        :class="cn('mr-2 h-4 w-4', selectedDepartmentID === department.DepartmentID.toString() ? 'opacity-100' : 'opacity-0')" />
                                    {{ department.name }}
                                </CommandItem>
                            </CommandGroup>
                        </CommandList>
                    </Command>
                </PopoverContent>
            </Popover>
            <span v-if="createAreaForm.errors.DepartmentID" class="text-red-500 text-sm">{{
                createAreaForm.errors.DepartmentID }}</span>
        </div>
        <div class="grid w-full max-w-sm items-center gap-1.5 mt-2">
            <Label for="area_description">Description</Label>
            <Input id="area_description" type="text" placeholder="Input Area description ..."
                v-model="createAreaForm.desc" @input="createAreaForm.errors.desc = undefined" />
            <span v-if="createAreaForm.errors.desc" class="text-red-500 text-sm">{{ createAreaForm.errors.desc }}</span>
        </div>
        <Button class="mt-3 w-full" @click="submitArea" :disabled="createAreaForm.processing">
            <CirclePlus class="w-4 h-4 mr-2" />
            {{ createAreaForm.processing ? 'Submitting...' : 'Submit Area' }}
        </Button>
    </div>
    <div v-if="showTableOnly" class="relative flex-1 rounded-xl border border-sidebar-border/2 dark:border-gray-800 p-3">
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
                <TableRow v-if="areas.length === 0">
                    <TableCell colspan="5" class="text-center">No areas available.</TableCell>
                </TableRow>
                <TableRow v-else v-for="area in areas" :key="area.AreaID">
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
                    <Input id="edit_area_name" type="text" v-model="editAreaForm.name" class="col-span-3"
                        @input="editAreaForm.errors.name = undefined" />
                    <span v-if="editAreaForm.errors.name" class="text-red-500 text-sm col-start-2 col-span-3">{{
                        editAreaForm.errors.name }}</span>
                </div>
                <div class="grid grid-cols-4 items-center gap-4">
                    <Label for="edit_area_department" class="text-right">Department</Label>
                    <div class="col-span-3">
                        <Popover v-model:open="isAreaComboboxOpen">
                            <PopoverTrigger as-child>
                                <Button variant="outline" role="combobox" :aria-expanded="isAreaComboboxOpen"
                                    class="w-full justify-between">
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
                                            <CommandItem v-for="department in departments" :key="department.DepartmentID"
                                                :value="department.DepartmentID.toString()"
                                                @select="updateDepartmentID(department.DepartmentID.toString())">
                                                <Check
                                                    :class="cn('mr-2 h-4 w-4', selectedDepartmentID === department.DepartmentID.toString() ? 'opacity-100' : 'opacity-0')" />
                                                {{ department.name }}
                                            </CommandItem>
                                        </CommandGroup>
                                    </CommandList>
                                </Command>
                            </PopoverContent>
                        </Popover>
                        <span v-if="editAreaForm.errors.DepartmentID" class="text-red-500 text-sm">{{
                            editAreaForm.errors.DepartmentID }}</span>
                    </div>
                </div>
                <div class="grid grid-cols-4 items-center gap-4">
                    <Label for="edit_area_desc" class="text-right">Description</Label>
                    <Input id="edit_area_desc" type="text" v-model="editAreaForm.desc" class="col-span-3"
                        @input="editAreaForm.errors.desc = undefined" />
                    <span v-if="editAreaForm.errors.desc" class="text-red-500 text-sm col-start-2 col-span-3">{{
                        editAreaForm.errors.desc }}</span>
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
                    This action cannot be undone. This will permanently delete the area "{{ areaToDelete?.name }}"
                    from the database, if no components have log data.
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
</template>