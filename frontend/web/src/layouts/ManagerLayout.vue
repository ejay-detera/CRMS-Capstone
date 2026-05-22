<script setup lang="ts">
import {
  Sidebar,
  SidebarContent,
  SidebarGroup,
  SidebarGroupContent,
  SidebarGroupLabel,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  SidebarMenuSub,
  SidebarMenuSubItem,
  SidebarMenuSubButton,
  SidebarProvider,
  SidebarTrigger,
  SidebarHeader,
  SidebarFooter,
} from "@/components/ui/sidebar";
import { Avatar, AvatarFallback } from "@/components/ui/avatar";
import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover";
import {
  LayoutDashboard,
  FileText,
  FilePlus2,
  Bell,
  LogOut,
  Search,
  User,
  ChevronDown,
} from "lucide-vue-next";
import { useRoute, useRouter } from "vue-router";
import { ref, watch, computed, onMounted } from "vue";
import { useAuth } from "@/composables/useAuth";
import logoUrl from "@/assets/sbsi logo.png";

const route  = useRoute();
const router = useRouter();
const { logout, hasPermission, refreshPermissions, user } = useAuth();

// Refresh permissions every time the layout mounts so sidebar items
// always reflect the latest permissions saved by an admin.
onMounted(() => refreshPermissions())

const contractPaths = ["/manager/contracts", "/manager/contract-requests"];
const contractsOpen = ref(contractPaths.includes(route.path));

watch(() => route.path, path => {
  if (contractPaths.includes(path)) contractsOpen.value = true;
});

const contractSubItems = computed(() => {
  const items = [];
  if (hasPermission('crms.contracts.view')) {
    items.push({ title: "All Contracts", url: "/manager/contracts", icon: FileText });
  }
  if (hasPermission('crms.contracts.create')) {
    items.push({ title: "Contract Requests", url: "/manager/contract-requests", icon: FilePlus2 });
  }
  return items;
});

// Dynamic user info derived from auth state
const displayUser = computed(() => {
  const u = user.value as any
  if (!u) return { name: 'Manager', role: 'Manager', initials: 'M', email: '' }
  const first = u.first_name ?? u.profile?.first_name ?? ''
  const last  = u.last_name  ?? u.profile?.last_name  ?? ''
  const name  = [first, last].filter(Boolean).join(' ') || u.email || 'Manager'
  const role  = u.role ?? u.profile?.role?.name ?? 'Manager'
  const initials = [first[0], last[0]].filter(Boolean).join('').toUpperCase() || 'M'
  return { name, role, initials, email: u.email ?? '' }
})

const searchQuery = ref("");

function lnk(active: boolean) {
  return active
    ? "bg-[#2F2F73] text-white"
    : "text-white/45 hover:text-white/80 hover:bg-white/10";
}
function ico(active: boolean) { return active ? "text-white" : "text-white/45"; }
function txt(active: boolean) { return active ? "text-white" : "text-white/45"; }
</script>

