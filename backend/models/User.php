<?php
// ============================================================
//  UnivThèqueFs - Modèle Utilisateur
//  Fichier: backend/models/User.php
// ============================================================
require_once __DIR__ . '/../config/database.php';

class User {
    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function findByEmail(string $email): ?array {
        return $this->db->query(
            "SELECT * FROM utilisateurs WHERE email = ? AND actif = 1", [$email]
        )->fetch() ?: null;
    }

    public function findById(int $id): ?array {
        return $this->db->query(
            "SELECT id,nom,prenom,email,role,niveau_id,created_at FROM utilisateurs WHERE id = ?", [$id]
        )->fetch() ?: null;
    }

    public function create(array $data): array {
        $existing = $this->findByEmail($data['email']);
        if ($existing) return ['success' => false, 'message' => 'Email déjà utilisé'];

        $hash = password_hash($data['mot_de_passe'], PASSWORD_BCRYPT, ['cost' => 12]);
        $this->db->query(
            "INSERT INTO utilisateurs (nom,prenom,email,mot_de_passe,role,niveau_id)
             VALUES (?,?,?,?,?,?)",
            [$data['nom'], $data['prenom'], $data['email'], $hash,
             $data['role'] ?? 'etudiant', $data['niveau_id'] ?? null]
        );
        return ['success' => true, 'id' => $this->db->lastInsertId()];
    }

    public function authenticate(string $email, string $password): array {
        $user = $this->findByEmail($email);
        if (!$user || !password_verify($password, $user['mot_de_passe'])) {
            return ['success' => false, 'message' => 'Email ou mot de passe incorrect'];
        }
        $token = $this->generateToken($user['id']);
        return ['success' => true, 'token' => $token, 'user' => [
            'id' => $user['id'], 'nom' => $user['nom'],
            'prenom' => $user['prenom'], 'email' => $user['email'],
            'role' => $user['role']
        ]];
    }

    private function generateToken(int $userId): string {
        $payload = base64_encode(json_encode([
            'uid' => $userId,
            'exp' => time() + SESSION_LIFETIME,
            'iat' => time()
        ]));
        $sig = hash_hmac('sha256', $payload, JWT_SECRET);
        return $payload . '.' . $sig;
    }

    public function verifyToken(string $token): ?array {
        [$payload, $sig] = explode('.', $token, 2) + [null, null];
        if (!$payload || !$sig) return null;
        if (!hash_equals(hash_hmac('sha256', $payload, JWT_SECRET), $sig)) return null;
        $data = json_decode(base64_decode($payload), true);
        if (!$data || $data['exp'] < time()) return null;
        return $this->findById($data['uid']);
    }

    public function getAll(): array {
        return $this->db->query(
            "SELECT id,nom,prenom,email,role,actif,created_at FROM utilisateurs ORDER BY created_at DESC"
        )->fetchAll();
    }
}
