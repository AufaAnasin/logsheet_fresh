<template>
  <Head title="User Management" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
      <!-- Flash Messages -->
      <div v-if="flash.success" class="rounded-xl border border-green-200 bg-green-50 p-3 text-green-700">
        {{ flash.success }}
      </div>
      <div v-if="flash.error" class="rounded-xl border border-red-200 bg-red-50 p-3 text-red-700">
        {{ flash.error }}
      </div>

      <div class="flex justify-between items-center">
        <Dialog v-model:open="isCreateDialogOpen">
          <DialogTrigger as-child>
            <Button variant="outline" class="w-auto flex items-center gap-2">
              <Plus class="h-4 w-4" /> Create User
            </Button>
          </DialogTrigger>
          <DialogContent class="sm:max-w-[425px]">
            <DialogHeader>
              <DialogTitle>Create User</DialogTitle>
              <DialogDescription>
                Make a user with a default password. Click save when you're done.
              </DialogDescription>
            </DialogHeader>
            <form @submit.prevent="createUser" class="grid gap-4 py-4">
              <div class="grid grid-cols-4 items-center gap-4">
                <Label for="name" class="text-right">Name</Label>
                <div class="col-span-3">
                  <Input id="name" v-model="form.name" placeholder="Enter user name" class="w-full" />
                  <p v-if="form.errors.name" class="text-red-500 text-sm mt-1">{{ form.errors.name }}</p>
                </div>
              </div>
              <div class="grid grid-cols-4 items-center gap-4">
                <Label for="email" class="text-right">Email</Label>
                <div class="col-span-3">
                  <Input id="email" v-model="form.email" placeholder="Enter BAI email" class="w-full" />
                  <p v-if="form.errors.email" class="text-red-500 text-sm mt-1">{{ form.errors.email }}</p>
                </div>
              </div>
              <div class="grid grid-cols-4 items-center gap-4">
                <Label for="role" class="text-right">Role</Label>
                <div class="col-span-3">
                  <Select v-model="form.role" :disabled="isRoleDisabled">
                    <SelectTrigger class="w-full">
                      <SelectValue placeholder="Select a Role" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectGroup>
                        <SelectLabel>Role</SelectLabel>
                        <SelectItem value="SuperUser">Super Admin</SelectItem>
                        <SelectItem value="DepartmentAdmin">Admin</SelectItem>
                        <SelectItem value="Operator">Operator</SelectItem>
                      </SelectGroup>
                    </SelectContent>
                  </Select>
                  <p v-if="form.errors.role" class="text-red-500 text-sm mt-1">{{ form.errors.role }}</p>
                </div>
              </div>
              <div class="grid grid-cols-4 items-center gap-4">
                <Label for="department" class="text-right">Department</Label>
                <div class="col-span-3">
                  <Select v-model="form.department_id" :disabled="isDepartmentDisabled" class="w-full">
                    <SelectTrigger class="w-full">
                      <SelectValue placeholder="Select a Department" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectGroup>
                        <SelectLabel>Departments</SelectLabel>
                        <SelectItem v-for="department in departments" :key="department.DepartmentID" :value="department.DepartmentID">
                          {{ department.name }}
                        </SelectItem>
                      </SelectGroup>
                    </SelectContent>
                  </Select>
                  <p v-if="form.errors.department_id" class="text-red-500 text-sm mt-1">{{ form.errors.department_id }}</p>
                </div>
              </div>
              <DialogFooter>
                <Button type="submit" :disabled="form.processing"><Save />Save</Button>
              </DialogFooter>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      <!-- Table Section -->
      <div class="w-full">
        <div class="flex flex-col gap-4">
          <!-- Search Bar -->
          <div class="relative w-full max-w-sm items-center">
            <Input
              id="search"
              type="text"
              v-model="searchQuery"
              placeholder="Search by name or email..."
              class="pl-10"
            />
            <span class="absolute start-0 inset-y-0 flex items-center justify-center px-2">
              <Search class="size-6 text-muted-foreground" />
            </span>
          </div>
          <div class="overflow-x-auto rounded-md border">
            <Table class="min-w-full">
              <TableCaption>A list of users.</TableCaption>
              <TableHeader>
                <TableRow>
                  <TableHead class="w-[100px]">ID</TableHead>
                  <TableHead>Name</TableHead>
                  <TableHead>Email</TableHead>
                  <TableHead>Role</TableHead>
                  <TableHead>Department</TableHead>
                  <TableHead>Action</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow v-for="user in paginatedUsers" :key="user.id">
                  <TableCell class="font-medium">{{ user.id }}</TableCell>
                  <TableCell>{{ user.name }}</TableCell>
                  <TableCell>{{ user.email }}</TableCell>
                  <TableCell>
                    {{ user.role === 'SuperUser' ? 'Super Admin' : user.role === 'DepartmentAdmin' ? 'Admin' : 'Operator' }}
                  </TableCell>
                  <TableCell>{{ user.department_name }}</TableCell>
                  <TableCell>
                    <DropdownMenu>
                      <DropdownMenuTrigger as-child>
                        <Button variant="ghost">
                          <Ellipsis />
                        </Button>
                      </DropdownMenuTrigger>
                      <DropdownMenuContent class="w-56">
                        <DropdownMenuLabel>Manage User</DropdownMenuLabel>
                        <DropdownMenuSeparator />
                        <DropdownMenuGroup>
                          <DropdownMenuItem @click.stop="openEditDialog(user)">
                            <FilePenLine />
                            <span>Edit</span>
                          </DropdownMenuItem>
                          <DropdownMenuItem @click.stop="openDeleteDialog(user)">
                            <Delete />
                            <span>Delete</span>
                          </DropdownMenuItem>
                        </DropdownMenuGroup>
                      </DropdownMenuContent>
                    </DropdownMenu>
                  </TableCell>
                </TableRow>
                <TableRow v-if="!paginatedUsers.length">
                  <TableCell colspan="6" class="text-center">No results found.</TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </div>
          <!-- Edit Dialog -->
          <Dialog :open="isEditDialogOpen" @update:open="closeEditDialog">
            <DialogContent class="sm:max-w-[425px]">
              <DialogHeader>
                <DialogTitle>Edit User</DialogTitle>
                <DialogDescription>
                  Make changes to the user here. Click save when you're done.
                </DialogDescription>
              </DialogHeader>
              <form @submit.prevent="updateUser" class="grid gap-4 py-4">
                <div class="grid grid-cols-4 items-center gap-4">
                  <Label for="edit-name" class="text-right">Name</Label>
                  <div class="col-span-3">
                    <Input id="edit-name" v-model="editForm.name" placeholder="Enter user name" class="w-full" />
                    <p v-if="editForm.errors.name" class="text-red-500 text-sm mt-1">{{ editForm.errors.name }}</p>
                  </div>
                </div>
                <div class="grid grid-cols-4 items-center gap-4">
                  <Label for="edit-email" class="text-right">Email</Label>
                  <div class="col-span-3">
                    <Input id="edit-email" v-model="editForm.email" placeholder="Enter BAI email" class="w-full" />
                    <p v-if="editForm.errors.email" class="text-red-500 text-sm mt-1">{{ editForm.errors.email }}</p>
                  </div>
                </div>
                <div class="grid grid-cols-4 items-center gap-4">
                  <Label for="edit-role" class="text-right">Role</Label>
                  <div class="col-span-3">
                    <Select v-model="editForm.role" class="w-full">
                      <SelectTrigger class="w-full">
                        <SelectValue placeholder="Select a Role" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectGroup>
                          <SelectLabel>Role</SelectLabel>
                          <SelectItem value="SuperUser">Super Admin</SelectItem>
                          <SelectItem value="DepartmentAdmin">Admin</SelectItem>
                          <SelectItem value="Operator">Operator</SelectItem>
                        </SelectGroup>
                      </SelectContent>
                    </Select>
                    <p v-if="editForm.errors.role" class="text-red-500 text-sm mt-1">{{ editForm.errors.role }}</p>
                  </div>
                </div>
                <div class="grid grid-cols-4 items-center gap-4">
                  <Label for="edit-department" class="text-right">Department</Label>
                  <div class="col-span-3">
                    <Select v-model="editForm.department_id" class="w-full">
                      <SelectTrigger class="w-full">
                        <SelectValue placeholder="Select a Department" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectGroup>
                          <SelectLabel>Departments</SelectLabel>
                          <SelectItem v-for="department in departments" :key="department.DepartmentID" :value="department.DepartmentID">
                            {{ department.name }}
                          </SelectItem>
                        </SelectGroup>
                      </SelectContent>
                    </Select>
                    <p v-if="editForm.errors.department_id" class="text-red-500 text-sm mt-1">{{ editForm.errors.department_id }}</p>
                  </div>
                </div>
                <DialogFooter>
                  <Button type="submit" :disabled="editForm.processing">Save changes</Button>
                </DialogFooter>
              </form>
            </DialogContent>
          </Dialog>
          <!-- Delete Dialog -->
          <Dialog :open="isDeleteDialogOpen" @update:open="closeDeleteDialog">
            <DialogContent class="sm:max-w-[425px]">
              <DialogHeader>
                <DialogTitle>Delete User</DialogTitle>
                <DialogDescription>
                  Are you sure you want to delete {{ editForm.name || 'this user' }}? This action cannot be undone.
                </DialogDescription>
              </DialogHeader>
              <DialogFooter>
                <Button variant="outline" @click="closeDeleteDialog">Cancel</Button>
                <Button variant="destructive" @click="deleteUser(editForm.id)" :disabled="deleteForm.processing"><Delete />Delete</Button>
              </DialogFooter>
            </DialogContent>
          </Dialog>
          <Pagination v-slot="{ page }" :items-per-page="itemsPerPage" :total="filteredUsers.length" :default-page="1">
            <PaginationContent v-slot="{ items }">
              <PaginationPrevious @click="currentPage = Math.max(1, currentPage - 1)" :disabled="currentPage === 1" />
              <template v-for="(item, index) in items" :key="index">
                <PaginationItem
                  v-if="item.type === 'page'"
                  :value="item.value"
                  :is-active="item.value === page"
                  @click="currentPage = item.value"
                >
                  {{ item.value }}
                </PaginationItem>
              </template>
              <PaginationEllipsis :index="4" />
              <PaginationNext @click="currentPage = Math.min(totalPages, currentPage + 1)" :disabled="currentPage === totalPages" />
            </PaginationContent>
          </Pagination>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Plus, Search, Ellipsis, FilePenLine, Delete, Save } from 'lucide-vue-next';
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectLabel,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
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
  Pagination,
  PaginationContent,
  PaginationEllipsis,
  PaginationItem,
  PaginationNext,
  PaginationPrevious,
} from '@/components/ui/pagination';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuGroup,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import AppLayout from '@/layouts/AppLayout.vue';
import { computed, ref, watch } from 'vue';

