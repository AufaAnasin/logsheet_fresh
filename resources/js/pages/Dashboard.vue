<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import PlaceholderPattern from '../components/PlaceholderPattern.vue';


// Input for Add Department

import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'


// button 
import { CirclePlus } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];
</script>

<template>

    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="grid auto-rows-min gap-4 md:grid-cols-3">

                <!-- For adding new department -->
                <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border p-3">
                    <p><b>Add Department</b></p>
                    <div class="grid w-full max-w-sm items-center gap-1.5 mt-2">
                        <Label for="name">Name</Label>
                        <Input id="department_name" type="text" placeholder="Put Department name ..." />
                    </div>


                    <div class="grid w-full max-w-sm items-center gap-1.5 mt-2">
                        <Label for="description">Description</Label>
                        <Input id="department_description" type="text" placeholder="Input Department description ..." />
                    </div>
                    <Button class="mt-2">
                        <CirclePlus class="w-4 h-4 mr-2" /> Submit Department
                    </Button>
                </div>

                <!-- end of For adding new department -->




                <div
                    class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border p-3">
                    <PlaceholderPattern />
                </div>
                <div
                    class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border p-3">
                    <PlaceholderPattern />
                </div>
            </div>





            <div
                class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border md:min-h-min">
                <PlaceholderPattern />
            </div>
        </div>
    </AppLayout>
</template>
