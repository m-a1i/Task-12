<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight OPTIONS requests gracefully
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

$host = 'localhost';
$db   = 'salon_cms';
$user = 'root'; 
$pass = '';     
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database connection fault: " . $e->getMessage()]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// --- READ: GET /services ---
if ($method === 'GET') {
    try {
        if (isset($_GET['id'])) {
            $stmt = $pdo->prepare("SELECT * FROM services WHERE id = :id");
            $stmt->execute([':id' => intval($_GET['id'])]);
            $result = $stmt->fetch();
        } else {
            $stmt = $pdo->query("SELECT * FROM services ORDER BY id DESC");
            $result = $stmt->fetchAll();
        }
        http_response_code(200);
        echo json_encode($result);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
}

// --- CREATE: POST /services ---
if ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true) ?? $_POST;

    $title       = isset($data['title']) ? trim($data['title']) : '';
    $description = isset($data['description']) ? trim($data['description']) : '';
    $image_url   = isset($data['image_url']) ? trim($data['image_url']) : '';
    $category    = isset($data['category']) ? trim($data['category']) : '';

    if (empty($title) || empty($description) || empty($image_url) || empty($category)) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Validation incomplete. Fields missing."]);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO services (title, description, image_url, category) VALUES (:t, :d, :i, :c)");
        $stmt->execute([
            ':t' => htmlspecialchars($title),
            ':d' => htmlspecialchars($description),
            ':i' => filter_var($image_url, FILTER_SANITIZE_URL),
            ':c' => htmlspecialchars($category)
        ]);
        http_response_code(201);
        echo json_encode(["status" => "success", "message" => "Service record instantiated successfully."]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
}

// --- UPDATE: PUT /services/:id ---
if ($method === 'PUT') {
    $data = json_decode(file_get_contents("php://input"), true);

    $id          = isset($data['id']) ? intval($data['id']) : 0;
    $title       = isset($data['title']) ? trim($data['title']) : '';
    $description = isset($data['description']) ? trim($data['description']) : '';
    $image_url   = isset($data['image_url']) ? trim($data['image_url']) : '';
    $category    = isset($data['category']) ? trim($data['category']) : '';

    if (!$id || empty($title) || empty($description) || empty($image_url) || empty($category)) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Update matrices parameters are missing required flags."]);
        exit;
    }

    try {
        $stmt = $pdo->prepare("UPDATE services SET title = :t, description = :d, image_url = :i, category = :c WHERE id = :id");
        $stmt->execute([
            ':t'  => htmlspecialchars($title),
            ':d'  => htmlspecialchars($description),
            ':i'  => filter_var($image_url, FILTER_SANITIZE_URL),
            ':c'  => htmlspecialchars($category),
            ':id' => $id
        ]);
        http_response_code(200);
        echo json_encode(["status" => "success", "message" => "Service record compiled and updated successfully."]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
}

// --- DELETE: DELETE /services/:id ---
if ($method === 'DELETE') {
    $data = json_decode(file_get_contents("php://input"), true);
    $id = isset($data['id']) ? intval($data['id']) : (isset($_GET['id']) ? intval($_GET['id']) : 0);

    if (!$id) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Target identification index strictly required to complete drop execution."]);
        exit;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM services WHERE id = :id");
        $stmt->execute([':id' => $id]);
        http_response_code(200);
        echo json_encode(["status" => "success", "message" => "Service row unlinked and removed successfully."]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
}
?>
