<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { ChartColumn, LayoutGrid, TextCursorInput, UserPen } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

// Define interface for page props
interface PageProps {
  userRole?: string | null;
}

// Access user role from page props
const page = usePage<{ props: PageProps }>();
const userRole = page.props.userRole || '';

// Define all navigation items
const allNavItems: NavItem[] = [
  {
    title: 'Dashboard',
    href: '/dashboard',
    icon: LayoutGrid,
  },
  {
    title: 'Log Data',
    href: '/logdata',
    icon: TextCursorInput,
  },
  {
    title: 'User Management',
    href: '/userlist',
    icon: UserPen,
  },
  {
    title: 'Analytics',
    href: '/analytics',
    icon: ChartColumn,
  },
];

// Filter navigation items based on user role
const mainNavItems = userRole === 'Operator'
  ? allNavItems.filter(item => item.title === 'Log Data')
  : allNavItems;

const footerNavItems: NavItem[] = [];
</script>

<template>
  <Sidebar collapsible="icon" variant="inset">
    <SidebarHeader>
      <SidebarMenu>
        <SidebarMenuItem>
          <SidebarMenuButton size="lg" as-child>
            <Link :href="route('dashboard')">
              <AppLogo />
            </Link>
          </SidebarMenuButton>
        </SidebarMenuItem>
      </SidebarMenu>
    </SidebarHeader>

    <SidebarContent>
      <NavMain :items="mainNavItems" />
    </SidebarContent>

    <SidebarFooter>
      <NavFooter :items="footerNavItems" />
      <NavUser />
    </SidebarFooter>
  </Sidebar>
  <slot />
</template>