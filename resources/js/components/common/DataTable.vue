<script setup lang="ts">
import { ref, computed, watch, reactive, watchEffect } from "vue";
import { usePage } from "@inertiajs/vue3";
import {
    Download,
    Trash2,
    Columns,
    Search,
    ChevronsUpDown,
    ChevronUp,
    ChevronDown,
    CircleCheckBig,
} from "lucide-vue-next";

import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";

import {
    Pagination,
    PaginationContent,
    PaginationEllipsis,
    PaginationItem,
    PaginationNext,
    PaginationPrevious,
} from "@/components/ui/pagination";

import {
    DropdownMenu,
    DropdownMenuTrigger,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
} from "@/components/ui/dropdown-menu";

import {
    Select,
    SelectTrigger,
    SelectValue,
    SelectContent,
    SelectItem,
} from "@/components/ui/select";

import { Input } from "@/components/ui/input";
import { Checkbox } from "@/components/ui/checkbox";
import { Button } from "@/components/ui/button";
import { Skeleton } from "@/components/ui/skeleton";
import * as XLSX from "xlsx";
import { saveAs } from "file-saver";
import jsPDF from "jspdf";
import autoTable from "jspdf-autotable";
import { toast } from "vue-sonner";
/* =========================
PROPS
========================= */

interface Column {
    key: string;
    label: string;
    sortable?: boolean;
    formatter?: (row: any) => string;
}

const page = usePage();

const props = defineProps<{
    columns: Column[];
    data: any[];
    perPage?: number;
    loading?: boolean;
    withAction?: boolean;
    showBulkDelete?: boolean;
    exportName?: string;
    searchKeys?: string[];
    hiddenColumns?: string[];
    exportColumnKeys?: string[];
}>();

const emit = defineEmits<{
    (e: "bulk-delete", ids: number[]): void;
    (
        e: "export",
        payload: {
            type: "excel" | "pdf";
            data: any[];
            columns: Column[];
        }
    ): void;
}>();

/* =========================
EXPORT
========================= */

const getActiveColumns = () => {
    if (props.exportColumnKeys?.length) {
        return props.columns.filter((col) =>
            props.exportColumnKeys?.includes(col.key),
        );
    }
    return props.columns.filter((col) => visibleColumns[col.key]);
};

const formatValue = (row: any, col: Column) => {
    if (col.formatter) {
        return col.formatter(row);
    }

    return row[col.key] ?? "";
};

const getPageName = () => {
    const component = page.component;
    const name = component.split("/").pop() ?? "export";
    return name.toLowerCase();
};

const exportExcel = () => {
    const columns = getActiveColumns();

    const formatted = filteredData.value.map((row) => {
        const obj: any = {};
        columns.forEach((col) => {
            obj[col.label] = formatValue(row, col);
        });
        return obj;
    });

    const worksheet = XLSX.utils.json_to_sheet(formatted);
    const workbook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, "Sheet1");

    const excelBuffer = XLSX.write(workbook, {
        bookType: "xlsx",
        type: "array",
    });

    const blob = new Blob([excelBuffer], {
        type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=UTF-8",
    });

    const filename = `${getPageName()}.xlsx`;

    saveAs(blob, filename);
    toast.success("Export Excel berhasil");
};

const exportPDF = () => {
    const columns = getActiveColumns();
    const doc = new jsPDF();

    const head = [columns.map((col) => col.label)];

    const body = filteredData.value.map((row) =>
        columns.map((col) => formatValue(row, col))
    );

    autoTable(doc, {
        head,
        body,
    });

    const filename = `${getPageName()}.pdf`;

    doc.save(filename);
    toast.success("Export PDF berhasil");
};
/* =========================
STATE
========================= */

const search = ref("");
const currentPage = ref(1);
const perPage = ref(props.perPage ?? 10);
const selected = ref<number[]>([]);
const sortKey = ref<string | null>(null);
const sortOrder = ref<"asc" | "desc">("asc");

/* =========================
VISIBLE COLUMNS
========================= */

const visibleColumns = reactive<{ [key: string]: boolean }>({});

watchEffect(() => {
    props.columns.forEach((col) => {
        if (!(col.key in visibleColumns)) {
            visibleColumns[col.key] = true;
        }
    });
    if (props.hiddenColumns?.length) {
        props.hiddenColumns.forEach((key) => {
            if (key in visibleColumns) {
                visibleColumns[key] = false;
            }
        });
    }
});

const visibleColumnList = computed(() =>
    props.columns.filter((col) => visibleColumns[col.key])
);

const firstVisibleColumnKey = computed(
    () => visibleColumnList.value[0]?.key ?? null
);
/* =========================
FILTER + SORT
========================= */

