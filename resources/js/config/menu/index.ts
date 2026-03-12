import { peserta } from "./peserta";
import { admin } from "./admin";
import { juri } from "./juri";
import type { MenuItem, RoleKey } from "@/types/menu";

export const roleMenus: Record<RoleKey, MenuItem[]> = {
    peserta,
    admin,
    juri,
};
