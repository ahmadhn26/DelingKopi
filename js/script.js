// Cart functionality with user persistence
let cart = []
let isLoggedIn = false
let currentUserId = null

// DOM elements
let cartBtn, cartSidebar, cartOverlay, closeCart, cartContent, cartFooter, cartCount, cartTotal
let hamburger, navMenu, searchInput, productModal, searchBar, searchToggle, searchClose

// Initialize DOM elements
function initializeElements() {
  cartBtn = document.getElementById("cart-btn")
  cartSidebar = document.getElementById("cart-sidebar")
  cartOverlay = document.getElementById("cart-overlay")
  closeCart = document.getElementById("close-cart")
  cartContent = document.getElementById("cart-content")
  cartFooter = document.getElementById("cart-footer")
  cartCount = document.getElementById("cart-count")
  cartTotal = document.getElementById("cart-total")
  hamburger = document.getElementById("hamburger")
  navMenu = document.getElementById("nav-menu")
  searchInput = document.getElementById("search-input")
  productModal = document.getElementById("product-modal")
  searchBar = document.getElementById("search-bar")
  searchToggle = document.getElementById("search-toggle")
  searchClose = document.getElementById("search-close")
}

// Initialize
document.addEventListener("DOMContentLoaded", () => {
  initializeElements()
  checkLoginStatus()
  loadUserCart()
  setupEventListeners()
  setupMenuCarousel()
  
  // Ensure proper mobile initialization
  initializeMobileOptimizations()
  
  feather.replace()
})

// Add mobile optimizations
function initializeMobileOptimizations() {
  // Force navbar to be responsive immediately
  const navbar = document.querySelector(".navbar")
  if (navbar) {
    // Add initial background for mobile devices
    if (window.innerWidth <= 768) {
      navbar.classList.add("scrolled")
    }
  }
  
  // Ensure touch events are properly handled
  document.body.style.touchAction = 'manipulation'
  
  // Add viewport-based optimizations
  if (window.innerWidth <= 480) {
    document.documentElement.style.setProperty('--touch-target-min', '44px')
  }
  
  // Optimize for mobile browsers
  if (/Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
    document.body.classList.add('mobile-device')
    
    // Improve touch scrolling
    document.addEventListener('touchstart', function() {}, { passive: true })
    document.addEventListener('touchmove', function() {}, { passive: true })
  }
}

// Check login status and get user ID
function checkLoginStatus() {
  const userMenu = document.querySelector(".user-menu")
  isLoggedIn = userMenu !== null

  // Get user ID from PHP session (will be set in HTML)
  const userIdElement = document.getElementById("user-id")
  currentUserId = userIdElement ? userIdElement.value : null
}

console.log("Mencoba menyimpan. Status login:", isLoggedIn, "User ID:", currentUserId);
// Load cart based on login status
async function loadUserCart() {
  if (isLoggedIn && currentUserId) {
    try {
      const response = await fetch("cart_api.php?action=load_cart")
      const data = await response.json()

      if (data.success) {
        cart = data.cart || []
        updateCartUI()
      }
    } catch (error) {
      console.error("Error loading cart:", error)
      // Fallback to localStorage
      cart = JSON.parse(localStorage.getItem("cart")) || []
      updateCartUI()
    }
  } else {
    // Load from localStorage for guests
    cart = JSON.parse(localStorage.getItem("cart")) || []
    updateCartUI()
  }
}

// Save cart to server or localStorage
async function saveCart() {
  if (isLoggedIn && currentUserId) {
    try {
      const formData = new FormData()
      formData.append("action", "save_cart")
      formData.append("cart_data", JSON.stringify(cart))

      await fetch("cart_api.php", {
        method: "POST",
        body: formData,
      })
    } catch (error) {
      console.error("Error saving cart:", error)
    }
  }

  // Always save to localStorage as backup
  localStorage.setItem("cart", JSON.stringify(cart))
}

