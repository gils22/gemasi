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
        is_superadmin?: boolean;
        impersonating?: boolean;
        superadmin_original?: User | null;
    };
    edisi: {
        aktif: Edisi | null;
        daftar: Edisi[];
    };
    app?: {
        logo?: string;
    };
    flash?: {
        success?: string | null;
        error?: string | null;
        welcome?: string | null;
        karya_id?: number | string | null;
    };
    landing?: {
        hero_badge: string | null;
        hero_title: string | null;
        hero_subtitle: string | null;
        about_text: string | null;
        video_file_url: string | null;
        video_url: string | null;
        login_carousel_items?: Array<{
            name: string | null;
            url?: string;
            preview_url?: string;
        }>;
        cta_badge: string | null;
        cta_label: string | null;
        cta_url: string | null;
        faq_items: Array<{ q: string; a: string }>;
    } | null;
    login_carousel_images?: Array<{
        name: string | null;
        url?: string;
        preview_url?: string;
    }>;
}
