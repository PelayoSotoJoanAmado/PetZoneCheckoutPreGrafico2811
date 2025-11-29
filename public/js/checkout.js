
//const CART_API = '../api/carrito.php';
const CART_API = '../routes/router.php?recurso=carrito';
const ENVIO_COSTO = 10.00;
const ENVIO_GRATIS_DESDE = 100.00;

let cartData = null;
let checkoutData = {};

// DISTRITOS POR DEPARTAMENTO
const distritosData = {
    'Lima': ['Lima', 'Miraflores', 'San Isidro', 'Surco', 'La Molina', 'San Borja', 'Jesús María', 'Lince', 'San Miguel', 'Pueblo Libre', 'Magdalena', 'Los Olivos', 'San Martín de Porres', 'Independencia', 'Comas', 'Ate', 'Santa Anita', 'La Victoria', 'San Juan de Lurigancho', 'Villa El Salvador'],
    'Arequipa': ['Arequipa', 'Cayma', 'Cerro Colorado', 'Mariano Melgar', 'Miraflores', 'Paucarpata', 'Sachaca', 'Yanahuara', 'José Luis Bustamante y Rivero'],
    'Cusco': ['Cusco', 'Wanchaq', 'San Sebastián', 'San Jerónimo', 'Santiago'],
    'La Libertad': ['Trujillo', 'Víctor Larco Herrera', 'Huanchaco', 'Moche', 'La Esperanza']
};

// INICIALIZACIÓN
document.addEventListener('DOMContentLoaded', () => {
    setupFormValidation();
    setupCardForm();
    setupDistrictsSelector();
    
    // CARGAR PRODUCTOS DEL CARRITO REAL
    loadCartData();
    
    // AUTOCOMPLETAR SI HAY USUARIO LOGUEADO
    loadUserDataIfAuthenticated();
});
/*
document.addEventListener('DOMContentLoaded', () => {
    setupFormValidation();
    setupCardForm();
    setupDistrictsSelector();
    
    // CARGAR PRODUCTOS DEL CARRITO REAL
    loadCartData();
});*/

// AUTOCOMPLETAR DATOS SI HAY USUARIO LOGUEADO - new
async function loadUserDataIfAuthenticated() {
    try {
        const response = await fetch('../routes/router.php?recurso=usuarios&action=check');
        const data = await response.json();
        
        if (data.authenticated && data.user) {
            // Autocompletar datos del usuario
            document.getElementById('nombre').value = data.user.nombre || '';
            document.getElementById('telefono').value = data.user.telefono || '';
            document.getElementById('email').value = data.user.email || '';
            
            console.log('✅ Datos de usuario autocargados');
        }
    } catch (error) {
        console.error('Error al cargar datos del usuario:', error);
    }
}

// CARGAR DATOS DEL CARRITO REAL
async function loadCartData() {
    try {
        const response = await fetch(`${CART_API}&action=get`);
        const data = await response.json();
        
        console.log('Datos del carrito:', data);
        
        if (data.success && data.items.length > 0) {
            cartData = data;
            console.log('Carrito cargado con', data.items.length, 'productos');
        } else {
            showToast('Tu carrito está vacío. Redirigiendo...', 'warning');
            setTimeout(() => {
                window.location.href = 'productos.html';
            }, 2000);
        }
    } catch (error) {
        console.error('Error al cargar carrito:', error);
        showToast('Error al cargar el carrito', 'error');
    }
}

// SETUP DISTRITOS
function setupDistrictsSelector() {
    const departamentoSelect = document.getElementById('departamento');
    const distritoSelect = document.getElementById('distrito');
    
    departamentoSelect.addEventListener('change', function() {
        const departamento = this.value;
        distritoSelect.disabled = !departamento;
        distritoSelect.innerHTML = '<option value="">Seleccionar distrito...</option>';

        if (departamento && distritosData[departamento]) {
            distritosData[departamento].forEach(distrito => {
                const option = document.createElement('option');
                option.value = distrito;
                option.textContent = distrito;
                distritoSelect.appendChild(option);
            });
        }
    });
}

// VALIDACIÓN DE FORMULARIO DE ENVÍO
function setupFormValidation() {
    const form = document.getElementById('shippingForm');
    
    form.addEventListener('submit', (e) => {
        e.preventDefault();
    });
    
    // Validación en tiempo real
    const inputs = form.querySelectorAll('input, textarea, select');
    inputs.forEach(input => {
        input.addEventListener('blur', () => validateField(input));
        input.addEventListener('input', () => {
            if (input.classList.contains('error')) {
                validateField(input);
            }
        });
    });
    
    // Validación de nombre - solo letras
    document.getElementById('nombre')?.addEventListener('input', function() {
        this.value = this.value.replace(/[^a-záéíóúñA-ZÁÉÍÓÚÑ\s]/g, '');
    });
    
    // Validación de teléfono - solo números
    document.getElementById('telefono')?.addEventListener('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '').slice(0, 9);
    });
}