// Setup all event listeners
function setupEventListeners() {
  // Hamburger menu - only use click events to prevent accidental triggers
  if (hamburger) {
    hamburger.addEventListener("click", handleHamburgerToggle)
    // Only add touch handling on actual mobile devices
    if (isMobileDevice()) {
      hamburger.addEventListener("touchend", (e) => {
        e.preventDefault()
        handleHamburgerToggle(e)
      })
    }
  }

  // Close nav-menu when clicking outside
  document.addEventListener("click", (e) => {
    if (navMenu && navMenu.classList.contains("active")) {
      // Ensure click is not within nav-menu or hamburger
      if (!navMenu.contains(e.target) && !hamburger.contains(e.target)) {
        closeNavMenu()
      }
    }
  })
  
  // Only add touch outside handler for actual mobile devices
  if (isMobileDevice()) {
    let touchStartTarget = null
    
    document.addEventListener("touchstart", (e) => {
      touchStartTarget = e.target
    }, { passive: true })
    
    document.addEventListener("touchend", (e) => {
      if (navMenu && navMenu.classList.contains("active")) {
        // Only close if both touchstart and touchend were outside the menu/hamburger
        if (touchStartTarget && e.target && 
            !navMenu.contains(touchStartTarget) && !hamburger.contains(touchStartTarget) &&
            !navMenu.contains(e.target) && !hamburger.contains(e.target)) {
          closeNavMenu()
        }
      }
      touchStartTarget = null
    }, { passive: true })
  }

  // Search functionality - use click primarily
  if (searchToggle) {
    searchToggle.addEventListener("click", handleSearchToggle)
    // Only add touch for mobile devices
    if (isMobileDevice()) {
      searchToggle.addEventListener("touchend", (e) => {
        e.preventDefault()
        handleSearchToggle(e)
      })
    }
  }

  if (searchInput) {
    searchInput.addEventListener("keypress", (e) => {
      if (e.key === "Enter") {
        performSearch()
      }
    })

    searchInput.addEventListener("click", (e) => {
      e.stopPropagation()
    })
  }

  // Close search when clicking outside
  document.addEventListener("click", (e) => {
    if (searchBar && searchBar.classList.contains("active")) {
      if (!searchBar.contains(e.target) && !searchToggle.contains(e.target)) {
        searchBar.classList.remove("active")
      }
    }
  })

  // Cart functionality - use click primarily
  if (cartBtn) {
    cartBtn.addEventListener("click", openCart)
    // Only add touch for mobile devices
    if (isMobileDevice()) {
      cartBtn.addEventListener("touchend", (e) => {
        e.preventDefault()
        openCart(e)
      })
    }
  }

  if (closeCart) {
    closeCart.addEventListener("click", closeCartSidebar)
    if (isMobileDevice()) {
      closeCart.addEventListener("touchend", (e) => {
        e.preventDefault()
        closeCartSidebar(e)
      })
    }
  }

  if (cartOverlay) {
    cartOverlay.addEventListener("click", closeCartSidebar)
    if (isMobileDevice()) {
      cartOverlay.addEventListener("touchend", closeCartSidebar)
    }
  }

  // Close modal events
  const closeModalBtn = document.querySelector(".close-modal")
  if (closeModalBtn) {
    closeModalBtn.addEventListener("click", closeModal)
    if (isMobileDevice()) {
      closeModalBtn.addEventListener("touchend", (e) => {
        e.preventDefault()
        closeModal(e)
      })
    }
  }

  if (productModal) {
    productModal.addEventListener("click", (e) => {
      if (e.target === productModal) {
        closeModal()
      }
    })
  }
}

// Helper function to detect mobile devices
function isMobileDevice() {
  return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) || 
         window.innerWidth <= 768 || 
         ('ontouchstart' in window && window.innerWidth <= 1024)
}

function handleHamburgerToggle(e) {
  e.preventDefault();
  e.stopPropagation();
  
  navMenu.classList.toggle("active");
  hamburger.classList.toggle("active");
  
  // Prevent body scroll when menu is open on mobile
  if (navMenu.classList.contains("active")) {
    document.body.style.overflow = "hidden";
  } else {
    // KODE LAMA: document.body.style.overflow = "auto";
    document.body.style.overflow = ""; // ✅ PERBAIKAN: Hapus inline style
  }
}

