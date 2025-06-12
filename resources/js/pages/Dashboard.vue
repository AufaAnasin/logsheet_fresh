<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { CirclePlus, Ellipsis, SquarePen, X } from 'lucide-vue-next';
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
const createForm = useForm({
    name: '',
    desc: '',
});

// Handle create form submission
const submitDepartment = () => {
    createForm.post('/departments', {
        preserveState: true,
        onSuccess: (response) => {
            createForm.reset();
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

// Dialog state
const isEditDialogOpen = ref(false);
const selectedDepartment = ref<Department | null>(null);

// Form for editing department
const editForm = useForm({
    name: '',
    desc: '',
});

// Open edit dialog
const openEditDialog = (department: Department) => {
    selectedDepartment.value = department;
    editForm.reset();
    editForm.clearErrors();
    editForm.name = department.name;
    editForm.desc = department.desc || '';
    isEditDialogOpen.value = true;
};

// Handle edit form submission
const updateDepartment = () => {
    if (!selectedDepartment.value) return;
    editForm.put(`/departments/${selectedDepartment.value.DepartmentID}`, {
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
                            v-model="createForm.name" @input="createForm.errors.name = null" />
                        <span v-if="createForm.errors.name" class="text-red-500 text-sm">{{
                            createForm.errors.name }}</span>
                    </div>

                    <div class="grid w-full max-w-sm items-center gap-1.5 mt-2">
                        <Label for="department_description">Description</Label>
                        <Input id="department_description" type="text" placeholder="Input Department description ..."
                            v-model="createForm.desc" @input="createForm.errors.desc = null" />
                        <span v-if="createForm.errors.desc" class="text-red-500 text-sm">{{
                            createForm.errors.desc }}</span>
                    </div>
                    <Button class="mt-2 w-full" @click="submitDepartment" :disabled="createForm.processing">
                        <CirclePlus class="w-4 h-4 mr-2" />
                        {{ createForm.processing ? 'Submitting...' : 'Submit Department' }}
                    </Button>
                </div>

                <!-- Placeholder cards -->
                <div
                    class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/2 dark:border-gray-800 p-3">
                    <PlaceholderPattern />
                </div>
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
                                <DropdownMenu>
                                    <DropdownMenuTrigger><Ellipsis /></DropdownMenuTrigger>
                                    <DropdownMenuContent>
                                        <DropdownMenuLabel>Select Action</DropdownMenuLabel>
                                        <DropdownMenuSeparator />
                                        <DropdownMenuItem @click="openEditDialog(department)">
                                            <SquarePen class="w-4 h-4 mr-2" />Edit
                                        </DropdownMenuItem>
                                        <DropdownMenuItem><X class="w-4 h-4 mr-2" />Delete</DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
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
                        <Input id="edit_name" v-model="editForm.name" class="col-span-3"
                            @input="editForm.errors.name = null" />
                        <span v-if="editForm.errors.name" class="text-red-500 text-sm col-start-2 col-span-3">{{
                            editForm.errors.name }}</span>
                    </div>
                    <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="edit_desc" class="text-right">Description</Label>
                        <Input id="edit_desc" v-model="editForm.desc" class="col-span-3"
                            @input="editForm.errors.desc = null" />
                        <span v-if="editForm.errors.desc" class="text-red-500 text-sm col-start-2 col-span-3">{{
                            editForm.errors.desc }}</span>
                    </div>
                </div>
                <DialogFooter>
                    <Button type="button" variant="outline" @click="isEditDialogOpen = false">Cancel</Button>
                    <Button type="submit" @click="updateDepartment" :disabled="editForm.processing">
                        {{ editForm.processing ? 'Saving...' : 'Save Changes' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>