function validateField(field) {
    const value = field.value.trim();
    let isValid = true;
    let errorMessage = '';
    
    removeFieldError(field);
    
    if (field.hasAttribute('required') && value === '') {
        isValid = false;
        errorMessage = 'Este campo es obligatorio';
    }
    
    if (isValid && value !== '') {
        switch(field.type) {
            case 'email':
                if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                    isValid = false;
                    errorMessage = 'Correo electrónico inválido';
                }
                break;
            case 'tel':
                if (!/^[9]\d{8}$/.test(value)) {
                    isValid = false;
                    errorMessage = 'Celular inválido (9 dígitos, empieza con 9)';
                }
                break;
        }
        
        if (field.id === 'nombre' && value.length < 3) {
            isValid = false;
            errorMessage = 'El nombre debe tener al menos 3 caracteres';
        }
        
        if (field.id === 'direccion' && value.length < 10) {
            isValid = false;
            errorMessage = 'La dirección debe ser más específica';
        }
    }
    
    if (!isValid) {
        showFieldError(field, errorMessage);
    }
    
    return isValid;
}

function showFieldError(field, message) {
    field.classList.add('error');
    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error';
    errorDiv.style.color = '#e74c3c';
    errorDiv.style.fontSize = '0.85rem';
    errorDiv.style.marginTop = '0.3rem';
    errorDiv.textContent = message;
    field.parentElement.appendChild(errorDiv);
}

function removeFieldError(field) {
    field.classList.remove('error');
    const errorDiv = field.parentElement.querySelector('.field-error');
    if (errorDiv) errorDiv.remove();
}

function validateForm() {
    const form = document.getElementById('shippingForm');
    const inputs = form.querySelectorAll('[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!validateField(input)) {
            isValid = false;
        }
    });
    
    if (!isValid) {
        showToast('Por favor completa correctamente todos los campos', 'warning');
    }
    
    return isValid;
}

// NAVEGACIÓN ENTRE PASOS
function goToStep2() {
    if (!validateForm()) return;
    
    checkoutData = {
        nombre: document.getElementById('nombre').value.trim(),
        telefono: document.getElementById('telefono').value.trim(),
        email: document.getElementById('email').value.trim(),
        departamento: document.getElementById('departamento').value,
        distrito: document.getElementById('distrito').value,
        direccion: document.getElementById('direccion').value.trim()
    };
    
    showSection(2);
    showToast('Información guardada correctamente', 'success');
}

function goToStep1() {
    showSection(1);
}

// MANEJAR PASO DE PAGO
function handlePaymentStep() {
    const metodoPago = document.querySelector('input[name="metodo_pago"]:checked');
    
    if (!metodoPago) {
        showToast('Selecciona un método de pago', 'warning');
        return;
    }
    
    checkoutData.metodo_pago = metodoPago.value;
    
    // SI ES TARJETA, MOSTRAR MODAL
    if (metodoPago.value === 'tarjeta') {
        showCardModal();
    } else {
        goToStep3();
    }
}

function goToStep3() {
    generateSummary();
    showSection(3);
}

function showSection(step) {
    document.querySelectorAll('.checkout-section').forEach(section => {
        section.classList.remove('active');
    });
    document.getElementById(`step${step}`).classList.add('active');
    updateStepper(step);
    window.scrollTo(0, 0);
}

function updateStepper(step) {
    document.querySelectorAll('.step').forEach((el, idx) => {
        const stepNum = idx + 1;
        el.classList.remove('active', 'completed');
        
        if (stepNum === step) {
            el.classList.add('active');
        } else if (stepNum < step) {
            el.classList.add('completed');
        }
    });
}

