<script setup lang="ts">
import { router } from "@inertiajs/vue3";
import { Button } from "@/components/ui/button";
import { LogOut } from "lucide-vue-next";

defineProps<{
    title?: string;
}>();

const logout = () => {
    router.post("/logout");
};
</script>

<template>
    <div class="min-h-screen bg-slate-50">
        <header class="h-16 border-b border-slate-200 bg-white/90 backdrop-blur">
            <div class="mx-auto flex h-full max-w-5xl items-center justify-between px-4">
                <div />

                <Button variant="outline" size="sm" @click="logout">
                    <LogOut class="h-4 w-4" />
                    Logout
                </Button>
            </div>
        </header>

        <main class="mx-auto max-w-5xl px-4 py-10">
            <slot />
        </main>
    </div>
</template>
