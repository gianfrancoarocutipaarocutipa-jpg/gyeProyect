<template>
  <div class="min-h-screen flex flex-col bg-slate-950">
    <AppTopbar />
    <div class="flex-1 overflow-hidden">
      <div class="flex h-full">
        <AppSidebar class="flex-shrink-0" />
        <div class="flex-1 overflow-y-auto bg-slate-950">
          <router-view />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import AppTopbar from '@/components/layout/AppTopbar.vue'
import AppSidebar from '@/components/layout/AppSidebar.vue'
</script>