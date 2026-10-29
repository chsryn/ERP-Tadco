# AGENTS.md — Smart ERP PT TADCo

Laravel ERP di `C:\laragon\www\ERP-Tadco`. ERP adalah **source of truth**. AI hanya lapisan assistant read-only di atasnya.

## Aturan wajib: dokumentasi otomatis ke Obsidian

Setiap pembuatan, perubahan, atau penghapusan file di repo ini **wajib** menghasilkan catatan Obsidian.

- Vault: `D:\skripsi.md\smart-erp`
- Template: `D:\skripsi.md\smart-erp\_templates\Catatan Perubahan.md`
- Nama file: `<Topik Singkat> - <YYYY-MM-DD>.md` di root vault (mis. `Perbaikan Login is_active - 2026-10-29.md`)
- Isi: frontmatter template + konteks, daftar file yang berubah, alur/desain, dampak per role, bukti test, risiko, tindak lanjut.
- Wajib menyertakan hash commit bila perubahan sudah di-commit.
- Jangan menulis API key, kredensial, atau data pelanggan ke catatan.
- Jangan mengedit `Log Perubahan/*.md` secara manual — itu output git hook.

Log commit otomatis sudah aktif lewat `core.hooksPath = scripts/hooks` → menulis ke `Log Perubahan/<tanggal>.md`. Catatan semantik tetap dibuat manual oleh agen.

## Aturan kerja

1. Audit dulu, ubah kemudian. Jangan menebak skema/route/service yang belum diverifikasi.
2. Jangan rewrite ERP, jangan hapus fitur, jangan ubah business logic yang tidak terkait.
3. Jangan migration destruktif, jangan dependency baru tanpa alasan.
4. Read-only pada AI berarti tidak ada jalur tulis; jangan andalkan system prompt sebagai boundary.
5. Identitas user selalu dari session (`$user`), tidak pernah dari parameter LLM.
6. Selesaikan satu fase, jalankan test, laporkan bukti, lalu berhenti.

## Prioritas saat ini

Pemantapan ERP lebih dulu daripada perluasan AI:

```text
P0a  Registrasi publik, is_active saat login, role middleware modul sensitif
P0b  Ownership filter (sales_id) + Policy/permission
P1   AI Gateway (interface + 2 adapter)
P2   Read-only DB connection (toggle env, kecuali test)
P3   AiInteraction audit log
P4   Evaluation / security testing
P5   Optional read-only tools
```

Agent loop dan write action: future work, jangan diimplementasikan.
