import {
    CalendarDays,
    CalendarClock,
    LayoutDashboard,
    Users,
    UserCheck,
    Trophy,
    BookOpen,
    FileText,
    ClipboardCheck,
} from "lucide-vue-next";
import type { MenuItem } from "@/types/menu";

export const admin: MenuItem[] = [
    {
        label: "Dashboard",
        href: "/admin",
        icon: LayoutDashboard,
    },
    {
        label: "Edisi Tahun",
        href: "/admin/edisi-lomba",
        icon: CalendarClock,
    },
    {
        label: "Timeline",
        href: "/admin/timeline",
        icon: CalendarDays,
    },
    {
        label: "Kategori",
        href: "/admin/kategori",
        icon: Trophy,
    },
    {
        label: "Guideline",
        href: "/admin/guideline",
        icon: BookOpen,
        children: [
            {
                label: "Ketentuan Umum",
                href: "/admin/guideline/ketentuan",
            },
            {
                label: "Bobot Penilaian",
                href: "/admin/guideline/bobot",
            },
            {
                label: "Template Proposal",
                href: "/admin/guideline/template",
            },
        ],
    },
    {
        label: "Landing",
        href: "/admin/landing",
        icon: LayoutDashboard,
    },
    {
        label: "Submission",
        href: "/admin/submission/karya",
        icon: FileText,
    },
    {
        label: "Pameran Karya",
        href: "/admin/pameran-karya",
        icon: FileText,
    },
    {
        label: "Penjurian",
        icon: ClipboardCheck,
        href: "/admin/penjurian",
    },
    {
        label: "Pemenang",
        href: "/admin/pemenang",
        icon: Trophy,
    },
    {
        label: "Manajemen User",
        icon: Users,
        children: [
            {
                label: "Peserta",
                href: "/admin/peserta",
                icon: UserCheck,
            },
            {
                label: "Juri",
                href: "/admin/juri",
                icon: UserCheck,
            },
            {
                label: "Data Dosen",
                href: "/admin/dosen",
                icon: UserCheck,
            },
        ],
    },
];
