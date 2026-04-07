<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Session.php';

class AuthController {
    private User    $userModel;
    private Session $session;

    public function __construct() {
        $this->userModel = new User();
        $this->session   = new Session();
    }

    public function login(): void {
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['email']) || empty($data['password'])) {
            $this->json(['success' => false, 'message' => 'Email et mot de passe requis'], 400);
            return;
        }

        $result = $this->userModel->authenticate($data['email'], $data['password']);

        if ($result['success']) {
            // Stocker en session PHP
            $this->session->setUser($result['user']);

            // Cookie "Remember Me" si demandé
            if (!empty($data['remember_me'])) {
                $this->session->setRememberMe($result['user']['id']);
            }
        }

        $this->json($result, $result['success'] ? 200 : 401);
    }

    public function logout(): void {
        $this->session->destroy();
        $this->json(['success' => true, 'message' => 'Déconnecté']);
    }

    public function register(): void {
        $data = json_decode(file_get_contents('php://input'), true);
        foreach (['nom','prenom','email','mot_de_passe'] as $f) {
            if (empty($data[$f])) {
                $this->json(['success' => false, 'message' => "Champ '$f' requis"], 400);
                return;
            }
        }
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->json(['success' => false, 'message' => 'Email invalide'], 400);
            return;
        }
        if (strlen($data['mot_de_passe']) < 6) {
            $this->json(['success' => false, 'message' => 'Mot de passe trop court (min 6 caractères)'], 400);
            return;
        }
        $result = $this->userModel->create($data);
        $this->json($result, $result['success'] ? 201 : 409);
    }

    public function me(array $user): void {
        $this->json(['success' => true, 'user' => $user]);
    }

    /** Consentement cookies */
    public function consent(): void {
        $data     = json_decode(file_get_contents('php://input'), true);
        $accepted = !empty($data['accepted']);
        $this->session->setConsent($accepted);
        $this->json(['success' => true, 'consent' => $accepted]);
    }

    /** Préférences utilisateur */
    public function prefs(): void {
        $method = $_SERVER['REQUEST_METHOD'];
        if ($method === 'GET') {
            $this->json(['success' => true, 'prefs' => $this->session->getPrefs()]);
        } elseif ($method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $allowed = array_intersect_key($data ?? [], array_flip(['theme','langue']));
            $this->session->setPrefs($allowed);
            $this->json(['success' => true, 'prefs' => $this->session->getPrefs()]);
        }
    }

    /** Historique de navigation */
    public function history(): void {
        $method = $_SERVER['REQUEST_METHOD'];
        if ($method === 'GET') {
            $this->json(['success' => true, 'history' => $this->session->getHistory()]);
        } elseif ($method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            if (!empty($data['code']) && !empty($data['titre'])) {
                $this->session->addToHistory($data['code'], $data['titre']);
            }
            $this->json(['success' => true]);
        }
    }

    /** Lit le token Bearer depuis toutes les sources possibles */
    private function getBearerToken(): ?string {
        $header = $_SERVER['HTTP_AUTHORIZATION']
               ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
               ?? '';

        if (empty($header) && function_exists('apache_request_headers')) {
            $h = apache_request_headers();
            $header = $h['Authorization'] ?? $h['authorization'] ?? '';
        }

        // Vérifier aussi la session PHP
        if (empty($header)) {
            $sessionUser = $this->session->getUser();
            if ($sessionUser) return 'SESSION_' . $sessionUser['id'];
        }

        if (!empty($_GET['token'])) return $_GET['token'];

        if (preg_match('/^Bearer\s+(.+)$/i', trim($header), $m)) return $m[1];
        return null;
    }

    public function requireAuth(): ?array {
        $token = $this->getBearerToken();

        // Token de session PHP
        if ($token && str_starts_with($token, 'SESSION_')) {
            $user = $this->session->getUser();
            if ($user) return $user;
        }

        // Token JWT
        if (!$token) {
            // Tenter remember me
            $user = $this->session->checkRememberMe();
            if ($user) return $user;

            $this->json(['success' => false, 'message' => 'Token manquant — reconnectez-vous'], 401);
            return null;
        }

        $user = $this->userModel->verifyToken($token);
        if (!$user) {
            // Tenter remember me en fallback
            $user = $this->session->checkRememberMe();
            if ($user) return $user;

            $this->json(['success' => false, 'message' => 'Session expirée — reconnectez-vous'], 401);
            return null;
        }
        return $user;
    }

    public function requireAdmin(): ?array {
        $user = $this->requireAuth();
        if ($user === null) return null; // requireAuth() a déjà renvoyé l'erreur
        if ($user['role'] !== 'admin') {
            $this->json(['success' => false, 'message' => 'Accès réservé aux administrateurs'], 403);
            return null;
        }
        return $user;
    }

    private function json(array $data, int $code = 200): void {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: Authorization, Content-Type');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
