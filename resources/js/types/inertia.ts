import { PageProps as InertiaPageProps } from "@inertiajs/core";

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string | null;
}

export interface Edisi {
    id: number;
    nama: string;
    tahun: number;
    status: string;
    aktif: boolean;
}

export interface PageProps extends InertiaPageProps {
    auth: {
        user: User | null;
        role: string | null;
    };
    edisi: {
        aktif: Edisi | null;
        daftar: Edisi[];
    };
    app?: {
        logo?: string;
    };
}
