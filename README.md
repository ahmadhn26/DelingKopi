# Statistical Analysis Dashboard

A comprehensive Shiny application for statistical analysis with modern UI design, covering all major statistical inference methods required for academic and professional use.

## 🚀 Features

### ✅ **Critical Issues Fixed**

1. **✅ Two-Sample Proportion & Variance Tests**
   - Complete implementation of 2-sample proportion tests using `prop.test()`
   - Complete implementation of 2-sample variance tests using `var.test()` (F-test)
   - Both 1-sample and 2-sample options available with proper UI controls

2. **✅ Real Moran's I P-Value Calculation**
   - Proper mathematical calculation of Moran's I statistic
   - Real p-value computation using Z-score and normal distribution
   - No more fake p-values - all calculations are statistically valid
   - Includes spatial weight matrix construction (K-NN and distance threshold)

3. **✅ Complete Download Functionality**
   - All download buttons now fully functional
   - PDF report generation for every analysis section
   - Downloadable reports include: Data overview, Assumption tests, T-tests, Proportion & Variance tests, ANOVA, Regression, and Spatial analysis

### 📊 **Comprehensive Statistical Analysis**

- **Data Import & Overview**: CSV file upload with data preview, summary statistics, and structure analysis
- **Exploratory Data Analysis**: Interactive histograms, correlation matrices, and scatter plots
- **Assumption Testing**: Normality tests (Shapiro-Wilk, Anderson-Darling), homoscedasticity tests (Bartlett, Levene), independence tests
- **T-Tests**: One-sample, two-sample independent, and paired t-tests with visualizations
- **Proportion Tests**: 1-sample and 2-sample proportion tests with confidence intervals
- **Variance Tests**: 1-sample (Chi-square) and 2-sample (F-test) variance tests
- **ANOVA**: One-way and two-way ANOVA with post-hoc Tukey HSD tests
- **Regression Analysis**: Linear and multiple regression with diagnostic plots
- **Spatial Analysis**: Moran's I with proper p-value calculation, spatial mapping, LISA analysis

### 🎨 **Modern UI Design**

- **Sleek Dashboard**: Clean, professional interface using `shinydashboard` and `shinydashboardPlus`
- **Custom Styling**: Modern color scheme with rounded corners and subtle shadows
- **Interactive Elements**: Collapsible boxes, tabbed panels, and responsive design
- **Rich Visualizations**: Interactive plots using `plotly` and `leaflet` for spatial data
- **User-Friendly**: Intuitive navigation with clear labels and helpful tooltips

## 🛠 Installation

### Prerequisites
- R (version 4.0 or higher)
- RStudio (recommended)

### Quick Setup

1. **Clone or download this repository**
2. **Install required packages**:
   ```r
   source("install_packages.R")
   ```
3. **Run the application**:
   ```r
   shiny::runApp("app.R")
   ```

### Manual Package Installation
If the automatic installation doesn't work, install packages manually:

```r
install.packages(c(
  "shiny", "shinydashboard", "shinydashboardPlus", "DT", 
  "plotly", "dplyr", "ggplot2", "corrplot", "leaflet", 
  "sf", "spdep", "rmarkdown", "knitr", "fresh", 
  "nortest", "car", "lmtest"
))
```

## 📁 Files Structure

```
├── app.R                 # Main Shiny application
├── install_packages.R    # Package installation script
├── sample_data.csv       # Sample dataset for testing
├── README.md            # This documentation
```

## 🎯 How to Use

### 1. **Data Import**
- Navigate to the "Data Import" tab
- Upload a CSV file with your data
- Configure separator and header options
- Preview your data and review summary statistics

### 2. **Exploratory Analysis**
- Use the "Exploratory Analysis" tab to understand your data
- Create histograms, correlation matrices, and scatter plots
- Adjust parameters to explore different aspects of your data

### 3. **Statistical Tests**

#### **Assumption Tests**
- Test normality using Shapiro-Wilk and Anderson-Darling tests
- Check homoscedasticity with Bartlett and Levene tests
- Examine independence with Durbin-Watson and Ljung-Box tests

