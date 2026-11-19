---
name: RateYourMie
description: Alabaster & Obsidian Fluid Workspace — Indeks 4 Aspek, Papan Tierlist S–F & Workspace Pengelola Mie Instan
colors:
  page-ground: "#FFFFFF"
  alabaster-band: "#F3F2EC"
  surface-card: "#F7F6F2"
  surface-card-hover: "#EFECE4"
  subtle-stone: "#F2F1EC"
  obsidian-spotlight: "#18181A"
  obsidian-elevated: "#252528"
  ink-primary: "#141517"
  ink-secondary: "#52565E"
  ink-muted: "#62666E"
  ink-on-dark: "#FFFFFF"
  crimson-accent: "#D92D20"
  crimson-hover: "#B42318"
  crimson-soft: "#FEF3F2"
  jade-success: "#0F766E"
  jade-soft: "#ECFDF5"
  status-online: "#10B981"
  amber-warning: "#D97706"
  amber-soft: "#FFFBEB"
  tier-c-cobalt: "#2563EB"
  tier-d-violet: "#7C3AED"
  tier-f-slate: "#4B5563"
  border-subtle: "#E7E6E0"
  border-strong: "#D2D0C7"
  dark-canvas: "#0D0F12"
  dark-surface: "#12151B"
  dark-alabaster: "#181C24"
  dark-card: "#1B1F28"
  dark-subtle: "#202530"
  dark-secondary: "#A4AAB6"
  dark-muted: "#8E95A2"
  dark-accent: "#F04438"
  dark-accent-soft: "#2C1618"
  dark-jade: "#2DD4BF"
  dark-jade-soft: "#112826"
  dark-warning: "#F59E0B"
  dark-warning-soft: "#2B2111"
  dark-border: "#252A35"
  dark-border-strong: "#363E4D"
typography:
  display:
    fontFamily: "'Archivo', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif"
    fontSize: "2.25rem"
    fontWeight: 500
    lineHeight: 1.08
    letterSpacing: "-0.035em"
  headline:
    fontFamily: "'Archivo', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif"
    fontSize: "1.48rem"
    fontWeight: 600
    lineHeight: 1.18
    letterSpacing: "-0.025em"
  title:
    fontFamily: "'Archivo', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif"
    fontSize: "1.12rem"
    fontWeight: 600
    lineHeight: 1.25
  body:
    fontFamily: "'Public Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif"
    fontSize: "0.95rem"
    fontWeight: 400
    lineHeight: 1.55
  label:
    fontFamily: "'Public Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif"
    fontSize: "0.84rem"
    fontWeight: 600
    lineHeight: 1.25
  caption:
    fontFamily: "'JetBrains Mono', ui-monospace, 'Cascadia Code', Consolas, monospace"
    fontSize: "0.75rem"
    fontWeight: 600
    lineHeight: 1.2
rounded:
  xs: "6px"
  sm: "10px"
  md: "14px"
  lg: "18px"
  pill: "99px"
spacing:
  xs: "6px"
  sm: "12px"
  md: "20px"
  lg: "32px"
  xl: "64px"
components:
  button-obsidian:
    backgroundColor: "{colors.obsidian-spotlight}"
    textColor: "{colors.ink-on-dark}"
    rounded: "{rounded.sm}"
    padding: "9px 17px"
  button-primary:
    backgroundColor: "{colors.crimson-accent}"
    textColor: "{colors.ink-on-dark}"
    rounded: "{rounded.sm}"
    padding: "9px 17px"
  button-secondary:
    backgroundColor: "{colors.page-ground}"
    textColor: "{colors.ink-primary}"
    rounded: "{rounded.sm}"
    padding: "9px 17px"
  card-noodle:
    backgroundColor: "{colors.surface-card}"
    textColor: "{colors.ink-primary}"
    rounded: "{rounded.lg}"
    padding: "19px 20px"
  card-noodle-spotlight:
    backgroundColor: "{colors.obsidian-spotlight}"
    textColor: "{colors.ink-on-dark}"
    rounded: "{rounded.lg}"
    padding: "19px 20px"