function closeNavMenu() {
  navMenu.classList.remove("active");
  hamburger.classList.remove("active");
  // KODE LAMA: document.body.style.overflow = "auto";
  document.body.style.overflow = ""; // ✅ PERBAIKAN: Hapus inline style
}

function handleSearchToggle(e) {
  e.preventDefault()
  e.stopPropagation()
  
  searchBar.classList.add("active")
  // Small delay to ensure proper rendering before focus
  setTimeout(() => {
    if (searchInput) {
      searchInput.focus()
    }
  }, 100)
}

// Menu Carousel Setup
function setupMenuCarousel() {
  const carousel = document.getElementById("menu-carousel")
  const prevBtn = document.getElementById("menu-prev")
  const nextBtn = document.getElementById("menu-next")

  if (!carousel || !prevBtn || !nextBtn) return

  const cardWidth = 300 // Approximate width including gap
  let currentPosition = 0

  prevBtn.addEventListener("click", () => {
    currentPosition = Math.max(0, currentPosition - cardWidth * 3)
    carousel.scrollTo({
      left: currentPosition,
      behavior: "smooth",
    })
  })

  nextBtn.addEventListener("click", () => {
    const maxScroll = carousel.scrollWidth - carousel.clientWidth
    currentPosition = Math.min(maxScroll, currentPosition + cardWidth * 3)
    carousel.scrollTo({
      left: currentPosition,
      behavior: "smooth",
    })
  })

  // Update button visibility based on scroll position
  carousel.addEventListener("scroll", () => {
    currentPosition = carousel.scrollLeft
    prevBtn.style.opacity = currentPosition > 0 ? "1" : "0.5"
    nextBtn.style.opacity = currentPosition < carousel.scrollWidth - carousel.clientWidth ? "1" : "0.5"
  })

  // Initial button state
  prevBtn.style.opacity = "0.5"
  nextBtn.style.opacity = carousel.scrollWidth > carousel.clientWidth ? "1" : "0.5"
}

function performSearch() {
  const searchQuery = searchInput.value.trim()
  if (searchQuery) {
    window.location.href = `?search=${encodeURIComponent(searchQuery)}`
  } else {
    window.location.href = window.location.pathname
  }
  searchBar.classList.remove("active")
}

function openCart() {
  if (cartSidebar && cartOverlay) {
    cartSidebar.classList.add("active")
    cartOverlay.classList.add("active")
    document.body.style.overflow = "hidden"
  }
}

function closeCartSidebar() {
  if (cartSidebar) cartSidebar.classList.remove("active");
  if (cartOverlay) cartOverlay.classList.remove("active");
  // KODE LAMA: document.body.style.overflow = "auto";
  document.body.style.overflow = ""; // ✅ PERBAIKAN: Hapus inline style
}

// Add to cart with menu restriction check
document.addEventListener("click", (e) => {
  if (e.target.closest(".add-to-cart")) {
    const btn = e.target.closest(".add-to-cart")
    const itemType = btn.dataset.type

    // Check if it's a menu item and show warning
    if (itemType === "menu") {
      const confirmAdd = confirm(
        "⚠️ PERHATIAN: Menu kopi hanya dapat dipesan untuk dinikmati langsung di kafe.\n\n" +
          "Menu ini TIDAK tersedia untuk pengiriman jarak jauh.\n\n" +
          "Apakah Anda yakin ingin menambahkan ke keranjang?",
      )

      if (!confirmAdd) {
        return
      }
    }

    const item = {
      id: btn.dataset.id,
      name: btn.dataset.name,
      price: Number.parseInt(btn.dataset.price),
      type: itemType,
      quantity: 1,
    }

    addToCart(item)
  }
})

