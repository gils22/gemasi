import type { LucideIcon } from "lucide-vue-next";

export type RoleKey = "admin" | "juri" | "peserta";

export interface MenuChildItem {
    label: string;
    href: string;
    icon?: LucideIcon;
}

export interface MenuItem {
    label: string;
    icon: LucideIcon;
    href?: string;
    children?: MenuChildItem[];
}
