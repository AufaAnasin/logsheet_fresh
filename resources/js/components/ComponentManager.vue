<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
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
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
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
import { ref, watch } from 'vue';

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

const props = defineProps<{
    areas: Area[];
    components: Component[];
    showTableOnly?: boolean;
}>();

// Form for adding new component
const createComponentForm = useForm({
    name: '',
    AreaID: undefined as number | undefined,
    desc: undefined as string | undefined,
});

// Combobox state for component area selection
const selectedComponentArea = ref<Area | null>(null);

// Sync AreaID with form
watch(selectedComponentArea, (newArea) => {
    createComponentForm.AreaID = newArea ? newArea.AreaID : undefined;
    createComponentForm.errors.AreaID = undefined;
});

// Handle component form submission
const submitComponent = () => {
    if (!createComponentForm.AreaID) {
        createComponentForm.errors.AreaID = 'Please select an area.';
        return;
    }
    createComponentForm.post('/components', {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            createComponentForm.reset();
            selectedComponentArea.value = null;
        },
        onError: () => { },
    });
};

// Edit component dialog state
const isEditComponentDialogOpen = ref(false);
const selectedComponent = ref<Component | null>(null);

// Form for editing component
const editComponentForm = useForm({
    name: '',
    AreaID: undefined as number | undefined,
    desc: undefined as string | undefined,
});

// Combobox state for edit component area selection
const selectedEditComponentArea = ref<Area | null>(null);

// Sync AreaID with edit form
watch(selectedEditComponentArea, (newArea) => {
    editComponentForm.AreaID = newArea ? newArea.AreaID : undefined;
    editComponentForm.errors.AreaID = undefined;
});

// Utility to convert null to undefined
const nullToUndefined = (value: string | null): string | undefined => value ?? undefined;

// Open edit component dialog
const openEditComponentDialog = (component: Component) => {
    selectedComponent.value = { ...component };
    editComponentForm.reset();
    editComponentForm.clearErrors();
    editComponentForm.name = component.name;
    editComponentForm.desc = nullToUndefined(component.desc);
    editComponentForm.AreaID = component.AreaID;
    selectedEditComponentArea.value = props.areas.find(
        (area) => area.AreaID === component.AreaID
    ) || null;
    isEditComponentDialogOpen.value = true;
};

// Handle edit component form submission
const updateComponent = () => {
    if (!selectedComponent.value || !editComponentForm.AreaID) return;
    editComponentForm.put(`/components/${selectedComponent.value.ComponentID}`, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            isEditComponentDialogOpen.value = false;
            selectedEditComponentArea.value = null;
        },
        onError: () => { },
    });
};

// Delete component dialog state
const isDeleteComponentDialogOpen = ref(false);
const componentToDelete = ref<Component | null>(null);

// Form for deleting component
const deleteComponentForm = useForm({});

// Open delete component dialog
const openDeleteComponentDialog = (component: Component) => {
    componentToDelete.value = { ...component };
    isDeleteComponentDialogOpen.value = true;
};

// Handle delete component confirmation
const deleteComponent = () => {
    if (!componentToDelete.value) return;
    deleteComponentForm.delete(`/components/${componentToDelete.value.ComponentID}`, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            isDeleteComponentDialogOpen.value = false;
            componentToDelete.value = null;
        },
        onError: () => { },
    });
};

// Emit event for log configuration
const emit = defineEmits<{
    (e: 'edit-log-config', component: Component): void;
}>();

// Handle log config click with debug
const handleEditLogConfig = (component: Component) => {
    console.log('Emitting edit-log-config for component:', component);
    emit('edit-log-config', component);
};
</script>

