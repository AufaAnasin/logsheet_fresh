<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import DepartmentManager from '@/components/DepartmentManager.vue';
import AreaManager from '@/components/AreaManager.vue';
import ComponentManager from '@/components/ComponentManager.vue';
import LogConfigManager from '@/components/LogConfigManager.vue';
import { onMounted } from 'vue';


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

interface Flash {
    success?: string | null;
    error?: string | null;
}

interface PageProps {
    flash?: Flash;
    errors?: Record<string, string>;
    departments?: Department[] | null;
    areas?: Area[] | null;
    components?: Component[] | null;
    logConfigurations?: LogConfiguration[] | null;
    userRole?: string | null;
}

const props = defineProps<{
    departments: Department[] | null;
    areas: Area[] | null;
    components: Component[] | null;
    logConfigurations: LogConfiguration[] | null;
}>();

const isLoading = ref(true);

// Safe arrays
const safeDepartments = computed(() => Array.isArray(props.departments) ? props.departments : []);
const safeAreas = computed(() => Array.isArray(props.areas) ? props.areas : []);
const safeComponents = computed(() => Array.isArray(props.components) ? props.components : []);
const safeLogConfigurations = computed(() => Array.isArray(props.logConfigurations) ? props.logConfigurations : []);

// Flash messages
const page = usePage<{ props: PageProps }>();
const flash = computed(() => {
    const flashObj: Flash = page.props.flash ?? { success: null, error: null };
    return {
        success: flashObj.success ?? null,
        error: page.props.errors ? Object.values(page.props.errors).join(', ') ?? null : flashObj.error ?? null,
    };
});

// Breadcrumbs
const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
];

// User role
const userRole = page.props.userRole;

// Reference to LogConfigManager
const logConfigManagerRef = ref<InstanceType<typeof LogConfigManager> | null>(null);

// Handle edit log config event
const handleEditLogConfig = (component: Component) => {
    console.log('Handling editLogConfig event for component:', component);
    if (logConfigManagerRef.value) {
        logConfigManagerRef.value.openEditLogConfigDialog(component);
    } else {
        console.error('LogConfigManager ref is not available');
    }
};

onMounted(() => {
    console.log('Dashboard mounted. Props:', {
        departments: props.departments,
        areas: props.areas,
        components: props.components,
        logConfigurations: props.logConfigurations,
    });
    isLoading.value = false;
});
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div v-if="isLoading" class="flex h-full items-center justify-center">
            <p>Loading...</p>
        </div>
        <div v-else class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Flash Messages -->
            <div v-if="flash.success" class="rounded-xl border border-green-200 bg-green-50 p-3 text-green-700">
                {{ flash.success }}
            </div>
            <div v-if="flash.error" class="rounded-xl border border-red-200 bg-red-50 p-3 text-red-700">
                {{ flash.error }}
            </div>

            <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                <DepartmentManager :departments="safeDepartments" :user-role="userRole" />
                <AreaManager :departments="safeDepartments" :areas="safeAreas" />
                <ComponentManager :areas="safeAreas" :components="safeComponents" @edit-log-config="handleEditLogConfig" />
            </div>

            <DepartmentManager :departments="safeDepartments" :user-role="userRole" show-table-only />
            <AreaManager :departments="safeDepartments" :areas="safeAreas" show-table-only />
            <ComponentManager :areas="safeAreas" :components="safeComponents" show-table-only @edit-log-config="handleEditLogConfig" />
            <LogConfigManager
                ref="logConfigManagerRef"
                :departments="safeDepartments"
                :areas="safeAreas"
                :log-configurations="safeLogConfigurations"
            />
        </div>
    </AppLayout>
</template>