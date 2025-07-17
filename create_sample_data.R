# ==============================================================================
# SCRIPT PEMBUATAN DATA CONTOH UNTUK ANALITIKA SOVI NUSANTARA
# ==============================================================================

cat("Membuat data contoh untuk testing aplikasi...\n\n")

# Set seed untuk reproducibility
set.seed(123)

# ==============================================================================
# 1. MEMBUAT DATA SOVI CONTOH
# ==============================================================================

cat("Membuat data SOVI contoh...\n")

# Jumlah wilayah/kabupaten
n_districts <- 100

# Buat data SOVI
sovi_sample <- data.frame(
  DISTRICTCODE = paste0("D", sprintf("%03d", 1:n_districts)),
  
  # Variabel demografi (persentase)
  CHILDREN = round(runif(n_districts, 5, 25), 2),      # 5-25%
  FEMALE = round(runif(n_districts, 48, 52), 2),       # 48-52%
  ELDERLY = round(runif(n_districts, 3, 15), 2),       # 3-15%
  FHEAD = round(runif(n_districts, 10, 30), 2),        # 10-30%
  
  # Variabel rumah tangga
  FAMILYSIZE = round(runif(n_districts, 3, 6), 2),     # 3-6 orang
  NOELECTRIC = round(runif(n_districts, 0, 20), 2),    # 0-20%
  RENTED = round(runif(n_districts, 5, 25), 2),        # 5-25%
  NOSEWER = round(runif(n_districts, 20, 60), 2),      # 20-60%
  TAPWATER = round(runif(n_districts, 40, 90), 2),     # 40-90%
  
  # Variabel pendidikan dan sosial
  LOWEDU = round(runif(n_districts, 10, 40), 2),       # 10-40%
  ILLITERATE = round(runif(n_districts, 2, 15), 2),    # 2-15%
  NOTRAINING = round(runif(n_districts, 30, 70), 2),   # 30-70%
  
  # Variabel ekonomi dan bencana
  POVERTY = round(runif(n_districts, 5, 30), 2),       # 5-30%
  DPRONE = round(runif(n_districts, 10, 50), 2),       # 10-50%
  GROWTH = round(runif(n_districts, -2, 5), 2),        # -2% to 5%
  
  # Populasi
  POPULATION = sample(10000:100000, n_districts, replace = TRUE)
)

# Tambahkan beberapa korelasi realistis
# Daerah dengan poverty tinggi cenderung memiliki pendidikan rendah
high_poverty_idx <- which(sovi_sample$POVERTY > 20)
sovi_sample$LOWEDU[high_poverty_idx] <- sovi_sample$LOWEDU[high_poverty_idx] + 
  runif(length(high_poverty_idx), 5, 15)
sovi_sample$LOWEDU[sovi_sample$LOWEDU > 40] <- 40

# Daerah dengan listrik rendah cenderung di daerah rawan bencana
no_electric_idx <- which(sovi_sample$NOELECTRIC > 15)
sovi_sample$DPRONE[no_electric_idx] <- sovi_sample$DPRONE[no_electric_idx] + 
  runif(length(no_electric_idx), 10, 20)
sovi_sample$DPRONE[sovi_sample$DPRONE > 50] <- 50

cat("✓ Data SOVI contoh berhasil dibuat (", nrow(sovi_sample), " wilayah)\n")

# ==============================================================================
# 2. MEMBUAT MATRIKS JARAK CONTOH
# ==============================================================================

cat("Membuat matriks jarak contoh...\n")

# Buat koordinat dummy untuk setiap wilayah
coords <- data.frame(
  x = runif(n_districts, 0, 100),
  y = runif(n_districts, 0, 100)
)

# Hitung matriks jarak Euclidean
distance_matrix <- matrix(0, nrow = n_districts, ncol = n_districts)

for(i in 1:n_districts) {
  for(j in 1:n_districts) {
    if(i != j) {
      distance_matrix[i, j] <- sqrt((coords$x[i] - coords$x[j])^2 + 
                                   (coords$y[i] - coords$y[j])^2)
    }
  }
}

# Bulatkan ke 2 desimal
distance_matrix <- round(distance_matrix, 2)

# Konversi ke data frame untuk export
distance_df <- as.data.frame(distance_matrix)
colnames(distance_df) <- 1:n_districts

cat("✓ Matriks jarak contoh berhasil dibuat (", nrow(distance_df), "x", ncol(distance_df), ")\n")

# ==============================================================================
# 3. SIMPAN DATA
# ==============================================================================

cat("\nMenyimpan file data...\n")

# Buat direktori jika belum ada
if (!dir.exists("sample_data")) {
  dir.create("sample_data")
}

# Simpan data SOVI
write.csv(sovi_sample, "sample_data/sovi_data_sample.csv", row.names = FALSE)
cat("✓ Data SOVI disimpan di: sample_data/sovi_data_sample.csv\n")

# Simpan matriks jarak
write.csv(distance_df, "sample_data/distance_sample.csv", row.names = FALSE)
cat("✓ Matriks jarak disimpan di: sample_data/distance_sample.csv\n")

# ==============================================================================
# 4. INFORMASI DATA
# ==============================================================================

cat("\n" , rep("=", 60), "\n")
cat("INFORMASI DATA CONTOH\n")
cat(rep("=", 60), "\n")

cat("Data SOVI:\n")
cat("- Jumlah wilayah:", nrow(sovi_sample), "\n")
cat("- Jumlah variabel:", ncol(sovi_sample), "\n")
cat("- Variabel numerik:", sum(sapply(sovi_sample, is.numeric)), "\n")

cat("\nStatistik ringkas beberapa variabel:\n")
print(summary(sovi_sample[, c("CHILDREN", "POVERTY", "POPULATION")]))

cat("\nMatriks Jarak:\n")
cat("- Dimensi:", nrow(distance_df), "x", ncol(distance_df), "\n")
cat("- Jarak minimum (non-zero):", round(min(distance_matrix[distance_matrix > 0]), 2), "\n")
cat("- Jarak maksimum:", round(max(distance_matrix), 2), "\n")
cat("- Jarak rata-rata:", round(mean(distance_matrix[distance_matrix > 0]), 2), "\n")

cat("\n" , rep("=", 60), "\n")
cat("Data contoh siap digunakan!\n")
cat("Untuk menggunakan dalam aplikasi, salin file ke lokasi yang sesuai atau\n")
cat("modifikasi path dalam app.R\n")
cat(rep("=", 60), "\n")