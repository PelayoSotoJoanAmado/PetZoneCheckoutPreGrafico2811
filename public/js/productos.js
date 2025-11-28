const API_URL = '../routes/router.php?recurso=productos';
const PRODUCTS_PER_PAGE = 6;
let allProducts = [];
let filteredProducts = [];
let currentFilter = 'todos';
let displayedCount = PRODUCTS_PER_PAGE;

document.addEventListener('DOMContentLoaded', () => {
    loadProductsFromDB();
    setupFilters();
    setupShowMoreButton();
});

async function loadProductsFromDB() {
    try {
        showLoading();
        
        const response = await fetch(`${API_URL}&action=list`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.success && data.productos.length > 0) {
            allProducts = data.productos;
            filteredProducts = [...allProducts];
            renderProducts();
            updateShowMoreButton();
        } else {
            showEmptyState();
        }
    } catch (error) {
        console.error('Error al cargar productos:', error);
        showErrorState();
    }
}

function renderProducts() {
    const grid = document.getElementById('productGrid');
    
    if (filteredProducts.length === 0) {
        grid.innerHTML = `
            <div style="grid-column: 1/-1; text-align: center; padding: 4rem 2rem;">
                <span class="material-icons" style="font-size: 5rem; color: #ddd;">inventory_2</span>
                <p style="font-size: 1.2rem; color: #666; margin-top: 1rem;">
                    No hay productos en esta categoría
                </p>
            </div>
        `;
        return;
    }
    
    const productsToShow = filteredProducts.slice(0, displayedCount);
    
    grid.innerHTML = productsToShow.map(producto => `
        <article class="product" data-category="${producto.categoria_slug}" data-id="${producto.id}">
            <div class="product__image">
                <img src="../public/${producto.imagen}" alt="${producto.nombre}" class="product__img" onerror="this.onerror=null;">
                ${producto.destacado == 1 ? '<span class="product__badge">⭐ Destacado</span>' : ''}
            </div>
            <div class="product__info">
                <h3 class="product__name">${producto.nombre}</h3>
                <p class="product__description">${producto.descripcion || ''}</p>
                <div class="product__footer">
                    <div class="product__price-row">
                        <span class="product__price">S/. ${parseFloat(producto.precio).toFixed(2)}</span>
                    </div>
                    <span class="product__stock ${producto.stock < 10 ? 'low-stock' : ''}">
                        Stock: ${producto.stock} unidades
                    </span>
                    ${producto.stock > 0 ? `
                        <div class="product__quantity-controls">
                            <button class="quantity-btn" type="button" onclick="decreaseQuantity(this)">
                                <span class="material-icons">remove</span>
                            </button>
                            <input type="number" class="quantity-input" value="1" min="1" max="${producto.stock}" readonly>
                            <button class="quantity-btn" type="button" onclick="increaseQuantity(this)">
                                <span class="material-icons">add</span>
                            </button>
                        </div>
                        <button class="product__btn" onclick="addToCart(this)">
                            <span class="material-icons">shopping_cart</span>
                            Comprar
                        </button>
                    ` : `
                        <button class="product__btn product__btn--disabled" disabled>
                            <span class="material-icons">block</span>
                            Sin Stock
                        </button>
                    `}
                </div>
            </div>
        </article>
    `).join('');
    
    animateProducts();
}

function setupFilters() {
    const filterButtons = document.querySelectorAll('.filters__btn');
    
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            filterButtons.forEach(btn => btn.classList.remove('filters__btn--active'));
            this.classList.add('filters__btn--active');
            
            currentFilter = this.getAttribute('data-filter');
            applyFilter();
        });
    });
}

function applyFilter() {
    displayedCount = PRODUCTS_PER_PAGE;
    
    if (currentFilter === 'todos') {
        filteredProducts = [...allProducts];
    } else {
        filteredProducts = allProducts.filter(producto => 
            producto.categoria_slug === currentFilter
        );
    }
    
    renderProducts();
    updateShowMoreButton();
}

function setupShowMoreButton() {
    const showMoreBtn = document.getElementById('showMoreBtn');
    
    if (showMoreBtn) {
        showMoreBtn.addEventListener('click', () => {
            displayedCount += PRODUCTS_PER_PAGE;
            renderProducts();
            updateShowMoreButton();
        });
    }
}

function updateShowMoreButton() {
    const showMoreBtn = document.getElementById('showMoreBtn');
    
    if (!showMoreBtn) return;
    
    if (displayedCount >= filteredProducts.length) {
        showMoreBtn.style.display = 'none';
    } else {
        showMoreBtn.style.display = 'flex';
        
        const remaining = filteredProducts.length - displayedCount;
        const moreText = showMoreBtn.querySelector('.catalog__more-text');
        if (moreText) {
            moreText.textContent = `Mostrar más (${remaining} productos)`;
        }
    }
}

function showLoading() {
    const grid = document.getElementById('productGrid');
    grid.innerHTML = `
        <div style="grid-column: 1/-1; text-align: center; padding: 4rem 2rem;">
            <span class="material-icons rotating" style="font-size: 4rem; color: #23906F;">sync</span>
            <p style="font-size: 1.2rem; color: #666; margin-top: 1rem;">Cargando productos...</p>
        </div>
    `;
}

function showEmptyState() {
    const grid = document.getElementById('productGrid');
    grid.innerHTML = `
        <div style="grid-column: 1/-1; text-align: center; padding: 4rem 2rem;">
            <span class="material-icons" style="font-size: 5rem; color: #ddd;">inventory_2</span>
            <p style="font-size: 1.2rem; color: #666; margin-top: 1rem;">
                No hay productos disponibles
            </p>
            <button onclick="loadProductsFromDB()" class="product__btn" style="margin-top: 1.5rem;">
                <span class="material-icons">refresh</span>
                Recargar
            </button>
        </div>
    `;
}

function showErrorState() {
    const grid = document.getElementById('productGrid');
    grid.innerHTML = `
        <div style="grid-column: 1/-1; text-align: center; padding: 4rem 2rem;">
            <span class="material-icons" style="font-size: 5rem; color: #ff4444;">error</span>
            <p style="font-size: 1.2rem; color: #666; margin-top: 1rem;">
                Error al cargar productos
            </p>
            <button onclick="loadProductsFromDB()" class="product__btn" style="margin-top: 1.5rem;">
                <span class="material-icons">refresh</span>
                Reintentar
            </button>
        </div>
    `;
}

function animateProducts() {
    const products = document.querySelectorAll('.product');
    
    products.forEach((product, index) => {
        product.style.opacity = '0';
        product.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            product.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            product.style.opacity = '1';
            product.style.transform = 'translateY(0)';
        }, index * 50);
    });
}

function increaseQuantity(button) {
    const input = button.closest('.product__quantity-controls').querySelector('.quantity-input');
    const max = parseInt(input.getAttribute('max')) || 999;
    let value = parseInt(input.value) || 1;
    
    if (value < max) {
        input.value = value + 1;
    }
}

function decreaseQuantity(button) {
    const input = button.closest('.product__quantity-controls').querySelector('.quantity-input');
    const min = parseInt(input.getAttribute('min')) || 1;
    let value = parseInt(input.value) || 1;
    
    if (value > min) {
        input.value = value - 1;
    }
}

console.log('✅ Sistema de productos dinámicos cargado');