interface User {
  id: string;
  name: string;
  email: string;
  role: string;
  department_name: string;
}

interface PageProps {
  flash?: { success?: string; error?: string };
  users: User[];
  departments: { DepartmentID: number; name: string }[];
  auth: { user: { id: string; name: string; email: string; role: string; DepartmentID: number | null } | null };
}

const props = defineProps<{
  users: User[];
  departments: { DepartmentID: number; name: string }[];
}>();

const breadcrumbs = [
  { title: 'User List', href: '/userlist' },
];

// Access authenticated user's role and department
const page = usePage<{ props: PageProps }>();
const userRole = computed(() => page.props.auth?.user?.role || '');
const userDepartmentId = computed(() => page.props.auth?.user?.DepartmentID || null);

// Create User Form
const isCreateDialogOpen = ref(false);
const form = useForm({
  name: '',
  email: '',
  role: userRole.value === 'DepartmentAdmin' ? 'Operator' : null as string | null,
  department_id: userRole.value === 'DepartmentAdmin' ? userDepartmentId.value : null as number | null,
});

// Disable role and department inputs for DepartmentAdmin
const isRoleDisabled = computed(() => userRole.value === 'DepartmentAdmin');
const isDepartmentDisabled = computed(() => userRole.value === 'DepartmentAdmin');

