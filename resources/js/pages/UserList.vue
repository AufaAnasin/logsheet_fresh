<template>
  <Head title="User Management" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
      <div class="flex justify-between items-center">
        <Dialog>
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
                  <Select v-model="form.role" class="w-full">
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
                  <Select v-model="form.department_id" class="w-full">
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
                <Button type="submit" :disabled="form.processing">Save</Button>
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
                </TableRow>
                <TableRow v-if="!paginatedUsers.length">
                  <TableCell colspan="5" class="text-center">No results found.</TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </div>
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
import { Head, useForm } from '@inertiajs/vue3';
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
import { Plus, Search } from 'lucide-vue-next';
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
import AppLayout from '@/layouts/AppLayout.vue';
import { computed, ref, watch } from 'vue';

interface User {
  id: string;
  name: string;
  email: string;
  role: string;
  department_name: string;
}

const props = defineProps<{
  users: User[];
  departments: { DepartmentID: number; name: string }[];
}>();

const breadcrumbs = [
  { title: 'User List', href: '/userlist' },
];

const form = useForm({
  name: '',
  email: '',
  role: null,
  department_id: null,
});

const createUser = () => {
  form.post(route('users.store'), {
    onSuccess: () => {
      form.reset();
    },
    onError: (errors) => {
      console.log('Errors:', errors);
    },
  });
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

// Reset currentPage to 1 when searchQuery changes to avoid empty pages
watch(searchQuery, () => {
  currentPage.value = 1;
});
</script>