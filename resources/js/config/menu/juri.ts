import { LayoutDashboard, ClipboardCheck } from "lucide-vue-next";
import type { MenuItem } from "@/types/menu";

export const juri: MenuItem[] = [
    {
        label: "Dashboard",
        href: "/juri",
        icon: LayoutDashboard,
    },
    {
        label: "Penjurian",
        icon: ClipboardCheck,
        href: "/juri/penjurian",
    },
];
