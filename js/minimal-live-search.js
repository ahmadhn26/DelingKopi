class MinimalLiveSearch {
  constructor() {
    this.searchInput = document.getElementById("search-input")
    this.searchContainer = document.querySelector(".search-container")
    this.suggestionsContainer = null
    this.currentQuery = ""
    this.searchTimeout = null
    this.selectedIndex = -1
    this.suggestions = []
    this.isVisible = false
    this.feather = window.feather // Declare feather variable
    this.addToCart = window.addToCart // Declare addToCart variable

    this.init()
  }

  init() {
    if (!this.searchInput) return

    this.createSuggestionsContainer()
    this.bindEvents()
  }

  createSuggestionsContainer() {
    this.suggestionsContainer = document.createElement("div")
    this.suggestionsContainer.className = "search-suggestions"

    const scrollContainer = document.createElement("div")
    scrollContainer.className = "suggestions-scroll"

    this.suggestionsContainer.appendChild(scrollContainer)
    this.searchInput.parentNode.appendChild(this.suggestionsContainer)
  }

  bindEvents() {
    this.searchInput.addEventListener("input", (e) => {
      this.handleInput(e.target.value)
    })

    this.searchInput.addEventListener("keydown", (e) => {
      this.handleKeydown(e)
    })

    this.searchInput.addEventListener("focus", () => {
      if (this.suggestions.length > 0) {
        this.showSuggestions()
      }
    })

    document.addEventListener("click", (e) => {
      if (!this.searchContainer.contains(e.target)) {
        this.hideSuggestions()
      }
    })

    this.searchInput.closest("form")?.addEventListener("submit", (e) => {
      if (this.selectedIndex >= 0) {
        e.preventDefault()
        this.selectSuggestion(this.selectedIndex)
      }
    })
  }

  handleInput(value) {
    const query = value.trim()

    if (this.searchTimeout) {
      clearTimeout(this.searchTimeout)
    }

    if (query.length < 2) {
      this.hideSuggestions()
      return
    }

    this.searchTimeout = setTimeout(() => {
      this.performSearch(query)
    }, 300)
  }

  async performSearch(query) {
    if (query === this.currentQuery) return

    this.currentQuery = query
    this.showLoading()

    try {
      const response = await fetch(`search_suggestions.php?q=${encodeURIComponent(query)}`)
      const data = await response.json()

      if (data.suggestions) {
        this.suggestions = data.suggestions
        this.renderSuggestions()
        this.showSuggestions()
      }
    } catch (error) {
      console.error("Search error:", error)
      this.showError()
    }
  }

  renderSuggestions() {
    const scrollContainer = this.suggestionsContainer.querySelector(".suggestions-scroll")

    if (this.suggestions.length === 0) {
      scrollContainer.innerHTML = `
        <div class="search-suggestion-item no-results">
          <span>Tidak ditemukan hasil untuk "${this.currentQuery}"</span>
        </div>
      `
      return
    }

    // Group by type
    const menuItems = this.suggestions.filter((item) => item.type === "menu")
    const productItems = this.suggestions.filter((item) => item.type === "product")

    let html = ""

    // Menu section
    if (menuItems.length > 0) {
      html += `<div class="suggestion-category">
        <i data-feather="coffee"></i> Menu Kafe
      </div>`
      html += menuItems.map((item, index) => this.createSuggestionHTML(item, index)).join("")
    }

    // Products section
    if (productItems.length > 0) {
      html += `<div class="suggestion-category">
        <i data-feather="package"></i> Produk
      </div>`
      const startIndex = menuItems.length
      html += productItems.map((item, index) => this.createSuggestionHTML(item, startIndex + index)).join("")
    }

    // View all option
    html += `
      <div class="search-suggestion-item view-all" data-action="view-all">
        <span>Lihat semua hasil untuk "${this.currentQuery}"</span>
      </div>
    `

    scrollContainer.innerHTML = html
    this.bindSuggestionEvents()

    if (this.feather) {
      this.feather.replace()
    }
  }

createSuggestionHTML(item, index) {
    // Tentukan gambar default jika tidak ada
    const image =
      item.image || (item.type === "menu" ? "img/menu/default-coffee.jpg" : "img/products/default-product.jpg");

    // HTML yang sudah disederhanakan
    return `
      <div class="search-suggestion-item" data-index="${index}">
        <div class="suggestion-image">
          <img src="${image}" alt="${item.name}" loading="lazy">
        </div>
        <div class="suggestion-content">
          <div class="suggestion-header">
            <h4>${this.highlightQuery(item.name)}</h4>
          </div>
        </div>
        
        <div class="suggestion-action">
          <button class="add-to-cart-suggestion" 
                  data-id="${item.id}" 
                  data-name="${item.name}" 
                  data-price="${item.price}" 
                  data-type="${item.type}"
                  title="Tambah ke keranjang">
            <i data-feather="plus"></i>
          </button>
        </div>
      </div>
    `;
  }

  bindSuggestionEvents() {
    const scrollContainer = this.suggestionsContainer.querySelector(".suggestions-scroll")

    scrollContainer.querySelectorAll(".search-suggestion-item").forEach((item, index) => {
      item.addEventListener("click", (e) => {
        if (e.target.closest(".add-to-cart-suggestion")) {
          e.stopPropagation()
          this.handleAddToCart(e.target.closest(".add-to-cart-suggestion"))
        } else if (item.dataset.action === "view-all") {
          this.viewAllResults()
        } else if (item.dataset.index) {
          this.selectSuggestion(Number.parseInt(item.dataset.index))
        }
      })

      item.addEventListener("mouseenter", () => {
        if (item.dataset.index) {
          this.setSelectedIndex(Number.parseInt(item.dataset.index))
        }
      })
    })
  }

  handleAddToCart(button) {
    const item = {
      id: button.dataset.id,
      name: button.dataset.name,
      price: Number.parseInt(button.dataset.price),
      type: button.dataset.type,
      quantity: 1,
    }

    if (item.type === "menu") {
      const confirmAdd = confirm(
        "⚠️ PERHATIAN: Menu kopi hanya dapat dipesan untuk dinikmati langsung di kafe.\n\n" +
          "Menu ini TIDAK tersedia untuk pengiriman jarak jauh.\n\n" +
          "Apakah Anda yakin ingin menambahkan ke keranjang?",
      )

      if (!confirmAdd) {
        return
      }
    }

    if (typeof this.addToCart === "function") {
      this.addToCart(item)
    }

    // Success feedback
    button.classList.add("success")
    button.innerHTML = '<i data-feather="check"></i>'

    if (this.feather) {
      this.feather.replace()
    }

    setTimeout(() => {
      button.classList.remove("success")
      button.innerHTML = '<i data-feather="plus"></i>'
      if (this.feather) {
        this.feather.replace()
      }
    }, 1500)

    setTimeout(() => {
      this.hideSuggestions()
    }, 1000)
  }

  highlightQuery(text) {
    if (!this.currentQuery || !text) return text

    const regex = new RegExp(`(${this.currentQuery.replace(/[.*+?^${}()|[\]\\]/g, "\\$&")})`, "gi")
    return text.replace(regex, "<mark>$1</mark>")
  }

  handleKeydown(e) {
    if (!this.isVisible) return

    switch (e.key) {
      case "ArrowDown":
        e.preventDefault()
        this.moveSelection(1)
        break
      case "ArrowUp":
        e.preventDefault()
        this.moveSelection(-1)
        break
      case "Enter":
        e.preventDefault()
        if (this.selectedIndex >= 0) {
          this.selectSuggestion(this.selectedIndex)
        } else {
          this.viewAllResults()
        }
        break
      case "Escape":
        this.hideSuggestions()
        this.searchInput.blur()
        break
    }
  }

  moveSelection(direction) {
    const maxIndex = this.suggestions.length

    this.selectedIndex += direction

    if (this.selectedIndex < -1) {
      this.selectedIndex = maxIndex - 1
    } else if (this.selectedIndex >= maxIndex) {
      this.selectedIndex = -1
    }

    this.updateSelectionDisplay()
  }

  setSelectedIndex(index) {
    this.selectedIndex = index
    this.updateSelectionDisplay()
  }

  updateSelectionDisplay() {
    const scrollContainer = this.suggestionsContainer.querySelector(".suggestions-scroll")

    scrollContainer.querySelectorAll(".search-suggestion-item").forEach((item) => {
      item.classList.remove("selected")
    })

    if (this.selectedIndex >= 0) {
      const selectedItem = scrollContainer.querySelector(`[data-index="${this.selectedIndex}"]`)
      if (selectedItem) {
        selectedItem.classList.add("selected")
        selectedItem.scrollIntoView({ behavior: "smooth", block: "nearest" })
      }
    }
  }

  selectSuggestion(index) {
    if (index >= 0 && index < this.suggestions.length) {
      const suggestion = this.suggestions[index]
      this.searchInput.value = suggestion.name
      this.hideSuggestions()
      this.scrollToItem(suggestion)
    }
  }

  scrollToItem(suggestion) {
    const itemSelector =
      suggestion.type === "menu"
        ? `.menu-card[data-id="${suggestion.id}"]`
        : `.product-card[data-id="${suggestion.id}"]`

    const element = document.querySelector(itemSelector)
    if (element) {
      element.scrollIntoView({ behavior: "smooth", block: "center" })

      // Simple highlight effect
      element.style.boxShadow = "0 0 20px rgba(182, 137, 91, 0.5)"
      setTimeout(() => {
        element.style.boxShadow = ""
      }, 2000)
    } else {
      this.viewAllResults()
    }
  }

  viewAllResults() {
    const query = this.currentQuery || this.searchInput.value
    if (query) {
      window.location.href = `?search=${encodeURIComponent(query)}`
    }
  }

  showLoading() {
    const scrollContainer = this.suggestionsContainer.querySelector(".suggestions-scroll")
    scrollContainer.innerHTML = `
      <div class="search-suggestion-item loading">
        <div class="loading-spinner"></div>
        <span>Mencari...</span>
      </div>
    `
    this.showSuggestions()
  }

  showError() {
    const scrollContainer = this.suggestionsContainer.querySelector(".suggestions-scroll")
    scrollContainer.innerHTML = `
      <div class="search-suggestion-item no-results">
        <span>Terjadi kesalahan. Silakan coba lagi.</span>
      </div>
    `
    this.showSuggestions()
  }

  showSuggestions() {
    this.suggestionsContainer.classList.add("visible")
    this.isVisible = true
  }

  hideSuggestions() {
    this.suggestionsContainer.classList.remove("visible")
    this.selectedIndex = -1
    this.isVisible = false
  }
}

// Initialize when DOM is loaded
document.addEventListener("DOMContentLoaded", () => {
  new MinimalLiveSearch()
})
