<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
include_once "../functions.php";
include_once "../sqlServerinfo.php";

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'fetchInitialData':
        fetchInitialData($conn);
        break;
    case 'fetchNations':
        fetchNations($conn);
        break;
    case 'fetchBooks':
        fetchBooks($conn);
        break;
    case 'fetchFormationsFromBook':
        fetchFormationsFromBook($conn);
        break;
    default:
        echo json_encode(['error' => 'Invalid action']);
}

function fetchInitialData($conn) {
    $Periods = fetchDataAjax($conn,"SELECT DISTINCT period, periodLong FROM nationBooks");
    echo json_encode(['Periods' => $Periods]);
}

function fetchNations($conn) {
    $period = $_GET['period'] ?? '';
    $Nations = fetchDataAjax($conn, "SELECT DISTINCT Nation FROM nationBooks WHERE period = '{$period}'");
    echo json_encode(['Nations' => $Nations]);
}

function fetchBooks($conn) {
    $period = $_GET['period'] ?? '';
    $nation = $_GET['nation'] ?? '';
    $Books = fetchDataAjax($conn, "SELECT * FROM nationBooks WHERE period = '{$period}' AND Nation = '{$nation}'");
    echo json_encode(['Books' => $Books]);
}

function fetchFormationDetails($conn) {
    $formationNr = $_GET['formationNr'] ?? 1;
    $query = parseUrlQuery();
    $bookTitle = $_GET['bookTitle'] ?? '';

    $Formation_DB = fetchDataAjax($conn, "SELECT * FROM formation_DB WHERE formation LIKE '%F$formationNr%'");
    $formationCards = fetchDataAjax($conn,  "SELECT DISTINCT cmdCardFormationMod.Book AS Book, cmdCardFormationMod.formation AS formation, cmdCardFormationMod.card AS card, cmdCardCost.platoonTypes AS platoonTypes, cmdCardCost.pricePerTeam AS pricePerTeam, cmdCardCost.price AS cost, cmdCardsText.code AS code, cmdCardsText.title AS title, CONCAT(cmdCardsText.notes, ' ', cmdCardsText.unitModifier, ' ', cmdCardsText.statsModifier) AS notes FROM cmdCardFormationMod LEFT JOIN cmdCardCost ON cmdCardCost.Book = cmdCardFormationMod.Book AND cmdCardCost.card = cmdCardFormationMod.card LEFT JOIN cmdCardsText ON cmdCardCost.Book = cmdCardsText.Book AND cmdCardCost.card = cmdCardsText.card WHERE cmdCardsText.Book LIKE '%$bookTitle%' AND cmdCardFormationMod.formation LIKE '%F$formationNr%'");

    $formationDetails = processFormationDetails($Formation_DB, $formationCards, $query, $formationNr);

    echo json_encode($formationDetails);
}

function fetchFormationsFromBook($conn) {
    $bookTitle = $_GET['Book'] ?? '';
    $Formations = fetchDataAjax($conn, "SELECT * FROM formations WHERE Book LIKE '%$bookTitle%'");
    echo json_encode(['Formations' => $Formations]);
}

function processFormationDetails($Formation_DB, $formationCards, $query, $formationNr) {
    $formationDetails = [];
    // Your processing logic here...

    return $formationDetails;
}
?>
