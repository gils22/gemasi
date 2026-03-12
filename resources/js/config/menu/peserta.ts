import { LayoutDashboard, FileText, Image } from "lucide-vue-next";
import type { MenuItem } from "@/types/menu";

export const peserta: MenuItem[] = [
    {
        label: "Dashboard",
        href: "/peserta",
        icon: LayoutDashboard,
    },
    {
        label: "Daftar Karya",
        href: "/peserta/daftar-karya",
        icon: FileText,
    },
    {
        label: "Pameran Karya",
        href: "/peserta/pameran-karya",
        icon: Image,
    },
];
