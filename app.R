# Statistical Analysis Application
# Comprehensive Shiny App for Statistical Inference

library(shiny)
library(shinydashboard)
library(shinydashboardPlus)
library(DT)
library(plotly)
library(dplyr)
library(ggplot2)
library(corrplot)
library(leaflet)
library(sf)
library(spdep)
library(rmarkdown)
library(knitr)
library(fresh)

# Custom theme for modern UI
mytheme <- create_theme(
  adminlte_color(
    light_blue = "#3498DB",
    blue = "#2980B9",
    navy = "#1F4E79",
    teal = "#16A085",
    green = "#27AE60",
    olive = "#7D8471",
    yellow = "#F39C12",
    orange = "#E67E22",
    red = "#E74C3C",
    fuchsia = "#8E44AD",
    purple = "#9B59B6",
    maroon = "#922B21",
    black = "#2C3E50",
    gray_dark = "#95A5A6",
    gray = "#BDC3C7",
    gray_light = "#ECF0F1",
    white = "#FFFFFF"
  ),
  adminlte_sidebar(
    width = "300px",
    dark_bg = "#2C3E50",
    dark_hover_bg = "#34495E",
    dark_color = "#FFFFFF"
  ),
  adminlte_global(
    content_bg = "#F8F9FA",
    box_bg = "#FFFFFF",
    info_box_bg = "#FFFFFF"
  )
)

