<?php
require_once __DIR__ . '/../config/database.php';

class Document {
    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getAll(array $filters = [], int $page = 1, int $limit = 20): array {
        $where  = ["d.statut = 'publie'"];
        $params = [];

        if (!empty($filters['ue_id']))         { $where[] = 'd.ue_id = ?';         $params[] = $filters['ue_id']; }
        if (!empty($filters['type_doc_id']))   { $where[] = 'd.type_doc_id = ?';   $params[] = $filters['type_doc_id']; }
        if (!empty($filters['professeur_id'])) { $where[] = 'd.professeur_id = ?'; $params[] = $filters['professeur_id']; }
        if (!empty($filters['annee_id']))      { $where[] = 'd.annee_id = ?';      $params[] = $filters['annee_id']; }
        if (!empty($filters['niveau_code']))   { $where[] = 'n.code = ?';          $params[] = $filters['niveau_code']; }
        if (!empty($filters['semestre']))      { $where[] = 's.numero = ?';        $params[] = $filters['semestre']; }
        if (!empty($filters['keyword'])) {
            $where[]  = '(d.titre LIKE ? OR d.description LIKE ?)';
            $kw       = '%' . $filters['keyword'] . '%';
            $params[] = $kw;
            $params[] = $kw;
        }

        $whereStr = implode(' AND ', $where);
        $offset   = ($page - 1) * $limit;

        $total = (int)$this->db->query(
            "SELECT COUNT(*) FROM documents d
             JOIN unites_enseignement ue ON d.ue_id = ue.id
             JOIN semestres s ON ue.semestre_id = s.id
             JOIN niveaux n ON s.niveau_id = n.id
             WHERE $whereStr", $params
        )->fetchColumn();

        $data = $this->db->query(
            "SELECT d.id, d.titre, d.nom_fichier, d.statut, d.created_at,
                    d.nb_telechargements, d.nb_vues,
                    ue.code AS code_ue, ue.intitule AS intitule_ue,
                    td.code AS type_code, td.intitule AS type_label,
                    CONCAT(p.grade,' ',p.nom) AS professeur,
                    n.code AS niveau, s.numero AS semestre,
                    aa.intitule AS annee
             FROM documents d
             JOIN unites_enseignement ue ON d.ue_id = ue.id
             JOIN types_documents td     ON d.type_doc_id = td.id
             JOIN semestres s            ON ue.semestre_id = s.id
             JOIN niveaux n              ON s.niveau_id = n.id
             LEFT JOIN professeurs p     ON d.professeur_id = p.id
             LEFT JOIN annees_academiques aa ON d.annee_id = aa.id
             WHERE $whereStr
             ORDER BY d.created_at DESC
             LIMIT $limit OFFSET $offset", $params
        )->fetchAll();

        return [
            'data'       => $data,
            'total'      => $total,
            'page'       => $page,
            'limit'      => $limit,
            'totalPages' => (int)ceil($total / $limit),
        ];
    }

    public function getById(int $id, bool $publie_only = false): ?array {
        $where = $publie_only ? "d.id = ? AND d.statut = 'publie'" : "d.id = ?";
        return $this->db->query(
            "SELECT d.*, ue.code AS code_ue, ue.intitule AS intitule_ue,
                    td.code AS type_code, td.intitule AS type_label,
                    CONCAT(p.grade,' ',p.nom) AS professeur,
                    n.code AS niveau, aa.intitule AS annee
             FROM documents d
             JOIN unites_enseignement ue ON d.ue_id = ue.id
             JOIN types_documents td ON d.type_doc_id = td.id
             JOIN semestres s ON ue.semestre_id = s.id
             JOIN niveaux n ON s.niveau_id = n.id
             LEFT JOIN professeurs p ON d.professeur_id = p.id
             LEFT JOIN annees_academiques aa ON d.annee_id = aa.id
             WHERE $where", [$id]
        )->fetch() ?: null;
    }

