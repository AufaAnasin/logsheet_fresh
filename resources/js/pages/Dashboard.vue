<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { CirclePlus, Check, ChevronsUpDown, Ellipsis, SquarePen, X } from 'lucide-vue-next';
import PlaceholderPattern from '../components/PlaceholderPattern.vue';
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
import { cn } from '@/lib/utils';
import { ref, reactive } from 'vue';

// Define department interface
interface Department {
    DepartmentID: number;
    name: string;
    desc: string | null;
}

// Receive departments from Laravel
const props = defineProps<{
    departments: Department[];
}>();

// Reactive departments for dynamic updates
const localDepartments = reactive(props.departments.slice());

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

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
const isEditDialogOpen = ref(false);
const selectedDepartment = ref<Department | null>(null);

// Form for editing department
const editDepartmentForm = useForm({
    name: '',
    desc: '',
});

// Open edit department dialog
const openEditDialog = (department: Department) => {
    selectedDepartment.value = department;
    editDepartmentForm.reset();
    editDepartmentForm.clearErrors();
    editDepartmentForm.name = department.name;
    editDepartmentForm.desc = department.desc || '';
    isEditDialogOpen.value = true;
};

// Handle edit department form submission
const updateDepartment = () => {
    if (!selectedDepartment.value) return;
    editDepartmentForm.put(`/departments/${selectedDepartment.value.DepartmentID}`, {
        preserveState: true,
        onSuccess: (response) => {
            alert('Department updated successfully!');
            isEditDialogOpen.value = false;
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
const isDeleteDialogOpen = ref(false);
const departmentToDelete = ref<Department | null>(null);

// Form for deleting department
const deleteDepartmentForm = useForm({});

// Open delete department dialog
const openDeleteDialog = (department: Department) => {
    departmentToDelete.value = department;
    isDeleteDialogOpen.value = true;
};

// Handle delete department confirmation
const deleteDepartment = () => {
    if (!departmentToDelete.value) return;
    deleteDepartmentForm.delete(`/departments/${departmentToDelete.value.DepartmentID}`, {
        preserveState: true,
        onSuccess: () => {
            alert('Department deleted successfully!');
            isDeleteDialogOpen.value = false;
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
            isDeleteDialogOpen.value = false;
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
        onSuccess: () => {
            createAreaForm.reset();
            selectedDepartmentID.value = '';
            alert('Area created successfully!');
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
    createAreaForm.errors.DepartmentID = null;
    selectedDepartmentID.value = departmentID;
    isAreaComboboxOpen.value = false;
};
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                <!-- Form for adding new department -->
                <div
                    class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/2 dark:border-gray-800 p-3">
                    <p><b>Add Department</b></p>
                    <div class="grid w-full max-w-sm items-center gap-1.5 mt-2">
                        <Label for="department_name">Name</Label>
                        <Input id="department_name" type="text" placeholder="Put Department name ..."
                            v-model="createDepartmentForm.name" @input="createDepartmentForm.errors.name = null" />
                        <span v-if="createDepartmentForm.errors.name" class="text-red-500 text-sm">{{
                            createDepartmentForm.errors.name }}</span>
                    </div>
                    <div class="grid w-full max-w-sm items-center gap-1.5 mt-2">
                        <Label for="department_description">Description</Label>
                        <Input id="department_description" type="text" placeholder="Input Department description ..."
                            v-model="createDepartmentForm.desc" @input="createDepartmentForm.errors.desc = null" />
                        <span v-if="createDepartmentForm.errors.desc" class="text-red-500 text-sm">{{
                            createDepartmentForm.errors.desc }}</span>
                    </div>
                    <Button class="mt-3 w-full" @click="submitDepartment" :disabled="createDepartmentForm.processing">
                        <CirclePlus class="w-4 h-4 mr-2" />
                        {{ createDepartmentForm.processing ? 'Submitting...' : 'Submit Department' }}
                    </Button>
                </div>

                <!-- Form for adding new area -->
                <div
                    class="relative aspect-video rounded-xl border border-sidebar-border/2 dark:border-gray-800 p-3">
                    <p><b>Create Area</b></p>
                    <div class="grid w-full max-w-sm items-center gap-1.5 mt-2">
                        <Label for="area_name">Area Name</Label>
                        <Input id="area_name" type="text" placeholder="Input Area name ..."
                            v-model="createAreaForm.name" @input="createAreaForm.errors.name = null" />
                        <span v-if="createAreaForm.errors.name" class="text-red-500 text-sm">{{
                            createAreaForm.errors.name }}</span>
                    </div>
                    <div class="grid w-full max-w-sm items-center gap-1.5 mt-2">
                        <Label for="area_department">Department</Label>
                        <Popover v-model:open="isAreaComboboxOpen">
                            <PopoverTrigger as-child>
                                <Button variant="outline" role="combobox" :aria-expanded="isAreaComboboxOpen"
                                    class="w-full justify-between">
                                    {{ selectedDepartmentID
                                        ? localDepartments.find((dep) => dep.DepartmentID.toString() === selectedDepartmentID)?.name
                                        : 'Select department...' }}
                                    <ChevronsUpDown class="ml-2 h-4 w-4 shrink-0 opacity-50" />
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent class="w-full p-0">
                                <Command>
                                    <CommandInput placeholder="Search department..." />
                                    <CommandEmpty>No department found.</CommandEmpty>
                                    <CommandList>
                                        <CommandGroup>
                                            <CommandItem
                                                v-for="department in localDepartments"
                                                :key="department.DepartmentID"
                                                :value="department.DepartmentID.toString()"
                                                @select="updateDepartmentID(department.DepartmentID.toString())"
                                            >
                                                <Check
                                                    :class="cn(
                                                        'mr-2 h-4 w-4',
                                                        selectedDepartmentID === department.DepartmentID.toString()
                                                            ? 'opacity-100'
                                                            : 'opacity-0'
                                                    )"
                                                />
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
                            v-model="createAreaForm.desc" @input="createAreaForm.errors.desc = null" />
                        <span v-if="createAreaForm.errors.desc" class="text-red-500 text-sm">{{
                            createAreaForm.errors.desc }}</span>
                    </div>
                    <Button class="mt-3 w-full" @click="submitArea" :disabled="createAreaForm.processing">
                        <CirclePlus class="w-4 h-4 mr-2" />
                        {{ createAreaForm.processing ? 'Submitting...' : 'Submit Area' }}
                    </Button>
                </div>

                <!-- Placeholder card -->
                <div
                    class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/2 dark:border-gray-800 p-3">
                    <PlaceholderPattern />
                </div>
            </div>

            <!-- Department table -->
            <div class="relative flex-1 rounded-xl border border-sidebar-border/2 dark:border-gray-800 p-3">
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
                                            <DropdownMenuItem @click="openEditDialog(department)">
                                                <SquarePen class="w-4 h-4 mr-2" />Edit
                                            </DropdownMenuItem>
                                            <DropdownMenuItem @click="openDeleteDialog(department)">
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
        </div>

        <!-- Edit Department Dialog -->
        <Dialog v-model:open="isEditDialogOpen">
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>Edit Department</DialogTitle>
                    <DialogDescription>
                        Make changes to the department details here. Click save when you're done.
                    </DialogDescription>
                </DialogHeader>
                <div class="grid gap-4 py-4">
                    <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="edit_name" class="text-right">Name</Label>
                        <Input id="edit_name" v-model="editDepartmentForm.name" class="col-span-3"
                            @input="editDepartmentForm.errors.name = null" />
                        <span v-if="editDepartmentForm.errors.name" class="text-red-500 text-sm col-start-2 col-span-3">{{
                            editDepartmentForm.errors.name }}</span>
                    </div>
                    <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="edit_desc" class="text-right">Description</Label>
                        <Input id="edit_desc" v-model="editDepartmentForm.desc" class="col-span-3"
                            @input="editDepartmentForm.errors.desc = null" />
                        <span v-if="editDepartmentForm.errors.desc" class="text-red-500 text-sm col-start-2 col-span-3">{{
                            editDepartmentForm.errors.desc }}</span>
                    </div>
                </div>
                <DialogFooter>
                    <Button type="button" variant="outline" @click="isEditDialogOpen = false">Cancel</Button>
                    <Button type="submit" @click="updateDepartment" :disabled="editDepartmentForm.processing">
                        {{ editDepartmentForm.processing ? 'Saving...' : 'Save Changes' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Delete Department Alert Dialog -->
        <AlertDialog v-model:open="isDeleteDialogOpen">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Are you sure?</AlertDialogTitle>
                    <AlertDialogDescription>
                        This action cannot be undone. This will permanently delete the department "{{
                            departmentToDelete?.name
                        }}" from the database.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel @click="isDeleteDialogOpen = false">Cancel</AlertDialogCancel>
                    <AlertDialogAction @click="deleteDepartment" :disabled="deleteDepartmentForm.processing">
                        {{ deleteDepartmentForm.processing ? 'Deleting...' : 'Delete' }}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>