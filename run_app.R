# ==============================================================================
# SCRIPT UNTUK MENJALANKAN ANALITIKA SOVI NUSANTARA
# ==============================================================================

cat("Memulai Analitika SOVI Nusantara...\n")
cat("===================================\n\n")

# Cek apakah file app.R ada
if (!file.exists("app.R")) {
  stop("File app.R tidak ditemukan! Pastikan Anda berada di direktori yang benar.")
}

# Cek package yang diperlukan
required_packages <- c("shiny", "shinydashboard", "dplyr", "ggplot2", "DT", 
                      "pander", "leaflet", "sf", "car", "knitr", "rmarkdown")

missing_packages <- c()
for (pkg in required_packages) {
  if (!require(pkg, character.only = TRUE, quietly = TRUE)) {
    missing_packages <- c(missing_packages, pkg)
  }
}

if (length(missing_packages) > 0) {
  cat("❌ Package berikut belum terinstall:\n")
  for (pkg in missing_packages) {
    cat(paste("-", pkg, "\n"))
  }
  cat("\nJalankan perintah berikut untuk menginstall:\n")
  cat("source('install_packages.R')\n\n")
  stop("Package belum lengkap!")
}

cat("✅ Semua package tersedia\n")
cat("🚀 Meluncurkan aplikasi...\n\n")

# Informasi aplikasi
cat("=================================================\n")
cat("     ANALITIKA SOVI NUSANTARA                   \n")
cat("  Dashboard Analisis Kerentanan Sosial         \n")
cat("=================================================\n")
cat("Fitur:\n")
cat("• Manajemen dan transformasi data\n")
cat("• Eksplorasi data interaktif\n")
cat("• Uji asumsi statistik\n")
cat("• Statistik inferensia lengkap\n")
cat("• Regresi linear berganda\n")
cat("• Export hasil dalam berbagai format\n")
cat("=================================================\n\n")

cat("Aplikasi akan terbuka di browser Anda...\n")
cat("Tutup aplikasi dengan menekan Ctrl+C di console R\n\n")

# Jalankan aplikasi
tryCatch({
  shiny::runApp("app.R", launch.browser = TRUE)
}, error = function(e) {
  cat("❌ Terjadi error saat menjalankan aplikasi:\n")
  cat(paste("Error:", e$message, "\n\n"))
  cat("Solusi yang dapat dicoba:\n")
  cat("1. Pastikan semua package terinstall dengan benar\n")
  cat("2. Periksa file app.R tidak corrupt\n")
  cat("3. Restart R session dan coba lagi\n")
})