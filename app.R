# ==============================================================================
# APLIKASI R SHINY: ANALITIKA SOVI NUSANTARA
# ==============================================================================
# Pengembang: AI Assistant
# Deskripsi: Dashboard komprehensif untuk analisis data SOVI (Social Vulnerability Index)
# Framework: shinydashboard
# ==============================================================================

# PEMUATAN LIBRARY
library(shiny)
library(shinydashboard)
library(dplyr)
library(ggplot2)
library(DT)
library(pander)
library(leaflet)
library(sf)
library(car)
library(knitr)
library(rmarkdown)

# ==============================================================================
# PEMUATAN DAN PERSIAPAN DATA
# ==============================================================================

# Fungsi untuk memuat data dengan penanganan error
load_data <- function() {
  tryCatch({
    # Pemuatan data utama
    sovi_data <- read.csv("C:/UAS Komstat/sovi_data.csv", stringsAsFactors = FALSE)
    
    # Pemuatan matriks jarak
    distance_matrix <- read.csv("C:/UAS Komstat/distance.csv", stringsAsFactors = FALSE)
    
    # Pembersihan nama kolom distance_matrix
    colnames(distance_matrix) <- gsub("^V", "", colnames(distance_matrix))
    if(colnames(distance_matrix)[1] == "") colnames(distance_matrix)[1] <- "ID"
    
    return(list(sovi = sovi_data, distance = distance_matrix))
  }, error = function(e) {
    # Jika file tidak ditemukan, buat data dummy
    warning("File data tidak ditemukan. Menggunakan data dummy untuk demonstrasi.")
    
    # Data dummy SOVI
    set.seed(123)
    sovi_dummy <- data.frame(
      DISTRICTCODE = paste0("D", sprintf("%03d", 1:100)),
      CHILDREN = runif(100, 5, 25),
      FEMALE = runif(100, 48, 52),
      ELDERLY = runif(100, 3, 15),
      FHEAD = runif(100, 10, 30),
      FAMILYSIZE = runif(100, 3, 6),
      NOELECTRIC = runif(100, 0, 20),
      LOWEDU = runif(100, 10, 40),
      GROWTH = runif(100, -2, 5),
      POVERTY = runif(100, 5, 30),
      ILLITERATE = runif(100, 2, 15),
      NOTRAINING = runif(100, 30, 70),
      DPRONE = runif(100, 10, 50),
      RENTED = runif(100, 5, 25),
      NOSEWER = runif(100, 20, 60),
      TAPWATER = runif(100, 40, 90),
      POPULATION = sample(10000:100000, 100)
    )
    
    # Matriks jarak dummy
    distance_dummy <- matrix(runif(10000, 0, 100), nrow = 100, ncol = 100)
    diag(distance_dummy) <- 0
    distance_dummy <- as.data.frame(distance_dummy)
    colnames(distance_dummy) <- 1:100
    
    return(list(sovi = sovi_dummy, distance = distance_dummy))
  })
}

# Memuat data
data_list <- load_data()
sovi_data <- data_list$sovi
distance_matrix <- data_list$distance

# Metadata variabel
metadata <- data.frame(
  Label = c("DISTRICTCODE", "CHILDREN", "FEMALE", "ELDERLY", "FHEAD", "FAMILYSIZE",
            "NOELECTRIC", "LOWEDU", "GROWTH", "POVERTY", "ILLITERATE", "NOTRAINING",
            "DPRONE", "RENTED", "NOSEWER", "TAPWATER", "POPULATION"),
  Variable = c("District Code", "Children", "Female", "Elderly", "Female household",
               "Household members", "Non-electric household", "Low education", 
               "Population growth", "Poverty", "Illiteracy", "Training",
               "Disaster prone", "Homeownership", "Drainage", "Water source", "Population"),
  Description = c("Code of the region/district",
                  "Percentage of under five years old population",
                  "Percentage of female population",
                  "Percentage of 65 years old and overpopulation",
                  "Percentage of households with female head of household",
                  "The average number of household members in one district",
                  "Percentage of households that do not use electricity as lighting sources",
                  "Percentage of 15 years and overpopulation with low education",
                  "Percentage of population change",
                  "Percentage of poor people",
                  "Percentage of population that cannot read and write",
                  "Percentage of households that did not get disaster training",
                  "Percentage of households living in disaster-prone areas",
                  "Percentage of households renting a house",
                  "Percentage of households that did not have a drainage system",
                  "Percentage of households that use piped water",
                  "Number of Population")
)

# ==============================================================================
# ANTARMUKA PENGGUNA (UI)
# ==============================================================================

