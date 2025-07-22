<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { CirclePlus, Ellipsis, SquarePen, X } from 'lucide-vue-next';
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
import PlaceholderPattern from '@/components/PlaceholderPattern.vue';
import { ref } from 'vue';

interface Department {
    DepartmentID: number;
    name: string;
    desc: string | null;
}

defineProps<{
    departments: Department[];
    userRole: string | null;
    showTableOnly?: boolean;
}>();

// Utility to convert null to undefined
const nullToUndefined = (value: string | null): string | undefined => value ?? undefined;

// Form for adding new department
const createDepartmentForm = useForm({
    name: '',
    desc: undefined as string | undefined,
});

// Handle create department form submission
const submitDepartment = () => {
    createDepartmentForm.post('/departments', {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => createDepartmentForm.reset(),
        onError: () => { },
    });
};

// Edit department dialog state
const isEditDepartmentDialogOpen = ref(false);
const selectedDepartment = ref<Department | null>(null);

// Form for editing department
const editDepartmentForm = useForm({
    name: '',
    desc: undefined as string | undefined,
});

// Open edit department dialog
const openEditDepartmentDialog = (department: Department) => {
    selectedDepartment.value = { ...department };
    editDepartmentForm.reset();
    editDepartmentForm.clearErrors();
    editDepartmentForm.name = department.name;
    editDepartmentForm.desc = nullToUndefined(department.desc);
    isEditDepartmentDialogOpen.value = true;
};

// Handle edit department form submission
const updateDepartment = () => {
    if (!selectedDepartment.value) return;
    editDepartmentForm.put(`/departments/${selectedDepartment.value.DepartmentID}`, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            isEditDepartmentDialogOpen.value = false;
        },
        onError: () => { },
    });
};

// Delete department dialog state
const isDeleteDepartmentDialogOpen = ref(false);
const departmentToDelete = ref<Department | null>(null);

// Form for deleting department
const deleteDepartmentForm = useForm({});

// Open delete department dialog
const openDeleteDepartmentDialog = (department: Department) => {
    departmentToDelete.value = { ...department };
    isDeleteDepartmentDialogOpen.value = true;
};

// Handle delete department confirmation
const deleteDepartment = () => {
    if (!departmentToDelete.value) return;
    deleteDepartmentForm.delete(`/departments/${departmentToDelete.value.DepartmentID}`, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            isDeleteDepartmentDialogOpen.value = false;
            departmentToDelete.value = null;
        },
        onError: () => { },
    });
};
</script>

<template>
    <div v-if="!showTableOnly && userRole === 'SuperUser'"
        class="relative flex flex-col min-h-full rounded-xl border border-gray-200 dark:border-gray-800 p-3">
        <p><b>Add Department</b></p>
        <div class="grid w-full max-w-sm items-center gap-1.5 mt-2">
            <Label for="department_name">Name</Label>
            <Input id="department_name" type="text" placeholder="Put Department name ..."
                v-model="createDepartmentForm.name" @input="createDepartmentForm.errors.name = undefined" />
            <span v-if="createDepartmentForm.errors.name" class="text-red-500 text-sm">{{
                createDepartmentForm.errors.name }}</span>
        </div>
        <div class="grid w-full max-w-sm items-center gap-1.5 mt-2">
            <Label for="department_description">Description</Label>
            <Input id="department_description" type="text" placeholder="Input Department description ..."
                v-model="createDepartmentForm.desc" @input="createDepartmentForm.errors.desc = undefined" />
            <span v-if="createDepartmentForm.errors.desc" class="text-red-500 text-sm">{{
                createDepartmentForm.errors.desc }}</span>
        </div>
        <Button class="mt-auto w-full" @click="submitDepartment" :disabled="createDepartmentForm.processing">
            <CirclePlus class="w-4 h-4 mr-2" />
            {{ createDepartmentForm.processing ? 'Submitting...' : 'Submit Department' }}
        </Button>
    </div>
    <div v-else-if="!showTableOnly"
        class="relative flex flex-col min-h-full rounded-xl border border-gray-200 dark:border-gray-800 p-3">
        <PlaceholderPattern />
    </div>
    <div v-if="showTableOnly" class="relative flex-1 rounded-xl border border-sidebar-border/2 dark:border-gray-800 p-3">
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
                <TableRow v-if="departments.length === 0">
                    <TableCell colspan="4" class="text-center">No departments available.</TableCell>
                </TableRow>
                <TableRow v-else v-for="department in departments" :key="department.DepartmentID">
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
                    <Input id="edit_department_name" type="text" v-model="editDepartmentForm.name" class="col-span-3"
                        @input="editDepartmentForm.errors.name = undefined" />
                    <span v-if="editDepartmentForm.errors.name" class="text-red-500 text-sm col-start-2 col-span-3">{{
                        editDepartmentForm.errors.name }}</span>
                </div>
                <div class="grid grid-cols-4 items-center gap-4">
                    <Label for="edit_department_desc" class="text-right">Description</Label>
                    <Input id="edit_department_desc" type="text" v-model="editDepartmentForm.desc" class="col-span-3"
                        @input="editDepartmentForm.errors.desc = undefined" />
                    <span v-if="editDepartmentForm.errors.desc" class="text-red-500 text-sm col-start-2 col-span-3">{{
                        editDepartmentForm.errors.desc }}</span>
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
                    This action cannot be undone. This will permanently delete the department "{{
                        departmentToDelete?.name }}" from the database, if no areas are associated.
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
</template>