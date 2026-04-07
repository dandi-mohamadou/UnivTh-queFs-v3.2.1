<?php
require_once __DIR__ . '/../config/database.php';

/**
 * Gestion des sessions et cookies — UnivThèqueFs
 * - Sessions PHP + table MySQL pour persistance
 * - Cookie "remember me" 30 jours
 * - Historique de navigation
 * - Préférences utilisateur
 */
class Session {
    private Database $db;
    private const COOKIE_REMEMBER = 'ut_remember';
    private const COOKIE_PREFS    = 'ut_prefs';
    private const COOKIE_HISTORY  = 'ut_history';
    private const COOKIE_CONSENT  = 'ut_consent';
    private const REMEMBER_DAYS   = 30;

    public function __construct() {
        $this->db = Database::getInstance();
        if (session_status() === PHP_SESSION_NONE) {
            session_name('UNIVTHEQUE_SESSION');
            session_set_cookie_params([
                'lifetime' => 86400,      // 24h
                'path'     => '/',
                'secure'   => false,      // true en HTTPS
                'httponly' => true,       // inaccessible au JS
                'samesite' => 'Lax',
            ]);
            session_start();
        }
    }

    // ── REMEMBER ME ────────────────────────────────────────────

    /** Crée un cookie "remember me" valide 30 jours */
    public function setRememberMe(int $userId): void {
        $token  = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', time() + self::REMEMBER_DAYS * 86400);

        // Sauvegarder le token en base
        // Supprimer l'ancien token de cet utilisateur avant d'en créer un nouveau
        $this->db->query(
            "DELETE FROM sessions_persistantes WHERE utilisateur_id = ?", [$userId]
        );
        $this->db->query(
            "INSERT INTO sessions_persistantes (utilisateur_id, token, expiry, ip_adresse, user_agent)
             VALUES (?,?,?,?,?)",
            [$userId, hash('sha256', $token), $expiry,
             $_SERVER['REMOTE_ADDR'] ?? '', substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255)]
        );

        // Poser le cookie
        setcookie(self::COOKIE_REMEMBER, $token, [
            'expires'  => time() + self::REMEMBER_DAYS * 86400,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    /** Vérifie le cookie remember me, retourne l'utilisateur si valide */
    public function checkRememberMe(): ?array {
        $token = $_COOKIE[self::COOKIE_REMEMBER] ?? null;
        if (!$token) return null;

        $row = $this->db->query(
            "SELECT sp.utilisateur_id, u.nom, u.prenom, u.email, u.role
             FROM sessions_persistantes sp
             JOIN utilisateurs u ON sp.utilisateur_id = u.id
             WHERE sp.token = ? AND sp.expiry > NOW() AND u.actif = 1",
            [hash('sha256', $token)]
        )->fetch();

        if (!$row) {
            $this->clearRememberMe();
            return null;
        }

        // Renouveler le cookie si proche de l'expiration
        $this->setRememberMe($row['utilisateur_id']);
        return $row;
    }

    /** Supprime le cookie remember me */
    public function clearRememberMe(): void {
        $token = $_COOKIE[self::COOKIE_REMEMBER] ?? null;
        if ($token) {
            $this->db->query(
                "DELETE FROM sessions_persistantes WHERE token = ?",
                [hash('sha256', $token)]
            );
        }
        setcookie(self::COOKIE_REMEMBER, '', [
            'expires' => time() - 3600, 'path' => '/', 'httponly' => true
        ]);
    }

    // ── CONSENTEMENT COOKIES ────────────────────────────────────

    public function setConsent(bool $accepted): void {
        setcookie(self::COOKIE_CONSENT, $accepted ? '1' : '0', [
            'expires'  => time() + 365 * 86400, // 1 an
            'path'     => '/',
            'httponly' => false, // lisible en JS
            'samesite' => 'Lax',
        ]);
    }

    public function hasConsent(): ?bool {
        if (!isset($_COOKIE[self::COOKIE_CONSENT])) return null; // pas encore choisi
        return $_COOKIE[self::COOKIE_CONSENT] === '1';
    }

    // ── HISTORIQUE DE NAVIGATION ────────────────────────────────

    /** Ajoute une UE à l'historique (max 10) */
    public function addToHistory(string $code, string $titre): void {
        $history = $this->getHistory();
        // Retirer si déjà présent
        $history = array_filter($history, fn($h) => $h['code'] !== $code);
        // Ajouter en tête
        array_unshift($history, [
            'code'  => $code,
            'titre' => $titre,
            'ts'    => time()
        ]);
        // Garder max 10
        $history = array_slice(array_values($history), 0, 10);

        setcookie(self::COOKIE_HISTORY, json_encode($history), [
            'expires'  => time() + 7 * 86400, // 7 jours
            'path'     => '/',
            'httponly' => false, // lisible en JS
            'samesite' => 'Lax',
        ]);
    }

    /** Retourne l'historique de navigation */
    public function getHistory(): array {
        $raw = $_COOKIE[self::COOKIE_HISTORY] ?? '[]';
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    // ── PRÉFÉRENCES UTILISATEUR ─────────────────────────────────

    /** Sauvegarde les préférences (thème, langue) */
    public function setPrefs(array $prefs): void {
        $current = $this->getPrefs();
        $merged  = array_merge($current, $prefs);

        setcookie(self::COOKIE_PREFS, json_encode($merged), [
            'expires'  => time() + 365 * 86400, // 1 an
            'path'     => '/',
            'httponly' => false, // lisible en JS
            'samesite' => 'Lax',
        ]);
    }

    public function getPrefs(): array {
        $raw  = $_COOKIE[self::COOKIE_PREFS] ?? '{}';
        $data = json_decode($raw, true);
        return is_array($data) ? $data : ['theme' => 'light', 'langue' => 'fr'];
    }

    // ── SESSION PHP ─────────────────────────────────────────────

    public function setUser(array $user): void {
        $_SESSION['user']       = $user;
        $_SESSION['login_time'] = time();
        $_SESSION['ip']         = $_SERVER['REMOTE_ADDR'] ?? '';
    }

    public function getUser(): ?array {
        return $_SESSION['user'] ?? null;
    }

    public function destroy(): void {
        $this->clearRememberMe();
        $_SESSION = [];
        session_destroy();
    }

    // ── NETTOYAGE BDD ───────────────────────────────────────────

    public function cleanExpiredSessions(): void {
        $this->db->query("DELETE FROM sessions_persistantes WHERE expiry < NOW()");
    }
}
