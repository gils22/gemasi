<script setup lang="ts">
import { computed } from "vue";
import { router } from "@inertiajs/vue3";
import { ArrowUpRight, Search } from "lucide-vue-next";
import { Input } from "@/components/ui/input";
import { Popover, PopoverContent } from "@/components/ui/popover";
import { roleMenus } from "@/config/menu";
import type { MenuChildItem, MenuItem, RoleKey } from "@/types/menu";

type SearchItem = {
    label: string;
    href: string;
    group: string;
};

const props = defineProps<{
    open: boolean;
    role: RoleKey | null;
}>();

const emit = defineEmits<{
    (e: "update:open", value: boolean): void;
}>();

const query = defineModel<string>("query", { default: "" });

const flattenedItems = computed<SearchItem[]>(() => {
    if (!props.role) return [];

    const items: SearchItem[] = [];
    roleMenus[props.role].forEach((item: MenuItem) => {
        if (item.href) {
            items.push({ label: item.label, href: item.href, group: "Menu" });
        }

        item.children?.forEach((child: MenuChildItem) => {
            items.push({
                label: child.label,
                href: child.href,
                group: item.label,
            });
        });
    });

    return items;
});

const filteredItems = computed(() => {
    const keyword = query.value.trim().toLowerCase();
    if (!keyword) return flattenedItems.value;

    return flattenedItems.value.filter((item) => {
        const haystack =
            `${item.label} ${item.group} ${item.href}`.toLowerCase();
        return haystack.includes(keyword);
    });
});

const close = () => {
    emit("update:open", false);
    query.value = "";
};

const navigateTo = (href: string) => {
    close();
    router.get(href);
};
</script>

<template>
    <Popover :open="open" @update:open="emit('update:open', $event)">
        <slot name="trigger" :close="close" />

        <PopoverContent
            align="end"
            side="bottom"
            :side-offset="8"
            class="flex max-h-[28rem] w-[min(88vw,24rem)] flex-col gap-0 overflow-hidden border-slate-200 p-0 shadow-xl"
        >
            <div class="border-b border-slate-200 px-4 py-3">
                <div class="relative">
                    <Search
                        class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                    />
                    <Input
                        v-model="query"
                        autofocus
                        type="search"
                        placeholder="Cari menu..."
                        class="h-10 pl-9 pr-12"
                    />
                    <span
                        class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 rounded border border-slate-200 bg-slate-50 px-1.5 py-0.5 text-[10px] font-semibold text-slate-500"
                    >
                        Esc
                    </span>
                </div>
            </div>

            <div class="max-h-[17rem] flex-1 overflow-y-auto">
                <div v-if="filteredItems.length" class="p-2">
                    <button
                        v-for="item in filteredItems"
                        :key="item.href"
                        type="button"
                        class="flex w-full items-center justify-between rounded-sm px-3 py-2.5 text-left transition-colors hover:bg-slate-100"
                        @click="navigateTo(item.href)"
                    >
                        <div class="min-w-0">
                            <p
                                class="truncate text-sm font-semibold text-slate-900"
                            >
                                {{ item.label }}
                            </p>
                            <p class="truncate text-[11px] text-slate-500">
                                {{ item.group }} · {{ item.href }}
                            </p>
                        </div>
                        <ArrowUpRight class="h-4 w-4 shrink-0 text-slate-400" />
                    </button>
                </div>

                <div
                    v-else
                    class="px-4 py-8 text-center text-sm text-slate-500"
                >
                    Tidak ada menu yang cocok.
                </div>
            </div>
        </PopoverContent>
    </Popover>
</template>
