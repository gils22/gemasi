<script setup>
import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import { onMounted, ref } from "vue";
import { toast } from "vue-sonner";
import AuthLayout from "@/Layouts/AuthLayout.vue";
import { Button } from "@/components/ui/button";
import googleLogo from "@/assets/logo-google.svg";

const page = usePage();

const errorMessage = computed(() => {
    // Supports both session flash (`with('error')`) and generic props.
    return (
        page.props.flash?.error ||
        page.props.error ||
        page.props.errors?.error ||
        ""
    );
});

const toastShown = ref(false);
onMounted(() => {
    if (toastShown.value) return;
    const message = String(errorMessage.value || "").trim();
    if (!message) return;
    toast.error(message);
    toastShown.value = true;
});
</script>

<template>
    <AuthLayout>
        <div class="space-y-6">
            <div class="text-center space-y-1">
                <h1 class="text-2xl font-semibold tracking-tight">GEMASI</h1>
                <p class="text-sm text-muted-foreground">
                    Gelar Karya Mahasiswa Sistem Informasi
                </p>
            </div>

            <p
                v-if="errorMessage"
                class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
            >
                {{ errorMessage }}
            </p>

            <Button
                class="w-full h-11 flex items-center justify-center gap-2"
                as-child
            >
                <a href="/auth/google" class="flex items-center gap-2">
                    <img :src="googleLogo" class="h-4 w-4" />
                    Login dengan Google
                </a>
            </Button>

            <p class="text-xs text-center text-muted-foreground">
                Masuk dengan akun Google Amikom.
            </p>
        </div>
    </AuthLayout>
</template>