// GENERAR RESUMEN DEL PEDIDO
function generateSummary() {
    if (!cartData || !cartData.items) {
        showToast('Error: No hay datos del carrito', 'error');
        return;
    }
    
    const subtotal = parseFloat(cartData.totales.subtotal);
    const envio = subtotal >= ENVIO_GRATIS_DESDE ? 0 : ENVIO_COSTO;
    const total = subtotal + envio;
    
    const summaryHTML = `
        <div style="background: var(--gray-light); padding: 1.5rem; border-radius: 10px; margin-bottom: 2rem;">
            <h3 style="margin-bottom: 1rem; color: var(--text);">📋 Datos de Envío</h3>
            <p><strong>Nombre:</strong> ${checkoutData.nombre}</p>
            <p><strong>Teléfono:</strong> ${checkoutData.telefono}</p>
            <p><strong>Email:</strong> ${checkoutData.email}</p>
            <p><strong>Ubicación:</strong> ${checkoutData.distrito}, ${checkoutData.departamento}</p>
            <p><strong>Dirección:</strong> ${checkoutData.direccion}</p>
            <p style="margin-top: 1rem;"><strong>💳 Método de Pago:</strong> ${getPaymentMethodName(checkoutData.metodo_pago)}</p>
        </div>

        <h3 style="margin-bottom: 1rem;">🛒 Productos del Carrito</h3>
        <div class="summary-items">
            ${cartData.items.map(item => `
                <div class="summary-item">
                    <img src="${item.imagen || '../IMG/no-image.png'}" alt="${item.nombre}">
                    <div class="summary-item-info">
                        <div style="font-weight: 600; margin-bottom: 0.3rem;">${item.nombre}</div>
                        <div style="color: var(--text-light); font-size: 0.9rem;">
                            Cantidad: ${item.cantidad} x S/. ${parseFloat(item.precio_unitario).toFixed(2)}
                        </div>
                    </div>
                    <div style="font-weight: 700; color: var(--primary);">
                        S/. ${parseFloat(item.subtotal).toFixed(2)}
                    </div>
                </div>
            `).join('')}
        </div>

        <div class="summary-totals">
            <div class="total-row">
                <span>Subtotal:</span>
                <span>S/. ${subtotal.toFixed(2)}</span>
            </div>
            <div class="total-row">
                <span>Envío:</span>
                <span>${envio === 0 ? '<span style="color: var(--success); font-weight: 700;">GRATIS</span>' : 'S/. ' + envio.toFixed(2)}</span>
            </div>
            <div class="total-row total-final">
                <span>Total a Pagar:</span>
                <span>S/. ${total.toFixed(2)}</span>
            </div>
        </div>
    `;

    document.getElementById('summaryContent').innerHTML = summaryHTML;
    checkoutData.total = total;
}

function getPaymentMethodName(method) {
    const names = {
        'yape': '📱 Yape',
        'plin': '📱 Plin',
        'tarjeta': '💳 Tarjeta de Crédito/Débito',
        'efectivo': '💵 Efectivo (Contra entrega)'
    };
    return names[method] || method;
}

// VALIDACIÓN DE TARJETA
function setupCardForm() {
    const cardForm = document.getElementById('cardForm');
    const cardNumber = document.getElementById('cardNumber');
    const cardExpiry = document.getElementById('cardExpiry');
    const cardCVV = document.getElementById('cardCVV');
    const cardHolder = document.getElementById('cardHolder');

    // Formatear número de tarjeta
    cardNumber.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
        let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
        e.target.value = formattedValue;
        detectCardBrand(value);
    });

    // Formatear fecha de expiración
    cardExpiry.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length >= 2) {
            value = value.slice(0, 2) + '/' + value.slice(2, 4);
        }
        e.target.value = value;
    });

    // Solo números en CVV
    cardCVV.addEventListener('input', function(e) {
        e.target.value = e.target.value.replace(/\D/g, '');
    });

    // Solo letras en nombre
    cardHolder.addEventListener('input', function(e) {
        e.target.value = e.target.value.replace(/[^a-zA-Z\s]/g, '').toUpperCase();
    });

    // Validar formulario
    cardForm.addEventListener('submit', function(e) {
        e.preventDefault();
        validateAndProcessCard();
    });
}

function detectCardBrand(number) {
    const brandDiv = document.getElementById('cardBrand');
    const firstDigit = number.charAt(0);
    const firstTwo = number.slice(0, 2);
    
    let brand = '';

    if (number.length < 1) {
        brandDiv.classList.remove('active');
        return;
    }

    if (firstDigit === '4') {
        brand = '💳 Visa';
    } else if (['51', '52', '53', '54', '55'].includes(firstTwo)) {
        brand = '💳 Mastercard';
    } else if (firstTwo === '37' || firstTwo === '34') {
        brand = '💳 American Express';
    } else if (number.length >= 4) {
        brand = '❓ Tarjeta no reconocida';
    }

    if (brand) {
        brandDiv.textContent = brand;
        brandDiv.classList.add('active');
    }
}

function luhnCheck(cardNumber) {
    const digits = cardNumber.replace(/\s/g, '').split('').reverse();
    let sum = 0;
    
    for (let i = 0; i < digits.length; i++) {
        let digit = parseInt(digits[i]);
        
        if (i % 2 === 1) {
            digit *= 2;
            if (digit > 9) digit -= 9;
        }
        
        sum += digit;
    }
    
    return sum % 10 === 0;
}

