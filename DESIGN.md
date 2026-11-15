---
name: RateYourMie
description: Indeks Rating 4 Aspek & Papan Tierlist Mie Instan (Editorial & Utilitarian)
colors:
  page-ground: "#FBF9F5"
  surface-white: "#FFFFFF"
  subtle-stone: "#F3EFE7"
  ink-primary: "#181613"
  ink-secondary: "#5C564E"
  ink-muted: "#665F56"
  vermilion-accent: "#C2301A"
  vermilion-hover: "#9E2411"
  vermilion-soft: "#FDF1EE"
  scallion-success: "#15693F"
  scallion-soft: "#EAF5EF"
  amber-warning: "#B45309"
  amber-soft: "#FEF4E6"
  tier-c-cobalt: "#1D56A3"
  tier-d-shallot: "#6B3FA0"
  tier-f-charcoal: "#4A453E"
  hairline-subtle: "#E5DFD3"
  hairline-strong: "#C9C1B4"
typography:
  display:
    fontFamily: "'Archivo', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif"
    fontSize: "2.1rem"
    fontWeight: 700
    lineHeight: 1.18
    letterSpacing: "-0.02em"
  headline:
    fontFamily: "'Archivo', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif"
    fontSize: "1.45rem"
    fontWeight: 700
    lineHeight: 1.18
    letterSpacing: "-0.02em"
  title:
    fontFamily: "'Archivo', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif"
    fontSize: "1.12rem"
    fontWeight: 700
    lineHeight: 1.25
  body:
    fontFamily: "'Public Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif"
    fontSize: "0.95rem"
    fontWeight: 400
    lineHeight: 1.55
  label:
    fontFamily: "'JetBrains Mono', ui-monospace, 'Cascadia Code', Consolas, monospace"
    fontSize: "0.84rem"
    fontWeight: 600
    lineHeight: 1.2
rounded:
  xs: "4px"
  sm: "6px"
  md: "10px"
  pill: "99px"
spacing:
  xs: "6px"
  sm: "12px"
  md: "20px"
  lg: "32px"
  xl: "64px"
components:
  button-primary:
    backgroundColor: "{colors.vermilion-accent}"
    textColor: "{colors.surface-white}"
    rounded: "{rounded.sm}"
    padding: "9px 16px"
  button-primary-hover:
    backgroundColor: "{colors.vermilion-hover}"
  button-secondary:
    backgroundColor: "{colors.surface-white}"
    textColor: "{colors.ink-primary}"
    rounded: "{rounded.sm}"
    padding: "9px 16px"
  card-noodle:
    backgroundColor: "{colors.surface-white}"
    textColor: "{colors.ink-primary}"
    rounded: "{rounded.md}"
    padding: "16px 18px"
---

# Design System: RateYourMie

## Overview

**Editorial & Utilitarian Catalog** — RateYourMie menyajikan indeks mie instan dengan keterbacaan publikasi kuliner dan presisi alat evaluasi (*Operate*): kanvas kertas hangat alabaster (`#FBF9F5`), kartu putih bersih berbingkai hairline tipis (`1px solid #E5DFD3`), aksen vermilion terukur (`#C2301A`), serta angka monospasi tabular tanpa kotak bersarang atau ornamen berlebihan.

## Colors

- **Page Ground** (`#FBF9F5`): Latar utama seluruh halaman dan area dropzone tierboard.
- **Surface White** (`#FFFFFF`): Permukaan kartu katalog, panel detail, tabel data, dan header lengket tembus pandang.
- **Subtle Stone** (`#F3EFE7`): Panggung visual kemasan mie, kepala tabel, dan latar hover halus.
- **Ink Primary** (`#181613`), **Secondary** (`#5C564E`), **Muted** (`#665F56`): Teks dengan kontras minimum ≥ 4.5:1 di seluruh permukaan terang.
- **Vermilion Accent** (`#C2301A` / hover `#9E2411`): Aksi primer, sorotan navigasi aktif, skor akhir, dan lencana Tier S.
- **Tier Signal Palette**: S (`#C2301A`), A (`#B45309`), B (`#15693F`), C (`#1D56A3`), D (`#6B3FA0`), F (`#4A453E`).

## Typography

- **Display / Headings:** `Archivo` (600, 700, 800) dengan letter-spacing `-0.02em`.
- **Body & UI:** `Public Sans` (400, 500, 600, 700), `0.95rem`, `line-height: 1.55`.
- **Numeric / Tabular:** `JetBrains Mono` dengan `font-variant-numeric: tabular-nums` khusus untuk skor aspek, skor akhir, harga Rupiah, dan data tabel.

## Layout & Elevation

- Kontainer `max-width: 1200px` dengan grid katalog responsif (`1` kolom ponsel, `2` kolom tablet, `3` kolom desktop) menggunakan `minmax(0, 1fr)` agar bebas luapan horizontal.
- Garis batas seragam `1px solid #E5DFD3` (dan `#C9C1B4` untuk kontrol input dan hover), tanpa kotak bersarang di dalam kartu.
- Saat foto kemasan belum diunggah, `render_noodle_visual()` menampilkan monogram tipografi bersih tanpa bingkai bergerigi.
