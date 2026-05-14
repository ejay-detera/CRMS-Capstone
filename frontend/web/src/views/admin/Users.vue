<script setup lang="ts">
import { computed } from 'vue'
import {
  Plus,
  FileText,
  Pencil,
  Trash2,
  Users,
  Briefcase,
  Shield,
} from 'lucide-vue-next'
import { ref } from 'vue'

import { Button } from '@/components/ui/button'
import {
  Card,
  CardContent,
  CardFooter,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { Badge } from '@/components/ui/badge'
import {
  Pagination,
  PaginationEllipsis,
  PaginationFirst,
  PaginationLast,
  PaginationContent,
  PaginationItem,
  PaginationNext,
  PaginationPrevious,
} from '@/components/ui/pagination'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'

type Role = 'Admin' | 'Manager' | 'Sales' | 'Editor'
type Status = 'Active' | 'Inactive'

interface User {
  name: string
  title: string
  email: string
  role: Role
  department: string
  status: Status
}

const users = ref<User[]>([
  { name: 'Sarah Jenkins', title: 'Lead Developer', email: 'sarah.j@enterprise.com', role: 'Admin', department: 'Engineering', status: 'Active' },
  { name: 'Marcus Chen', title: 'HR Manager', email: 'm.chen@enterprise.com', role: 'Manager', department: 'Human Resources', status: 'Active' },
  { name: 'Elena Rodriguez', title: 'UI Designer', email: 'e.rodriguez@enterprise.com', role: 'Editor', department: 'Creative', status: 'Inactive' },
  { name: 'David Kim', title: 'Security Specialist', email: 'd.kim@enterprise.com', role: 'Admin', department: 'Cybersecurity', status: 'Active' },
  { name: 'Jessica Williams', title: 'Sales Associate', email: 'j.williams@enterprise.com', role: 'Sales', department: 'Sales', status: 'Active' },
  { name: 'Michael Brown', title: 'Marketing Head', email: 'm.brown@enterprise.com', role: 'Manager', department: 'Marketing', status: 'Active' },
])

const getRoleCount = (role: Role) => users.value.filter(user => user.role === role).length

const adminCount = getRoleCount('Admin')
const managerCount = getRoleCount('Manager')
const salesCount = getRoleCount('Sales')

const currentPage = ref(1)
const itemsPerPage = 4

const paginatedUsers = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage
  const end = start + itemsPerPage
  return users.value.slice(start, end)
})

const showingFrom = computed(() => (currentPage.value - 1) * itemsPerPage + 1)
const showingTo = computed(() => Math.min(currentPage.value * itemsPerPage, users.value.length))

</script>

<template>
  <div class="p-8">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-3xl font-bold">
          User Management
        </h1>
        <p class="text-muted-foreground">
          Manage {{ users.length }} organization members across various departments.
        </p>
      </div>
      <Button>
        <Plus class="w-4 h-4 mr-2" />
        Add New User
      </Button>
    </div>

    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3 mb-6">
      <Card>
        <CardHeader class="flex flex-row items-center justify-between pb-2">
          <CardTitle class="text-sm font-medium">
            Admins
          </CardTitle>
          <Shield class="w-4 h-4 text-muted-foreground" />
        </CardHeader>
        <CardContent>
          <div class="text-2xl font-bold">
            {{ adminCount }}
          </div>
        </CardContent>
      </Card>
      <Card>
        <CardHeader class="flex flex-row items-center justify-between pb-2">
          <CardTitle class="text-sm font-medium">
            Managers
          </CardTitle>
          <Briefcase class="w-4 h-4 text-muted-foreground" />
        </CardHeader>
        <CardContent>
          <div class="text-2xl font-bold">
            {{ managerCount }}
          </div>
        </CardContent>
      </Card>
      <Card>
        <CardHeader class="flex flex-row items-center justify-between pb-2">
          <CardTitle class="text-sm font-medium">
            Sales
          </CardTitle>
          <Users class="w-4 h-4 text-muted-foreground" />
        </CardHeader>
        <CardContent>
          <div class="text-2xl font-bold">
            {{ salesCount }}
          </div>
        </CardContent>
      </Card>
    </div>

    <Card>
      <CardHeader>
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2">
            <Select>
              <SelectTrigger class="w-45">
                <SelectValue placeholder="All Roles" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">
                  All Roles
                </SelectItem>
                <SelectItem value="admin">
                  Admin
                </SelectItem>
                <SelectItem value="manager">
                  Manager
                </SelectItem>
                <SelectItem value="editor">
                  Editor
                </SelectItem>
                 <SelectItem value="sales">
                  Sales
                </SelectItem>
              </SelectContent>
            </Select>
            <Select>
              <SelectTrigger class="w-45">
                <SelectValue placeholder="Any Status" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="any">
                  Any Status
                </SelectItem>
                <SelectItem value="active">
                  Active
                </SelectItem>
                <SelectItem value="inactive">
                  Inactive
                </SelectItem>
              </SelectContent>
            </Select>
          </div>
          <Button variant="outline">
            <FileText class="w-4 h-4 mr-2" />
            Export CSV
          </Button>
        </div>
      </CardHeader>
      <CardContent>
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>User</TableHead>
              <TableHead>Email</TableHead>
              <TableHead>Role</TableHead>
              <TableHead>Department</TableHead>
              <TableHead>Status</TableHead>
              <TableHead>Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-for="user in paginatedUsers" :key="user.email">
              <TableCell>
                <div class="font-medium">{{ user.name }}</div>
                <div class="text-sm text-muted-foreground">{{ user.title }}</div>
              </TableCell>
              <TableCell>{{ user.email }}</TableCell>
              <TableCell>
                <Badge variant="outline">{{ user.role }}</Badge>
              </TableCell>
              <TableCell>{{ user.department }}</TableCell>
              <TableCell>
                <div class="flex items-center">
                  <span :class="['w-2 h-2 rounded-full mr-2', user.status === 'Active' ? 'bg-green-500' : 'bg-gray-500']"></span>
                  {{ user.status }}
                </div>
              </TableCell>
              <TableCell>
                 <div class="flex items-center gap-2">
                    <Button variant="ghost" size="icon">
                      <Pencil class="w-4 h-4" />
                    </Button>
                    <Button variant="ghost" size="icon">
                      <Trash2 class="w-4 h-4" />
                    </Button>
                  </div>
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </CardContent>
       <CardFooter class="flex items-center justify-between">
        <div class="text-sm text-muted-foreground">
          Showing {{ showingFrom }} to {{ showingTo }} of {{ users.length }} results
        </div>
        <Pagination
          :total="users.length"
          :sibling-count="1"
          show-edges
          :items-per-page="itemsPerPage"
          v-model:page="currentPage"
        >
          <PaginationContent v-slot="{ items }">
            <PaginationFirst />
            <PaginationPrevious />
            <template v-for="(item, index) in items">
              <PaginationItem v-if="item.type === 'page'" :key="index" :value="item.value">
                {{ item.value }}
              </PaginationItem>
              <PaginationEllipsis v-else :key="item.type" :index="index" />
            </template>
            <PaginationNext />
            <PaginationLast />
          </PaginationContent>
        </Pagination>
      </CardFooter>
    </Card>
  </div>
</template>
