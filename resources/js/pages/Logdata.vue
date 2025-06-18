<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';

const breadcrumbs = [
    { title: 'Log Data', href: '/logdata' },
];
</script>

<template>
    <Head title="Log Data" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            Test
        </div>
    </AppLayout>
</template>