    public function create(array $data, array $file): array {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $msgs = [
                UPLOAD_ERR_INI_SIZE   => 'Fichier trop grand (limite php.ini)',
                UPLOAD_ERR_FORM_SIZE  => 'Fichier trop grand (limite formulaire)',
                UPLOAD_ERR_NO_FILE    => 'Aucun fichier reçu',
                UPLOAD_ERR_NO_TMP_DIR => 'Dossier temporaire manquant',
                UPLOAD_ERR_CANT_WRITE => 'Impossible d\'écrire sur le disque',
            ];
            return ['success' => false, 'message' => $msgs[$file['error']] ?? 'Erreur upload '.$file['error']];
        }

        if ($file['size'] > MAX_FILE_SIZE) {
            return ['success' => false, 'message' => 'Fichier trop volumineux (max 20 Mo)'];
        }

        // Créer le dossier uploads si nécessaire
        if (!is_dir(UPLOAD_DIR)) {
            if (!mkdir(UPLOAD_DIR, 0777, true)) {
                return ['success' => false, 'message' => 'Impossible de créer le dossier uploads/'];
            }
        }

        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed  = ['pdf','doc','docx','ppt','pptx'];
        if (!in_array($ext, $allowed)) {
            return ['success' => false, 'message' => 'Extension non autorisée. Formats : PDF, DOC, DOCX, PPT, PPTX'];
        }

        $filename = uniqid('doc_') . '_' . time() . '.' . $ext;
        $dest     = UPLOAD_DIR . $filename;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return ['success' => false, 'message' => 'Impossible de sauvegarder le fichier. Vérifie les permissions du dossier backend/uploads/'];
        }

        $this->db->query(
            "INSERT INTO documents
             (titre, description, ue_id, type_doc_id, professeur_id, annee_id,
              nom_fichier, taille_fichier, uploade_par, statut)
             VALUES (?,?,?,?,?,?,?,?,?,'publie')",
            [
                trim($data['titre']),
                $data['description'] ?? null,
                (int)$data['ue_id'],
                (int)$data['type_doc_id'],
                !empty($data['professeur_id']) ? (int)$data['professeur_id'] : null,
                !empty($data['annee_id'])      ? (int)$data['annee_id']      : null,
                $filename,
                $file['size'],
                (int)$data['uploade_par'],
            ]
        );

        return ['success' => true, 'id' => (int)$this->db->lastInsertId(), 'fichier' => $filename];
    }

    public function setStatut(int $id, string $statut): bool {
        if (!in_array($statut, ['publie', 'refuse', 'en_attente'])) return false;
        $this->db->query("UPDATE documents SET statut = ? WHERE id = ?", [$statut, $id]);
        return true;
    }

    public function delete(int $id): bool {
        $doc = $this->db->query("SELECT nom_fichier FROM documents WHERE id = ?", [$id])->fetch();
        if (!$doc) return false;
        $path = UPLOAD_DIR . $doc['nom_fichier'];
        if (file_exists($path)) unlink($path);
        $this->db->query("DELETE FROM documents WHERE id = ?", [$id]);
        return true;
    }

    public function getStats(): array {
        return [
            'total_documents' => (int)$this->db->query("SELECT COUNT(*) FROM documents")->fetchColumn(),
            'publies'         => (int)$this->db->query("SELECT COUNT(*) FROM documents WHERE statut='publie'")->fetchColumn(),
            'en_attente'      => (int)$this->db->query("SELECT COUNT(*) FROM documents WHERE statut='en_attente'")->fetchColumn(),
            'telechargements' => (int)$this->db->query("SELECT COALESCE(SUM(nb_telechargements),0) FROM documents")->fetchColumn(),
            'total_ues'       => (int)$this->db->query("SELECT COUNT(*) FROM unites_enseignement")->fetchColumn(),
            'total_profs'     => (int)$this->db->query("SELECT COUNT(*) FROM professeurs")->fetchColumn(),
        ];
    }
}
