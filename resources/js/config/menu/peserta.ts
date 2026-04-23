import {
    LayoutDashboard,
    FileText,
    Image,
    Archive,
    Trophy,
} from "lucide-vue-next";
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
    {
        label: "Juara",
        href: "/peserta/juara",
        icon: Trophy,
    },
    {
        label: "Arsip",
        href: "/peserta/arsip",
        icon: Archive,
    },
];
