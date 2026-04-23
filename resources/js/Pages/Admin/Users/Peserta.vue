<script setup lang="ts">
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import { router, usePage } from "@inertiajs/vue3";
import DataTable from "@/components/common/DataTable.vue";
import { Avatar, AvatarImage, AvatarFallback } from "@/components/ui/avatar";
import { toast } from "vue-sonner";
import { computed } from "vue";
import type { PageProps } from "@/types/inertia";

type Role = {
    id: number;
    name: string;
};

type User = {
    id: number;
    name: string;
    email: string;
    avatar: string | null;
    roles: Role[];
};

const page = usePage<PageProps & { users: User[] }>();

const columns = [
    {
        key: "name",
        label: "Nama",
        sortable: true,
    },
    {
        key: "email",
        label: "Email",
        sortable: true,
    },
];

const handleBulkDelete = (ids: number[]) => {
    if (!ids.length) return;

    toast.warning("Yakin ingin menghapus data peserta?", {
        description: `${ids.length} user akan dihapus.`,
        action: {
            label: "Ya, Hapus",
            onClick: () => {
                router.delete("/admin/users/bulk-delete", {
                    data: { ids },
                    preserveScroll: true,
                    onSuccess: () => {
                        toast.success("Peserta berhasil dihapus");
                    },
                    onError: () => {
                        toast.error("Gagal menghapus peserta");
                    },
                });
            },
        },
        cancel: {
            label: "Batal",
            onClick: () => {},
        },
    });
};

defineOptions({
    layout: (h, page) => h(DashboardLayout, { title: "Peserta" }, () => page),
});
</script>

<template>
    <DataTable
        :columns="columns"
        :data="page.props.users"
        :withAction="false"
        :showBulkDelete="true"
        @bulk-delete="handleBulkDelete"
    >
        <template #name="{ row }: { row: User }">
            <div class="flex items-center gap-3">
                <Avatar class="h-8 w-8">
                    <AvatarImage
                        :src="
                            row.avatar ??
                            `https://ui-avatars.com/api/?name=${encodeURIComponent(
                                row.name
                            )}`
                        "
                    />
                    <AvatarFallback>
                        {{ row.name.charAt(0).toUpperCase() }}
                    </AvatarFallback>
                </Avatar>
                <span>{{ row.name }}</span>
            </div>
        </template>
    </DataTable>
</template>

