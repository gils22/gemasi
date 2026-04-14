<script setup lang="ts">
import { ref, onMounted, onUnmounted } from "vue";
import Sidebar from "@/components/dashboard/Sidebar.vue";
import Topbar from "@/components/dashboard/Topbar.vue";

defineProps<{
    title?: string;
}>();

const collapsed = ref(false);
const userCollapsed = ref(false);
const mobileOpen = ref(false);
const isMobile = ref(false);
const isCompact = ref(false);

const checkScreen = () => {
    const width = window.innerWidth;
    isMobile.value = width < 768;
    isCompact.value = width < 1280;

    if (isMobile.value) {
        collapsed.value = false;
        return;
    }

    if (isCompact.value) {
        collapsed.value = true;
        return;
    }

    collapsed.value = userCollapsed.value;
};

onMounted(() => {
    checkScreen();
    window.addEventListener("resize", checkScreen);
});

onUnmounted(() => {
    window.removeEventListener("resize", checkScreen);
});
</script>

<template>
    <div class="min-h-screen bg-slate-100 flex relative">
        <div
            v-if="mobileOpen"
            class="fixed inset-0 bg-black/30 backdrop-blur-sm z-40 lg:hidden"
            @click="mobileOpen = false"
        />

        <Sidebar
            :collapsed="collapsed"
            :mobile-open="mobileOpen"
            :is-mobile="isMobile"
            @close-mobile="mobileOpen = false"
            class="z-50 shrink-0"
        />

        <div
            class="flex-1 min-w-0 flex flex-col transition-all duration-300"
        >
            <Topbar
                :title="title"
                :collapsed="collapsed"
                @toggle-sidebar="
                    isMobile
                        ? (mobileOpen = true)
                        : isCompact
                          ? ((userCollapsed = true), (collapsed = true))
                          : (userCollapsed = !userCollapsed,
                            (collapsed = userCollapsed))
                "
            />

            <main
                class="flex-1 min-w-0 overflow-y-auto overflow-x-hidden p-3 sm:p-4 lg:p-6"
            >
                <slot />
            </main>
        </div>
    </div>
</template>

