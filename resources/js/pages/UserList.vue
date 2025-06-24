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
                  Make a user with a default password ("Logsheet123#"). Click save when you're done.
                </DialogDescription>
              </DialogHeader>
              <form @submit.prevent="createUser" class="grid gap-4 py-4">
                <div class="grid grid-cols-4 items-center gap-4">
                  <Label for="name" class="text-right">Name</Label>
                  <Input id="name" v-model="form.name" placeholder="Enter user name" class="col-span-3" />
                </div>
                <div class="grid grid-cols-4 items-center gap-4">
                  <Label for="email" class="text-right">Email</Label>
                  <Input id="email" v-model="form.email" placeholder="Enter BAI email" class="col-span-3" />
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
                  </div>
                </div>
                <DialogFooter>
                  <Button type="submit" :disabled="form.processing">Save</Button>
                </DialogFooter>
              </form>
            </DialogContent>
          </Dialog>
        </div>
        <div class="mt-4">
          <p class="text-gray-600 dark:text-gray-400">User table will be displayed here.</p>
        </div>
      </div>
    </AppLayout>
  </template>

  <script setup lang="ts">
  import AppLayout from '@/layouts/AppLayout.vue';
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
  import { Plus } from 'lucide-vue-next';
  import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectLabel,
    SelectTrigger,
    SelectValue,
  } from '@/components/ui/select';

  defineProps<{
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
  </script>