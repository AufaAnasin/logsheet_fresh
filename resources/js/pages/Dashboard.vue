<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { CirclePlus, Ellipsis, SquarePen, X } from 'lucide-vue-next';
import PlaceholderPattern from '../components/PlaceholderPattern.vue';

// table
import {
    Table,
    TableBody,
    TableCaption,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

// dropdown menu

import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'

// Define department interface
interface Department {
    DepartmentID: number;
    name: string;
    desc: string | null;
}

// Receive departments from Laravel
defineProps<{
    departments: Department[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

// Initialize Inertia form
const form = useForm({
    name: '',
    desc: '',
});

// Handle form submission
const submitDepartment = () => {
    form.post('/departments', {
        preserveState: true,
        onSuccess: () => {
            form.reset();
            alert('Department created successfully!');
        },
        onError: (errors) => {
            console.error(errors);
            // Display specific validation error or a generic message
            const errorMessage = errors.name || 'Failed to create department. Check the form inputs.';
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
                            v-model="form.name" @input="form.errors.name = null" />
                        <span v-if="form.errors.name" class="text-red-500 text-sm">{{ form.errors.name }}</span>
                    </div>

                    <div class="grid w-full max-w-sm items-center gap-1.5 mt-2">
                        <Label for="department_description">Description</Label>
                        <Input id="department_description" type="text" placeholder="Input Department description ..."
                            v-model="form.desc" @input="form.errors.desc = null" />
                        <span v-if="form.errors.desc" class="text-red-500 text-sm">{{ form.errors.desc }}</span>
                    </div>
                    <Button class="mt-2 w-full" @click="submitDepartment" :disabled="form.processing">
                        <CirclePlus class="w-4 h-4 mr-2" />
                        {{ form.processing ? 'Submitting...' : 'Submit Department' }}
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
                        <TableRow v-for="department in departments" :key="department.DepartmentID">
                            <TableCell class="font-medium">{{ department.DepartmentID }}</TableCell>
                            <TableCell>{{ department.name }}</TableCell>
                            <TableCell>{{ department.desc || 'No description' }}</TableCell>
                            <TableCell>
                                <DropdownMenu>
                                    <DropdownMenuTrigger><Ellipsis /></DropdownMenuTrigger>
                                    <DropdownMenuContent>
                                        <DropdownMenuLabel>Select Action</DropdownMenuLabel>
                                        <DropdownMenuSeparator />
                                        <DropdownMenuItem><SquarePen />Edit</DropdownMenuItem>
                                        <DropdownMenuItem><X />Delete</DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </div>
    </AppLayout>
</template>