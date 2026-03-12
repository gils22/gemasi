export type FormDaftarKarya = {
    id?: number;
    kategori: string;
    namaKarya: string;
    waKetua: string;
    dosenPembimbing: {
        nik: string;
        nama: string;
        email: string;
        bidang: string;
    };
    anggotaTim: Array<{
        nim: string;
        nama: string;
        email: string;
        peran: "ketua" | "anggota";
    }>;
    lampiran: Array<{
        id?: number;
        file: File | null;
        namaFile: string;
        deskripsi: string;
        url?: string;
    }>;
    proposalLink: string;
    linkTambahan: Array<{
        label: string;
        url: string;
    }>;
};