async function addToCart(item) {
  // Check stock availability from the button data
  const addButton = document.querySelector(`[data-id="${item.id}"][data-type="${item.type}"].add-to-cart`);
  const availableStock = addButton ? parseInt(addButton.dataset.stock) || 0 : 0;
  
  const existingItem = cart.find((cartItem) => cartItem.id === item.id && cartItem.type === item.type)
  const currentCartQuantity = existingItem ? existingItem.quantity : 0;
  
  // Check if adding this item would exceed stock
  if (currentCartQuantity >= availableStock) {
    showNotification(`Stok tidak mencukupi! Stok tersedia: ${availableStock}`, "error");
    return;
  }

  if (existingItem) {
    existingItem.quantity += 1
  } else {
    cart.push(item)
  }

  await saveCart()
  updateCartUI()

  // Show success message with menu warning if applicable
  let message = "Item berhasil ditambahkan ke keranjang!"
  if (item.type === "menu") {
    message += "\n\n📍 Catatan: Menu kopi hanya tersedia untuk dinikmati di kafe."
  }

  showNotification(message)
}

async function updateCartQuantity(id, type, quantity) {
  if (quantity <= 0) {
    cart = cart.filter((item) => !(item.id === id && item.type === type))
  } else {
    // Check stock availability when increasing quantity
    const addButton = document.querySelector(`[data-id="${id}"][data-type="${type}"].add-to-cart`);
    const availableStock = addButton ? parseInt(addButton.dataset.stock) || 0 : 0;
    
    if (quantity > availableStock) {
      showNotification(`Stok tidak mencukupi! Stok tersedia: ${availableStock}`, "error");
      return;
    }
    
    const item = cart.find((item) => item.id === id && item.type === type)
    if (item) {
      item.quantity = quantity
    }
  }

  await saveCart()
  updateCartUI()
}

function updateCartUI() {
  const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0)
  const totalPrice = cart.reduce((sum, item) => sum + item.price * item.quantity, 0)

  if (cartCount) cartCount.textContent = totalItems
  if (cartTotal) cartTotal.textContent = totalPrice.toLocaleString("id-ID")

  if (cartContent) {
    if (cart.length === 0) {
      cartContent.innerHTML = '<p class="empty-cart">Keranjang kosong</p>'
      if (cartFooter) cartFooter.style.display = "none"
    } else {
      cartContent.innerHTML = cart
        .map(
          (item) => `
              <div class="cart-item">
                  <div class="cart-item-info">
                      <h4>${item.name}</h4>
                      <p class="cart-item-price">Rp ${item.price.toLocaleString("id-ID")}</p>
                      ${item.type === "menu" ? '<p class="menu-warning">📍 Hanya di kafe</p>' : ""}
                  </div>
                  <div class="cart-item-controls">
                      <button onclick="updateCartQuantity('${item.id}', '${item.type}', ${item.quantity - 1})">
                          <i data-feather="minus"></i>
                      </button>
                      <span>${item.quantity}</span>
                      <button onclick="updateCartQuantity('${item.id}', '${item.type}', ${item.quantity + 1})">
                          <i data-feather="plus"></i>
                      </button>
                  </div>
              </div>
          `,
        )
        .join("")
      if (cartFooter) cartFooter.style.display = "block"
      feather.replace()
    }
  }
}

// Product detail modal
// Product detail modal
document.addEventListener("click", (e) => {
  if (e.target.closest(".product-detail")) {
    const btn = e.target.closest(".product-detail");
    showProductModal({
      id: btn.dataset.id,
      name: btn.dataset.name,
      price: Number.parseInt(btn.dataset.price),
      description: btn.dataset.description,
      image: btn.dataset.image,
      stock: Number.parseInt(btn.dataset.stock),
    });
  }
});

function showProductModal(product) {
  if (!productModal) return

  document.getElementById("modal-title").textContent = product.name
  document.getElementById("modal-image").src = product.image
  document.getElementById("modal-description").textContent = product.description
  document.getElementById("modal-price").textContent = product.price.toLocaleString("id-ID")

  const addCartBtn = document.getElementById("modal-add-cart")
  const stock = product.stock || 0;
  
  // Update button based on stock availability
  if (stock <= 0) {
    addCartBtn.textContent = "Stok Habis"
    addCartBtn.disabled = true
    addCartBtn.style.opacity = "0.5"
    addCartBtn.style.cursor = "not-allowed"
  } else {
    addCartBtn.innerHTML = '<i data-feather="plus"></i> Tambah ke Keranjang'
    addCartBtn.disabled = false
    addCartBtn.style.opacity = "1"
    addCartBtn.style.cursor = "pointer"
    
    addCartBtn.onclick = () => {
      addToCart({
        id: product.id,
        name: product.name,
        price: product.price,
        type: "product",
        quantity: 1,
      })
      closeModal()
    }
  }

  productModal.classList.add("active")
  document.body.style.overflow = "hidden"
  feather.replace()
}

