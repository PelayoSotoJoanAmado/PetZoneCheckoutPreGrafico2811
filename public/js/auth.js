/**
 * Sistema de Autenticación - PetZone
 */

const AUTH_API = '../routes/router.php?recurso=usuarios';

// INICIALIZACIÓN
document.addEventListener('DOMContentLoaded', () => {
    // Login Form
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', handleLogin);
    }
    
    // Registro Form
    const registroForm = document.getElementById('registroForm');
    if (registroForm) {
        registroForm.addEventListener('submit', handleRegistro);
        
        // Validar teléfono en tiempo real
        const telefonoInput = document.getElementById('telefono');
        if (telefonoInput) {
            telefonoInput.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '').slice(0, 9);
            });
        }
    }
});

// HANDLE LOGIN
async function handleLogin(e) {
    e.preventDefault();
    
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    const submitBtn = e.target.querySelector('button[type="submit"]');
    
    if (!email || !password) {
        showToast('Por favor completa todos los campos', 'warning');
        return;
    }
    
    // Deshabilitar botón
    const originalHTML = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="material-icons rotating">sync</span> Iniciando...';
    
    try {
        const response = await fetch(`${AUTH_API}&action=login`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, password })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('¡Bienvenido de vuelta!', 'success');
            
            // Redirigir después de 1 segundo
            setTimeout(() => {
                // Si venía de checkout, volver ahí, sino ir a productos
                const urlParams = new URLSearchParams(window.location.search);
                const redirect = urlParams.get('redirect') || 'productos.html';
                window.location.href = redirect;
            }, 1000);
        } else {
            showToast(data.message || 'Error al iniciar sesión', 'error');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalHTML;
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Error de conexión', 'error');
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalHTML;
    }
}

// HANDLE REGISTRO
async function handleRegistro(e) {
    e.preventDefault();
    
    const nombre = document.getElementById('nombre').value.trim();
    const email = document.getElementById('email').value.trim();
    const telefono = document.getElementById('telefono').value.trim();
    const password = document.getElementById('password').value;
    const passwordConfirm = document.getElementById('password_confirm').value;
    const submitBtn = e.target.querySelector('button[type="submit"]');
    
    // Validaciones
    if (!nombre || !email || !telefono || !password || !passwordConfirm) {
        showToast('Por favor completa todos los campos', 'warning');
        return;
    }
    
    if (password !== passwordConfirm) {
        showToast('Las contraseñas no coinciden', 'warning');
        return;
    }
    
    if (password.length < 6) {
        showToast('La contraseña debe tener al menos 6 caracteres', 'warning');
        return;
    }
    
    if (!/^[9]\d{8}$/.test(telefono)) {
        showToast('Teléfono inválido (9 dígitos, empieza con 9)', 'warning');
        return;
    }
    
    // Deshabilitar botón
    const originalHTML = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="material-icons rotating">sync</span> Creando cuenta...';
    
    try {
        const response = await fetch(`${AUTH_API}&action=registrar`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ nombre, email, telefono, password })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('¡Cuenta creada exitosamente!', 'success');
            
            // Redirigir después de 1 segundo
            setTimeout(() => {
                const urlParams = new URLSearchParams(window.location.search);
                const redirect = urlParams.get('redirect') || 'productos.html';
                window.location.href = redirect;
            }, 1000);
        } else {
            showToast(data.message || 'Error al crear cuenta', 'error');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalHTML;
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Error de conexión', 'error');
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalHTML;
    }
}

// TOGGLE PASSWORD VISIBILITY
function togglePassword(inputId = 'password') {
    const input = document.getElementById(inputId);
    const button = input.parentElement.querySelector('.toggle-password');
    const icon = button.querySelector('.material-icons');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.textContent = 'visibility_off';
    } else {
        input.type = 'password';
        icon.textContent = 'visibility';
    }
}

// SHOW TOAST
function showToast(message, type = 'info') {
    const existingToast = document.querySelector('.toast-notification');
    if (existingToast) {
        existingToast.remove();
    }
    
    const toast = document.createElement('div');
    toast.className = `toast-notification toast-${type}`;
    
    const icon = {
        success: 'check_circle',
        error: 'error',
        warning: 'warning',
        info: 'info'
    }[type] || 'info';
    
    toast.innerHTML = `
        <span class="material-icons">${icon}</span>
        <span>${message}</span>
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => toast.classList.add('show'), 10);
    
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}

console.log('Sistema de autenticación cargado');