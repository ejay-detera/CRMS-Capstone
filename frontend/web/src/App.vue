<script setup lang="ts">
import Toaster from '@/components/ui/toast/Toaster.vue'
import LoaderOverlay from '@/components/shared/LoaderOverlay.vue'
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <main>
      <router-view />
    </main>
    <Toaster />
    <LoaderOverlay />
  </div>
</template>