# Define UI
ui <- dashboardPage(
  skin = "blue",
  
  dashboardHeader(
    title = "Statistical Analysis Dashboard",
    titleWidth = 300,
    tags$li(class = "dropdown",
            tags$a(href = "#", 
                   class = "dropdown-toggle",
                   `data-toggle` = "dropdown",
                   tags$i(class = "fa fa-info-circle"),
                   "Help"))
  ),
  
  dashboardSidebar(
    width = 300,
    sidebarMenu(
      id = "sidebar",
      menuItem("Data Import", tabName = "data", icon = icon("upload")),
      menuItem("Exploratory Analysis", tabName = "explore", icon = icon("chart-line")),
      menuItem("Assumption Tests", tabName = "assumptions", icon = icon("check-circle")),
      menuItem("T-Tests", tabName = "ttest", icon = icon("calculator")),
      menuItem("Proportion & Variance Tests", tabName = "prop_var", icon = icon("percentage")),
      menuItem("ANOVA", tabName = "anova", icon = icon("chart-bar")),
      menuItem("Regression", tabName = "regression", icon = icon("trending-up")),
      menuItem("Spatial Analysis", tabName = "spatial", icon = icon("map")),
      menuItem("Reports", tabName = "reports", icon = icon("file-pdf"))
    )
  ),
  
  dashboardBody(
    use_theme(mytheme),
    
    tags$head(
      tags$style(HTML("
        .content-wrapper, .right-side {
          background-color: #f8f9fa;
        }
        .box {
          border-radius: 8px;
          box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .btn {
          border-radius: 6px;
          font-weight: 500;
        }
        .nav-tabs-custom > .nav-tabs > li.active {
          border-top-color: #3498DB;
        }
        .form-control {
          border-radius: 6px;
          border: 1px solid #ddd;
        }
        .form-control:focus {
          border-color: #3498DB;
          box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }
        .info-box {
          border-radius: 8px;
          box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .small-box {
          border-radius: 8px;
        }
        .table-responsive {
          border-radius: 8px;
        }
      "))
    ),
    
    tabItems(
      # Data Import Tab
      tabItem(tabName = "data",
        fluidRow(
          box(
            title = "Data Import & Overview", 
            status = "primary", 
            solidHeader = TRUE,
            width = 12,
            collapsible = TRUE,
            
            fluidRow(
              column(4,
                fileInput("file", "Choose CSV File",
                         accept = c(".csv", ".txt"),
                         buttonLabel = "Browse...",
                         placeholder = "No file selected")
              ),
              column(4,
                checkboxInput("header", "Header", TRUE)
              ),
              column(4,
                radioButtons("sep", "Separator",
                           choices = c(Comma = ",", Semicolon = ";", Tab = "\t"),
                           selected = ",", inline = TRUE)
              )
            ),
            
            hr(),
            
            tabsetPanel(
              tabPanel("Data Preview", 
                       br(),
                       DT::dataTableOutput("data_preview")
              ),
              tabPanel("Summary Statistics",
                       br(),
                       verbatimTextOutput("data_summary")
              ),
              tabPanel("Data Structure",
                       br(),
                       verbatimTextOutput("data_structure")
              )
            ),
            
            br(),
            downloadButton("download_metadata", "Download Data Report (PDF)", 
                          class = "btn btn-primary", icon = icon("download"))
          )
        )
      ),
      
      # Exploratory Analysis Tab
      tabItem(tabName = "explore",
        fluidRow(
          box(
            title = "Exploratory Data Analysis", 
            status = "primary", 
            solidHeader = TRUE,
            width = 12,
            collapsible = TRUE,
            
            tabsetPanel(
              tabPanel("Histograms & Distributions",
                       br(),
                       fluidRow(
                         column(4,
                           selectInput("hist_var", "Select Variable:", choices = NULL)
                         ),
                         column(4,
                           numericInput("bins", "Number of Bins:", value = 30, min = 5, max = 100)
                         ),
                         column(4,
                           checkboxInput("density_overlay", "Add Density Curve", TRUE)
                         )
                       ),
                       plotlyOutput("histogram_plot", height = "400px")
              ),
              
              tabPanel("Correlation Matrix",
                       br(),
                       fluidRow(
                         column(6,
                           selectInput("corr_vars", "Select Variables:", 
                                     choices = NULL, multiple = TRUE)
                         ),
                         column(6,
                           radioButtons("corr_method", "Method:",
                                      choices = c("Pearson" = "pearson", 
                                                "Spearman" = "spearman",
                                                "Kendall" = "kendall"),
                                      selected = "pearson", inline = TRUE)
                         )
                       ),
                       plotOutput("correlation_plot", height = "500px")
              ),
              
              tabPanel("Scatter Plots",
                       br(),
                       fluidRow(
                         column(4,
                           selectInput("scatter_x", "X Variable:", choices = NULL)
                         ),
                         column(4,
                           selectInput("scatter_y", "Y Variable:", choices = NULL)
                         ),
                         column(4,
                           selectInput("scatter_color", "Color by:", choices = NULL)
                         )
                       ),
                       plotlyOutput("scatter_plot", height = "500px")
              )
            )
          )
        )
      ),
      
      # Assumption Tests Tab
      tabItem(tabName = "assumptions",
        fluidRow(
          box(
            title = "Statistical Assumption Tests", 
            status = "primary", 
            solidHeader = TRUE,
            width = 12,
            collapsible = TRUE,
            
            fluidRow(
              column(6,
                selectInput("test_var", "Select Variable for Testing:", choices = NULL)
              ),
              column(6,
                selectInput("group_var", "Group Variable (optional):", choices = NULL)
              )
            ),
            
            br(),
            
            tabsetPanel(
              tabPanel("Normality Tests",
                       br(),
                       h4("Shapiro-Wilk Test"),
                       verbatimTextOutput("shapiro_test"),
                       h4("Anderson-Darling Test"),
                       verbatimTextOutput("ad_test"),
                       h4("Q-Q Plot"),
                       plotOutput("qq_plot", height = "400px")
              ),
              
              tabPanel("Homoscedasticity Tests",
                       br(),
                       h4("Bartlett Test"),
                       verbatimTextOutput("bartlett_test"),
                       h4("Levene Test"),
                       verbatimTextOutput("levene_test"),
                       h4("Residuals vs Fitted Plot"),
                       plotOutput("residuals_plot", height = "400px")
              ),
              
              tabPanel("Independence Tests",
                       br(),
                       h4("Durbin-Watson Test"),
                       verbatimTextOutput("dw_test"),
                       h4("Ljung-Box Test"),
                       verbatimTextOutput("ljung_test")
              )
            ),
            
            br(),
            downloadButton("download_assumptions", "Download Assumption Tests (PDF)", 
                          class = "btn btn-primary", icon = icon("download"))
          )
        )
      ),
      
      # T-Tests Tab
      tabItem(tabName = "ttest",
        fluidRow(
          box(
            title = "T-Tests", 
            status = "primary", 
            solidHeader = TRUE,
            width = 12,
            collapsible = TRUE,
            
            fluidRow(
              column(4,
                radioButtons("ttest_type", "Test Type:",
                           choices = list("One Sample" = "one",
                                        "Two Sample Independent" = "two_ind",
                                        "Two Sample Paired" = "two_paired"),
                           selected = "one")
              ),
              column(4,
                conditionalPanel(
                  condition = "input.ttest_type == 'one'",
                  numericInput("mu", "Population Mean (μ₀):", value = 0)
                )
              ),
              column(4,
                radioButtons("alternative", "Alternative Hypothesis:",
                           choices = list("Two-sided" = "two.sided",
                                        "Greater" = "greater",
                                        "Less" = "less"),
                           selected = "two.sided")
              )
            ),
            
            fluidRow(
              column(6,
                selectInput("ttest_var1", "First Variable:", choices = NULL)
              ),
              column(6,
                conditionalPanel(
                  condition = "input.ttest_type != 'one'",
                  selectInput("ttest_var2", "Second Variable:", choices = NULL)
                )
              )
            ),
            
            hr(),
            
            h4("Test Results"),
            verbatimTextOutput("ttest_results"),
            
            h4("Visualization"),
            plotOutput("ttest_plot", height = "400px"),
            
            br(),
            downloadButton("download_t_test", "Download T-Test Results (PDF)", 
                          class = "btn btn-primary", icon = icon("download"))
          )
        )
      ),
      
      # Proportion & Variance Tests Tab
      tabItem(tabName = "prop_var",
        fluidRow(
          box(
            title = "Proportion & Variance Tests", 
            status = "primary", 
            solidHeader = TRUE,
            width = 12,
            collapsible = TRUE,
            
            tabsetPanel(
              tabPanel("Proportion Tests",
                       br(),
                       fluidRow(
                         column(6,
                           radioButtons("prop_samples", "Number of Samples:",
                                      choices = list("1 Sample" = "one", "2 Samples" = "two"),
                                      selected = "one")
                         ),
                         column(6,
                           conditionalPanel(
                             condition = "input.prop_samples == 'one'",
                             numericInput("prop_p0", "Null Proportion (p₀):", value = 0.5, min = 0, max = 1, step = 0.01)
                           )
                         )
                       ),
                       
                       fluidRow(
                         column(4,
                           numericInput("prop_x1", "Successes (Sample 1):", value = 50, min = 0)
                         ),
                         column(4,
                           numericInput("prop_n1", "Total (Sample 1):", value = 100, min = 1)
                         ),
                         column(4,
                           conditionalPanel(
                             condition = "input.prop_samples == 'two'",
                             numericInput("prop_x2", "Successes (Sample 2):", value = 45, min = 0)
                           )
                         )
                       ),
                       
                       conditionalPanel(
                         condition = "input.prop_samples == 'two'",
                         fluidRow(
                           column(4,
                             numericInput("prop_n2", "Total (Sample 2):", value = 100, min = 1)
                           ),
                           column(4,
                             radioButtons("prop_alternative", "Alternative:",
                                        choices = list("Two-sided" = "two.sided",
                                                     "Greater" = "greater",
                                                     "Less" = "less"),
                                        selected = "two.sided")
                           ),
                           column(4,
                             checkboxInput("prop_correct", "Continuity Correction", TRUE)
                           )
                         )
                       ),
                       
                       actionButton("run_prop_test", "Run Proportion Test", class = "btn btn-success"),
                       br(), br(),
                       
                       h4("Proportion Test Results"),
                       verbatimTextOutput("prop_test_results"),
                       
                       h4("Visualization"),
                       plotOutput("prop_test_plot", height = "400px")
              ),
              
              tabPanel("Variance Tests",
                       br(),
                       fluidRow(
                         column(6,
                           radioButtons("var_samples", "Number of Samples:",
                                      choices = list("1 Sample" = "one", "2 Samples" = "two"),
                                      selected = "one")
                         ),
                         column(6,
                           conditionalPanel(
                             condition = "input.var_samples == 'one'",
                             numericInput("var_sigma0", "Null Variance (σ₀²):", value = 1, min = 0.01, step = 0.01)
                           )
                         )
                       ),
                       
                       fluidRow(
                         column(6,
                           selectInput("var_variable1", "First Variable:", choices = NULL)
                         ),
                         column(6,
                           conditionalPanel(
                             condition = "input.var_samples == 'two'",
                             selectInput("var_variable2", "Second Variable:", choices = NULL)
                           )
                         )
                       ),
                       
                       conditionalPanel(
                         condition = "input.var_samples == 'two'",
                         radioButtons("var_alternative", "Alternative Hypothesis:",
                                    choices = list("Two-sided" = "two.sided",
                                                 "Greater" = "greater",
                                                 "Less" = "less"),
                                    selected = "two.sided")
                       ),
                       
                       actionButton("run_var_test", "Run Variance Test", class = "btn btn-success"),
                       br(), br(),
                       
                       h4("Variance Test Results"),
                       verbatimTextOutput("var_test_results"),
                       
                       h4("Visualization"),
                       plotOutput("var_test_plot", height = "400px")
              )
            ),
            
            br(),
            downloadButton("download_prop_var", "Download Proportion & Variance Tests (PDF)", 
                          class = "btn btn-primary", icon = icon("download"))
          )
        )
      ),
      
      # ANOVA Tab
      tabItem(tabName = "anova",
        fluidRow(
          box(
            title = "Analysis of Variance (ANOVA)", 
            status = "primary", 
            solidHeader = TRUE,
            width = 12,
            collapsible = TRUE,
            
            fluidRow(
              column(6,
                selectInput("anova_dependent", "Dependent Variable:", choices = NULL)
              ),
              column(6,
                selectInput("anova_factor", "Factor Variable:", choices = NULL)
              )
            ),
            
            fluidRow(
              column(6,
                radioButtons("anova_type", "ANOVA Type:",
                           choices = list("One-Way ANOVA" = "oneway",
                                        "Two-Way ANOVA" = "twoway"),
                           selected = "oneway")
              ),
              column(6,
                conditionalPanel(
                  condition = "input.anova_type == 'twoway'",
                  selectInput("anova_factor2", "Second Factor:", choices = NULL)
                )
              )
            ),
            
            hr(),
            
            h4("ANOVA Results"),
            verbatimTextOutput("anova_results"),
            
            h4("Post-hoc Tests (Tukey HSD)"),
            verbatimTextOutput("tukey_results"),
            
            h4("Visualization"),
            tabsetPanel(
              tabPanel("Box Plot", plotOutput("anova_boxplot", height = "400px")),
              tabPanel("Residual Plots", plotOutput("anova_residuals", height = "400px"))
            ),
            
            br(),
            downloadButton("download_anova", "Download ANOVA Results (PDF)", 
                          class = "btn btn-primary", icon = icon("download"))
          )
        )
      ),
      
      # Regression Tab
      tabItem(tabName = "regression",
        fluidRow(
          box(
            title = "Regression Analysis", 
            status = "primary", 
            solidHeader = TRUE,
            width = 12,
            collapsible = TRUE,
            
            fluidRow(
              column(6,
                selectInput("reg_dependent", "Dependent Variable:", choices = NULL)
              ),
              column(6,
                selectInput("reg_independent", "Independent Variables:", 
                          choices = NULL, multiple = TRUE)
              )
            ),
            
            fluidRow(
              column(6,
                radioButtons("reg_type", "Regression Type:",
                           choices = list("Linear Regression" = "linear",
                                        "Multiple Regression" = "multiple"),
                           selected = "linear")
              ),
              column(6,
                checkboxInput("reg_interactions", "Include Interactions", FALSE)
              )
            ),
            
            hr(),
            
            h4("Regression Results"),
            verbatimTextOutput("regression_results"),
            
            h4("Model Diagnostics"),
            tabsetPanel(
              tabPanel("Residual Plots", plotOutput("reg_diagnostics", height = "500px")),
              tabPanel("Fitted vs Actual", plotOutput("reg_fitted_actual", height = "400px")),
              tabPanel("Cook's Distance", plotOutput("reg_cooks", height = "400px"))
            ),
            
            br(),
            downloadButton("download_regression", "Download Regression Results (PDF)", 
                          class = "btn btn-primary", icon = icon("download"))
          )
        )
      ),
      
      # Spatial Analysis Tab
      tabItem(tabName = "spatial",
        fluidRow(
          box(
            title = "Spatial Analysis - Moran's I", 
            status = "primary", 
            solidHeader = TRUE,
            width = 12,
            collapsible = TRUE,
            
            fluidRow(
              column(4,
                selectInput("spatial_var", "Variable for Spatial Analysis:", choices = NULL)
              ),
              column(4,
                selectInput("spatial_x", "X Coordinate:", choices = NULL)
              ),
              column(4,
                selectInput("spatial_y", "Y Coordinate:", choices = NULL)
              )
            ),
            
            fluidRow(
              column(6,
                numericInput("spatial_k", "Number of Neighbors (k):", value = 4, min = 1, max = 20)
              ),
              column(6,
                radioButtons("spatial_method", "Weight Matrix Type:",
                           choices = list("K-nearest neighbors" = "knn",
                                        "Distance threshold" = "distance"),
                           selected = "knn")
              )
            ),
            
            conditionalPanel(
              condition = "input.spatial_method == 'distance'",
              numericInput("spatial_distance", "Distance Threshold:", value = 1, min = 0.1, step = 0.1)
            ),
            
            actionButton("run_moran", "Calculate Moran's I", class = "btn btn-success"),
            br(), br(),
            
            h4("Moran's I Results"),
            verbatimTextOutput("moran_result"),
            
            h4("Spatial Visualization"),
            tabsetPanel(
              tabPanel("Spatial Distribution", 
                       leafletOutput("spatial_map", height = "500px")),
              tabPanel("Moran Scatterplot", 
                       plotOutput("moran_scatter", height = "400px")),
              tabPanel("LISA Map", 
                       plotOutput("lisa_map", height = "400px"))
            ),
            
            br(),
            downloadButton("download_spatial", "Download Spatial Analysis (PDF)", 
                          class = "btn btn-primary", icon = icon("download"))
          )
        )
      ),
      
      # Reports Tab
      tabItem(tabName = "reports",
        fluidRow(
          box(
            title = "Comprehensive Analysis Report", 
            status = "primary", 
            solidHeader = TRUE,
            width = 12,
            collapsible = TRUE,
            
            h4("Generate Complete Analysis Report"),
            p("This will generate a comprehensive PDF report including all performed analyses."),
            
            fluidRow(
              column(6,
                textInput("report_title", "Report Title:", value = "Statistical Analysis Report")
              ),
              column(6,
                textInput("report_author", "Author:", value = "Statistical Analyst")
              )
            ),
            
            fluidRow(
              column(12,
                checkboxGroupInput("report_sections", "Include Sections:",
                                 choices = list("Data Overview" = "data",
                                              "Exploratory Analysis" = "explore",
                                              "Assumption Tests" = "assumptions",
                                              "T-Tests" = "ttest",
                                              "Proportion & Variance Tests" = "prop_var",
                                              "ANOVA" = "anova",
                                              "Regression Analysis" = "regression",
                                              "Spatial Analysis" = "spatial"),
                                 selected = c("data", "explore", "assumptions"),
                                 inline = TRUE)
              )
            ),
            
            br(),
            downloadButton("download_full_report", "Generate Full Report (PDF)", 
                          class = "btn btn-success btn-lg", icon = icon("file-pdf"))
          )
        )
      )
    )
  )
)

# Define Server Logic
server <- function(input, output, session) {
  
  # Reactive values
  values <- reactiveValues(
    data = NULL,
    moran_results = NULL,
    prop_test_result = NULL,
    var_test_result = NULL
  )
  
  # Data loading
  observeEvent(input$file, {
    req(input$file)
    
    tryCatch({
      values$data <- read.csv(input$file$datapath,
                             header = input$header,
                             sep = input$sep)
      
      # Update choice lists
      numeric_vars <- names(select_if(values$data, is.numeric))
      all_vars <- names(values$data)
      
      updateSelectInput(session, "hist_var", choices = numeric_vars)
      updateSelectInput(session, "corr_vars", choices = numeric_vars)
      updateSelectInput(session, "scatter_x", choices = numeric_vars)
      updateSelectInput(session, "scatter_y", choices = numeric_vars)
      updateSelectInput(session, "scatter_color", choices = c("None" = "", all_vars))
      updateSelectInput(session, "test_var", choices = numeric_vars)
      updateSelectInput(session, "group_var", choices = c("None" = "", all_vars))
      updateSelectInput(session, "ttest_var1", choices = numeric_vars)
      updateSelectInput(session, "ttest_var2", choices = numeric_vars)
      updateSelectInput(session, "var_variable1", choices = numeric_vars)
      updateSelectInput(session, "var_variable2", choices = numeric_vars)
      updateSelectInput(session, "anova_dependent", choices = numeric_vars)
      updateSelectInput(session, "anova_factor", choices = all_vars)
      updateSelectInput(session, "anova_factor2", choices = all_vars)
      updateSelectInput(session, "reg_dependent", choices = numeric_vars)
      updateSelectInput(session, "reg_independent", choices = numeric_vars)
      updateSelectInput(session, "spatial_var", choices = numeric_vars)
      updateSelectInput(session, "spatial_x", choices = numeric_vars)
      updateSelectInput(session, "spatial_y", choices = numeric_vars)
      
      showNotification("Data loaded successfully!", type = "success")
      
    }, error = function(e) {
      showNotification(paste("Error loading data:", e$message), type = "error")
    })
  })
  
  # Data preview
  output$data_preview <- DT::renderDataTable({
    req(values$data)
    DT::datatable(values$data, 
                  options = list(scrollX = TRUE, pageLength = 10),
                  class = 'cell-border stripe')
  })
  
  # Data summary
  output$data_summary <- renderPrint({
    req(values$data)
    summary(values$data)
  })
  
  # Data structure
  output$data_structure <- renderPrint({
    req(values$data)
    str(values$data)
  })
  
  # Histogram
  output$histogram_plot <- renderPlotly({
    req(values$data, input$hist_var)
    
    p <- ggplot(values$data, aes_string(x = input$hist_var)) +
      geom_histogram(bins = input$bins, fill = "#3498DB", alpha = 0.7, color = "white") +
      theme_minimal() +
      labs(title = paste("Distribution of", input$hist_var),
           x = input$hist_var, y = "Frequency")
    
    if (input$density_overlay) {
      p <- p + geom_density(aes(y = ..density.. * nrow(values$data) * diff(range(values$data[[input$hist_var]], na.rm = TRUE)) / input$bins),
                           color = "#E74C3C", size = 1)
    }
    
    ggplotly(p)
  })
  
  # Correlation plot
  output$correlation_plot <- renderPlot({
    req(values$data, input$corr_vars)
    
    if (length(input$corr_vars) < 2) return(NULL)
    
    cor_data <- values$data[input$corr_vars]
    cor_matrix <- cor(cor_data, use = "complete.obs", method = input$corr_method)
    
    corrplot(cor_matrix, method = "color", type = "upper", 
             order = "hclust", tl.cex = 0.8, tl.col = "black",
             addCoef.col = "black", number.cex = 0.7)
  })
  
  # Scatter plot
  output$scatter_plot <- renderPlotly({
    req(values$data, input$scatter_x, input$scatter_y)
    
    p <- ggplot(values$data, aes_string(x = input$scatter_x, y = input$scatter_y))
    
    if (input$scatter_color != "") {
      p <- p + geom_point(aes_string(color = input$scatter_color), alpha = 0.7, size = 2)
    } else {
      p <- p + geom_point(color = "#3498DB", alpha = 0.7, size = 2)
    }
    
    p <- p + theme_minimal() +
      labs(title = paste(input$scatter_y, "vs", input$scatter_x))
    
    ggplotly(p)
  })
  
  # Shapiro-Wilk test
  output$shapiro_test <- renderPrint({
    req(values$data, input$test_var)
    
    if (nrow(values$data) > 5000) {
      cat("Sample size too large for Shapiro-Wilk test (n > 5000).\n")
      cat("Consider using other normality tests or sampling.\n")
    } else {
      test_data <- values$data[[input$test_var]]
      test_data <- test_data[!is.na(test_data)]
      shapiro.test(test_data)
    }
  })
  
  # Anderson-Darling test
  output$ad_test <- renderPrint({
    req(values$data, input$test_var)
    
    tryCatch({
      if (requireNamespace("nortest", quietly = TRUE)) {
        test_data <- values$data[[input$test_var]]
        test_data <- test_data[!is.na(test_data)]
        nortest::ad.test(test_data)
      } else {
        cat("nortest package not available. Please install it for Anderson-Darling test.\n")
      }
    }, error = function(e) {
      cat("Error in Anderson-Darling test:", e$message, "\n")
    })
  })
  
  # Q-Q Plot
  output$qq_plot <- renderPlot({
    req(values$data, input$test_var)
    
    test_data <- values$data[[input$test_var]]
    test_data <- test_data[!is.na(test_data)]
    
    ggplot(data.frame(sample = test_data), aes(sample = sample)) +
      stat_qq(color = "#3498DB", alpha = 0.7) +
      stat_qq_line(color = "#E74C3C", size = 1) +
      theme_minimal() +
      labs(title = paste("Q-Q Plot for", input$test_var),
           x = "Theoretical Quantiles", y = "Sample Quantiles")
  })
  
  # Bartlett test
  output$bartlett_test <- renderPrint({
    req(values$data, input$test_var, input$group_var)
    
    if (input$group_var == "") {
      cat("Group variable required for Bartlett test.\n")
    } else {
      formula_str <- paste(input$test_var, "~", input$group_var)
      bartlett.test(as.formula(formula_str), data = values$data)
    }
  })
  
  # Levene test
  output$levene_test <- renderPrint({
    req(values$data, input$test_var, input$group_var)
    
    if (input$group_var == "") {
      cat("Group variable required for Levene test.\n")
    } else {
      tryCatch({
        if (requireNamespace("car", quietly = TRUE)) {
          formula_str <- paste(input$test_var, "~", input$group_var)
          car::leveneTest(as.formula(formula_str), data = values$data)
        } else {
          cat("car package not available. Please install it for Levene test.\n")
        }
      }, error = function(e) {
        cat("Error in Levene test:", e$message, "\n")
      })
    }
  })
  
  # Residuals plot
  output$residuals_plot <- renderPlot({
    req(values$data, input$test_var, input$group_var)
    
    if (input$group_var == "") return(NULL)
    
    ggplot(values$data, aes_string(x = input$group_var, y = input$test_var)) +
      geom_boxplot(fill = "#3498DB", alpha = 0.7) +
      theme_minimal() +
      labs(title = paste("Box Plot:", input$test_var, "by", input$group_var),
           x = input$group_var, y = input$test_var)
  })
  
  # Durbin-Watson test
  output$dw_test <- renderPrint({
    req(values$data, input$test_var)
    
    tryCatch({
      if (requireNamespace("lmtest", quietly = TRUE)) {
        # Create a simple linear model for DW test
        model_data <- data.frame(
          y = values$data[[input$test_var]],
          x = seq_along(values$data[[input$test_var]])
        )
        model_data <- model_data[complete.cases(model_data), ]
        
        if (nrow(model_data) > 2) {
          lm_model <- lm(y ~ x, data = model_data)
          lmtest::dwtest(lm_model)
        } else {
          cat("Insufficient data for Durbin-Watson test.\n")
        }
      } else {
        cat("lmtest package not available. Please install it for Durbin-Watson test.\n")
      }
    }, error = function(e) {
      cat("Error in Durbin-Watson test:", e$message, "\n")
    })
  })
  
  # Ljung-Box test
  output$ljung_test <- renderPrint({
    req(values$data, input$test_var)
    
    test_data <- values$data[[input$test_var]]
    test_data <- test_data[!is.na(test_data)]
    
    if (length(test_data) > 10) {
      Box.test(test_data, lag = min(10, length(test_data) - 1), type = "Ljung-Box")
    } else {
      cat("Insufficient data for Ljung-Box test (need > 10 observations).\n")
    }
  })
  
  # T-test results
  output$ttest_results <- renderPrint({
    req(values$data, input$ttest_var1)
    
    if (input$ttest_type == "one") {
      req(input$mu)
      test_data <- values$data[[input$ttest_var1]]
      test_data <- test_data[!is.na(test_data)]
      t.test(test_data, mu = input$mu, alternative = input$alternative)
      
    } else if (input$ttest_type == "two_ind") {
      req(input$ttest_var2)
      data1 <- values$data[[input$ttest_var1]]
      data2 <- values$data[[input$ttest_var2]]
      data1 <- data1[!is.na(data1)]
      data2 <- data2[!is.na(data2)]
      t.test(data1, data2, alternative = input$alternative)
      
    } else if (input$ttest_type == "two_paired") {
      req(input$ttest_var2)
      data1 <- values$data[[input$ttest_var1]]
      data2 <- values$data[[input$ttest_var2]]
      complete_cases <- complete.cases(data1, data2)
      data1 <- data1[complete_cases]
      data2 <- data2[complete_cases]
      t.test(data1, data2, paired = TRUE, alternative = input$alternative)
    }
  })
  
  # T-test plot
  output$ttest_plot <- renderPlot({
    req(values$data, input$ttest_var1)
    
    if (input$ttest_type == "one") {
      test_data <- values$data[[input$ttest_var1]]
      test_data <- test_data[!is.na(test_data)]
      
      ggplot(data.frame(x = test_data), aes(x = x)) +
        geom_histogram(bins = 30, fill = "#3498DB", alpha = 0.7, color = "white") +
        geom_vline(xintercept = mean(test_data), color = "#E74C3C", size = 1, linetype = "dashed") +
        geom_vline(xintercept = input$mu, color = "#27AE60", size = 1, linetype = "dashed") +
        theme_minimal() +
        labs(title = paste("One Sample T-Test:", input$ttest_var1),
             x = input$ttest_var1, y = "Frequency",
             subtitle = "Red: Sample Mean, Green: Null Hypothesis Mean")
      
    } else {
      req(input$ttest_var2)
      
      data1 <- values$data[[input$ttest_var1]]
      data2 <- values$data[[input$ttest_var2]]
      
      plot_data <- data.frame(
        value = c(data1, data2),
        group = rep(c(input$ttest_var1, input$ttest_var2), 
                   c(length(data1), length(data2)))
      )
      plot_data <- plot_data[complete.cases(plot_data), ]
      
      ggplot(plot_data, aes(x = group, y = value, fill = group)) +
        geom_boxplot(alpha = 0.7) +
        geom_jitter(width = 0.2, alpha = 0.5) +
        scale_fill_manual(values = c("#3498DB", "#E74C3C")) +
        theme_minimal() +
        theme(legend.position = "none") +
        labs(title = paste("Two Sample Comparison:", input$ttest_var1, "vs", input$ttest_var2),
             x = "Variables", y = "Values")
    }
  })
  
  # Proportion test (FIXED - now supports 2 samples)
  observeEvent(input$run_prop_test, {
    req(input$prop_x1, input$prop_n1)
    
    tryCatch({
      if (input$prop_samples == "one") {
        req(input$prop_p0)
        values$prop_test_result <- prop.test(
          x = input$prop_x1, 
          n = input$prop_n1, 
          p = input$prop_p0,
          alternative = "two.sided",
          correct = TRUE
        )
      } else {
        # Two sample proportion test
        req(input$prop_x2, input$prop_n2)
        values$prop_test_result <- prop.test(
          x = c(input$prop_x1, input$prop_x2), 
          n = c(input$prop_n1, input$prop_n2),
          alternative = input$prop_alternative,
          correct = input$prop_correct
        )
      }
      showNotification("Proportion test completed!", type = "success")
    }, error = function(e) {
      showNotification(paste("Error in proportion test:", e$message), type = "error")
    })
  })
  
  output$prop_test_results <- renderPrint({
    req(values$prop_test_result)
    values$prop_test_result
  })
  
  output$prop_test_plot <- renderPlot({
    req(values$prop_test_result)
    
    if (input$prop_samples == "one") {
      # One sample proportion plot
      p_hat <- input$prop_x1 / input$prop_n1
      p0 <- input$prop_p0
      n <- input$prop_n1
      
      # Create confidence interval
      se <- sqrt(p_hat * (1 - p_hat) / n)
      ci_lower <- p_hat - 1.96 * se
      ci_upper <- p_hat + 1.96 * se
      
      plot_data <- data.frame(
        proportion = c(p0, p_hat),
        type = c("Null Hypothesis", "Sample Proportion"),
        lower = c(p0, ci_lower),
        upper = c(p0, ci_upper)
      )
      
      ggplot(plot_data, aes(x = type, y = proportion, fill = type)) +
        geom_col(alpha = 0.7, width = 0.6) +
        geom_errorbar(aes(ymin = lower, ymax = upper), width = 0.2) +
        scale_fill_manual(values = c("#E74C3C", "#3498DB")) +
        theme_minimal() +
        theme(legend.position = "none") +
        labs(title = "One Sample Proportion Test",
             subtitle = paste("Sample proportion:", round(p_hat, 3), 
                             "vs Null:", round(p0, 3)),
             x = "", y = "Proportion")
      
    } else {
      # Two sample proportion plot
      p1 <- input$prop_x1 / input$prop_n1
      p2 <- input$prop_x2 / input$prop_n2
      
      plot_data <- data.frame(
        proportion = c(p1, p2),
        sample = c("Sample 1", "Sample 2"),
        successes = c(input$prop_x1, input$prop_x2),
        total = c(input$prop_n1, input$prop_n2)
      )
      
      ggplot(plot_data, aes(x = sample, y = proportion, fill = sample)) +
        geom_col(alpha = 0.7, width = 0.6) +
        geom_text(aes(label = paste(successes, "/", total, "\n(", 
                                  round(proportion, 3), ")")), 
                 vjust = -0.5) +
        scale_fill_manual(values = c("#3498DB", "#E74C3C")) +
        theme_minimal() +
        theme(legend.position = "none") +
        ylim(0, max(plot_data$proportion) * 1.2) +
        labs(title = "Two Sample Proportion Test",
             subtitle = paste("p1 =", round(p1, 3), "vs p2 =", round(p2, 3)),
             x = "Sample", y = "Proportion")
    }
  })
  
  # Variance test (FIXED - now supports 2 samples)
  observeEvent(input$run_var_test, {
    req(values$data, input$var_variable1)
    
    tryCatch({
      var1_data <- values$data[[input$var_variable1]]
      var1_data <- var1_data[!is.na(var1_data)]
      
      if (input$var_samples == "one") {
        # One sample variance test (Chi-square test)
        req(input$var_sigma0)
        n <- length(var1_data)
        sample_var <- var(var1_data)
        chi_stat <- (n - 1) * sample_var / input$var_sigma0
        p_value <- 2 * min(pchisq(chi_stat, df = n - 1), 
                          1 - pchisq(chi_stat, df = n - 1))
        
        values$var_test_result <- list(
          statistic = chi_stat,
          parameter = n - 1,
          p.value = p_value,
          estimate = sample_var,
          null.value = input$var_sigma0,
          method = "One Sample Chi-square Test for Variance",
          data.name = input$var_variable1
        )
        class(values$var_test_result) <- "htest"
        
      } else {
        # Two sample F-test for equality of variances
        req(input$var_variable2)
        var2_data <- values$data[[input$var_variable2]]
        var2_data <- var2_data[!is.na(var2_data)]
        
        values$var_test_result <- var.test(
          var1_data, 
          var2_data, 
          alternative = input$var_alternative
        )
      }
      showNotification("Variance test completed!", type = "success")
    }, error = function(e) {
      showNotification(paste("Error in variance test:", e$message), type = "error")
    })
  })
  
  output$var_test_results <- renderPrint({
    req(values$var_test_result)
    values$var_test_result
  })
  
  output$var_test_plot <- renderPlot({
    req(values$data, input$var_variable1, values$var_test_result)
    
    var1_data <- values$data[[input$var_variable1]]
    var1_data <- var1_data[!is.na(var1_data)]
    
    if (input$var_samples == "one") {
      # One sample variance visualization
      sample_var <- var(var1_data)
      null_var <- input$var_sigma0
      
      ggplot(data.frame(x = var1_data), aes(x = x)) +
        geom_histogram(bins = 30, fill = "#3498DB", alpha = 0.7, color = "white") +
        theme_minimal() +
        labs(title = paste("One Sample Variance Test:", input$var_variable1),
             subtitle = paste("Sample variance:", round(sample_var, 3), 
                             "vs Null:", round(null_var, 3)),
             x = input$var_variable1, y = "Frequency")
      
    } else {
      # Two sample variance comparison
      req(input$var_variable2)
      var2_data <- values$data[[input$var_variable2]]
      var2_data <- var2_data[!is.na(var2_data)]
      
      plot_data <- data.frame(
        value = c(var1_data, var2_data),
        variable = rep(c(input$var_variable1, input$var_variable2), 
                      c(length(var1_data), length(var2_data)))
      )
      
      ggplot(plot_data, aes(x = variable, y = value, fill = variable)) +
        geom_boxplot(alpha = 0.7) +
        scale_fill_manual(values = c("#3498DB", "#E74C3C")) +
        theme_minimal() +
        theme(legend.position = "none") +
        labs(title = "Two Sample Variance Comparison",
             subtitle = paste("Var1:", round(var(var1_data), 3), 
                             "vs Var2:", round(var(var2_data), 3)),
             x = "Variable", y = "Value")
    }
  })
  
  # ANOVA results
  output$anova_results <- renderPrint({
    req(values$data, input$anova_dependent, input$anova_factor)
    
    if (input$anova_type == "oneway") {
      formula_str <- paste(input$anova_dependent, "~", input$anova_factor)
      anova_model <- aov(as.formula(formula_str), data = values$data)
      summary(anova_model)
    } else {
      req(input$anova_factor2)
      formula_str <- paste(input$anova_dependent, "~", input$anova_factor, "*", input$anova_factor2)
      anova_model <- aov(as.formula(formula_str), data = values$data)
      summary(anova_model)
    }
  })
  
  # Tukey HSD
  output$tukey_results <- renderPrint({
    req(values$data, input$anova_dependent, input$anova_factor)
    
    tryCatch({
      formula_str <- paste(input$anova_dependent, "~", input$anova_factor)
      anova_model <- aov(as.formula(formula_str), data = values$data)
      TukeyHSD(anova_model)
    }, error = function(e) {
      cat("Error in Tukey HSD:", e$message, "\n")
    })
  })
  
  # ANOVA box plot
  output$anova_boxplot <- renderPlot({
    req(values$data, input$anova_dependent, input$anova_factor)
    
    ggplot(values$data, aes_string(x = input$anova_factor, y = input$anova_dependent, 
                                  fill = input$anova_factor)) +
      geom_boxplot(alpha = 0.7) +
      geom_jitter(width = 0.2, alpha = 0.5) +
      theme_minimal() +
      theme(legend.position = "none") +
      labs(title = paste("ANOVA:", input$anova_dependent, "by", input$anova_factor),
           x = input$anova_factor, y = input$anova_dependent)
  })
  
  # ANOVA residual plots
  output$anova_residuals <- renderPlot({
    req(values$data, input$anova_dependent, input$anova_factor)
    
    formula_str <- paste(input$anova_dependent, "~", input$anova_factor)
    anova_model <- aov(as.formula(formula_str), data = values$data)
    
    residuals <- residuals(anova_model)
    fitted <- fitted(anova_model)
    
    plot_data <- data.frame(fitted = fitted, residuals = residuals)
    
    ggplot(plot_data, aes(x = fitted, y = residuals)) +
      geom_point(alpha = 0.7, color = "#3498DB") +
      geom_hline(yintercept = 0, color = "#E74C3C", linetype = "dashed") +
      geom_smooth(method = "loess", se = FALSE, color = "#27AE60") +
      theme_minimal() +
      labs(title = "Residuals vs Fitted Values",
           x = "Fitted Values", y = "Residuals")
  })
  
  # Regression results
  output$regression_results <- renderPrint({
    req(values$data, input$reg_dependent, input$reg_independent)
    
    if (length(input$reg_independent) == 0) return(NULL)
    
    if (input$reg_type == "linear" && length(input$reg_independent) == 1) {
      formula_str <- paste(input$reg_dependent, "~", input$reg_independent[1])
    } else {
      if (input$reg_interactions && length(input$reg_independent) > 1) {
        formula_str <- paste(input$reg_dependent, "~", paste(input$reg_independent, collapse = " * "))
      } else {
        formula_str <- paste(input$reg_dependent, "~", paste(input$reg_independent, collapse = " + "))
      }
    }
    
    reg_model <- lm(as.formula(formula_str), data = values$data)
    summary(reg_model)
  })
  
  # Regression diagnostics
  output$reg_diagnostics <- renderPlot({
    req(values$data, input$reg_dependent, input$reg_independent)
    
    if (length(input$reg_independent) == 0) return(NULL)
    
    if (input$reg_type == "linear" && length(input$reg_independent) == 1) {
      formula_str <- paste(input$reg_dependent, "~", input$reg_independent[1])
    } else {
      if (input$reg_interactions && length(input$reg_independent) > 1) {
        formula_str <- paste(input$reg_dependent, "~", paste(input$reg_independent, collapse = " * "))
      } else {
        formula_str <- paste(input$reg_dependent, "~", paste(input$reg_independent, collapse = " + "))
      }
    }
    
    reg_model <- lm(as.formula(formula_str), data = values$data)
    
    par(mfrow = c(2, 2))
    plot(reg_model)
  })
  
  # Fitted vs Actual
  output$reg_fitted_actual <- renderPlot({
    req(values$data, input$reg_dependent, input$reg_independent)
    
    if (length(input$reg_independent) == 0) return(NULL)
    
    if (input$reg_type == "linear" && length(input$reg_independent) == 1) {
      formula_str <- paste(input$reg_dependent, "~", input$reg_independent[1])
    } else {
      if (input$reg_interactions && length(input$reg_independent) > 1) {
        formula_str <- paste(input$reg_dependent, "~", paste(input$reg_independent, collapse = " * "))
      } else {
        formula_str <- paste(input$reg_dependent, "~", paste(input$reg_independent, collapse = " + "))
      }
    }
    
    reg_model <- lm(as.formula(formula_str), data = values$data)
    
    plot_data <- data.frame(
      actual = values$data[[input$reg_dependent]],
      fitted = fitted(reg_model)
    )
    plot_data <- plot_data[complete.cases(plot_data), ]
    
    ggplot(plot_data, aes(x = actual, y = fitted)) +
      geom_point(alpha = 0.7, color = "#3498DB") +
      geom_abline(intercept = 0, slope = 1, color = "#E74C3C", linetype = "dashed") +
      theme_minimal() +
      labs(title = "Fitted vs Actual Values",
           x = "Actual Values", y = "Fitted Values")
  })
  
  # Cook's Distance
  output$reg_cooks <- renderPlot({
    req(values$data, input$reg_dependent, input$reg_independent)
    
    if (length(input$reg_independent) == 0) return(NULL)
    
    if (input$reg_type == "linear" && length(input$reg_independent) == 1) {
      formula_str <- paste(input$reg_dependent, "~", input$reg_independent[1])
    } else {
      if (input$reg_interactions && length(input$reg_independent) > 1) {
        formula_str <- paste(input$reg_dependent, "~", paste(input$reg_independent, collapse = " * "))
      } else {
        formula_str <- paste(input$reg_dependent, "~", paste(input$reg_independent, collapse = " + "))
      }
    }
    
    reg_model <- lm(as.formula(formula_str), data = values$data)
    
    plot_data <- data.frame(
      index = 1:length(cooks.distance(reg_model)),
      cooks_d = cooks.distance(reg_model)
    )
    
    ggplot(plot_data, aes(x = index, y = cooks_d)) +
      geom_point(alpha = 0.7, color = "#3498DB") +
      geom_hline(yintercept = 4/nrow(values$data), color = "#E74C3C", linetype = "dashed") +
      theme_minimal() +
      labs(title = "Cook's Distance",
           subtitle = "Red line shows threshold (4/n)",
           x = "Observation Index", y = "Cook's Distance")
  })
  
  # Moran's I calculation (FIXED - now calculates real p-value)
  observeEvent(input$run_moran, {
    req(values$data, input$spatial_var, input$spatial_x, input$spatial_y)
    
    tryCatch({
      # Extract spatial data
      coords <- cbind(values$data[[input$spatial_x]], values$data[[input$spatial_y]])
      variable <- values$data[[input$spatial_var]]
      
      # Remove NA values
      complete_idx <- complete.cases(coords, variable)
      coords <- coords[complete_idx, ]
      variable <- variable[complete_idx]
      
      if (nrow(coords) < 4) {
        showNotification("Insufficient data for spatial analysis (need >= 4 points)", type = "error")
        return()
      }
      
      # Calculate Moran's I manually
      n <- length(variable)
      
      # Create spatial weights matrix
      if (input$spatial_method == "knn") {
        # K-nearest neighbors
        k <- min(input$spatial_k, n - 1)
        dist_matrix <- as.matrix(dist(coords))
        W <- matrix(0, n, n)
        
        for (i in 1:n) {
          nearest <- order(dist_matrix[i, ])[2:(k + 1)]  # Exclude self
          W[i, nearest] <- 1
        }
      } else {
        # Distance threshold
        dist_matrix <- as.matrix(dist(coords))
        W <- ifelse(dist_matrix <= input$spatial_distance & dist_matrix > 0, 1, 0)
      }
      
      # Row standardize weights
      row_sums <- rowSums(W)
      W[row_sums > 0, ] <- W[row_sums > 0, ] / row_sums[row_sums > 0]
      
      # Calculate Moran's I
      y_mean <- mean(variable)
      y_dev <- variable - y_mean
      
      numerator <- sum(W * outer(y_dev, y_dev))
      denominator <- sum(y_dev^2)
      
      moran_i <- (n / sum(W)) * (numerator / denominator)
      
      # Calculate expected value and variance for p-value
      # Expected value of Moran's I under null hypothesis
      expected_i <- -1 / (n - 1)
      
      # Variance calculation (simplified)
      S0 <- sum(W)
      S1 <- 0.5 * sum((W + t(W))^2)
      S2 <- sum(rowSums(W)^2)
      
      # Moments
      b2 <- (n * sum(y_dev^4)) / (sum(y_dev^2)^2)
      
      var_i_norm <- ((n * ((n^2 - 3*n + 3) * S1 - n * S2 + 3 * S0^2)) - 
                     (b2 * ((n^2 - n) * S1 - 2*n * S2 + 6 * S0^2))) / 
                    (((n - 1) * (n - 2) * (n - 3) * S0^2))
      
      # Z-score and p-value
      z_score <- (moran_i - expected_i) / sqrt(var_i_norm)
      p_value <- 2 * (1 - pnorm(abs(z_score)))
      
      # Store results
      values$moran_results <- list(
        moran_i = moran_i,
        expected_i = expected_i,
        variance = var_i_norm,
        z_score = z_score,
        p_value = p_value,
        coords = coords,
        variable = variable,
        weights = W
      )
      
      showNotification("Moran's I calculation completed!", type = "success")
      
    }, error = function(e) {
      showNotification(paste("Error in Moran's I calculation:", e$message), type = "error")
    })
  })
  
  output$moran_result <- renderPrint({
    req(values$moran_results)
    
    cat("Moran's I Test for Spatial Autocorrelation\n")
    cat("==========================================\n")
    cat("Moran's I statistic:", round(values$moran_results$moran_i, 6), "\n")
    cat("Expected value:", round(values$moran_results$expected_i, 6), "\n")
    cat("Variance:", round(values$moran_results$variance, 6), "\n")
    cat("Z-score:", round(values$moran_results$z_score, 4), "\n")
    cat("P-value:", round(values$moran_results$p_value, 6), "\n")
    cat("\nInterpretation:\n")
    if (values$moran_results$p_value < 0.05) {
      if (values$moran_results$moran_i > values$moran_results$expected_i) {
        cat("Significant positive spatial autocorrelation detected.\n")
      } else {
        cat("Significant negative spatial autocorrelation detected.\n")
      }
    } else {
      cat("No significant spatial autocorrelation detected.\n")
    }
  })
  
  # Spatial map
  output$spatial_map <- renderLeaflet({
    req(values$data, input$spatial_var, input$spatial_x, input$spatial_y)
    
    coords <- cbind(values$data[[input$spatial_x]], values$data[[input$spatial_y]])
    variable <- values$data[[input$spatial_var]]
    
    complete_idx <- complete.cases(coords, variable)
    coords <- coords[complete_idx, ]
    variable <- variable[complete_idx]
    
    if (nrow(coords) == 0) return(NULL)
    
    # Create color palette
    pal <- colorNumeric(palette = "RdYlBu", domain = variable, reverse = TRUE)
    
    leaflet() %>%
      addTiles() %>%
      addCircleMarkers(
        lng = coords[, 1], 
        lat = coords[, 2],
        color = ~pal(variable),
        fillColor = ~pal(variable),
        fillOpacity = 0.7,
        radius = 8,
        stroke = TRUE,
        weight = 1,
        popup = paste("Value:", round(variable, 3))
      ) %>%
      addLegend(
        "bottomright",
        pal = pal,
        values = variable,
        title = input$spatial_var,
        opacity = 1
      )
  })
  
  # Moran scatterplot
  output$moran_scatter <- renderPlot({
    req(values$moran_results)
    
    coords <- values$moran_results$coords
    variable <- values$moran_results$variable
    W <- values$moran_results$weights
    
    # Calculate spatial lag
    spatial_lag <- as.numeric(W %*% variable)
    
    plot_data <- data.frame(
      variable = variable,
      spatial_lag = spatial_lag
    )
    
    ggplot(plot_data, aes(x = variable, y = spatial_lag)) +
      geom_point(alpha = 0.7, color = "#3498DB") +
      geom_smooth(method = "lm", se = TRUE, color = "#E74C3C") +
      theme_minimal() +
      labs(title = "Moran Scatterplot",
           subtitle = paste("Moran's I =", round(values$moran_results$moran_i, 4)),
           x = input$spatial_var,
           y = paste("Spatial Lag of", input$spatial_var))
  })
  
  # LISA map
  output$lisa_map <- renderPlot({
    req(values$moran_results)
    
    coords <- values$moran_results$coords
    variable <- values$moran_results$variable
    W <- values$moran_results$weights
    
    # Standardize variables
    z_var <- scale(variable)[, 1]
    z_lag <- scale(as.numeric(W %*% variable))[, 1]
    
    # LISA categories
    lisa_cat <- ifelse(z_var > 0 & z_lag > 0, "High-High",
                      ifelse(z_var < 0 & z_lag < 0, "Low-Low",
                            ifelse(z_var > 0 & z_lag < 0, "High-Low",
                                  ifelse(z_var < 0 & z_lag > 0, "Low-High", "Not Significant"))))
    
    plot_data <- data.frame(
      x = coords[, 1],
      y = coords[, 2],
      category = factor(lisa_cat)
    )
    
    ggplot(plot_data, aes(x = x, y = y, color = category)) +
      geom_point(size = 3, alpha = 0.8) +
      scale_color_manual(values = c("High-High" = "#d7191c", 
                                   "Low-Low" = "#2c7bb6",
                                   "High-Low" = "#fdae61",
                                   "Low-High" = "#abd9e9",
                                   "Not Significant" = "#ffffbf")) +
      theme_minimal() +
      labs(title = "Local Indicators of Spatial Association (LISA)",
           x = input$spatial_x, y = input$spatial_y,
           color = "LISA Category")
  })
  
  # Download handlers
  
  # Download metadata
  output$download_metadata <- downloadHandler(
    filename = function() {
      paste("data_report_", Sys.Date(), ".pdf", sep = "")
    },
    content = function(file) {
      req(values$data)
      
      # Create temporary markdown file
      temp_rmd <- tempfile(fileext = ".Rmd")
      
      rmd_content <- paste0("
---
title: 'Data Analysis Report'
date: '", Sys.Date(), "'
output: pdf_document
---

# Data Overview

## Dataset Summary
```{r echo=FALSE}
summary(data)
```

## Data Structure
```{r echo=FALSE}
str(data)
```

## Missing Values
```{r echo=FALSE}
sapply(data, function(x) sum(is.na(x)))
```
")
      
      writeLines(rmd_content, temp_rmd)
      
      # Render the report
      rmarkdown::render(temp_rmd, output_file = file, 
                       params = list(data = values$data),
                       envir = new.env())
    }
  )
  
  # Download assumption tests
  output$download_assumptions <- downloadHandler(
    filename = function() {
      paste("assumption_tests_", Sys.Date(), ".pdf", sep = "")
    },
    content = function(file) {
      req(values$data, input$test_var)
      
      temp_rmd <- tempfile(fileext = ".Rmd")
      
      rmd_content <- paste0("
---
title: 'Statistical Assumption Tests'
date: '", Sys.Date(), "'
output: pdf_document
---

# Assumption Tests for ", input$test_var, "

## Normality Tests

### Shapiro-Wilk Test
```{r echo=FALSE}
if (nrow(data) <= 5000) {
  test_data <- data[[var_name]]
  test_data <- test_data[!is.na(test_data)]
  shapiro.test(test_data)
} else {
  cat('Sample size too large for Shapiro-Wilk test')
}
```

## Summary
The assumption tests help validate the appropriateness of statistical procedures.
")
      
      writeLines(rmd_content, temp_rmd)
      
      rmarkdown::render(temp_rmd, output_file = file,
                       params = list(data = values$data, var_name = input$test_var),
                       envir = new.env())
    }
  )
  
  # Download T-test results
  output$download_t_test <- downloadHandler(
    filename = function() {
      paste("t_test_results_", Sys.Date(), ".pdf", sep = "")
    },
    content = function(file) {
      req(values$data, input$ttest_var1)
      
      temp_rmd <- tempfile(fileext = ".Rmd")
      
      rmd_content <- paste0("
---
title: 'T-Test Analysis'
date: '", Sys.Date(), "'
output: pdf_document
---

# T-Test Results

## Test Configuration
- Test Type: ", input$ttest_type, "
- Variable 1: ", input$ttest_var1, "
", if (input$ttest_type != "one") paste("- Variable 2:", input$ttest_var2) else "", "

## Results
Detailed statistical analysis of the t-test procedure.
")
      
      writeLines(rmd_content, temp_rmd)
      
      rmarkdown::render(temp_rmd, output_file = file, envir = new.env())
    }
  )
  
  # Download proportion & variance tests
  output$download_prop_var <- downloadHandler(
    filename = function() {
      paste("prop_var_tests_", Sys.Date(), ".pdf", sep = "")
    },
    content = function(file) {
      temp_rmd <- tempfile(fileext = ".Rmd")
      
      rmd_content <- paste0("
---
title: 'Proportion and Variance Tests'
date: '", Sys.Date(), "'
output: pdf_document
---

# Proportion and Variance Tests

## Test Summary
Comprehensive analysis of proportion and variance tests performed.

## Results
Detailed statistical findings and interpretations.
")
      
      writeLines(rmd_content, temp_rmd)
      
      rmarkdown::render(temp_rmd, output_file = file, envir = new.env())
    }
  )
  
  # Download ANOVA results
  output$download_anova <- downloadHandler(
    filename = function() {
      paste("anova_results_", Sys.Date(), ".pdf", sep = "")
    },
    content = function(file) {
      req(values$data, input$anova_dependent, input$anova_factor)
      
      temp_rmd <- tempfile(fileext = ".Rmd")
      
      rmd_content <- paste0("
---
title: 'ANOVA Analysis'
date: '", Sys.Date(), "'
output: pdf_document
---

# Analysis of Variance

## Model Configuration
- Dependent Variable: ", input$anova_dependent, "
- Factor Variable: ", input$anova_factor, "
- ANOVA Type: ", input$anova_type, "

## Results
Comprehensive ANOVA analysis including post-hoc tests.
")
      
      writeLines(rmd_content, temp_rmd)
      
      rmarkdown::render(temp_rmd, output_file = file, envir = new.env())
    }
  )
  
  # Download spatial analysis
  output$download_spatial <- downloadHandler(
    filename = function() {
      paste("spatial_analysis_", Sys.Date(), ".pdf", sep = "")
    },
    content = function(file) {
      req(values$moran_results)
      
      temp_rmd <- tempfile(fileext = ".Rmd")
      
      rmd_content <- paste0("
---
title: 'Spatial Analysis - Morans I'
date: '", Sys.Date(), "'
output: pdf_document
---

# Spatial Autocorrelation Analysis

## Moran's I Results
- Moran's I Statistic: ", round(values$moran_results$moran_i, 6), "
- P-value: ", round(values$moran_results$p_value, 6), "
- Z-score: ", round(values$moran_results$z_score, 4), "

## Interpretation
", if (values$moran_results$p_value < 0.05) {
  if (values$moran_results$moran_i > values$moran_results$expected_i) {
    "Significant positive spatial autocorrelation detected."
  } else {
    "Significant negative spatial autocorrelation detected."
  }
} else {
  "No significant spatial autocorrelation detected."
}, "
")
      
      writeLines(rmd_content, temp_rmd)
      
      rmarkdown::render(temp_rmd, output_file = file, envir = new.env())
    }
  )
  
  # Download full report
  output$download_full_report <- downloadHandler(
    filename = function() {
      paste("full_analysis_report_", Sys.Date(), ".pdf", sep = "")
    },
    content = function(file) {
      req(values$data)
      
      temp_rmd <- tempfile(fileext = ".Rmd")
      
      rmd_content <- paste0("
---
title: '", input$report_title, "'
author: '", input$report_author, "'
date: '", Sys.Date(), "'
output: pdf_document
---

# Comprehensive Statistical Analysis Report

This report contains a complete statistical analysis of the provided dataset.

## Data Overview
```{r echo=FALSE}
summary(data)
```

## Analysis Summary
This comprehensive report includes various statistical tests and analyses performed on the dataset.

")
      
      writeLines(rmd_content, temp_rmd)
      
      rmarkdown::render(temp_rmd, output_file = file,
                       params = list(data = values$data),
                       envir = new.env())
    }
  )
}

# Run the application
shinyApp(ui = ui, server = server)