function closeModal() {
  if (productModal) {
    productModal.classList.remove("active")
    document.body.style.overflow = "auto"
  }
}

// Checkout button functionality with menu warning
document.addEventListener("click", (e) => {
  if (e.target.closest('a[href="checkout.php"]')) {
    e.preventDefault()

    if (cart.length === 0) {
      showNotification("Keranjang kosong! Tambahkan item terlebih dahulu.", "error")
      return
    }

    // Check if cart contains menu items
    const hasMenuItems = cart.some((item) => item.type === "menu")

    if (hasMenuItems) {
      const confirmCheckout = confirm(
        "⚠️ PERHATIAN PENTING!\n\n" +
          "Keranjang Anda berisi menu kopi yang HANYA dapat dinikmati langsung di kafe.\n\n" +
          "Menu kopi TIDAK tersedia untuk pengiriman jarak jauh.\n\n" +
          "Pastikan Anda akan datang ke kafe untuk menikmati pesanan Anda.\n\n" +
          "Lanjutkan ke checkout?",
      )

      if (!confirmCheckout) {
        return
      }
    }

    // Store cart and redirect to checkout
    saveCart()
    window.location.href = "checkout.php"
  }
})

  // Smooth scrolling for navigation links
  document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
    anchor.addEventListener("click", function (e) {
      e.preventDefault()
      const target = document.querySelector(this.getAttribute("href"))
      if (target) {
        target.scrollIntoView({
          behavior: "smooth",
          block: "start",
        })
      }
             // Close mobile menu if open
       if (navMenu) {
         navMenu.classList.remove("active")
         hamburger.classList.remove("active")
       }
    })
  })

       // Close mobile menu when clicking on mobile auth buttons (except for # links)
     document.querySelectorAll('.mobile-nav-btn').forEach((button) => {
       button.addEventListener("click", function (e) {
         // Only close menu for actual page navigation (not # links)
         if (!this.getAttribute("href").startsWith("#")) {
           // Close mobile menu
           if (navMenu) {
             navMenu.classList.remove("active")
             hamburger.classList.remove("active")
           }
         }
       })
     })

// Navbar scroll effect with improved mobile handling
window.addEventListener("scroll", () => {
  const navbar = document.querySelector(".navbar")
  if (navbar) {
    if (window.scrollY > 50 || window.innerWidth <= 768) {
      navbar.classList.add("scrolled")
    } else {
      navbar.classList.remove("scrolled")
    }
  }
})

// Handle orientation change and resize events for mobile
window.addEventListener("orientationchange", () => {
  setTimeout(() => {
    const navbar = document.querySelector(".navbar")
    if (navbar && window.innerWidth <= 768) {
      navbar.classList.add("scrolled")
    }
    
    // Close mobile menu on orientation change
    if (navMenu && navMenu.classList.contains("active")) {
      closeNavMenu()
    }
    
    // Close search bar on orientation change
    if (searchBar && searchBar.classList.contains("active")) {
      searchBar.classList.remove("active")
    }
  }, 100)
})

window.addEventListener("resize", () => {
  const navbar = document.querySelector(".navbar")
  
  // Ensure navbar has proper styling on mobile
  if (window.innerWidth <= 768) {
    navbar?.classList.add("scrolled")
  } else if (window.scrollY <= 50) {
    navbar?.classList.remove("scrolled")
  }
  
  // Close mobile elements when resizing to desktop
  if (window.innerWidth > 1024) {
    if (navMenu && navMenu.classList.contains("active")) {
      closeNavMenu()
    }
    if (searchBar && searchBar.classList.contains("active")) {
      searchBar.classList.remove("active")
    }
  }
})