const filteredData = computed(() => {
    let rows = [...props.data];

    if (search.value) {
        const keyword = search.value.toLowerCase();
        const keys = props.searchKeys?.length ? props.searchKeys : null;

        rows = rows.filter((row) => {
            if (keys) {
                return keys.some((key) =>
                    String(row?.[key] ?? "")
                        .toLowerCase()
                        .includes(keyword)
                );
            }

            return Object.values(row)
                .join(" ")
                .toLowerCase()
                .includes(keyword);
        });
    }

    if (sortKey.value) {
        rows.sort((a, b) => {
            const aVal = a[sortKey.value!];
            const bVal = b[sortKey.value!];
            if (aVal < bVal) return sortOrder.value === "asc" ? -1 : 1;
            if (aVal > bVal) return sortOrder.value === "asc" ? 1 : -1;
            return 0;
        });
    }

    return rows;
});

/* =========================
PAGINATION
========================= */

const paginatedData = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    return filteredData.value.slice(start, start + perPage.value);
});

const totalPages = computed(() =>
    Math.max(1, Math.ceil(filteredData.value.length / perPage.value))
);

const startRecord = computed(() =>
    filteredData.value.length === 0
        ? 0
        : (currentPage.value - 1) * perPage.value + 1
);

const endRecord = computed(() =>
    Math.min(currentPage.value * perPage.value, filteredData.value.length)
);

watch(currentPage, () => {
    selected.value = [];
});

watch(perPage, () => {
    currentPage.value = 1;
});

watch(filteredData, () => {
    if (currentPage.value > totalPages.value) {
        currentPage.value = totalPages.value;
    }
});

/* =========================
SELECTION
========================= */

const allChecked = computed({
    get() {
        return (
            filteredData.value.length > 0 &&
            filteredData.value.every((row: any) =>
                selected.value.includes(row.id)
            )
        );
    },
    set(val: boolean) {
        selected.value = val
            ? filteredData.value.map((row: any) => row.id)
            : [];
    },
});

const toggleSort = (key: string) => {
    if (sortKey.value !== key) {
        // klik pertama
        sortKey.value = key;
        sortOrder.value = "asc";
    } else if (sortOrder.value === "asc") {
        // klik kedua
        sortOrder.value = "desc";
    } else {
        // klik ketiga -> reset
        sortKey.value = null;
        sortOrder.value = "asc";
    }
};

const toggleRowSelection = (rowId: number, checked: boolean) => {
    if (checked) {
        if (!selected.value.includes(rowId)) selected.value.push(rowId);
        return;
    }
    const index = selected.value.indexOf(rowId);
    if (index >= 0) selected.value.splice(index, 1);
};
</script>