<template>
  <SidebarProvider class="flex h-screen">
    <!-- ── Sidebar ── -->
    <Sidebar
      collapsible="icon"
      class="border-r border-[#2F2F73] bg-[#252578] text-white shadow-sm"
      :style="{
        '--sidebar': '#252578',
        '--sidebar-foreground': '#FFFFFF',
        '--sidebar-accent': '#2F2F73',
        '--sidebar-accent-foreground': '#FFFFFF',
        '--sidebar-border': '#2F2F73',
      }"
    >
      <!-- Logo -->
      <SidebarHeader class="border-b border-white/10 px-4 py-5">
        <div class="flex items-center justify-center overflow-hidden">
          <img
            :src="logoUrl"
            alt="SBSI logo"
            class="h-12 w-44 object-contain group-data-[collapsible=icon]:w-10"
          />
        </div>
      </SidebarHeader>

      <!-- Navigation -->
      <SidebarContent class="px-3 py-3">

        <!-- Main group -->
        <SidebarGroup class="mb-1 p-0">
          <SidebarGroupLabel
            class="px-3 mb-1 text-[10px] font-semibold uppercase tracking-widest text-white/25 group-data-[collapsible=icon]:hidden"
          >Main</SidebarGroupLabel>
          <SidebarGroupContent>
            <SidebarMenu class="gap-0.5">
              <SidebarMenuItem>
                <SidebarMenuButton as-child :is-active="route.path === '/manager/dashboard'" class="h-auto p-0 rounded-lg">
                  <router-link
                    to="/manager/dashboard"
                    class="flex items-center gap-3 w-full px-3 py-2.5 rounded-lg transition-all duration-200 group-data-[collapsible=icon]:justify-center"
                    :class="lnk(route.path === '/manager/dashboard')"
                  >
                    <LayoutDashboard
                      class="w-4 h-4 shrink-0 group-data-[collapsible=icon]:w-5 group-data-[collapsible=icon]:h-5"
                      :class="ico(route.path === '/manager/dashboard')"
                    />
                    <span
                      class="text-sm font-medium flex-1 text-left group-data-[collapsible=icon]:hidden"
                      :class="txt(route.path === '/manager/dashboard')"
                    >Dashboard</span>
                  </router-link>
                </SidebarMenuButton>
              </SidebarMenuItem>
            </SidebarMenu>
          </SidebarGroupContent>
        </SidebarGroup>

        <!-- Contracts group -->
        <SidebarGroup class="mb-1 p-0">
          <SidebarGroupLabel
            class="px-3 mb-1 text-[10px] font-semibold uppercase tracking-widest text-white/25 group-data-[collapsible=icon]:hidden"
          >Contracts</SidebarGroupLabel>
          <SidebarGroupContent>
            <SidebarMenu class="gap-0.5">
              <SidebarMenuItem>
                <!-- Collapsible parent button -->
                <button
                  @click="contractsOpen = true; router.push('/manager/contracts')"
                  class="flex items-center gap-3 w-full px-3 py-2.5 rounded-lg transition-all duration-200 group-data-[collapsible=icon]:justify-center"
                  :class="lnk(contractPaths.includes(route.path))"
                >
                  <FileText
                    class="w-4 h-4 shrink-0 group-data-[collapsible=icon]:w-5 group-data-[collapsible=icon]:h-5"
                    :class="ico(contractPaths.includes(route.path))"
                  />
                  <span
                    class="text-sm font-medium flex-1 text-left group-data-[collapsible=icon]:hidden"
                    :class="txt(contractPaths.includes(route.path))"
                  >Contracts</span>
                  <ChevronDown
                    @click.stop="contractsOpen = !contractsOpen"
                    class="w-3.5 h-3.5 shrink-0 transition-transform duration-200 group-data-[collapsible=icon]:hidden"
                    :class="[contractsOpen ? 'rotate-180' : 'rotate-0', ico(contractPaths.includes(route.path))]"
                  />
                </button>

                <!-- Sub-items -->
                <SidebarMenuSub v-show="contractsOpen">
                  <SidebarMenuSubItem v-for="sub in contractSubItems" :key="sub.title">
                    <SidebarMenuSubButton as-child :is-active="route.path === sub.url" class="h-8">
                      <router-link
                        :to="sub.url"
                        class="flex items-center gap-2 w-full px-2 py-1.5 rounded-md text-sm transition-all duration-150"
                        :class="route.path === sub.url
                          ? 'text-white font-medium'
                          : 'text-white/40 hover:text-white/75'"
                      >
                        <component
                          :is="sub.icon"
                          class="w-3.5 h-3.5 shrink-0"
                          :class="route.path === sub.url ? 'text-white' : 'text-white/40'"
                        />
                        <span>{{ sub.title }}</span>
                      </router-link>
                    </SidebarMenuSubButton>
                  </SidebarMenuSubItem>
                </SidebarMenuSub>
              </SidebarMenuItem>
            </SidebarMenu>
          </SidebarGroupContent>
        </SidebarGroup>

        <!-- Vendor Management (Partners) group — shown only when permitted -->
        <SidebarGroup v-if="hasPermission('crms.partners.view')" class="mb-1 p-0">
          <SidebarGroupLabel
            class="px-3 mb-1 text-[10px] font-semibold uppercase tracking-widest text-white/25 group-data-[collapsible=icon]:hidden"
          >Vendor Management</SidebarGroupLabel>
          <SidebarGroupContent>
            <SidebarMenu class="gap-0.5">
              <SidebarMenuItem>
                <SidebarMenuButton as-child :is-active="route.path === '/manager/partners'" class="h-auto p-0 rounded-lg">
                  <router-link
                    to="/manager/partners"
                    class="flex items-center gap-3 w-full px-3 py-2.5 rounded-lg transition-all duration-200 group-data-[collapsible=icon]:justify-center"
                    :class="lnk(route.path === '/manager/partners')"
                  >
                    <svg
                      class="w-4 h-4 shrink-0 group-data-[collapsible=icon]:w-5 group-data-[collapsible=icon]:h-5"
                      :class="ico(route.path === '/manager/partners')"
                      viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"
                    >
                      <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                      <circle cx="9" cy="7" r="4" />
                      <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                      <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                    </svg>
                    <span
                      class="text-sm font-medium flex-1 text-left group-data-[collapsible=icon]:hidden"
                      :class="txt(route.path === '/manager/partners')"
                    >Business Partners</span>
                  </router-link>
                </SidebarMenuButton>
              </SidebarMenuItem>
            </SidebarMenu>
          </SidebarGroupContent>
        </SidebarGroup>

        <!-- General group -->
        <SidebarGroup class="mb-1 p-0">
          <SidebarGroupLabel
            class="px-3 mb-1 text-[10px] font-semibold uppercase tracking-widest text-white/25 group-data-[collapsible=icon]:hidden"
          >General</SidebarGroupLabel>
          <SidebarGroupContent>
            <SidebarMenu class="gap-0.5">
              <SidebarMenuItem>
                <SidebarMenuButton as-child :is-active="route.path === '/manager/notifications'" class="h-auto p-0 rounded-lg">
                  <router-link
                    to="/manager/notifications"
                    class="flex items-center gap-3 w-full px-3 py-2.5 rounded-lg transition-all duration-200 group-data-[collapsible=icon]:justify-center"
                    :class="lnk(route.path === '/manager/notifications')"
                  >
                    <Bell
                      class="w-4 h-4 shrink-0 group-data-[collapsible=icon]:w-5 group-data-[collapsible=icon]:h-5"
                      :class="ico(route.path === '/manager/notifications')"
                    />
                    <span
                      class="text-sm font-medium flex-1 text-left group-data-[collapsible=icon]:hidden"
                      :class="txt(route.path === '/manager/notifications')"
                    >Notifications</span>
                  </router-link>
                </SidebarMenuButton>
              </SidebarMenuItem>
            </SidebarMenu>
          </SidebarGroupContent>
        </SidebarGroup>

      </SidebarContent>

      <!-- Footer: Logout -->
      <SidebarFooter class="border-t border-white/10 p-3 space-y-1">
        <div
          @click="logout"
          class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-white/10 cursor-pointer transition-colors group-data-[collapsible=icon]:justify-center"
        >
          <LogOut class="w-4 h-4 text-white group-data-[collapsible=icon]:w-5 group-data-[collapsible=icon]:h-5" />
          <span class="text-sm font-medium text-white group-data-[collapsible=icon]:hidden">Logout</span>
        </div>
      </SidebarFooter>
    </Sidebar>

    <!-- ── Main Content ── -->
    <main class="flex-1 flex flex-col min-w-0 bg-[#F5F6FA] min-h-screen">
      <!-- Top Header -->
      <header class="h-16 flex items-center justify-between px-6 bg-white border-b border-black/10 shrink-0">
        <div class="flex items-center gap-3 flex-1">
          <SidebarTrigger class="text-black/60" />
          <div class="relative w-full max-w-md">
            <Search class="w-4 h-4 text-black/40 absolute left-3 top-1/2 -translate-y-1/2" />
            <input
              v-model.trim="searchQuery"
              type="text"
              placeholder="Search data..."
              class="w-full rounded-lg border border-black/10 bg-white py-2 pl-9 pr-3 text-sm text-black placeholder:text-black/40 focus:border-[#2E85D8] focus:outline-none"
            />
          </div>
        </div>

        <div class="flex items-center gap-3">
          <Popover>
            <PopoverTrigger as-child>
              <button class="w-9 h-9 flex items-center justify-center rounded-lg hover:bg-black/5 transition-colors text-black/60 relative">
                <Bell class="w-4.5 h-4.5" />
                <span class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-[#2E85D8] border-2 border-white"></span>
              </button>
            </PopoverTrigger>
            <PopoverContent class="w-72 p-4" align="end">
              <div class="text-center py-4">
                <Bell class="w-8 h-8 mx-auto text-black/30 mb-2" />
                <p class="text-sm font-medium text-black">No notifications</p>
                <p class="text-xs text-black/50 mt-1">You're all caught up!</p>
              </div>
            </PopoverContent>
          </Popover>
          <div class="h-6 w-px bg-black/10"></div>
          <Popover>
            <PopoverTrigger as-child>
              <div class="flex items-center gap-2.5 cursor-pointer hover:bg-black/5 rounded-lg px-2 py-1 transition-colors">
                <Avatar class="w-9 h-9 ring-2 ring-[#252578]/20">
                  <AvatarFallback class="bg-[#252578] text-white text-sm font-bold">
                    {{ displayUser.initials }}
                  </AvatarFallback>
                </Avatar>
                <div class="hidden sm:block text-left">
                  <p class="text-sm font-semibold text-black leading-tight">{{ displayUser.name }}</p>
                  <p class="text-xs text-black/50">{{ displayUser.role }}</p>
                </div>
              </div>
            </PopoverTrigger>
            <PopoverContent class="w-56 p-2" align="end">
              <div class="space-y-1">
                <div class="px-2 py-1.5 border-b border-black/5">
                  <p class="text-sm font-semibold text-black">{{ displayUser.name }}</p>
                  <p class="text-xs text-black/50">{{ displayUser.email }}</p>
                </div>
                <button @click="router.push('/manager/profile')"
                  class="w-full flex items-center gap-3 px-2 py-2 text-sm text-black hover:bg-black/5 rounded-lg transition-colors text-left">
                  <User class="w-4 h-4 text-black/60" />
                  <span>My Profile</span>
                </button>
                <div class="border-t border-black/5 my-1"></div>
                <button @click="logout" class="w-full flex items-center gap-3 px-2 py-2 text-sm text-red-600 hover:bg-red-50 rounded-lg transition-colors text-left">
                  <LogOut class="w-4 h-4" />
                  <span>Logout</span>
                </button>
              </div>
            </PopoverContent>
          </Popover>
        </div>
      </header>

      <!-- Page Content -->
      <div class="flex-1 overflow-auto">
        <router-view />
      </div>
    </main>
  </SidebarProvider>
</template>
