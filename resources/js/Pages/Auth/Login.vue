<script setup>
import { computed, ref } from "vue";
import AuthLayout from "@/Layouts/AuthLayout.vue";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { router, usePage } from "@inertiajs/vue3";
import googleLogo from "@/assets/logo-google.svg";

import { Mail, Lock, Eye, EyeOff, User } from "lucide-vue-next";

const page = usePage();

const showAdminLogin = ref(false);
const showPassword = ref(false);
const showTooltip = ref(false);
const formEmail = ref("");
const formPassword = ref("");
const isSubmitting = ref(false);
const errorMessage = computed(
    () => page.props.errors?.email || page.props.errors?.password || "",
);

const togglePassword = () => {
    showPassword.value = !showPassword.value;
};

const loginWithForm = () => {
    if (!formEmail.value || !formPassword.value) return;

    isSubmitting.value = true;
    router.post(
        "/auth/form",
        {
            email: formEmail.value,
            password: formPassword.value,
        },
        {
            onFinish: () => {
                isSubmitting.value = false;
            },
        },
    );
};
</script>

<template>
    <AuthLayout>
        <div class="space-y-8 relative">
            <!-- HEADER -->
            <div class="text-center">
                <h1 class="text-2xl font-semibold tracking-tight">
                    Login GEMASI
                </h1>
            </div>

            <!-- TRANSITION WRAPPER -->
            <transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="opacity-0 translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 translate-y-2"
                mode="out-in"
            >
                <!-- ========================= -->
                <!-- PESERTA LOGIN -->
                <!-- ========================= -->
                <div v-if="!showAdminLogin" key="peserta" class="space-y-6">
                    <Button
                        class="w-full h-11 flex items-center justify-center gap-2"
                        as-child
                    >
                        <a
                            href="/auth/google/peserta"
                            class="flex items-center gap-2"
                        >
                            <img
                                :src="googleLogo"
                                class="h-4 w-4"
                            />
                            Login dengan Google
                        </a>
                    </Button>

                    <p class="text-xs text-center text-muted-foreground">
                        Silahkan login menggunakan akun Google Student
                    </p>
                </div>

                <!-- ========================= -->
                <!-- ADMIN / JURI LOGIN -->
                <!-- ========================= -->
                <div v-else key="admin" class="space-y-6">
                    <!-- Email -->
                    <div class="relative">
                        <Mail
                            class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                        />
                        <Input
                            v-model="formEmail"
                            type="email"
                            placeholder="Email"
                            class="pl-10 h-11"
                        />
                    </div>

                    <!-- Password -->
                    <div class="relative">
                        <Lock
                            class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                        />
                        <Input
                            v-model="formPassword"
                            :type="showPassword ? 'text' : 'password'"
                            placeholder="Password"
                            class="pl-10 pr-10 h-11"
                        />
                        <button
                            type="button"
                            @click="togglePassword"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground"
                        >
                            <Eye v-if="!showPassword" class="h-4 w-4" />
                            <EyeOff v-else class="h-4 w-4" />
                        </button>
                    </div>

                    <p
                        v-if="errorMessage"
                        class="text-sm text-center text-rose-600"
                    >
                        {{ errorMessage }}
                    </p>

                    <Button
                        class="w-full h-11 flex items-center justify-center gap-2"
                        :disabled="isSubmitting"
                        @click="loginWithForm"
                    >
                        Login
                    </Button>

                    <Button
                        variant="outline"
                        class="w-full h-11 flex items-center justify-center gap-2"
                        as-child
                    >
                        <a
                            href="/auth/google/admin"
                            class="flex items-center gap-2"
                        >
                            <img
                                :src="googleLogo"
                                class="h-4 w-4"
                            />
                            Login dengan Google
                        </a>
                    </Button>
                </div>
            </transition>
        </div>

        <!-- FLOATING ADMIN BUTTON -->
        <div class="fixed bottom-6 left-6">
            <!-- Tooltip -->
            <transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 translate-x-1"
                enter-to-class="opacity-100 translate-x-0"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100 translate-x-0"
                leave-to-class="opacity-0 translate-x-1"
            >
                <div
                    v-if="showTooltip"
                    class="absolute left-14 bottom-3 text-xs bg-foreground text-background px-2 py-1 rounded-md shadow-sm whitespace-nowrap"
                >
                    {{
                        showAdminLogin ? "Login Peserta" : "Login Admin / Juri"
                    }}
                </div>
            </transition>

            <button
                @mouseenter="showTooltip = true"
                @mouseleave="showTooltip = false"
                @click="showAdminLogin = !showAdminLogin"
                :class="[
                    'h-12 w-12 rounded-full border bg-background shadow-md flex items-center justify-center transition-all duration-200',
                    showAdminLogin ? 'scale-110' : 'hover:scale-105',
                ]"
            >
                <User class="h-5 w-5 text-muted-foreground" />
            </button>
        </div>
    </AuthLayout>
</template>
