<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItemType } from '@/types';
import { ref, onMounted } from 'vue';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItemType[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const dbInfo = ref<any>(null);

onMounted(async () => {
    try {
        const response = await fetch('/api/debug/database-info');
        if (response.ok) {
            dbInfo.value = await response.json();
        }
    } catch (error) {
        console.error('Failed to fetch database info:', error);
    }
});
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
    >
        <div class="flex items-center gap-2 flex-1">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>

        <!-- Database Debug Info -->
        <div v-if="dbInfo" class="ml-auto text-xs text-muted-foreground bg-muted/50 rounded px-2 py-1 font-mono">
            <div class="flex items-center gap-4">
                <span class="font-semibold text-blue-600">
                    DB: {{ dbInfo.current_db?.database || 'unknown' }}@{{ dbInfo.current_db?.host || 'unknown' }}
                </span>
                <span>Cases: {{ dbInfo.total_cases }}</span>
                <span>Active: {{ dbInfo.active_cases }}</span>
                <span>With Data: {{ dbInfo.cases_with_tenant_data }}</span>
                <span v-if="dbInfo.broken_cases > 0" class="text-red-600 font-semibold">
                    Broken: {{ dbInfo.broken_cases }}
                </span>
            </div>
        </div>
    </header>
</template>