// Enhanced notification system - SUDAH DIPERBAIKI
// Enhanced notification system - VERSI FINAL YANG BENAR
function showNotification(message, type = "success") {
    // Cari atau buat container untuk notifikasi
    let notificationContainer = document.getElementById('notification-container');
    if (!notificationContainer) {
        notificationContainer = document.createElement('div');
        notificationContainer.id = 'notification-container';
        // Styling untuk container agar notifikasi menumpuk dengan rapi
        notificationContainer.style.cssText = `
            position: fixed;
            top: 100px;
            right: 20px;
            z-index: 1003;
            display: flex;
            flex-direction: column;
            gap: 10px;
            align-items: flex-end;
            pointer-events: none; /* Agar tidak menghalangi klik di belakangnya */
        `;
        document.body.appendChild(notificationContainer);
    }

    const notification = document.createElement("div");
    // Pastikan notifikasi itu sendiri bisa di-klik jika perlu di masa depan
    notification.style.pointerEvents = 'auto'; 
    notification.className = `notification ${type}`;

    // Handle pesan multiline
    const lines = message.split("\n");
    notification.innerHTML = lines.map((line) => `<div>${line}</div>`).join("");
    
    // Tambahkan notifikasi ke container
    notificationContainer.appendChild(notification);

    // Memicu animasi untuk memunculkan notifikasi
    setTimeout(() => {
        notification.classList.add("show");
    }, 10);

    // Durasi notifikasi
    const duration = type === "error" || type === "warning" ? 5000 : 3000;

    // Sembunyikan dan hapus notifikasi
    setTimeout(() => {
        notification.classList.remove("show");

        // Hapus elemen dari DOM setelah animasi selesai
        notification.addEventListener('transitionend', () => {
            if (notification.parentElement) {
                notification.parentElement.removeChild(notification);
            }
        });
    }, duration);
}


// Contact form submission
// Contact form submission - SUDAH DIPERBAIKI DENGAN AJAX
const contactForm = document.getElementById("contact-form"); // Gunakan ID untuk selector yang lebih spesifik
if (contactForm) {
  contactForm.addEventListener("submit", function (e) {
    // 1. Tetap hentikan pengiriman form standar
    e.preventDefault(); 

    const submitButton = contactForm.querySelector('button[type="submit"]');
    const originalButtonText = submitButton.innerHTML;

    // Tampilkan status loading pada tombol
    submitButton.innerHTML = 'Mengirim...';
    submitButton.disabled = true;

    // 2. Kumpulkan data dari form
    const formData = new FormData(contactForm);

    // 3. Kirim data ke skrip PHP menggunakan Fetch API
    fetch('kirim_pesan.php', {
      method: 'POST',
      body: formData
    })
    .then(response => response.json()) // Ubah response dari server menjadi object JSON
    .then(data => {
      // 4. Tampilkan notifikasi berdasarkan response dari PHP
      if (data.success) {
        showNotification(data.message, 'success');
        // 5. Kosongkan form jika berhasil
        contactForm.reset(); 
      } else {
        showNotification(data.message, 'error');
      }
    })
    .catch(error => {
      // Handle jika ada error jaringan
      console.error('Error:', error);
      showNotification('Terjadi kesalahan jaringan. Coba lagi.', 'error');
    })
    .finally(() => {
      // Kembalikan tombol ke keadaan semula setelah selesai
      submitButton.innerHTML = originalButtonText;
      submitButton.disabled = false;
    });
  });
}

// Clear cart function for logout
async function clearUserCart() {
  if (isLoggedIn && currentUserId) {
    try {
      const formData = new FormData()
      formData.append("action", "clear_cart")

      await fetch("cart_api.php", {
        method: "POST",
        body: formData,
      })
    } catch (error) {
      console.error("Error clearing cart:", error)
    }
  }

  cart = []
  localStorage.removeItem("cart")
  updateCartUI()
}

