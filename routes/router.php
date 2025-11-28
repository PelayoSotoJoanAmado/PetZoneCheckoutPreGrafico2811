<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../model/BaseModel.php';
require_once __DIR__ . '/../controller/BaseController.php';

$recurso = $_GET['recurso'] ?? '';
$action = $_GET['action'] ?? 'index';

// Si es POST y no hay action en GET, buscar en el body JSON
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($action)) {
    $postData = json_decode(file_get_contents('php://input'), true);
    $action = $postData['action'] ?? 'index';
}

$controllerMap = [
    'productos' => 'ProductoController',
    'servicios' => 'ServiciosController',
    'reservas' => 'ReservaController',
    'citas' => 'CitaController',
    'carrito' => 'CarritoController',
    'sliders' => 'SliderController',
    'anuncios' => 'AnuncioController',
    'auth' => 'AuthController',
    'categorias' => 'CategoriaController',
    'estadisticas' => 'EstadisticaController'
];

if (empty($recurso) || !isset($controllerMap[$recurso])) {
    http_response_code(404);
    die(json_encode([
        'success' => false, 
        'message' => 'Recurso no encontrado',
        'recurso' => $recurso
    ]));
}

$controllerName = $controllerMap[$recurso];
$controllerFile = __DIR__ . "/../controller/{$controllerName}.php";

if (!file_exists($controllerFile)) {
    http_response_code(500);
    die(json_encode([
        'success' => false, 
        'message' => 'Controller no existe',
        'ruta' => $controllerFile
    ]));
}

require_once $controllerFile;

// Cargar el modelo correspondiente
$modelFile = __DIR__ . "/../model/" . str_replace('Controller', 'Model', $controllerName) . ".php";
if (file_exists($modelFile)) {
    require_once $modelFile;
}

if (!class_exists($controllerName)) {
    http_response_code(500);
    die(json_encode([
        'success' => false,
        'message' => 'Clase no existe',
        'clase' => $controllerName
    ]));
}

try {
    $controller = new $controllerName();
    
    if (!method_exists($controller, $action)) {
        http_response_code(404);
        die(json_encode([
            'success' => false, 
            'message' => 'Método no existe',
            'metodo' => $action,
            'metodos_disponibles' => get_class_methods($controller)
        ]));
    }
    
    $controller->$action();
    
} catch (Exception $e) {
    http_response_code(500);
    die(json_encode([
        'success' => false, 
        'message' => 'Error del servidor',
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]));
}