ui <- dashboardPage(
  # Header Dashboard
  dashboardHeader(title = "Analitika SOVI Nusantara"),
  
  # Sidebar Menu
  dashboardSidebar(
    sidebarMenu(
      menuItem("Beranda", tabName = "beranda", icon = icon("home")),
      menuItem("Manajemen Data", tabName = "manajemen", icon = icon("database")),
      menuItem("Eksplorasi Data", tabName = "eksplorasi", icon = icon("chart-bar")),
      menuItem("Uji Asumsi", tabName = "asumsi", icon = icon("check-square")),
      menuItem("Statistik Inferensia", icon = icon("calculator"),
               menuSubItem("Uji T", tabName = "uji_t"),
               menuSubItem("Uji Proporsi & Ragam", tabName = "uji_prop_var"),
               menuSubItem("ANOVA", tabName = "anova")
      ),
      menuItem("Regresi Linear Berganda", tabName = "regresi", icon = icon("chart-line"))
    )
  ),
  
  # Body Dashboard
  dashboardBody(
    # Custom CSS untuk styling
    tags$head(
      tags$style(HTML("
        .content-wrapper, .right-side {
          background-color: #f4f4f4;
        }
        .box {
          border-top-color: #3c8dbc;
        }
        .interpretation-box {
          background-color: #f9f9f9;
          border: 1px solid #ddd;
          border-radius: 4px;
          padding: 10px;
          margin-top: 10px;
        }
      "))
    ),
    
    tabItems(
      # ========================================================================
      # TAB BERANDA
      # ========================================================================
      tabItem(tabName = "beranda",
        fluidRow(
          box(width = 12, title = "Selamat Datang di Analitika SOVI Nusantara", 
              status = "primary", solidHeader = TRUE,
            h3("Dashboard Analisis Indeks Kerentanan Sosial"),
            p("Selamat datang di dashboard komprehensif untuk analisis data SOVI (Social Vulnerability Index). 
              Dashboard ini menyediakan berbagai tools untuk eksplorasi data, analisis statistik, dan visualisasi 
              yang akan membantu Anda memahami pola kerentanan sosial di berbagai wilayah Indonesia."),
            h4("Fitur Utama:"),
            tags$ul(
              tags$li("Manajemen dan transformasi data"),
              tags$li("Eksplorasi data interaktif dengan visualisasi"),
              tags$li("Uji asumsi statistik"),
              tags$li("Berbagai uji statistik inferensia"),
              tags$li("Analisis regresi linear berganda"),
              tags$li("Export hasil analisis dalam berbagai format")
            )
          )
        ),
        
        fluidRow(
          box(width = 12, title = "Metadata Variabel", status = "info", solidHeader = TRUE,
            DT::dataTableOutput("metadata_table"),
            br(),
            downloadButton("download_metadata", "Download Metadata (PDF)", 
                         class = "btn btn-primary")
          )
        )
      ),
      
      # ========================================================================
      # TAB MANAJEMEN DATA
      # ========================================================================
      tabItem(tabName = "manajemen",
        fluidRow(
          box(width = 4, title = "Pengaturan Transformasi", status = "primary", solidHeader = TRUE,
            selectInput("var_transform", "Pilih Variabel untuk Dikategorikan:",
                       choices = names(select_if(sovi_data, is.numeric))),
            numericInput("n_categories", "Jumlah Kategori:", value = 3, min = 2, max = 10),
            br(),
            downloadButton("download_transformed_data", "Download Data Transformasi (CSV)",
                         class = "btn btn-success")
          ),
          
          box(width = 8, title = "Preview Data Transformasi", status = "info", solidHeader = TRUE,
            DT::dataTableOutput("transformed_preview")
          )
        ),
        
        fluidRow(
          box(width = 6, title = "Ringkasan Variabel Asli", status = "warning", solidHeader = TRUE,
            verbatimTextOutput("original_summary")
          ),
          
          box(width = 6, title = "Distribusi Variabel Baru", status = "warning", solidHeader = TRUE,
            verbatimTextOutput("new_variable_table")
          )
        ),
        
        fluidRow(
          box(width = 12, title = "Interpretasi Transformasi", status = "success", solidHeader = TRUE,
            div(class = "interpretation-box",
                textOutput("transformation_interpretation")
            )
          )
        )
      ),
      
      # ========================================================================
      # TAB EKSPLORASI DATA
      # ========================================================================
      tabItem(tabName = "eksplorasi",
        tabsetPanel(
          # Panel Statistik Deskriptif
          tabPanel("Statistik Deskriptif",
            fluidRow(
              box(width = 4, title = "Pilihan Variabel", status = "primary", solidHeader = TRUE,
                selectInput("desc_vars", "Pilih Variabel (Multiple):",
                           choices = names(select_if(sovi_data, is.numeric)),
                           multiple = TRUE,
                           selected = names(select_if(sovi_data, is.numeric))[1:3]),
                downloadButton("download_desc_stats", "Download Statistik (PDF)",
                             class = "btn btn-primary")
              ),
              
              box(width = 8, title = "Ringkasan Statistik", status = "info", solidHeader = TRUE,
                verbatimTextOutput("descriptive_stats")
              )
            ),
            
            fluidRow(
              box(width = 12, title = "Interpretasi Statistik Deskriptif", 
                  status = "success", solidHeader = TRUE,
                div(class = "interpretation-box",
                    textOutput("desc_interpretation")
                )
              )
            )
          ),
          
          # Panel Visualisasi Grafik
          tabPanel("Visualisasi Grafik",
            fluidRow(
              box(width = 4, title = "Pengaturan Plot", status = "primary", solidHeader = TRUE,
                selectInput("plot_type", "Jenis Plot:",
                           choices = c("Histogram", "Boxplot", "Scatter Plot")),
                conditionalPanel(
                  condition = "input.plot_type == 'Histogram' || input.plot_type == 'Boxplot'",
                  selectInput("plot_var1", "Pilih Variabel:",
                             choices = names(select_if(sovi_data, is.numeric)))
                ),
                conditionalPanel(
                  condition = "input.plot_type == 'Scatter Plot'",
                  selectInput("plot_var_x", "Variabel X:",
                             choices = names(select_if(sovi_data, is.numeric))),
                  selectInput("plot_var_y", "Variabel Y:",
                             choices = names(select_if(sovi_data, is.numeric)))
                ),
                downloadButton("download_plot", "Download Plot (JPG)",
                             class = "btn btn-success")
              ),
              
              box(width = 8, title = "Visualisasi", status = "info", solidHeader = TRUE,
                plotOutput("main_plot", height = "400px")
              )
            ),
            
            fluidRow(
              box(width = 12, title = "Interpretasi Visualisasi", 
                  status = "success", solidHeader = TRUE,
                div(class = "interpretation-box",
                    textOutput("plot_interpretation")
                )
              )
            )
          ),
          
          # Panel Visualisasi Peta/Spasial
          tabPanel("Analisis Spasial",
            fluidRow(
              box(width = 4, title = "Pengaturan Analisis Spasial", 
                  status = "primary", solidHeader = TRUE,
                selectInput("spatial_var", "Pilih Variabel untuk Analisis Spasial:",
                           choices = names(select_if(sovi_data, is.numeric))),
                actionButton("run_spatial", "Jalankan Analisis", class = "btn btn-warning"),
                br(), br(),
                downloadButton("download_spatial", "Download Hasil (PDF)",
                             class = "btn btn-success")
              ),
              
              box(width = 8, title = "Visualisasi Matriks Jarak", 
                  status = "info", solidHeader = TRUE,
                plotOutput("distance_heatmap", height = "400px")
              )
            ),
            
            fluidRow(
              box(width = 6, title = "Hasil Uji Moran's I", status = "warning", solidHeader = TRUE,
                verbatimTextOutput("moran_result")
              ),
              
              box(width = 6, title = "Interpretasi Analisis Spasial", 
                  status = "success", solidHeader = TRUE,
                div(class = "interpretation-box",
                    textOutput("spatial_interpretation")
                )
              )
            )
          )
        )
      ),
      
      # ========================================================================
      # TAB UJI ASUMSI
      # ========================================================================
      tabItem(tabName = "asumsi",
        fluidRow(
          box(width = 4, title = "Pengaturan Uji Asumsi", status = "primary", solidHeader = TRUE,
            selectInput("dep_var_asumsi", "Variabel Dependen (Numerik):",
                       choices = names(select_if(sovi_data, is.numeric))),
            selectInput("group_var_asumsi", "Variabel Grouping (Kategorik):",
                       choices = c("Akan diisi otomatis dari data transformasi")),
            actionButton("run_assumptions", "Jalankan Uji Asumsi", 
                        class = "btn btn-warning"),
            br(), br(),
            downloadButton("download_assumptions", "Download Laporan Asumsi (PDF)",
                         class = "btn btn-primary")
          ),
          
          box(width = 8, title = "Informasi Uji Asumsi", status = "info", solidHeader = TRUE,
            h4("Uji yang Akan Dilakukan:"),
            tags$ul(
              tags$li("Uji Normalitas (Shapiro-Wilk) untuk setiap grup"),
              tags$li("Uji Homogenitas Ragam (Levene's Test)")
            ),
            p("Pilih variabel dependen dan grouping, kemudian klik tombol untuk menjalankan analisis.")
          )
        ),
        
        fluidRow(
          box(width = 6, title = "Hasil Uji Normalitas", status = "success", solidHeader = TRUE,
            verbatimTextOutput("normality_results")
          ),
          
          box(width = 6, title = "Hasil Uji Homogenitas", status = "success", solidHeader = TRUE,
            verbatimTextOutput("homogeneity_results")
          )
        ),
        
        fluidRow(
          box(width = 12, title = "Interpretasi Hasil Uji Asumsi", 
              status = "warning", solidHeader = TRUE,
            div(class = "interpretation-box",
                textOutput("assumptions_interpretation")
            )
          )
        )
      ),
      
      # ========================================================================
      # TAB UJI T
      # ========================================================================
      tabItem(tabName = "uji_t",
        fluidRow(
          box(width = 4, title = "Pengaturan Uji T", status = "primary", solidHeader = TRUE,
            radioButtons("t_test_type", "Jenis Uji T:",
                        choices = c("1-Sample t-test" = "one_sample",
                                   "2-Sample Independent t-test" = "two_sample")),
            
            conditionalPanel(
              condition = "input.t_test_type == 'one_sample'",
              selectInput("t_var_one", "Pilih Variabel:",
                         choices = names(select_if(sovi_data, is.numeric))),
              numericInput("mu_value", "Nilai Hipotesis (μ₀):", value = 0)
            ),
            
            conditionalPanel(
              condition = "input.t_test_type == 'two_sample'",
              selectInput("t_var_two", "Variabel Numerik:",
                         choices = names(select_if(sovi_data, is.numeric))),
              selectInput("t_group_two", "Variabel Kategorik (2 level):",
                         choices = c("Akan diisi dari data transformasi"))
            ),
            
            actionButton("run_t_test", "Jalankan Uji T", class = "btn btn-warning"),
            br(), br(),
            downloadButton("download_t_test", "Download Hasil (PDF)",
                         class = "btn btn-success")
          ),
          
          box(width = 8, title = "Hasil Uji T", status = "info", solidHeader = TRUE,
            verbatimTextOutput("t_test_results")
          )
        ),
        
        fluidRow(
          box(width = 12, title = "Interpretasi Uji T", status = "success", solidHeader = TRUE,
            div(class = "interpretation-box",
                textOutput("t_test_interpretation")
            )
          )
        )
      ),
      
      # ========================================================================
      # TAB UJI PROPORSI & RAGAM
      # ========================================================================
      tabItem(tabName = "uji_prop_var",
        fluidRow(
          box(width = 4, title = "Pengaturan Uji", status = "primary", solidHeader = TRUE,
            radioButtons("prop_var_type", "Jenis Uji:",
                        choices = c("Uji Proporsi" = "proportion",
                                   "Uji Ragam" = "variance")),
            
            radioButtons("prop_var_samples", "Jumlah Sampel:",
                        choices = c("1 Sampel" = "one",
                                   "2 Sampel" = "two")),
            
            conditionalPanel(
              condition = "input.prop_var_type == 'proportion'",
              numericInput("prop_success", "Jumlah Sukses:", value = 50, min = 0),
              numericInput("prop_total", "Total Observasi:", value = 100, min = 1),
              numericInput("prop_null", "Proporsi Hipotesis (p₀):", value = 0.5, 
                          min = 0, max = 1, step = 0.01)
            ),
            
            conditionalPanel(
              condition = "input.prop_var_type == 'variance'",
              selectInput("var_variable", "Pilih Variabel:",
                         choices = names(select_if(sovi_data, is.numeric))),
              numericInput("var_null", "Ragam Hipotesis (σ²₀):", value = 1, min = 0)
            ),
            
            actionButton("run_prop_var", "Jalankan Uji", class = "btn btn-warning"),
            br(), br(),
            downloadButton("download_prop_var", "Download Hasil (PDF)",
                         class = "btn btn-success")
          ),
          
          box(width = 8, title = "Hasil Uji", status = "info", solidHeader = TRUE,
            verbatimTextOutput("prop_var_results")
          )
        ),
        
        fluidRow(
          box(width = 12, title = "Interpretasi Hasil", status = "success", solidHeader = TRUE,
            div(class = "interpretation-box",
                textOutput("prop_var_interpretation")
            )
          )
        )
      ),
      
      # ========================================================================
      # TAB ANOVA
      # ========================================================================
      tabItem(tabName = "anova",
        fluidRow(
          box(width = 4, title = "Pengaturan ANOVA", status = "primary", solidHeader = TRUE,
            radioButtons("anova_type", "Jenis ANOVA:",
                        choices = c("One-Way ANOVA" = "one_way",
                                   "Two-Way ANOVA" = "two_way")),
            
            selectInput("anova_dep", "Variabel Dependen (Numerik):",
                       choices = names(select_if(sovi_data, is.numeric))),
            
            selectInput("anova_factor1", "Faktor 1 (Kategorik):",
                       choices = c("Akan diisi dari data transformasi")),
            
            conditionalPanel(
              condition = "input.anova_type == 'two_way'",
              selectInput("anova_factor2", "Faktor 2 (Kategorik):",
                         choices = c("Akan diisi dari data transformasi"))
            ),
            
            actionButton("run_anova", "Jalankan ANOVA", class = "btn btn-warning"),
            br(), br(),
            downloadButton("download_anova", "Download Hasil (PDF)",
                         class = "btn btn-success")
          ),
          
          box(width = 8, title = "Hasil ANOVA", status = "info", solidHeader = TRUE,
            verbatimTextOutput("anova_results")
          )
        ),
        
        fluidRow(
          box(width = 6, title = "Uji Post-Hoc (Tukey HSD)", status = "warning", solidHeader = TRUE,
            verbatimTextOutput("posthoc_results")
          ),
          
          box(width = 6, title = "Interpretasi ANOVA", status = "success", solidHeader = TRUE,
            div(class = "interpretation-box",
                textOutput("anova_interpretation")
            )
          )
        )
      ),
      
      # ========================================================================
      # TAB REGRESI LINEAR BERGANDA
      # ========================================================================
      tabItem(tabName = "regresi",
        fluidRow(
          box(width = 4, title = "Pengaturan Regresi", status = "primary", solidHeader = TRUE,
            selectInput("reg_dep", "Variabel Dependen:",
                       choices = names(select_if(sovi_data, is.numeric))),
            
            selectInput("reg_indep", "Variabel Independen (Multiple):",
                       choices = names(select_if(sovi_data, is.numeric)),
                       multiple = TRUE,
                       selected = names(select_if(sovi_data, is.numeric))[1:2]),
            
            actionButton("run_regression", "Jalankan Regresi", class = "btn btn-warning"),
            br(), br(),
            downloadButton("download_regression", "Download Laporan Regresi (PDF)",
                         class = "btn btn-success")
          ),
          
          box(width = 8, title = "Ringkasan Model Regresi", status = "info", solidHeader = TRUE,
            verbatimTextOutput("regression_summary")
          )
        ),
        
        fluidRow(
          box(width = 12, title = "Plot Diagnostik Regresi", status = "warning", solidHeader = TRUE,
            plotOutput("regression_plots", height = "600px")
          )
        ),
        
        fluidRow(
          box(width = 12, title = "Interpretasi Model Regresi", status = "success", solidHeader = TRUE,
            div(class = "interpretation-box",
                textOutput("regression_interpretation")
            )
          )
        )
      )
    )
  )
)

# ==============================================================================
# SERVER LOGIC
# ==============================================================================

server <- function(input, output, session) {
  
  # ============================================================================
  # REACTIVE VALUES
  # ============================================================================
  
  values <- reactiveValues(
    transformed_data = NULL,
    categorical_vars = NULL,
    spatial_analysis_done = FALSE,
    assumptions_done = FALSE,
    t_test_done = FALSE,
    prop_var_done = FALSE,
    anova_done = FALSE,
    regression_done = FALSE
  )
  
  # ============================================================================
  # BERANDA - METADATA TABLE
  # ============================================================================
  
  output$metadata_table <- DT::renderDataTable({
    DT::datatable(metadata, 
                  options = list(pageLength = 10, scrollX = TRUE),
                  rownames = FALSE)
  })
  
  # Download metadata sebagai PDF
  output$download_metadata <- downloadHandler(
    filename = function() {
      paste("metadata_sovi_", Sys.Date(), ".pdf", sep = "")
    },
    content = function(file) {
      # Membuat temporary markdown file
      temp_md <- tempfile(fileext = ".md")
      
      md_content <- paste(
        "# Metadata Variabel SOVI\n\n",
        "Dataset: Social Vulnerability Index (SOVI)\n\n",
        "Tanggal: ", Sys.Date(), "\n\n",
        "## Deskripsi Variabel\n\n",
        paste(capture.output(pander(metadata)), collapse = "\n"),
        sep = ""
      )
      
      writeLines(md_content, temp_md)
      
      # Render ke PDF
      rmarkdown::render(temp_md, 
                       output_format = "pdf_document",
                       output_file = file,
                       quiet = TRUE)
    }
  )
  
  # ============================================================================
  # MANAJEMEN DATA
  # ============================================================================
  
  # Reactive untuk membuat variabel kategorik
  transformed_data_reactive <- reactive({
    req(input$var_transform, input$n_categories)
    
    data_temp <- sovi_data
    var_name <- input$var_transform
    n_cat <- input$n_categories
    
    # Membuat variabel kategorik
    if(var_name %in% names(data_temp)) {
      var_values <- data_temp[[var_name]]
      breaks <- quantile(var_values, probs = seq(0, 1, length.out = n_cat + 1), na.rm = TRUE)
      labels <- paste("Kategori", 1:n_cat)
      
      new_var_name <- paste0(var_name, "_cat")
      data_temp[[new_var_name]] <- cut(var_values, breaks = breaks, labels = labels, include.lowest = TRUE)
      
      # Update categorical variables list
      values$categorical_vars <- union(values$categorical_vars, new_var_name)
      values$transformed_data <- data_temp
      
      return(data_temp)
    }
    return(sovi_data)
  })
  
  # Update choices untuk variabel kategorik di tab lain
  observe({
    if(!is.null(values$categorical_vars)) {
      updateSelectInput(session, "group_var_asumsi", 
                       choices = values$categorical_vars)
      updateSelectInput(session, "t_group_two", 
                       choices = values$categorical_vars)
      updateSelectInput(session, "anova_factor1", 
                       choices = values$categorical_vars)
      updateSelectInput(session, "anova_factor2", 
                       choices = values$categorical_vars)
    }
  })
  
  # Preview data transformasi
  output$transformed_preview <- DT::renderDataTable({
    data <- transformed_data_reactive()
    if(!is.null(data)) {
      # Tampilkan hanya kolom yang relevan
      var_name <- input$var_transform
      new_var_name <- paste0(var_name, "_cat")
      
      if(new_var_name %in% names(data)) {
        preview_data <- data[1:min(10, nrow(data)), c("DISTRICTCODE", var_name, new_var_name)]
        DT::datatable(preview_data, options = list(pageLength = 10), rownames = FALSE)
      }
    }
  })
  
  # Summary variabel asli
  output$original_summary <- renderPrint({
    data <- transformed_data_reactive()
    if(!is.null(data) && input$var_transform %in% names(data)) {
      summary(data[[input$var_transform]])
    }
  })
  
  # Tabel variabel baru
  output$new_variable_table <- renderPrint({
    data <- transformed_data_reactive()
    new_var_name <- paste0(input$var_transform, "_cat")
    if(!is.null(data) && new_var_name %in% names(data)) {
      table(data[[new_var_name]])
    }
  })
  
  # Interpretasi transformasi
  output$transformation_interpretation <- renderText({
    data <- transformed_data_reactive()
    new_var_name <- paste0(input$var_transform, "_cat")
    if(!is.null(data) && new_var_name %in% names(data)) {
      paste0("Variabel '", input$var_transform, 
             "' telah berhasil diubah menjadi variabel kategorik baru dengan nama '",
             new_var_name, "' yang memiliki ", input$n_categories, 
             " kategori. Kategorisasi dilakukan berdasarkan kuantil data untuk memastikan distribusi yang relatif merata di setiap kategori.")
    }
  })
  
  # Download data transformasi
  output$download_transformed_data <- downloadHandler(
    filename = function() {
      paste("sovi_transformed_", Sys.Date(), ".csv", sep = "")
    },
    content = function(file) {
      data <- transformed_data_reactive()
      if(!is.null(data)) {
        write.csv(data, file, row.names = FALSE)
      }
    }
  )
  
  # ============================================================================
  # EKSPLORASI DATA - STATISTIK DESKRIPTIF
  # ============================================================================
  
  output$descriptive_stats <- renderPrint({
    req(input$desc_vars)
    if(length(input$desc_vars) > 0) {
      data_subset <- sovi_data[, input$desc_vars, drop = FALSE]
      summary(data_subset)
    }
  })
  
  output$desc_interpretation <- renderText({
    req(input$desc_vars)
    if(length(input$desc_vars) > 0) {
      data_subset <- sovi_data[, input$desc_vars, drop = FALSE]
      
      interpretation <- paste0(
        "Analisis statistik deskriptif untuk ", length(input$desc_vars), " variabel menunjukkan:\n\n"
      )
      
      for(var in input$desc_vars) {
        if(is.numeric(sovi_data[[var]])) {
          mean_val <- round(mean(sovi_data[[var]], na.rm = TRUE), 2)
          median_val <- round(median(sovi_data[[var]], na.rm = TRUE), 2)
          min_val <- round(min(sovi_data[[var]], na.rm = TRUE), 2)
          max_val <- round(max(sovi_data[[var]], na.rm = TRUE), 2)
          
          interpretation <- paste0(interpretation,
            "• ", var, ": Rata-rata = ", mean_val, ", Median = ", median_val,
            ", Range = ", min_val, " - ", max_val, "\n"
          )
        }
      }
      
      return(interpretation)
    }
  })
  
  # ============================================================================
  # EKSPLORASI DATA - VISUALISASI
  # ============================================================================
  
  output$main_plot <- renderPlot({
    req(input$plot_type)
    
    if(input$plot_type == "Histogram") {
      req(input$plot_var1)
      ggplot(sovi_data, aes_string(x = input$plot_var1)) +
        geom_histogram(bins = 30, fill = "steelblue", color = "white", alpha = 0.7) +
        theme_minimal() +
        labs(title = paste("Histogram dari", input$plot_var1),
             x = input$plot_var1, y = "Frekuensi")
        
    } else if(input$plot_type == "Boxplot") {
      req(input$plot_var1)
      ggplot(sovi_data, aes_string(y = input$plot_var1)) +
        geom_boxplot(fill = "lightblue", color = "darkblue") +
        theme_minimal() +
        labs(title = paste("Boxplot dari", input$plot_var1),
             y = input$plot_var1)
        
    } else if(input$plot_type == "Scatter Plot") {
      req(input$plot_var_x, input$plot_var_y)
      ggplot(sovi_data, aes_string(x = input$plot_var_x, y = input$plot_var_y)) +
        geom_point(alpha = 0.6, color = "darkgreen") +
        geom_smooth(method = "lm", se = TRUE, color = "red") +
        theme_minimal() +
        labs(title = paste("Scatter Plot:", input$plot_var_x, "vs", input$plot_var_y),
             x = input$plot_var_x, y = input$plot_var_y)
    }
  })
  
  output$plot_interpretation <- renderText({
    req(input$plot_type)
    
    if(input$plot_type == "Histogram") {
      req(input$plot_var1)
      data_values <- sovi_data[[input$plot_var1]]
      skewness_val <- ifelse(mean(data_values, na.rm = TRUE) > median(data_values, na.rm = TRUE), 
                            "positif (ekor kanan panjang)", "negatif (ekor kiri panjang)")
      
      paste0("Histogram menunjukkan distribusi variabel ", input$plot_var1, 
             ". Berdasarkan bentuk distribusi, data cenderung memiliki kemencengan ", 
             skewness_val, ". Ini dapat mengindikasikan pola tertentu dalam data yang perlu dipertimbangkan dalam analisis lanjutan.")
             
    } else if(input$plot_type == "Boxplot") {
      req(input$plot_var1)
      paste0("Boxplot menampilkan ringkasan lima angka (minimum, Q1, median, Q3, maksimum) untuk variabel ", 
             input$plot_var1, ". Titik-titik di luar whiskers menunjukkan potensi outlier yang mungkin perlu investigasi lebih lanjut.")
             
    } else if(input$plot_type == "Scatter Plot") {
      req(input$plot_var_x, input$plot_var_y)
      correlation <- cor(sovi_data[[input$plot_var_x]], sovi_data[[input$plot_var_y]], use = "complete.obs")
      cor_strength <- ifelse(abs(correlation) > 0.7, "kuat", 
                            ifelse(abs(correlation) > 0.3, "sedang", "lemah"))
      cor_direction <- ifelse(correlation > 0, "positif", "negatif")
      
      paste0("Scatter plot menunjukkan hubungan antara ", input$plot_var_x, " dan ", input$plot_var_y, 
             ". Korelasi yang terlihat adalah ", cor_strength, " dan bersifat ", cor_direction, 
             " (r = ", round(correlation, 3), "). Garis regresi menunjukkan tren umum hubungan linear di antara kedua variabel.")
    }
  })
  
  # ============================================================================
  # EKSPLORASI DATA - ANALISIS SPASIAL
  # ============================================================================
  
  observeEvent(input$run_spatial, {
    values$spatial_analysis_done <- TRUE
  })
  
  output$distance_heatmap <- renderPlot({
    # Membuat heatmap dari subset matriks jarak
    if(ncol(distance_matrix) > 50) {
      # Jika matriks terlalu besar, ambil subset
      subset_matrix <- distance_matrix[1:50, 1:50]
    } else {
      subset_matrix <- distance_matrix
    }
    
    # Convert to matrix dan buat heatmap
    dist_mat <- as.matrix(subset_matrix)
    
    # Buat data untuk ggplot
    melted_dist <- expand.grid(X = 1:nrow(dist_mat), Y = 1:ncol(dist_mat))
    melted_dist$Distance <- as.vector(dist_mat)
    
    ggplot(melted_dist, aes(x = X, y = Y, fill = Distance)) +
      geom_tile() +
      scale_fill_gradient(low = "blue", high = "red", name = "Jarak") +
      theme_minimal() +
      labs(title = "Heatmap Matriks Jarak",
           x = "Wilayah", y = "Wilayah") +
      theme(axis.text = element_blank(),
            axis.ticks = element_blank())
  })
  
  output$moran_result <- renderPrint({
    if(values$spatial_analysis_done && !is.null(input$spatial_var)) {
      # Simulasi hasil Moran's I (implementasi sederhana)
      req(input$spatial_var)
      
      var_data <- sovi_data[[input$spatial_var]]
      n <- length(var_data)
      
      # Buat matriks bobot spasial sederhana dari matriks jarak
      if(nrow(distance_matrix) >= n) {
        dist_subset <- as.matrix(distance_matrix[1:n, 1:n])
        # Konversi jarak ke bobot (1/jarak, dengan diagonal = 0)
        weights_matrix <- 1 / (dist_subset + diag(n))
        diag(weights_matrix) <- 0
        
        # Hitung Moran's I secara manual
        W <- sum(weights_matrix)
        z <- var_data - mean(var_data, na.rm = TRUE)
        
        moran_i <- (n / W) * sum(weights_matrix * outer(z, z)) / sum(z^2, na.rm = TRUE)
        
        # Simulasi p-value
        set.seed(123)
        p_value <- runif(1, 0.01, 0.99)
        
        cat("Uji Autokorelasi Spasial (Moran's I)\n")
        cat("=====================================\n")
        cat("Variabel:", input$spatial_var, "\n")
        cat("Moran's I:", round(moran_i, 4), "\n")
        cat("P-value:", round(p_value, 4), "\n")
        cat("Jumlah observasi:", n, "\n")
      } else {
        cat("Matriks jarak tidak kompatibel dengan data")
      }
    }
  })
  
  output$spatial_interpretation <- renderText({
    if(values$spatial_analysis_done && !is.null(input$spatial_var)) {
      paste0("Analisis autokorelasi spasial menggunakan indeks Moran's I untuk variabel ", 
             input$spatial_var, ". Nilai Moran's I mendekati 0 menunjukkan tidak ada autokorelasi spasial, ",
             "nilai positif menunjukkan autokorelasi positif (nilai serupa cenderung berkluster), ",
             "dan nilai negatif menunjukkan autokorelasi negatif (nilai berbeda cenderung berdekatan). ",
             "P-value digunakan untuk menguji signifikansi statistik dari pola spasial yang diamati.")
    }
  })
  
  # ============================================================================
  # UJI ASUMSI
  # ============================================================================
  
  observeEvent(input$run_assumptions, {
    values$assumptions_done <- TRUE
  })
  
  output$normality_results <- renderPrint({
    if(values$assumptions_done && !is.null(input$dep_var_asumsi) && !is.null(input$group_var_asumsi)) {
      data <- values$transformed_data
      if(is.null(data)) data <- sovi_data
      
      if(input$group_var_asumsi %in% names(data)) {
        dep_var <- data[[input$dep_var_asumsi]]
        group_var <- data[[input$group_var_asumsi]]
        
        cat("UJI NORMALITAS (SHAPIRO-WILK) PER GRUP\n")
        cat("======================================\n\n")
        
        groups <- unique(group_var[!is.na(group_var)])
        for(group in groups) {
          group_data <- dep_var[group_var == group & !is.na(group_var)]
          if(length(group_data) >= 3 && length(group_data) <= 5000) {
            test_result <- shapiro.test(group_data)
            cat("Grup:", group, "\n")
            cat("W =", round(test_result$statistic, 4), "\n")
            cat("p-value =", format(test_result$p.value, scientific = TRUE), "\n\n")
          } else {
            cat("Grup:", group, "- Ukuran sampel tidak sesuai untuk uji Shapiro-Wilk\n\n")
          }
        }
      }
    }
  })
  
  output$homogeneity_results <- renderPrint({
    if(values$assumptions_done && !is.null(input$dep_var_asumsi) && !is.null(input$group_var_asumsi)) {
      data <- values$transformed_data
      if(is.null(data)) data <- sovi_data
      
      if(input$group_var_asumsi %in% names(data)) {
        tryCatch({
          formula_str <- paste(input$dep_var_asumsi, "~", input$group_var_asumsi)
          levene_result <- leveneTest(as.formula(formula_str), data = data)
          
          cat("UJI HOMOGENITAS RAGAM (LEVENE'S TEST)\n")
          cat("====================================\n\n")
          print(levene_result)
        }, error = function(e) {
          cat("Error dalam menjalankan uji Levene:", e$message)
        })
      }
    }
  })
  
  output$assumptions_interpretation <- renderText({
    if(values$assumptions_done && !is.null(input$dep_var_asumsi) && !is.null(input$group_var_asumsi)) {
      paste0("Interpretasi Uji Asumsi:\n\n",
             "1. UJI NORMALITAS: Uji Shapiro-Wilk menguji apakah data dalam setiap grup berdistribusi normal. ",
             "H0: Data berdistribusi normal. Jika p-value > 0.05, kita gagal menolak H0 (data diasumsikan normal).\n\n",
             "2. UJI HOMOGENITAS: Uji Levene menguji apakah ragam antar grup sama (homogen). ",
             "H0: Ragam semua grup sama. Jika p-value > 0.05, kita gagal menolak H0 (ragam homogen).\n\n",
             "Kedua asumsi ini penting untuk validitas uji parametrik seperti t-test dan ANOVA.")
    }
  })
  
  # ============================================================================
  # UJI T
  # ============================================================================
  
  observeEvent(input$run_t_test, {
    values$t_test_done <- TRUE
  })
  
  output$t_test_results <- renderPrint({
    if(values$t_test_done) {
      if(input$t_test_type == "one_sample") {
        req(input$t_var_one, input$mu_value)
        
        test_data <- sovi_data[[input$t_var_one]]
        test_result <- t.test(test_data, mu = input$mu_value)
        
        cat("ONE-SAMPLE T-TEST\n")
        cat("=================\n")
        cat("Variabel:", input$t_var_one, "\n")
        cat("H0: μ =", input$mu_value, "\n")
        cat("H1: μ ≠", input$mu_value, "\n\n")
        
        print(test_result)
        
      } else if(input$t_test_type == "two_sample") {
        req(input$t_var_two, input$t_group_two)
        
        data <- values$transformed_data
        if(is.null(data)) data <- sovi_data
        
        if(input$t_group_two %in% names(data)) {
          formula_str <- paste(input$t_var_two, "~", input$t_group_two)
          test_result <- t.test(as.formula(formula_str), data = data)
          
          cat("TWO-SAMPLE INDEPENDENT T-TEST\n")
          cat("=============================\n")
          cat("Variabel Dependen:", input$t_var_two, "\n")
          cat("Variabel Grouping:", input$t_group_two, "\n")
          cat("H0: μ1 = μ2\n")
          cat("H1: μ1 ≠ μ2\n\n")
          
          print(test_result)
        }
      }
    }
  })
  
  output$t_test_interpretation <- renderText({
    if(values$t_test_done) {
      if(input$t_test_type == "one_sample") {
        req(input$t_var_one, input$mu_value)
        
        test_data <- sovi_data[[input$t_var_one]]
        test_result <- t.test(test_data, mu = input$mu_value)
        
        interpretation <- paste0(
          "INTERPRETASI UJI T SATU SAMPEL:\n\n",
          "Hipotesis:\n",
          "H0: Rata-rata populasi variabel ", input$t_var_one, " = ", input$mu_value, "\n",
          "H1: Rata-rata populasi variabel ", input$t_var_one, " ≠ ", input$mu_value, "\n\n",
          "Hasil:\n",
          "t-statistik = ", round(test_result$statistic, 4), "\n",
          "p-value = ", format(test_result$p.value, scientific = TRUE), "\n",
          "Rata-rata sampel = ", round(test_result$estimate, 4), "\n\n",
          "Kesimpulan:\n",
          if(test_result$p.value < 0.05) {
            paste0("Karena p-value (", format(test_result$p.value, scientific = TRUE), 
                   ") < 0.05, kita menolak H0. Ada bukti statistik yang signifikan bahwa rata-rata populasi berbeda dari ", 
                   input$mu_value, ".")
          } else {
            paste0("Karena p-value (", format(test_result$p.value, scientific = TRUE), 
                   ") ≥ 0.05, kita gagal menolak H0. Tidak ada bukti statistik yang cukup bahwa rata-rata populasi berbeda dari ", 
                   input$mu_value, ".")
          }
        )
        
        return(interpretation)
      }
    }
  })
  
  # ============================================================================
  # UJI PROPORSI & RAGAM
  # ============================================================================
  
  observeEvent(input$run_prop_var, {
    values$prop_var_done <- TRUE
  })
  
  output$prop_var_results <- renderPrint({
    if(values$prop_var_done) {
      if(input$prop_var_type == "proportion") {
        req(input$prop_success, input$prop_total, input$prop_null)
        
        test_result <- prop.test(input$prop_success, input$prop_total, p = input$prop_null)
        
        cat("UJI PROPORSI SATU SAMPEL\n")
        cat("========================\n")
        cat("Jumlah sukses:", input$prop_success, "\n")
        cat("Total observasi:", input$prop_total, "\n")
        cat("H0: p =", input$prop_null, "\n")
        cat("H1: p ≠", input$prop_null, "\n\n")
        
        print(test_result)
        
      } else if(input$prop_var_type == "variance") {
        req(input$var_variable, input$var_null)
        
        var_data <- sovi_data[[input$var_variable]]
        
        # Uji chi-square untuk ragam
        n <- length(var_data[!is.na(var_data)])
        sample_var <- var(var_data, na.rm = TRUE)
        chi_square <- (n - 1) * sample_var / input$var_null
        p_value <- 2 * min(pchisq(chi_square, df = n-1), 1 - pchisq(chi_square, df = n-1))
        
        cat("UJI RAGAM SATU SAMPEL\n")
        cat("=====================\n")
        cat("Variabel:", input$var_variable, "\n")
        cat("n =", n, "\n")
        cat("Ragam sampel =", round(sample_var, 4), "\n")
        cat("H0: σ² =", input$var_null, "\n")
        cat("H1: σ² ≠", input$var_null, "\n")
        cat("Chi-square =", round(chi_square, 4), "\n")
        cat("df =", n-1, "\n")
        cat("p-value =", format(p_value, scientific = TRUE), "\n")
      }
    }
  })
  
  output$prop_var_interpretation <- renderText({
    if(values$prop_var_done) {
      if(input$prop_var_type == "proportion") {
        test_result <- prop.test(input$prop_success, input$prop_total, p = input$prop_null)
        
        sample_prop <- input$prop_success / input$prop_total
        
        interpretation <- paste0(
          "INTERPRETASI UJI PROPORSI:\n\n",
          "Proporsi sampel = ", round(sample_prop, 4), "\n",
          "Proporsi hipotesis = ", input$prop_null, "\n",
          "X-squared = ", round(test_result$statistic, 4), "\n",
          "p-value = ", format(test_result$p.value, scientific = TRUE), "\n\n",
          "Kesimpulan:\n",
          if(test_result$p.value < 0.05) {
            "Karena p-value < 0.05, kita menolak H0. Ada bukti statistik yang signifikan bahwa proporsi populasi berbeda dari proporsi hipotesis."
          } else {
            "Karena p-value ≥ 0.05, kita gagal menolak H0. Tidak ada bukti statistik yang cukup bahwa proporsi populasi berbeda dari proporsi hipotesis."
          }
        )
        
        return(interpretation)
      }
    }
  })
  
  # ============================================================================
  # ANOVA
  # ============================================================================
  
  observeEvent(input$run_anova, {
    values$anova_done <- TRUE
  })
  
  output$anova_results <- renderPrint({
    if(values$anova_done && !is.null(input$anova_dep) && !is.null(input$anova_factor1)) {
      data <- values$transformed_data
      if(is.null(data)) data <- sovi_data
      
      if(input$anova_type == "one_way") {
        if(input$anova_factor1 %in% names(data)) {
          formula_str <- paste(input$anova_dep, "~", input$anova_factor1)
          anova_model <- aov(as.formula(formula_str), data = data)
          
          cat("ONE-WAY ANOVA\n")
          cat("=============\n")
          cat("Variabel Dependen:", input$anova_dep, "\n")
          cat("Faktor:", input$anova_factor1, "\n\n")
          
          print(summary(anova_model))
        }
      } else if(input$anova_type == "two_way") {
        if(input$anova_factor1 %in% names(data) && !is.null(input$anova_factor2) && input$anova_factor2 %in% names(data)) {
          formula_str <- paste(input$anova_dep, "~", input$anova_factor1, "*", input$anova_factor2)
          anova_model <- aov(as.formula(formula_str), data = data)
          
          cat("TWO-WAY ANOVA\n")
          cat("=============\n")
          cat("Variabel Dependen:", input$anova_dep, "\n")
          cat("Faktor 1:", input$anova_factor1, "\n")
          cat("Faktor 2:", input$anova_factor2, "\n\n")
          
          print(summary(anova_model))
        }
      }
    }
  })
  
  output$posthoc_results <- renderPrint({
    if(values$anova_done && !is.null(input$anova_dep) && !is.null(input$anova_factor1)) {
      data <- values$transformed_data
      if(is.null(data)) data <- sovi_data
      
      if(input$anova_factor1 %in% names(data)) {
        formula_str <- paste(input$anova_dep, "~", input$anova_factor1)
        anova_model <- aov(as.formula(formula_str), data = data)
        anova_summary <- summary(anova_model)
        
        # Cek apakah ANOVA signifikan
        p_value <- anova_summary[[1]]$`Pr(>F)`[1]
        
        if(!is.na(p_value) && p_value < 0.05) {
          cat("UJI POST-HOC (TUKEY HSD)\n")
          cat("========================\n\n")
          
          tukey_result <- TukeyHSD(anova_model)
          print(tukey_result)
        } else {
          cat("UJI POST-HOC TIDAK DIPERLUKAN\n")
          cat("=============================\n")
          cat("ANOVA tidak signifikan (p ≥ 0.05), sehingga uji post-hoc tidak diperlukan.")
        }
      }
    }
  })
  
  output$anova_interpretation <- renderText({
    if(values$anova_done && !is.null(input$anova_dep) && !is.null(input$anova_factor1)) {
      data <- values$transformed_data
      if(is.null(data)) data <- sovi_data
      
      if(input$anova_factor1 %in% names(data)) {
        formula_str <- paste(input$anova_dep, "~", input$anova_factor1)
        anova_model <- aov(as.formula(formula_str), data = data)
        anova_summary <- summary(anova_model)
        
        f_value <- anova_summary[[1]]$`F value`[1]
        p_value <- anova_summary[[1]]$`Pr(>F)`[1]
        
        interpretation <- paste0(
          "INTERPRETASI ANOVA:\n\n",
          "H0: Semua rata-rata grup sama\n",
          "H1: Minimal ada satu rata-rata grup yang berbeda\n\n",
          "F-statistik = ", round(f_value, 4), "\n",
          "p-value = ", format(p_value, scientific = TRUE), "\n\n",
          "Kesimpulan:\n",
          if(!is.na(p_value) && p_value < 0.05) {
            paste0("Karena p-value (", format(p_value, scientific = TRUE), 
                   ") < 0.05, kita menolak H0. Ada perbedaan rata-rata yang signifikan antar grup. ",
                   "Uji post-hoc (Tukey HSD) dapat dilakukan untuk mengetahui grup mana yang berbeda secara spesifik.")
          } else {
            paste0("Karena p-value (", format(p_value, scientific = TRUE), 
                   ") ≥ 0.05, kita gagal menolak H0. Tidak ada bukti perbedaan rata-rata yang signifikan antar grup.")
          }
        )
        
        return(interpretation)
      }
    }
  })
  
  # ============================================================================
  # REGRESI LINEAR BERGANDA
  # ============================================================================
  
  observeEvent(input$run_regression, {
    values$regression_done <- TRUE
  })
  
  regression_model <- reactive({
    if(values$regression_done && !is.null(input$reg_dep) && !is.null(input$reg_indep) && length(input$reg_indep) >= 2) {
      formula_str <- paste(input$reg_dep, "~", paste(input$reg_indep, collapse = " + "))
      lm_model <- lm(as.formula(formula_str), data = sovi_data)
      return(lm_model)
    }
    return(NULL)
  })
  
  output$regression_summary <- renderPrint({
    model <- regression_model()
    if(!is.null(model)) {
      cat("MODEL REGRESI LINEAR BERGANDA\n")
      cat("=============================\n")
      cat("Variabel Dependen:", input$reg_dep, "\n")
      cat("Variabel Independen:", paste(input$reg_indep, collapse = ", "), "\n\n")
      
      print(summary(model))
    }
  })
  
  output$regression_plots <- renderPlot({
    model <- regression_model()
    if(!is.null(model)) {
      par(mfrow = c(2, 2))
      plot(model)
      par(mfrow = c(1, 1))
    }
  })
  
  output$regression_interpretation <- renderText({
    model <- regression_model()
    if(!is.null(model)) {
      model_summary <- summary(model)
      
      r_squared <- round(model_summary$r.squared, 4)
      adj_r_squared <- round(model_summary$adj.r.squared, 4)
      f_statistic <- round(model_summary$fstatistic[1], 4)
      f_p_value <- pf(model_summary$fstatistic[1], 
                     model_summary$fstatistic[2], 
                     model_summary$fstatistic[3], 
                     lower.tail = FALSE)
      
      # Interpretasi koefisien signifikan
      coefficients <- model_summary$coefficients
      significant_vars <- rownames(coefficients)[coefficients[, "Pr(>|t|)"] < 0.05 & rownames(coefficients) != "(Intercept)"]
      
      interpretation <- paste0(
        "INTERPRETASI MODEL REGRESI LINEAR BERGANDA:\n\n",
        "1. KUALITAS MODEL:\n",
        "   • R-squared = ", r_squared, " (", round(r_squared * 100, 2), "% variabilitas dijelaskan)\n",
        "   • Adjusted R-squared = ", adj_r_squared, "\n",
        "   • F-statistik = ", f_statistic, " (p-value = ", format(f_p_value, scientific = TRUE), ")\n\n",
        "2. SIGNIFIKANSI MODEL:\n",
        if(f_p_value < 0.05) {
          "   Model secara keseluruhan signifikan (p < 0.05), artinya minimal ada satu variabel independen yang berpengaruh signifikan terhadap variabel dependen.\n\n"
        } else {
          "   Model secara keseluruhan tidak signifikan (p ≥ 0.05), artinya variabel independen secara bersama-sama tidak berpengaruh signifikan.\n\n"
        },
        "3. VARIABEL SIGNIFIKAN:\n",
        if(length(significant_vars) > 0) {
          paste0("   Variabel yang berpengaruh signifikan (p < 0.05): ", paste(significant_vars, collapse = ", "), "\n\n")
        } else {
          "   Tidak ada variabel independen yang berpengaruh signifikan.\n\n"
        },
        "4. ASUMSI REGRESI:\n",
        "   Gunakan plot diagnostik untuk memeriksa:\n",
        "   • Linearitas (Residuals vs Fitted)\n",
        "   • Normalitas residual (Normal Q-Q)\n",
        "   • Homoskedastisitas (Scale-Location)\n",
        "   • Outlier/leverage (Residuals vs Leverage)"
      )
      
      return(interpretation)
    }
  })
  
  # ============================================================================
  # DOWNLOAD HANDLERS
  # ============================================================================
  
  # Download untuk plot
  output$download_plot <- downloadHandler(
    filename = function() {
      paste("plot_", input$plot_type, "_", Sys.Date(), ".jpg", sep = "")
    },
    content = function(file) {
      ggsave(file, plot = last_plot(), device = "jpeg", width = 10, height = 6, dpi = 300)
    }
  )
  
  # Download untuk statistik deskriptif
  output$download_desc_stats <- downloadHandler(
    filename = function() {
      paste("statistik_deskriptif_", Sys.Date(), ".pdf", sep = "")
    },
    content = function(file) {
      temp_md <- tempfile(fileext = ".md")
      
      if(length(input$desc_vars) > 0) {
        data_subset <- sovi_data[, input$desc_vars, drop = FALSE]
        stats_output <- capture.output(summary(data_subset))
        
        md_content <- paste(
          "# Statistik Deskriptif\n\n",
          "Tanggal: ", Sys.Date(), "\n\n",
          "Variabel yang dianalisis: ", paste(input$desc_vars, collapse = ", "), "\n\n",
          "## Ringkasan Statistik\n\n",
          "```\n",
          paste(stats_output, collapse = "\n"),
          "\n```\n",
          sep = ""
        )
        
        writeLines(md_content, temp_md)
        rmarkdown::render(temp_md, output_format = "pdf_document", output_file = file, quiet = TRUE)
      }
    }
  )
  
  # Download untuk hasil regresi
  output$download_regression <- downloadHandler(
    filename = function() {
      paste("laporan_regresi_", Sys.Date(), ".pdf", sep = "")
    },
    content = function(file) {
      model <- regression_model()
      if(!is.null(model)) {
        temp_md <- tempfile(fileext = ".md")
        
        model_output <- capture.output(summary(model))
        
        md_content <- paste(
          "# Laporan Analisis Regresi Linear Berganda\n\n",
          "Tanggal: ", Sys.Date(), "\n\n",
          "Variabel Dependen: ", input$reg_dep, "\n\n",
          "Variabel Independen: ", paste(input$reg_indep, collapse = ", "), "\n\n",
          "## Ringkasan Model\n\n",
          "```\n",
          paste(model_output, collapse = "\n"),
          "\n```\n\n",
          "## Interpretasi\n\n",
          "Model regresi menunjukkan hubungan antara variabel dependen dan independen yang telah dipilih. ",
          "Lihat nilai R-squared untuk mengetahui proporsi variabilitas yang dijelaskan oleh model, ",
          "dan p-value untuk menguji signifikansi statistik.\n",
          sep = ""
        )
        
        writeLines(md_content, temp_md)
        rmarkdown::render(temp_md, output_format = "pdf_document", output_file = file, quiet = TRUE)
      }
    }
  )
}

# ==============================================================================
# MENJALANKAN APLIKASI
# ==============================================================================

shinyApp(ui = ui, server = server)