function validateAndProcessCard() {
    const cardNumber = document.getElementById('cardNumber').value.replace(/\s/g, '');
    const cardExpiry = document.getElementById('cardExpiry').value;
    const cardCVV = document.getElementById('cardCVV').value;
    const cardHolder = document.getElementById('cardHolder').value.trim();

    let isValid = true;

    // Validar número de tarjeta
    if (cardNumber.length < 13 || cardNumber.length > 19) {
        showToast('Número de tarjeta inválido', 'error');
        isValid = false;
    } else if (!luhnCheck(cardNumber)) {
        showToast('Esta tarjeta no es válida (Algoritmo de Luhn)', 'error');
        isValid = false;
    }

    // Validar fecha de expiración
    const expiryParts = cardExpiry.split('/');
    if (expiryParts.length !== 2) {
        showToast('Formato de fecha inválido', 'error');
        isValid = false;
    } else {
        const month = parseInt(expiryParts[0]);
        const year = parseInt('20' + expiryParts[1]);
        const currentDate = new Date();
        const currentYear = currentDate.getFullYear();
        const currentMonth = currentDate.getMonth() + 1;

        if (month < 1 || month > 12 || year < currentYear || (year === currentYear && month < currentMonth)) {
            showToast('Tarjeta expirada o fecha inválida', 'error');
            isValid = false;
        }
    }

    // Validar CVV
    if (cardCVV.length < 3) {
        showToast('CVV inválido', 'error');
        isValid = false;
    }

    // Validar titular
    if (cardHolder.length < 5) {
        showToast('Nombre del titular inválido', 'error');
        isValid = false;
    }

    if (isValid) {
        showToast('✓ Tarjeta validada correctamente', 'success');
        closeCardModal();
        goToStep3();
    }
}

// MODALES
function showCardModal() {
    document.getElementById('cardModal').classList.add('active');
    document.getElementById('cardNumber').focus();
}

function closeCardModal() {
    document.getElementById('cardModal').classList.remove('active');
    document.getElementById('cardForm').reset();
    document.getElementById('cardBrand').classList.remove('active');
}

function showQRModal(metodo) {
    const methodName = metodo === 'yape' ? 'Yape' : 'Plin';
    document.getElementById('qrTitle').textContent = `Pagar con ${methodName}`;
    document.getElementById('qrAmount').textContent = `S/. ${checkoutData.total.toFixed(2)}`;
    document.getElementById('qrModal').classList.add('active');
}

function closeQRModal() {
    document.getElementById('qrModal').classList.remove('active');
}

function confirmQRPayment() {
    if (!document.getElementById('qrConfirmation').checked) {
        showToast('Por favor confirma que realizaste el pago', 'warning');
        return;
    }
    closeQRModal();
    processPayment();
}

// REALIZAR PEDIDO
async function placeOrder() {
    if (!cartData || !checkoutData) {
        showToast('Error: Datos incompletos', 'error');
        return;
    }
    
    const metodo = checkoutData.metodo_pago;
    
    if (metodo === 'yape' || metodo === 'plin') {
        showQRModal(metodo);
    } else {
        await processPayment();
    }
}

async function processPayment() {
    const btnOrder = document.querySelector('.btn-primary[onclick="placeOrder()"]');
    const originalText = btnOrder.innerHTML;
    btnOrder.disabled = true;
    btnOrder.innerHTML = '<span class="material-icons rotating">sync</span> Procesando...';
    
    try {
        const direccionCompleta = `${checkoutData.direccion}, ${checkoutData.distrito}, ${checkoutData.departamento}`;
        
        const response = await fetch(`${CART_API}&action=checkout`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                nombre: checkoutData.nombre,
                email: checkoutData.email,
                telefono: checkoutData.telefono,
                direccion: direccionCompleta,
                metodo_pago: checkoutData.metodo_pago,
                notas: ''
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showSuccessModal(data.codigo_pedido);
            document.getElementById('shippingForm').reset();
        } else {
            showToast(data.message || 'Error al procesar el pedido', 'error');
            btnOrder.disabled = false;
            btnOrder.innerHTML = originalText;
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Error de conexión', 'error');
        btnOrder.disabled = false;
        btnOrder.innerHTML = originalText;
    }
}

// MODAL DE ÉXITO
function showSuccessModal(codigoPedido) {
    document.getElementById('orderCode').textContent = codigoPedido;
    document.getElementById('successModal').classList.add('active');
}

function closeSuccessModal() {
    document.getElementById('successModal').classList.remove('active');
    setTimeout(() => {
        window.location.href = '../index.html';
    }, 500);
}

// TOAST NOTIFICATIONS
function showToast(message, type = 'info') {
    const toast = document.getElementById('toast');
    const icons = {
        success: 'check_circle',
        error: 'error',
        warning: 'warning',
        info: 'info'
    };
    
    toast.className = `toast ${type}`;
    toast.innerHTML = `
        <span class="material-icons">${icons[type]}</span>
        <span>${message}</span>
    `;
    toast.classList.add('show');
    
    setTimeout(() => {
        toast.classList.remove('show');
    }, 4000);
}

console.log('Checkout.js cargado');