const createUser = () => {
  form.post(route('users.store'), {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      form.reset();
      isCreateDialogOpen.value = false;
      console.log('User created successfully, should redirect to /userlist');
    },
    onError: (errors) => {
      console.error('Create Errors:', errors);
      isCreateDialogOpen.value = true;
    },
  });
};

// Edit User Form
const editForm = useForm({
  id: null as string | null,
  name: '',
  email: '',
  role: null as string | null,
  department_id: null as number | null,
});

const isEditDialogOpen = ref(false);
const isDeleteDialogOpen = ref(false);

const openEditDialog = (user: User) => {
  editForm.id = user.id;
  editForm.name = user.name;
  editForm.email = user.email;
  editForm.role = user.role;
  editForm.department_id = props.departments.find(d => d.name === user.department_name)?.DepartmentID || null;
  setTimeout(() => {
    isEditDialogOpen.value = true;
  }, 100);
};

const closeEditDialog = () => {
  isEditDialogOpen.value = false;
  editForm.reset();
};

const updateUser = () => {
  if (editForm.id) {
    editForm.put(route('users.update', editForm.id), {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => {
        closeEditDialog();
      },
      onError: (errors) => {
        console.error('Update Errors:', errors);
      },
    });
  }
};

// Delete User Form
const deleteForm = useForm({});

const openDeleteDialog = (user: User) => {
  editForm.id = user.id;
  editForm.name = user.name;
  setTimeout(() => {
    isDeleteDialogOpen.value = true;
  }, 100);
};

const closeDeleteDialog = () => {
  isDeleteDialogOpen.value = false;
  editForm.reset();
};

const deleteUser = (id: string | null) => {
  if (id) {
    deleteForm.delete(route('users.destroy', id), {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => {
        closeDeleteDialog();
      },
      onError: (errors) => {
        console.error('Delete Errors:', errors);
      },
    });
  }
};

// Search and Pagination logic
const searchQuery = ref('');
const itemsPerPage = 8;
const currentPage = ref(1);

const filteredUsers = computed(() => {
  if (!searchQuery.value.trim()) {
    return props.users;
  }
  const query = searchQuery.value.toLowerCase();
  return props.users.filter(
    (user) =>
      user.name.toLowerCase().includes(query) ||
      user.email.toLowerCase().includes(query)
  );
});

const totalPages = computed(() => Math.ceil(filteredUsers.value.length / itemsPerPage));

const paginatedUsers = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage;
  const end = start + itemsPerPage;
  return filteredUsers.value.slice(start, end);
});

watch(searchQuery, () => {
  currentPage.value = 1;
});

// Access flash messages safely
const flash = computed(() => page.props.flash || {});
</script>