<template>
    <div class="space-y-4">
        <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-3">
            <!-- LEFT SIDE -->
            <div class="flex flex-col sm:flex-row sm:flex-wrap sm:items-center gap-2">
                <!-- SEARCH -->
                <div class="relative w-full sm:w-72">
                    <Search
                        class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"
                    />
                    <Input
                        v-model="search"
                        placeholder="Search..."
                        class="pl-9 bg-white"
                    />
                </div>

                <!-- FILTER / CUSTOM LEFT -->
                <div class="w-full sm:w-auto">
                    <slot name="toolbar-left" />
                </div>

                <!-- COLUMN TOGGLE -->
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <Button variant="outline" class="w-full sm:w-auto">
                            <Columns class="w-4 h-4" />
                            Columns
                        </Button>
                    </DropdownMenuTrigger>

                    <DropdownMenuContent align="start" class="w-44">
                        <div
                            v-for="col in columns"
                            :key="col.key"
                            @click="
                                visibleColumns[col.key] =
                                    !visibleColumns[col.key]
                            "
                            class="flex items-center gap-2 px-3 py-2 text-sm cursor-pointer hover:bg-muted rounded-md transition"
                        >
                            <CircleCheckBig
                                v-if="visibleColumns[col.key]"
                                class="w-4 h-4 text-primary"
                            />
                            <span>{{ col.label }}</span>
                        </div>
                    </DropdownMenuContent>
                </DropdownMenu>

                <!-- EXPORT -->
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <Button
                            variant="outline"
                            class="w-full sm:w-auto flex items-center gap-2"
                        >
                            <Download class="w-4 h-4" />
                            Export
                        </Button>
                    </DropdownMenuTrigger>

                    <DropdownMenuContent align="start" class="w-20">
                        <DropdownMenuItem
                            @click="exportExcel"
                            class="flex items-center gap-2 px-2 py-1 text-sm cursor-pointer hover:bg-muted rounded-sm transition"
                        >
                            Excel
                        </DropdownMenuItem>

                        <DropdownMenuSeparator />

                        <DropdownMenuItem
                            @click="exportPDF"
                            class="flex items-center gap-2 px-2 py-1 text-sm cursor-pointer hover:bg-muted rounded-md transition"
                        >
                            PDF
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>

            <!-- RIGHT SIDE -->
            <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                <Button
                    v-if="props.showBulkDelete !== false"
                    variant="destructive"
                    :disabled="selected.length === 0"
                    @click="emit('bulk-delete', selected)"
                    class="w-full sm:w-auto flex items-center gap-2"
                >
                    <Trash2 class="w-4 h-4" />
                    Delete
                    <span
                        v-if="selected.length > 0"
                        class="bg-white/25 text-white text-xs px-2 py-0.5 rounded-full"
                    >
                        {{ selected.length }}
                    </span>
                </Button>

                <slot name="toolbar-right" />
            </div>
        </div>

        <!-- TABLE DESKTOP -->
        <div class="bg-white border rounded-xl p-4 shadow-sm">
            <div class="hidden md:block">
            <Table>
                <!-- ================= HEADER ================= -->
                <TableHeader>
                    <TableRow>
                        <!-- Checkbox -->
                        <TableHead class="w-12">
                            <Checkbox v-model="allChecked" />
                        </TableHead>

                        <!-- Dynamic Columns -->
                        <template v-for="col in columns" :key="col.key">
                            <TableHead
                                v-if="visibleColumns[col.key]"
                                @click="col.sortable && toggleSort(col.key)"
                                class="cursor-pointer select-none"
                            >
                                <div class="flex items-center gap-2">
                                    {{ col.label }}

                                    <!-- Default -->
                                    <ChevronsUpDown
                                        v-if="
                                            col.sortable && sortKey !== col.key
                                        "
                                        class="w-4 h-4 text-muted-foreground"
                                    />

                                    <!-- ASC -->
                                    <ChevronUp
                                        v-if="
                                            sortKey === col.key &&
                                            sortOrder === 'asc'
                                        "
                                        class="w-4 h-4"
                                    />

                                    <!-- DESC -->
                                    <ChevronDown
                                        v-if="
                                            sortKey === col.key &&
                                            sortOrder === 'desc'
                                        "
                                        class="w-4 h-4"
                                    />
                                </div>
                            </TableHead>
                        </template>

                        <!-- ACTION HEADER -->
                        <TableHead v-if="withAction" class="text-right w-20">
                            Action
                        </TableHead>
                    </TableRow>
                </TableHeader>

                <!-- ================= BODY ================= -->
                <TableBody>
                    <!-- ========== SKELETON ========== -->
                    <template v-if="loading">
                        <TableRow v-for="n in perPage" :key="'skeleton-' + n">
                            <TableCell>
                                <Skeleton class="h-4 w-4 rounded" />
                            </TableCell>

                            <template v-for="col in columns" :key="col.key">
                                <TableCell v-if="visibleColumns[col.key]">
                                    <Skeleton class="h-4 w-24" />
                                </TableCell>
                            </template>

                            <!-- Skeleton Action -->
                            <TableCell v-if="withAction" class="text-right">
                                <Skeleton class="h-8 w-8 ml-auto rounded-md" />
                            </TableCell>
                        </TableRow>
                    </template>

                    <!-- ========== REAL DATA ========== -->
                    <template v-else>
                        <TableRow
                            v-for="row in paginatedData"
                            :key="row.id"
                            class="hover:bg-muted/40 transition"
                        >
                            <!-- Checkbox -->
                            <TableCell>
                                <Checkbox
                                    :model-value="selected.includes(row.id)"
                                    @update:model-value="
                                        (val) =>
                                            toggleRowSelection(row.id, !!val)
                                    "
                                />
                            </TableCell>

                            <!-- Dynamic Cells -->
                            <template v-for="col in columns" :key="col.key">
                                <TableCell v-if="visibleColumns[col.key]">
                                    <slot :name="col.key" :row="row">
                                        {{ row[col.key] }}
                                    </slot>
                                </TableCell>
                            </template>

                            <!-- ACTION CELL -->
                            <TableCell v-if="withAction" class="text-right">
                                <slot name="actions" :row="row" />
                            </TableCell>
                        </TableRow>

                        <!-- ========== EMPTY STATE ========== -->
                        <TableRow v-if="filteredData.length === 0">
                            <TableCell
                                :colspan="
                                    1 +
                                    visibleColumnList.length +
                                    (withAction ? 1 : 0)
                                "
                                class="text-center py-10 text-muted-foreground"
                            >
                                Tidak ada data
                            </TableCell>
                        </TableRow>
                    </template>
                </TableBody>
            </Table>
            </div>

            <!-- MOBILE CARDS -->
            <div class="md:hidden space-y-3">
                <template v-if="loading">
                    <div
                        v-for="n in perPage"
                        :key="'mobile-skeleton-' + n"
                        class="rounded-xl border p-3 space-y-3"
                    >
                        <Skeleton class="h-4 w-24" />
                        <Skeleton class="h-4 w-full" />
                        <Skeleton class="h-4 w-2/3" />
                    </div>
                </template>

                <template v-else-if="paginatedData.length > 0">
                    <div
                        v-for="row in paginatedData"
                        :key="'mobile-' + row.id"
                        class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm space-y-3"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-xs text-slate-500">Data</p>
                                <p class="text-sm font-semibold text-slate-800 truncate">
                                    <slot
                                        v-if="firstVisibleColumnKey"
                                        :name="firstVisibleColumnKey"
                                        :row="row"
                                    >
                                        {{
                                            firstVisibleColumnKey
                                                ? row[firstVisibleColumnKey]
                                                : row.id
                                        }}
                                    </slot>
                                </p>
                            </div>

                            <Checkbox
                                :model-value="selected.includes(row.id)"
                                @update:model-value="(val) => toggleRowSelection(row.id, !!val)"
                            />
                        </div>

                        <div class="space-y-1.5">
                            <div
                                v-for="col in visibleColumnList"
                                :key="'mobile-col-' + row.id + '-' + col.key"
                                v-show="col.key !== firstVisibleColumnKey"
                                class="flex items-start justify-between gap-3 text-sm"
                            >
                                <p class="text-slate-500 shrink-0">{{ col.label }}</p>
                                <p class="text-right text-slate-800 break-all">
                                    <slot :name="col.key" :row="row">
                                        {{ row[col.key] }}
                                    </slot>
                                </p>
                            </div>
                        </div>

                        <div
                            v-if="withAction"
                            class="border-t border-slate-100 pt-2 flex flex-wrap justify-end gap-2"
                        >
                            <slot name="actions" :row="row" />
                        </div>
                    </div>
                </template>

                <div
                    v-else
                    class="rounded-xl border border-dashed border-slate-300 p-6 text-center text-sm text-muted-foreground"
                >
                    Tidak ada data
                </div>
            </div>

            <!-- PAGINATION -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between mt-6 gap-3">
                <!-- LEFT SIDE -->
                <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4">
                    <!-- Page Size Selector -->
                    <div
                        class="flex items-center gap-2 text-sm text-muted-foreground"
                    >
                        <span>Rows per page</span>

                        <Select v-model="perPage">
                            <SelectTrigger class="h-8 w-20 text-sm">
                                <SelectValue />
                            </SelectTrigger>

                            <SelectContent>
                                <SelectItem :value="5">5</SelectItem>
                                <SelectItem :value="10">10</SelectItem>
                                <SelectItem :value="25">25</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Showing Info -->
                    <p class="text-sm text-muted-foreground">
                        Showing {{ startRecord }} - {{ endRecord }} of
                        {{ filteredData.length }}
                    </p>
                </div>

                <!-- RIGHT SIDE PAGINATION -->
                <div class="hidden md:block">
                    <Pagination
                        v-slot="{ page }"
                        :items-per-page="perPage"
                        :total="filteredData.length"
                        :default-page="1"
                        @update:page="(val) => (currentPage = val)"
                    >
                        <PaginationContent v-slot="{ items }" class="gap-1">
                            <PaginationPrevious
                                :disabled="currentPage === 1"
                                class="text-sm"
                            />

                            <template
                                v-for="(item, index) in items"
                                :key="index"
                            >
                                <PaginationItem
                                    v-if="item.type === 'page'"
                                    :value="item.value"
                                    :is-active="item.value === page"
                                    class="text-sm"
                                >
                                    {{ item.value }}
                                </PaginationItem>

                                <PaginationEllipsis
                                    v-else-if="item.type === 'ellipsis'"
                                    class="text-sm"
                                />
                            </template>

                            <PaginationNext
                                :disabled="
                                    currentPage * perPage >= filteredData.length
                                "
                                class="text-sm"
                            />
                        </PaginationContent>
                    </Pagination>
                </div>

                <div class="md:hidden flex items-center justify-between gap-2">
                    <Button
                        variant="outline"
                        :disabled="currentPage === 1"
                        @click="currentPage = Math.max(1, currentPage - 1)"
                    >
                        Previous
                    </Button>
                    <p class="text-xs text-muted-foreground">
                        Page {{ currentPage }} / {{ totalPages }}
                    </p>
                    <Button
                        variant="outline"
                        :disabled="currentPage >= totalPages"
                        @click="currentPage = Math.min(totalPages, currentPage + 1)"
                    >
                        Next
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>


