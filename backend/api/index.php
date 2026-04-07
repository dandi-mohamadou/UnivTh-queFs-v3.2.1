<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET,POST,PATCH,DELETE,OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/DocumentController.php';

$method = $_SERVER['REQUEST_METHOD'];
$full   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pos    = strpos($full, 'index.php');
$path   = $pos !== false ? trim(substr($full, $pos + strlen('index.php')), '/') : '';
$parts  = $path !== '' ? explode('/', $path) : [];

$route = $parts[0] ?? '';
$id    = isset($parts[1]) && is_numeric($parts[1]) ? (int)$parts[1] : null;
$sub   = $parts[2] ?? null;

$auth = new AuthController();
$doc  = new DocumentController();

match(true) {
    // ── Auth ──
    $route==='auth' && ($parts[1]??'')==='login'    && $method==='POST' => $auth->login(),
    $route==='auth' && ($parts[1]??'')==='logout'   && $method==='POST' => $auth->logout(),
    $route==='auth' && ($parts[1]??'')==='register' && $method==='POST' => $auth->register(),
    $route==='auth' && ($parts[1]??'')==='me'       && $method==='GET'  => $auth->me($auth->requireAuth()),

    // ── Cookies & Sessions ──
    $route==='consent' && in_array($method,['GET','POST'])  => $auth->consent(),
    $route==='prefs'   && in_array($method,['GET','POST'])  => $auth->prefs(),
    $route==='history' && in_array($method,['GET','POST'])  => $auth->history(),

    // ── Documents ──
    $route==='documents' && $method==='GET'   && !$id               => $doc->index(),
    $route==='documents' && $method==='POST'  && !$id               => $doc->store($auth->requireAuth()),
    $route==='documents' && $method==='GET'   && $id && !$sub       => $doc->show($id),
    $route==='documents' && $method==='GET'   && $id && $sub==='download' => $doc->download($id),
    $route==='documents' && $method==='PATCH' && $id && $sub==='statut'   => (fn()=>$auth->requireAdmin()&&$doc->updateStatut($id))(),
    $route==='documents' && $method==='DELETE'&& $id                => (fn()=>$auth->requireAdmin()&&$doc->destroy($id))(),

    // ── Référentiels ──
    $route==='niveaux'         && $method==='GET' => $doc->niveaux(),
    $route==='ues'             && $method==='GET' => $doc->ues(),
    $route==='professeurs'     && $method==='GET' => $doc->professeurs(),
    $route==='types-documents' && $method==='GET' => $doc->typesDocuments(),
    $route==='annees'          && $method==='GET' => $doc->annees(),
    $route==='stats'           && $method==='GET' => $doc->stats(),

    // ── Test ──
    $route==='' && $method==='GET' => (function(){
        echo json_encode([
            'success' => true,
            'message' => 'UnivThèqueFs API v3.1 — opérationnelle',
            'features'=> ['JWT auth','PHP Sessions','Remember Me 30j','Cookie consent','Historique navigation','Préférences utilisateur']
        ], JSON_UNESCAPED_UNICODE);
    })(),

    default => (function(){ http_response_code(404); echo json_encode(['error'=>'Route introuvable']); })()
};
