# Analitika SOVI Nusantara

## Deskripsi
Dashboard R Shiny komprehensif untuk analisis data **SOVI (Social Vulnerability Index)** yang menyediakan berbagai tools untuk eksplorasi data, analisis statistik, dan visualisasi untuk memahami pola kerentanan sosial di berbagai wilayah Indonesia.

## Fitur Utama

### 🏠 **Beranda**
- Informasi dashboard dan metadata variabel
- Tabel interaktif metadata dengan 17 variabel SOVI
- Download metadata dalam format PDF

### 📊 **Manajemen Data**
- Transformasi variabel numerik menjadi kategorik
- Preview data transformasi
- Ringkasan statistik variabel asli dan baru
- Interpretasi otomatis proses transformasi
- Download data transformasi dalam format CSV

### 🔍 **Eksplorasi Data**
**Statistik Deskriptif:**
- Analisis multipel variabel
- Interpretasi otomatis statistik deskriptif
- Download laporan statistik (PDF)

**Visualisasi Grafik:**
- Histogram, Boxplot, dan Scatter Plot
- Interpretasi otomatis untuk setiap jenis plot
- Download visualisasi (JPG)

**Analisis Spasial:**
- Heatmap matriks jarak
- Uji autokorelasi spasial (Moran's I)
- Interpretasi pola spasial

### ✅ **Uji Asumsi**
- Uji Normalitas (Shapiro-Wilk) per grup
- Uji Homogenitas Ragam (Levene's Test)
- Interpretasi lengkap hasil uji
- Download laporan asumsi (PDF)

### 🧮 **Statistik Inferensia**
**Uji T:**
- One-sample t-test
- Two-sample independent t-test
- Interpretasi hipotesis dan kesimpulan

**Uji Proporsi & Ragam:**
- Uji proporsi satu/dua sampel
- Uji ragam (Chi-square)
- Interpretasi hasil statistik

**ANOVA:**
- One-Way ANOVA
- Two-Way ANOVA
- Uji Post-Hoc (Tukey HSD)
- Interpretasi efek utama dan interaksi

### 📈 **Regresi Linear Berganda**
- Model regresi dengan multipel variabel independen
- Plot diagnostik lengkap
- Interpretasi R-squared, F-statistik, dan koefisien
- Evaluasi asumsi klasik regresi
- Download laporan regresi (PDF)

## Instalasi

### 1. Instalasi Package R
```r
# Install required packages
install.packages(c(
  "shiny",
  "shinydashboard", 
  "dplyr",
  "ggplot2",
  "DT",
  "pander",
  "leaflet",
  "sf",
  "car",
  "knitr",
  "rmarkdown"
))
```

### 2. Persiapan Data
Pastikan file data tersedia di lokasi berikut:
- `C:/UAS Komstat/sovi_data.csv` - Data utama SOVI
- `C:/UAS Komstat/distance.csv` - Matriks jarak

**Catatan:** Jika file tidak tersedia, aplikasi akan otomatis menggunakan data dummy untuk demonstrasi.

### 3. Menjalankan Aplikasi
```r
# Jalankan aplikasi
shiny::runApp("app.R")
```

## Struktur Data

### Data SOVI (`sovi_data.csv`)
Harus memiliki kolom-kolom berikut:
- `DISTRICTCODE`: Kode wilayah/kabupaten
- `CHILDREN`: Persentase populasi di bawah 5 tahun
- `FEMALE`: Persentase populasi perempuan
- `ELDERLY`: Persentase populasi 65 tahun ke atas
- `FHEAD`: Persentase rumah tangga dengan kepala rumah tangga perempuan
- `FAMILYSIZE`: Rata-rata anggota rumah tangga
- `NOELECTRIC`: Persentase rumah tangga tanpa listrik
- `LOWEDU`: Persentase populasi berpendidikan rendah
- `GROWTH`: Persentase pertumbuhan populasi
- `POVERTY`: Persentase penduduk miskin
- `ILLITERATE`: Persentase populasi buta huruf
- `NOTRAINING`: Persentase rumah tangga tanpa pelatihan bencana
- `DPRONE`: Persentase rumah tangga di daerah rawan bencana
- `RENTED`: Persentase rumah tangga menyewa
- `NOSEWER`: Persentase rumah tangga tanpa drainase
- `TAPWATER`: Persentase rumah tangga dengan air ledeng
- `POPULATION`: Jumlah populasi

### Matriks Jarak (`distance.csv`)
- Matrix NxN berisi jarak antar wilayah
- Diagonal matrix harus bernilai 0

## Cara Penggunaan

### 1. Mulai Analisis
1. Buka aplikasi → Tab **Beranda**
2. Lihat metadata variabel dan fitur yang tersedia
3. Download metadata jika diperlukan

### 2. Transformasi Data
1. Pindah ke tab **Manajemen Data**
2. Pilih variabel numerik untuk dikategorikan
3. Tentukan jumlah kategori (2-10)
4. Lihat preview dan ringkasan transformasi
5. Download data hasil transformasi

### 3. Eksplorasi Data
1. Tab **Eksplorasi Data** → pilih sub-tab:
   - **Statistik Deskriptif**: Pilih variabel → lihat ringkasan → download laporan
   - **Visualisasi Grafik**: Pilih jenis plot → konfigurasikan → download plot
   - **Analisis Spasial**: Pilih variabel → jalankan analisis → download hasil

### 4. Uji Asumsi
1. Tab **Uji Asumsi**
2. Pilih variabel dependen dan grouping
3. Klik "Jalankan Uji Asumsi"
4. Lihat hasil normalitas dan homogenitas
5. Download laporan lengkap

### 5. Statistik Inferensia
**Uji T:**
1. Tab **Statistik Inferensia** → **Uji T**
2. Pilih jenis uji (1-sample/2-sample)
3. Konfigurasikan parameter
4. Klik "Jalankan Uji T"
5. Interpretasi hasil

**Uji Proporsi & Ragam:**
1. Sub-tab **Uji Proporsi & Ragam**
2. Pilih jenis uji dan sampel
3. Input parameter yang diperlukan
4. Jalankan analisis

**ANOVA:**
1. Sub-tab **ANOVA**
2. Pilih jenis ANOVA (One-Way/Two-Way)
3. Tentukan variabel dependen dan faktor
4. Lihat hasil dan uji post-hoc

### 6. Regresi Linear
1. Tab **Regresi Linear Berganda**
2. Pilih variabel dependen
3. Pilih 2+ variabel independen
4. Klik "Jalankan Regresi"
5. Analisis hasil dan plot diagnostik
6. Download laporan lengkap

## Interpretasi Otomatis

Setiap analisis dilengkapi dengan interpretasi otomatis yang mencakup:
- **Hipotesis** (H0 dan H1)
- **Statistik uji** dan p-value
- **Kesimpulan** dalam bahasa Indonesia yang mudah dipahami
- **Rekomendasi** tindak lanjut

## Export dan Download

Aplikasi menyediakan berbagai format download:
- **PDF**: Laporan lengkap, metadata, statistik deskriptif
- **JPG**: Visualisasi dan plot
- **CSV**: Data hasil transformasi

## Keunggulan Aplikasi

1. **User-Friendly**: Interface intuitif dengan navigasi yang jelas
2. **Comprehensive**: Mencakup seluruh tahapan analisis statistik
3. **Interactive**: Semua output responsif terhadap perubahan input
4. **Educational**: Interpretasi otomatis untuk pembelajaran
5. **Professional**: Export hasil dalam format standar
6. **Robust**: Penanganan error dan data dummy untuk demonstrasi

## Teknologi

- **Framework**: R Shiny dengan shinydashboard
- **Visualisasi**: ggplot2, leaflet
- **Analisis**: Base R, car package
- **Export**: rmarkdown, knitr
- **UI/UX**: HTML, CSS, Bootstrap

## Kontributor

Dikembangkan sebagai solusi komprehensif untuk analisis data SOVI dalam konteks ujian Komputasi Statistik.

## Lisensi

Aplikasi ini dibuat untuk tujuan edukatif dan penelitian.

---

**Catatan**: Pastikan semua package R telah terinstall sebelum menjalankan aplikasi. Untuk troubleshooting, periksa console R untuk error messages dan pastikan path file data sesuai dengan konfigurasi sistem Anda.