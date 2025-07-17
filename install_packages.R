# ==============================================================================
# SCRIPT INSTALASI PACKAGE UNTUK ANALITIKA SOVI NUSANTARA
# ==============================================================================

cat("Memulai instalasi package untuk Analitika SOVI Nusantara...\n\n")

# Daftar package yang diperlukan
required_packages <- c(
  "shiny",           # Framework web application
  "shinydashboard",  # Dashboard layout dan komponen
  "dplyr",           # Data manipulation
  "ggplot2",         # Visualisasi data
  "DT",              # Interactive datatables
  "pander",          # Formatting output
  "leaflet",         # Interactive maps
  "sf",              # Spatial features
  "car",             # Companion to Applied Regression
  "knitr",           # Dynamic report generation
  "rmarkdown"        # R Markdown documents
)

# Fungsi untuk instalasi package
install_if_missing <- function(package_name) {
  if (!require(package_name, character.only = TRUE, quietly = TRUE)) {
    cat(paste("Menginstall", package_name, "...\n"))
    install.packages(package_name, dependencies = TRUE, quiet = TRUE)
    
    # Verifikasi instalasi
    if (require(package_name, character.only = TRUE, quietly = TRUE)) {
      cat(paste("✓", package_name, "berhasil diinstall\n"))
    } else {
      cat(paste("✗ Gagal menginstall", package_name, "\n"))
    }
  } else {
    cat(paste("✓", package_name, "sudah tersedia\n"))
  }
}

# Install semua package
cat("Memeriksa dan menginstall package yang diperlukan:\n")
cat("=====================================================\n")

for (package in required_packages) {
  install_if_missing(package)
}

cat("\n=====================================================\n")
cat("Instalasi selesai!\n\n")

# Verifikasi final
cat("Memverifikasi instalasi...\n")
missing_packages <- c()

for (package in required_packages) {
  if (!require(package, character.only = TRUE, quietly = TRUE)) {
    missing_packages <- c(missing_packages, package)
  }
}

if (length(missing_packages) == 0) {
  cat("✅ Semua package berhasil terinstall!\n")
  cat("Anda dapat menjalankan aplikasi dengan perintah:\n")
  cat("shiny::runApp('app.R')\n\n")
} else {
  cat("❌ Package berikut gagal terinstall:\n")
  for (pkg in missing_packages) {
    cat(paste("-", pkg, "\n"))
  }
  cat("\nSilakan install manual dengan perintah:\n")
  cat("install.packages(c('", paste(missing_packages, collapse = "', '"), "'))\n\n")
}

# Informasi versi R
cat("Informasi Sistem:\n")
cat("=================\n")
cat("Versi R:", R.version.string, "\n")
cat("Platform:", R.version$platform, "\n")
cat("Tanggal instalasi:", Sys.Date(), "\n")