document.addEventListener('DOMContentLoaded', () => {

  // Opsi untuk Intersection Observer
  // threshold: 0.1 berarti animasi akan terpicu saat 10% elemen terlihat
  const observerOptions = {
    root: null,
    rootMargin: '0px',
    threshold: 0.1 
  };

  // Fungsi callback yang akan dijalankan saat elemen terlihat
  const observerCallback = (entries, observer) => {
    entries.forEach(entry => {
      // Jika elemen masuk ke dalam viewport (terlihat)
      if (entry.isIntersecting) {
        // Tambahkan class 'is-visible' untuk memicu transisi
        entry.target.classList.add('is-visible');
        
        // Hentikan pengamatan pada elemen ini agar animasi tidak berulang
        observer.unobserve(entry.target);
      }
    });
  };

  // Buat observer baru
  const observer = new IntersectionObserver(observerCallback, observerOptions);

  // Pilih semua elemen yang ingin dianimasikan dan mulai amati
  const elementsToAnimate = document.querySelectorAll('.slide-from-right');
  elementsToAnimate.forEach(el => {
    observer.observe(el);
  });

});

document.addEventListener('DOMContentLoaded', () => {

    // --- Konfigurasi Efek Mengetik ---
    const typingTextElement = document.getElementById('typing-text');
    
    const phrases = [
        "Sebuah Kisah.",
        "Warisan Leluhur.",
        "Momen Terbaikmu."
    ];
        
    // Kecepatan sekarang menjadi rentang (min, max)
    const typingSpeedMin = 50;  // milidetik
    const typingSpeedMax = 150; // milidetik
    
    const deletingSpeed = 40;  // Dibuat sedikit lebih cepat dan konsisten
    const delayBetweenPhrases = 2000; // Jeda 2 detik

    // --- Variabel Internal ---
    let phraseIndex = 0;
    let charIndex = 0;
    let isDeleting = false;

    function sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    async function typingLoop() {
        while (true) {
            const currentPhrase = phrases[phraseIndex];

            if (isDeleting) {
                // Proses menghapus
                if (charIndex > 0) {
                    typingTextElement.textContent = currentPhrase.substring(0, charIndex - 1);
                    charIndex--;
                    await sleep(deletingSpeed);
                } else {
                    isDeleting = false;
                    phraseIndex = (phraseIndex + 1) % phrases.length;
                    // Jeda singkat sebelum mulai mengetik kalimat baru
                    await sleep(500); 
                }
            } else {
                // Proses mengetik
                if (charIndex < currentPhrase.length) {
                    const char = currentPhrase.charAt(charIndex);
                    typingTextElement.textContent += char;
                    charIndex++;
                    
                    // Jeda tambahan jika karakter adalah koma atau titik
                    const pause = (char === ',' || char === '.') ? 400 : 0;
                    
                    // Kecepatan mengetik dibuat random agar lebih natural
                    const randomTypingSpeed = Math.floor(Math.random() * (typingSpeedMax - typingSpeedMin + 1)) + typingSpeedMin;
                    await sleep(randomTypingSpeed + pause);
                } else {
                    // Selesai mengetik, jeda panjang lalu mulai hapus
                    isDeleting = true;
                    await sleep(delayBetweenPhrases);
                }
            }
        }
    }

    // Memulai loop efek mengetik
    typingLoop();
});


hamburger.addEventListener("click", () => {
  console.log("Hamburger clicked");
  navMenu.classList.toggle("active");
  hamburger.classList.toggle("active");
});

document.addEventListener("click", (e) => {
  if (navMenu && navMenu.classList.contains("active")) {
    if (!navMenu.contains(e.target) && !hamburger.contains(e.target)) {
      console.log("Clicked outside nav-menu");
      navMenu.classList.remove("active");
      hamburger.classList.remove("active");
    }
  }
});


// In setupEventListeners() or at the end of script.js
document.querySelectorAll('.mobile-auth-buttons .btn, .mobile-nav-btn').forEach((button) => {
  button.addEventListener("click", function (e) {
    // Only close menu for actual page navigation (not # links)
    if (!this.getAttribute("href").startsWith("#")) {
      // Close mobile menu
      if (navMenu) {
        navMenu.classList.remove("active");
        hamburger.classList.remove("active");
      }
    }
  });
});