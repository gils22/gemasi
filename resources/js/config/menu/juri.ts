import { LayoutDashboard, ClipboardList, ClipboardCheck } from "lucide-vue-next";
import type { MenuItem } from "@/types/menu";

export const juri: MenuItem[] = [
    {
        label: "Dashboard",
        href: "/juri",
        icon: LayoutDashboard,
    },
    {
        label: "Penilaian Tahap 1",
        icon: ClipboardList,
        href: "/juri/submission/karya",
    },
    {
        label: "Penilaian Tahap 2",
        icon: ClipboardCheck,
        href: "/juri/penjurian",
    },
];