#### **T-Tests**
- Choose between one-sample, two-sample independent, or paired t-tests
- Set null hypothesis values and alternative hypotheses
- View results with automatic visualizations

#### **Proportion & Variance Tests**
- **Proportion Tests**: 
  - 1-sample: Test against a null proportion
  - 2-sample: Compare proportions between two groups
- **Variance Tests**:
  - 1-sample: Chi-square test against a null variance
  - 2-sample: F-test for equality of variances

#### **ANOVA**
- Perform one-way or two-way ANOVA
- Automatic post-hoc Tukey HSD tests
- Residual analysis and diagnostic plots

#### **Regression Analysis**
- Linear and multiple regression models
- Model diagnostics including residual plots and Cook's distance
- Fitted vs actual value comparisons

### 4. **Spatial Analysis**
- **Requirements**: Data must include coordinate columns (X, Y) and a variable for analysis
- **Moran's I Calculation**: Choose between K-nearest neighbors or distance threshold for spatial weights
- **Visualizations**: Interactive maps, Moran scatterplots, and LISA maps
- **Real P-Values**: Proper statistical calculation of significance

### 5. **Report Generation**
- Download individual analysis reports as PDF files
- Generate comprehensive reports including multiple analysis sections
- Customize report title and author information

## 📋 Sample Data

The included `sample_data.csv` contains:
- **50 observations** with 10 variables
- **Numeric variables**: Age, Height, Weight, Score, Latitude, Longitude
- **Categorical variables**: Group, Treatment, Gender
- **Spatial coordinates**: Latitude/Longitude for testing spatial analysis

## 🔧 Technical Features

### **Robust Error Handling**
- Graceful handling of missing data
- Clear error messages for user guidance
- Automatic data validation

### **Performance Optimization**
- Efficient data processing with `dplyr`
- Reactive programming for smooth user experience
- Memory-efficient calculations

### **Statistical Accuracy**
- All tests implemented using standard R statistical functions
- Proper handling of assumptions and edge cases
- Validated against statistical textbook examples

## 📊 Key Statistical Methods Implemented

1. **Descriptive Statistics**: Summary statistics, data structure analysis
2. **Normality Testing**: Shapiro-Wilk, Anderson-Darling, Q-Q plots
3. **Variance Testing**: Bartlett test, Levene test, F-test
4. **Mean Testing**: One-sample, two-sample, and paired t-tests
5. **Proportion Testing**: One and two-sample proportion tests
6. **Analysis of Variance**: One-way and two-way ANOVA with post-hoc tests
7. **Regression Analysis**: Simple and multiple linear regression
8. **Spatial Statistics**: Moran's I with proper significance testing
9. **Independence Testing**: Durbin-Watson, Ljung-Box tests

## 🎓 Educational Use

This application is perfect for:
- **Statistics Courses**: Comprehensive coverage of inferential statistics
- **Research Projects**: Professional-grade statistical analysis
- **Data Science Training**: Hands-on experience with statistical methods
- **Academic Assessments**: All major statistical inference topics covered

## 🐛 Troubleshooting

### Common Issues:

1. **Package Installation Errors**:
   - Try installing packages one by one
   - Check R version compatibility
   - Install from source if binary packages fail

2. **Data Upload Issues**:
   - Ensure CSV file has proper encoding (UTF-8)
   - Check that column names don't contain special characters
   - Verify data types are appropriate for analysis

3. **Spatial Analysis Errors**:
   - Ensure coordinate columns contain numeric values
   - Check that you have at least 4 spatial points
   - Verify coordinate system is appropriate

## 🤝 Support

For issues or questions:
1. Check the troubleshooting section above
2. Verify all packages are properly installed
3. Ensure your data format matches the expected structure
4. Try the sample dataset first to confirm functionality

## 📈 Future Enhancements

Potential improvements for future versions:
- Additional statistical tests (non-parametric methods)
- Time series analysis capabilities
- Machine learning integration
- Advanced spatial statistics
- Interactive data cleaning tools

---

**Created with ❤️ for statistical analysis and education**