<template>
    <div v-if="!showTableOnly"
        class="relative flex flex-col min-h-full rounded-xl border border-gray-200 dark:border-gray-800 p-3">
        <p><b>Create Component</b></p>
        <div class="grid w-full max-w-sm items-center gap-1.5 mt-2">
            <Label for="component_name">Component Name</Label>
            <Input id="component_name" type="text" placeholder="Put Component name ..."
                v-model="createComponentForm.name" @input="createComponentForm.errors.name = undefined" />
            <span v-if="createComponentForm.errors.name" class="text-red-500 text-sm">{{
                createComponentForm.errors.name }}</span>
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
                        <ComboboxInput class="focus-visible:ring-0 border-0 border-b rounded-none h-10"
                            placeholder="Search area..." />
                        <span class="absolute start-0 inset-y-0 flex items-center justify-center px-3">
                            <Search class="size-4 text-muted-foreground" />
                        </span>
                    </div>
                    <ComboboxEmpty>No area found.</ComboboxEmpty>
                    <ComboboxGroup>
                        <ComboboxItem v-for="area in areas" :key="area.AreaID" :value="area">
                            {{ area.name }}
                            <ComboboxItemIndicator>
                                <Check
                                    :class="cn('ml-auto h-4 w-4', selectedComponentArea?.AreaID === area.AreaID ? 'opacity-100' : 'opacity-0')" />
                            </ComboboxItemIndicator>
                        </ComboboxItem>
                    </ComboboxGroup>
                </ComboboxList>
            </Combobox>
            <span v-if="createComponentForm.errors.AreaID" class="text-red-500 text-sm">{{
                createComponentForm.errors.AreaID }}</span>
        </div>
        <div class="grid w-full max-w-sm items-center gap-1.5 mt-2">
            <Label for="component_description">Description</Label>
            <Input id="component_description" type="text" placeholder="Put Component description ..."
                v-model="createComponentForm.desc" @input="createComponentForm.errors.desc = undefined" />
            <span v-if="createComponentForm.errors.desc" class="text-red-500 text-sm">{{
                createComponentForm.errors.desc }}</span>
        </div>
        <Button class="mt-3 w-full" @click="submitComponent" :disabled="createComponentForm.processing">
            <CirclePlus class="w-4 h-4 mr-2" />
            {{ createComponentForm.processing ? 'Submitting...' : 'Create Component' }}
        </Button>
    </div>
    <div v-if="showTableOnly" class="relative flex-1 rounded-xl border border-sidebar-border/2 dark:border-gray-800 p-3">
        <p><b>List All Components</b></p>
        <Table>
            <TableCaption>A list of components.</TableCaption>
            <TableHeader>
                <TableRow>
                    <TableHead class="w-[100px]">ID</TableHead>
                    <TableHead>Name</TableHead>
                    <TableHead>Area</TableHead>
                    <TableHead>Description</TableHead>
                    <TableHead>Action</TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                <TableRow v-if="components.length === 0">
                    <TableCell colspan="5" class="text-center">No components available.</TableCell>
                </TableRow>
                <TableRow v-else v-for="component in components" :key="component.ComponentID">
                    <TableCell class="font-medium">{{ component.ComponentID }}</TableCell>
                    <TableCell>{{ component.name }}</TableCell>
                    <TableCell>{{ component.area_name || 'No area' }}</TableCell>
                    <TableCell>{{ component.desc || 'No description' }}</TableCell>
                    <TableCell>
                        <div class="flex items-center space-x-2">
                            <DropdownMenu>
                                <DropdownMenuTrigger>
                                    <Ellipsis />
                                </DropdownMenuTrigger>
                                <DropdownMenuContent>
                                    <DropdownMenuLabel>Select Action</DropdownMenuLabel>
                                    <DropdownMenuSeparator />
                                    <DropdownMenuItem @click="openEditComponentDialog(component)">
                                        <SquarePen class="w-4 h-4 mr-2" />Edit
                                    </DropdownMenuItem>
                                    <DropdownMenuItem @click="openDeleteComponentDialog(component)">
                                        <X class="w-4 h-4 mr-2" />Delete
                                    </DropdownMenuItem>
                                    <DropdownMenuItem @click="handleEditLogConfig(component)">
                                        <SquarePen class="w-4 h-4 mr-2" />Log Configuration
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    </TableCell>
                </TableRow>
            </TableBody>
        </Table>
    </div>

    <!-- Edit Component Dialog -->
    <Dialog v-model:open="isEditComponentDialogOpen">
        <DialogContent class="sm:max-w-[425px]">
            <DialogHeader>
                <DialogTitle>Edit Component</DialogTitle>
                <DialogDescription>
                    Make changes to the component details here. Click save when you're done.
                </DialogDescription>
            </DialogHeader>
            <div class="grid gap-4 py-4">
                <div class="grid grid-cols-4 items-center gap-4">
                    <Label for="edit_component_name" class="text-right">Name</Label>
                    <Input id="edit_component_name" type="text" v-model="editComponentForm.name" class="col-span-3"
                        @input="editComponentForm.errors.name = undefined" />
                    <span v-if="editComponentForm.errors.name" class="text-red-500 text-sm col-start-2 col-span-3">{{
                        editComponentForm.errors.name }}</span>
                </div>
                <div class="grid grid-cols-4 items-center gap-4">
                    <Label for="edit_component_area" class="text-right">Area</Label>
                    <div class="col-span-3">
                        <Combobox v-model="selectedEditComponentArea" class="w-full" by="AreaID">
                            <ComboboxAnchor as-child>
                                <ComboboxTrigger as-child>
                                    <Button variant="outline" class="justify-between w-full">
                                        {{ selectedEditComponentArea?.name ?? 'Select area...' }}
                                        <ChevronsUpDown class="ml-2 h-4 w-4 shrink-0 opacity-50" />
                                    </Button>
                                </ComboboxTrigger>
                            </ComboboxAnchor>
                            <ComboboxList>
                                <div class="relative w-full max-w-sm items-center">
                                    <ComboboxInput class="focus-visible:ring-0 border-0 border-b rounded-none h-10"
                                        placeholder="Search area..." />
                                    <span class="absolute start-0 inset-y-0 flex items-center justify-center px-3">
                                        <Search class="size-4 text-muted-foreground" />
                                    </span>
                                </div>
                                <ComboboxEmpty>No area found.</ComboboxEmpty>
                                <ComboboxGroup>
                                    <ComboboxItem v-for="area in areas" :key="area.AreaID" :value="area">
                                        {{ area.name }}
                                        <ComboboxItemIndicator>
                                            <Check
                                                :class="cn('ml-auto h-4 w-4', selectedEditComponentArea?.AreaID === area.AreaID ? 'opacity-100' : 'opacity-0')" />
                                        </ComboboxItemIndicator>
                                    </ComboboxItem>
                                </ComboboxGroup>
                            </ComboboxList>
                        </Combobox>
                        <span v-if="editComponentForm.errors.AreaID" class="text-red-500 text-sm">{{
                            editComponentForm.errors.AreaID }}</span>
                    </div>
                </div>
                <div class="grid grid-cols-4 items-center gap-4">
                    <Label for="edit_component_desc" class="text-right">Description</Label>
                    <Input id="edit_component_desc" type="text" v-model="editComponentForm.desc" class="col-span-3"
                        @input="editComponentForm.errors.desc = undefined" />
                    <span v-if="editComponentForm.errors.desc" class="text-red-500 text-sm col-start-2 col-span-3">{{
                        editComponentForm.errors.desc }}</span>
                </div>
            </div>
            <DialogFooter>
                <Button type="button" variant="outline" @click="isEditComponentDialogOpen = false">Cancel</Button>
                <Button type="submit" @click="updateComponent" :disabled="editComponentForm.processing">
                    {{ editComponentForm.processing ? 'Saving...' : 'Save Changes' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- Delete Component Alert Dialog -->
    <AlertDialog v-model:open="isDeleteComponentDialogOpen">
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle>Are you sure?</AlertDialogTitle>
                <AlertDialogDescription>
                    This action cannot be undone. This will permanently delete the component "{{
                        componentToDelete?.name }}" from the database, if no log data is associated.
                </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel @click="isDeleteComponentDialogOpen = false">Cancel</AlertDialogCancel>
                <AlertDialogAction @click="deleteComponent" :disabled="deleteComponentForm.processing">
                    {{ deleteComponentForm.processing ? 'Deleting...' : 'Delete' }}
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>