---

# Design System: RateYourMie (Alabaster & Obsidian Fluid Workspace)

## Overview

**Alabaster & Obsidian Fluid Workspace** menyatukan katalog ulasan 4 aspek mie instan, papan tierlist S–F, dan panel pengelola dalam tata letak dua panel modern yang terinspirasi ruang kerja editorial fluida: sidebar navigasi putih bersih (`248px`), pita perintah & analitik alabaster hangat (`#F3F2EC`), kanvas utama putih murni (`#FFFFFF`), kartu alabaster lembut (`#F7F6F2`), serta kartu sorotan obsidian berpenampilan kontras tinggi (`#18181A`) yang dapat dipindahkan secara interaktif. Dukungan mode gelap *Midnight Warkop* (`#0D0F12` / `#12151B` / `#181C24`) tersedia di seluruh rute publik dan admin.

## Colors

- **Alabaster & Obsidian (Light):** Kanvas utama `#FFFFFF`, sidebar `#FFFFFF`, pita analitik `#F3F2EC`, kartu `#F7F6F2` (hover `#EFECE4`), kapsul aktif `#F2F1EC`, kartu sorotan obsidian `#18181A` (`#252528` elevated), garis batas `#E7E6E0` / `#D2D0C7`, teks `#141517` / `#52565E` / `#62666E`.
- **Midnight Warkop (Dark):** Kanvas `#0D0F12`, sidebar `#12151B`, pita analitik `#181C24`, kartu `#1B1F28`, kapsul aktif `#202530`, sorotan terbalik `#F5F4EF`, garis batas `#252A35` / `#363E4D`, teks `#F4F5F7` / `#A4AAB6` / `#8E95A2`.
- **Accents & Tier Signals:** Crimson (`#D92D20`), Jade (`#0F766E`), Amber (`#D97706`), Cobalt (`#2563EB`), Violet (`#7C3AED`), Slate (`#4B5563`).

## Typography

- **Display / Headings:** `Archivo` (`2.25rem`, `1.48rem`, `1.12rem`, weight `600`) dengan letter-spacing `-0.025em`.
- **Body & UI Controls:** `Public Sans` (`0.95rem` body, `0.84rem` label/button).
- **Numeric / Tabular:** `JetBrains Mono` (`0.75rem` caption/micro-code) dengan `font-variant-numeric: tabular-nums` untuk seluruh skor, harga Rupiah, KPI analitik, dan jumlah penilai.

## Layout & Patterns

- **Shell Dua Panel Terpadu:** Sidebar kiri tetap `248px` di desktop (`>= 1024px`) berisi identitas `RateYourMie`, grup navigasi utama dengan kapsul aktif `#F2F1EC` dan lencana jumlah, daftar `Kategori Cepat`, `Kurator Komunitas` dengan avatar berindikator status, serta footer pengguna dengan pengalih tema Terang/Gelap. Di mobile (`< 1024px`), beralih ke header ringkas lengket berbilah kapsul horizontal.
- **Pita Analitik Alabaster 4 Kolom:** Berisi baris perintah pencarian tanpa bingkai (`/`), dropdown filter inline, tombol CTA obsidian bundar, diikuti 4 kolom visualisasi langsung:
  1. Diagram batang ganda berarsir diagonal & solid (`RAS`, `TEK`, `POR`, `HRG`, `AVG` atau sebaran Tier S–F).
  2. Pengukur setengah lingkaran 36 *tick* radial SVG untuk persentase varian yang lolos syarat minimal penilai.
  3. Dua metrik KPI geometris besar dengan tautan panah kanan (`→`).
- **Kartu Alabaster & Sorotan Obsidian:** Kartu `border-radius: 18px` tanpa garis tepi keras, memuat 4 *micro-bar* proporsional, lencana Tier S–F, serta kapsul harga Rupiah tabular. Kartu sorotan aktif tampil dalam kontras obsidian `#18181A` dengan detail tambahan.
