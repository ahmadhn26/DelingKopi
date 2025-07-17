# Install required packages for Statistical Analysis Shiny App
# Run this script before running the main application

# List of required packages
required_packages <- c(
  "shiny",
  "shinydashboard", 
  "shinydashboardPlus",
  "DT",
  "plotly",
  "dplyr",
  "ggplot2",
  "corrplot",
  "leaflet",
  "sf",
  "spdep",
  "rmarkdown",
  "knitr",
  "fresh",
  "nortest",
  "car",
  "lmtest"
)

# Function to install packages if they're not already installed
install_if_missing <- function(packages) {
  for (pkg in packages) {
    if (!require(pkg, character.only = TRUE)) {
      cat("Installing package:", pkg, "\n")
      install.packages(pkg, dependencies = TRUE)
    } else {
      cat("Package", pkg, "already installed\n")
    }
  }
}

# Install missing packages
cat("Checking and installing required packages...\n")
install_if_missing(required_packages)

cat("\nAll required packages are now installed!\n")
cat("You can now run the Shiny application with: shiny::runApp('app.R')\n")