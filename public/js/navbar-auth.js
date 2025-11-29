/**
 * Control de Autenticación en Navbar - PetZone
 */

const AUTH_API_NAVBAR = window.location.pathname.includes('/public/') 
    ? '../routes/router.php?recurso=usuarios' 
    : 'routes/router.php?recurso=usuarios';

// INICIALIZACIÓN
document.addEventListener('DOMContentLoaded', () => {
    checkAuthStatus();
});

// VERIFICAR ESTADO DE AUTENTICACIÓN
async function checkAuthStatus() {
    try {
        const response = await fetch(`${AUTH_API_NAVBAR}&action=check`);
        const data = await response.json();
        
        if (data.authenticated && data.user) {
            showUserLoggedIn(data.user);
        } else {
            showLoginButtons();
        }
    } catch (error) {
        console.error('Error verificando autenticación:', error);
        showLoginButtons();
    }
}

// MOSTRAR USUARIO LOGUEADO
function showUserLoggedIn(user) {
    const authContainer = document.getElementById('authButtons');
    if (!authContainer) return;
    
    const firstName = user.nombre.split(' ')[0];
    
    authContainer.innerHTML = `
        <div class="user-menu">
            <button class="user-menu-btn" onclick="toggleUserDropdown()">
                <span class="material-icons">account_circle</span>
                <span class="user-name">${firstName}</span>
                <span class="material-icons dropdown-icon">arrow_drop_down</span>
            </button>
            <div class="user-dropdown" id="userDropdown">
                <div class="dropdown-header">
                    <span class="material-icons">person</span>
                    <div>
                        <strong>${user.nombre}</strong>
                        <small>${user.email}</small>
                    </div>
                </div>
                <div class="dropdown-divider"></div>
                <a href="#" class="dropdown-item" onclick="goToPerfil(); return false;">
                    <span class="material-icons">person</span>
                    Mi Perfil
                </a>
                <div class="dropdown-divider"></div>
                <a href="#" class="dropdown-item logout" onclick="handleLogout(); return false;">
                    <span class="material-icons">logout</span>
                    Cerrar Sesión
                </a>
            </div>
        </div>
    `;
}

// MOSTRAR BOTONES DE LOGIN/REGISTRO
function showLoginButtons() {
    const authContainer = document.getElementById('authButtons');
    if (!authContainer) return;
    
    const isIndexPage = window.location.pathname.endsWith('index.html') || window.location.pathname.endsWith('/');
    const prefix = isIndexPage ? 'public/' : '';
    
    authContainer.innerHTML = `
        <a href="${prefix}login.html" class="auth-link">
            <span class="material-icons">login</span>
            <span>Ingresar</span>
        </a>
        <a href="${prefix}registro.html" class="auth-link auth-link--register">
            <span class="material-icons">person_add</span>
            <span>Registrarse</span>
        </a>
    `;
}

// TOGGLE DROPDOWN
function toggleUserDropdown() {
    const dropdown = document.getElementById('userDropdown');
    if (dropdown) {
        dropdown.classList.toggle('show');
    }
}

// CERRAR DROPDOWN AL HACER CLIC FUERA
document.addEventListener('click', (e) => {
    const dropdown = document.getElementById('userDropdown');
    const userMenuBtn = document.querySelector('.user-menu-btn');
    
    if (dropdown && !dropdown.contains(e.target) && e.target !== userMenuBtn && !userMenuBtn?.contains(e.target)) {
        dropdown.classList.remove('show');
    }
});

// LOGOUT
async function handleLogout() {
    if (!confirm('¿Estás seguro de cerrar sesión?')) return;
    
    try {
        const response = await fetch(`${AUTH_API_NAVBAR}&action=logout`, {
            method: 'POST'
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToastNavbar('Sesión cerrada exitosamente', 'success');
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        }
    } catch (error) {
        console.error('Error al cerrar sesión:', error);
        showToastNavbar('Error al cerrar sesión', 'error');
    }
}

// IR A PERFIL
function goToPerfil() {
    const isIndexPage = window.location.pathname.endsWith('index.html') || window.location.pathname.endsWith('/');
    const prefix = isIndexPage ? 'public/' : '';
    window.location.href = `${prefix}perfil.html`;
}

// IR A MIS PEDIDOS
function goToMisPedidos() {
    const isIndexPage = window.location.pathname.endsWith('index.html') || window.location.pathname.endsWith('/');
    const prefix = isIndexPage ? 'public/' : '';
    window.location.href = `${prefix}mis-pedidos.html`;
}

// TOAST
function showToastNavbar(message, type = 'info') {
    const existingToast = document.querySelector('.toast-notification');
    if (existingToast) existingToast.remove();
    
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
    }, 3000);
}

console.log('✅ Navbar Auth cargado');