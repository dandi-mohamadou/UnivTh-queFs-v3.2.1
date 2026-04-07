<?php
require_once __DIR__ . '/../models/Document.php';
require_once __DIR__ . '/../config/database.php';

class DocumentController {
    private Document $model;
    private Database $db;

    public function __construct() {
        $this->model = new Document();
        $this->db    = Database::getInstance();
    }

    public function index(): void {
        $filters = [
            'ue_id'         => $_GET['ue_id']         ?? null,
            'type_doc_id'   => $_GET['type_doc_id']   ?? null,
            'professeur_id' => $_GET['professeur_id'] ?? null,
            'annee_id'      => $_GET['annee_id']       ?? null,
            'niveau_code'   => $_GET['niveau']         ?? null,
            'semestre'      => $_GET['semestre']       ?? null,
            'keyword'       => $_GET['q']              ?? null,
        ];
        $page   = max(1, (int)($_GET['page']  ?? 1));
        $limit  = min(50, max(1, (int)($_GET['limit'] ?? 20)));
        $this->json($this->model->getAll(array_filter($filters), $page, $limit));
    }

    public function show(int $id): void {
        $doc = $this->model->getById($id);
        if (!$doc) { $this->json(['error' => 'Document introuvable'], 404); return; }
        $this->json($doc);
    }

    public function store(?array $user): void {
        if (!$user) return;
        $data = $_POST;
        $data['uploade_par'] = $user['id'];

        if (empty($data['titre']) || empty($data['ue_id']) || empty($data['type_doc_id'])) {
            $this->json(['success' => false, 'message' => 'Titre, UE et type de document sont requis'], 400);
            return;
        }
        if (empty($_FILES['fichier']) || $_FILES['fichier']['error'] === UPLOAD_ERR_NO_FILE) {
            $this->json(['success' => false, 'message' => 'Fichier manquant'], 400);
            return;
        }

        $result = $this->model->create($data, $_FILES['fichier']);
        $this->json($result, $result['success'] ? 201 : 400);
    }

    public function updateStatut(int $id): void {
        $data   = json_decode(file_get_contents('php://input'), true);
        $statut = $data['statut'] ?? '';
        $ok     = $this->model->setStatut($id, $statut);
        $this->json(['success' => $ok], $ok ? 200 : 400);
    }

    public function destroy(int $id): void {
        $ok = $this->model->delete($id);
        $this->json(['success' => $ok], $ok ? 200 : 404);
    }

    public function download(int $id): void {
        $doc = $this->db->query(
            "SELECT * FROM documents WHERE id = ? AND statut = 'publie'", [$id]
        )->fetch();

        if (!$doc) { $this->json(['error' => 'Document introuvable ou non publié'], 404); return; }

        $path = UPLOAD_DIR . $doc['nom_fichier'];
        if (!file_exists($path)) { $this->json(['error' => 'Fichier physique introuvable'], 404); return; }

        // Incrémenter le compteur + logger
        $this->db->query("UPDATE documents SET nb_telechargements = nb_telechargements + 1 WHERE id = ?", [$id]);
        // Log dans journaux_acces
        try {
            $userId = null;
            $token  = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
            if (preg_match('/Bearer\s+(.+)/i', $token, $m)) {
                require_once __DIR__ . '/../models/User.php';
                $userModel = new User();
                $u = $userModel->verifyToken($m[1]);
                if ($u) $userId = $u['id'];
            }
            $this->db->query(
                "INSERT INTO journaux_acces (utilisateur_id, document_id, action, ip_adresse) VALUES (?,?,'telechargement',?)",
                [$userId, $id, $_SERVER['REMOTE_ADDR'] ?? '']
            );
        } catch (\Throwable $e) { /* log silencieux */ }

        $ext  = strtolower(pathinfo($doc['nom_fichier'], PATHINFO_EXTENSION));
        $mime = match($ext) {
            'pdf'  => 'application/pdf',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'doc'  => 'application/msword',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            default => 'application/octet-stream',
        };

        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . addslashes($doc['titre']) . '.' . $ext . '"');
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: no-cache');
        ob_clean();
        flush();
        readfile($path);
        exit;
    }

    public function stats(): void { $this->json($this->model->getStats()); }

    public function niveaux(): void {
        $this->json($this->db->query("SELECT * FROM niveaux ORDER BY code")->fetchAll());
    }

    public function ues(): void {
        $sql = "SELECT ue.id, ue.code, ue.intitule, ue.credits,
                       s.numero AS semestre_numero, n.code AS niveau_code
                FROM unites_enseignement ue
                JOIN semestres s ON ue.semestre_id = s.id
                JOIN niveaux n ON s.niveau_id = n.id";
        $params = [];
        $where  = [];
        if (!empty($_GET['semestre'])) { $where[] = 's.numero = ?'; $params[] = $_GET['semestre']; }
        if (!empty($_GET['niveau']))   { $where[] = 'n.code = ?';   $params[] = $_GET['niveau']; }
        if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
        $sql .= ' ORDER BY n.code, s.numero, ue.code';
        $this->json($this->db->query($sql, $params)->fetchAll());
    }

    public function professeurs(): void {
        $this->json($this->db->query(
            "SELECT id, CONCAT(grade,' ',nom) AS nom_complet, email FROM professeurs ORDER BY nom"
        )->fetchAll());
    }

    public function typesDocuments(): void {
        $this->json($this->db->query("SELECT * FROM types_documents ORDER BY id")->fetchAll());
    }

    public function annees(): void {
        $this->json($this->db->query(
            "SELECT * FROM annees_academiques ORDER BY intitule DESC"
        )->fetchAll());
    }

    private function json(mixed $data, int $code = 200): void {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
