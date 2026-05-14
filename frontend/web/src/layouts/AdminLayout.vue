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
  SidebarProvider,
  SidebarTrigger,
  SidebarHeader,
  SidebarFooter,
} from "@/components/ui/sidebar";
import { Avatar, AvatarFallback } from "@/components/ui/avatar";
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from "@/components/ui/popover";
import {
  LayoutDashboard,
  FileText,
  Users,
  Shield,
  Handshake,
  ClipboardList,
  Settings2,
  LogOut,
  Search,
  Bell,
  Mail,
  User,
  Settings,
} from "lucide-vue-next";
import { useRoute } from "vue-router";
import { ref } from "vue";
import logoUrl from "@/assets/sbsi logo.png";

const route = useRoute();

// ── Nav groups ──────────────────────────────────────────────────────
const navGroups = [
  {
    label: "Main",
    items: [
      { title: "Dashboard", url: "/admin/dashboard", icon: LayoutDashboard },
      { title: "Contracts", url: "/admin/contracts",  icon: FileText        },
    ],
  },
  {
    label: "Management",
    items: [
      { title: "User Management",    url: "/admin/users",    icon: Users     },
      { title: "Roles & Permission", url: "/admin/roles",    icon: Shield    },
      { title: "Business & Suppliers", url: "/admin/partners", icon: Handshake },
    ],
  },
  {
    label: "System",
    items: [
      { title: "Notifications",      url: "/admin/notifications", icon: Bell         },
      { title: "Audit Log",          url: "/admin/audit-log",     icon: ClipboardList },
      { title: "System Configuration", url: "/admin/system-config", icon: Settings2  },
    ],
  },
];

const user = {
  name: "Shadrack Castro",
  role: "Admin",
  initials: "SC",
  email: "shadrack.castro@sbsi.com",
};

// ── Calendar in Header ──
const searchQuery = ref("");
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
        <SidebarGroup
          v-for="group in navGroups"
          :key="group.label"
          class="mb-1 p-0"
        >
          <SidebarGroupLabel
            class="px-3 mb-1 text-[10px] font-semibold uppercase tracking-widest text-white/25 group-data-[collapsible=icon]:hidden"
          >
            {{ group.label }}
          </SidebarGroupLabel>
          <SidebarGroupContent>
            <SidebarMenu class="gap-0.5">
              <SidebarMenuItem v-for="item in group.items" :key="item.title">
                <SidebarMenuButton
                  as-child
                  :is-active="route.path === item.url"
                  class="h-auto p-0 rounded-lg"
                >
                  <component
                    :is="'router-link'"
                    :to="item.url"
                    class="flex items-center gap-3 w-full px-3 py-2.5 rounded-lg transition-all duration-200 group-data-[collapsible=icon]:justify-center"
                    :class="route.path === item.url
                      ? 'bg-[#2F2F73] text-white'
                      : 'text-white/45 hover:text-white/80 hover:bg-white/10'"
                  >
                    <component
                      :is="item.icon"
                      class="w-4 h-4 shrink-0 group-data-[collapsible=icon]:w-5 group-data-[collapsible=icon]:h-5"
                      :class="route.path === item.url ? 'text-white' : 'text-white/45'"
                    />
                    <span
                      class="text-sm font-medium flex-1 text-left group-data-[collapsible=icon]:hidden"
                      :class="route.path === item.url ? 'text-white' : 'text-white/45'"
                    >
                      {{ item.title }}
                    </span>
                  </component>
                </SidebarMenuButton>
              </SidebarMenuItem>
            </SidebarMenu>
          </SidebarGroupContent>
        </SidebarGroup>
      </SidebarContent>

      <!-- Footer: Settings + Logout -->
      <SidebarFooter class="border-t border-white/10 p-3 space-y-1">
        <div
          class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-white/10 cursor-pointer transition-colors group-data-[collapsible=icon]:justify-center"
        >
          <LogOut class="w-4 h-4 text-white group-data-[collapsible=icon]:w-5 group-data-[collapsible=icon]:h-5" />
          <span
            class="text-sm font-medium text-white group-data-[collapsible=icon]:hidden"
            >Logout</span
          >
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
              v-model="searchQuery"
              type="text"
              placeholder="Search data..."
              class="w-full rounded-lg border border-black/10 bg-white py-2 pl-9 pr-3 text-sm text-black placeholder:text-black/40 focus:border-[#2E85D8] focus:outline-none"
            />
          </div>
        </div>

        <div class="flex items-center gap-3">
          <Popover>
            <PopoverTrigger as-child>
              <button
                class="w-9 h-9 flex items-center justify-center rounded-lg hover:bg-black/5 transition-colors text-black/60 relative"
              >
                <Bell class="w-4.5 h-4.5" />
                <span
                  class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-[#2E85D8] border-2 border-white"
                ></span>
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
                    {{ user.initials }}
                  </AvatarFallback>
                </Avatar>
                <div class="hidden sm:block text-left">
                  <p class="text-sm font-semibold text-black leading-tight">
                    {{ user.name }}
                  </p>
                  <p class="text-xs text-black/50">{{ user.role }}</p>
                </div>
              </div>
            </PopoverTrigger>
            <PopoverContent class="w-56 p-2" align="end">
              <div class="space-y-1">
                <div class="px-2 py-1.5 border-b border-black/5">
                  <p class="text-sm font-semibold text-black">{{ user.name }}</p>
                  <p class="text-xs text-black/50">{{ user.email }}</p>
                </div>
                <button class="w-full flex items-center gap-3 px-2 py-2 text-sm text-black hover:bg-black/5 rounded-lg transition-colors text-left">
                  <User class="w-4 h-4 text-black/60" />
                  <span>My Profile</span>
                </button>
                <button class="w-full flex items-center gap-3 px-2 py-2 text-sm text-black hover:bg-black/5 rounded-lg transition-colors text-left">
                  <Settings class="w-4 h-4 text-black/60" />
                  <span>Settings</span>
                </button>
                <div class="border-t border-black/5 my-1"></div>
                <button class="w-full flex items-center gap-3 px-2 py-2 text-sm text-red-600 hover:bg-red-50 rounded-lg transition-colors text-left">
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
