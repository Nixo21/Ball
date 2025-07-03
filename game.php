<?php
session_start();

$words = [
    "komputer", "pies", "wakacje", "samochód", "szkoła",
    "czekolada", "programowanie", "książka", "telefon", "muzyka",
    "rower", "lampa", "drzewo", "kawa", "herbata",
    "butelka", "zegarek", "kwiat", "samolot", "gwiazda",
    "kanapa", "poduszka", "lód", "miód", "marchewka"
];

function newGame() {
    global $words;
    $_SESSION['word'] = $words[array_rand($words)];
    $_SESSION['guessed'] = [];
    $_SESSION['wrong'] = 0;
    $_SESSION['finished'] = false;
    $_SESSION['won'] = false;
}

if (!isset($_SESSION['word'])) {
    newGame();
}

function checkWin($word, $guessed) {
    foreach (mb_str_split($word) as $l) {
        if (!in_array($l, $guessed)) return false;
    }
    return true;
}

$action = $_POST['action'] ?? '';

header('Content-Type: application/json');

if ($action === 'restart') {
    newGame();
    echo json_encode([
        'word' => $_SESSION['word'],
        'guessed' => $_SESSION['guessed'],
        'wrong' => $_SESSION['wrong'],
        'finished' => $_SESSION['finished']
    ]);
    exit;
}

if ($action === 'guess') {
    if ($_SESSION['finished']) {
        echo json_encode(['error' => 'Gra zakończona, zacznij nową.']);
        exit;
    }

    $letter = mb_strtolower($_POST['letter'] ?? '');
    if (!preg_match('/^[a-ząćęłńóśźż]$/u', $letter)) {
        echo json_encode(['error' => 'Nieprawidłowa litera']);
        exit;
    }

    if (in_array($letter, $_SESSION['guessed'])) {
        echo json_encode(['error' => 'Litera już była']);
        exit;
    }

    $_SESSION['guessed'][] = $letter;

    if (mb_strpos($_SESSION['word'], $letter) === false) {
        $_SESSION['wrong']++;
    }

    if ($_SESSION['wrong'] >= 7) { // 7 części wisielca
        $_SESSION['finished'] = true;
        $_SESSION['won'] = false;
    } else if (checkWin($_SESSION['word'], $_SESSION['guessed'])) {
        $_SESSION['finished'] = true;
        $_SESSION['won'] = true;
        $wins = isset($_COOKIE['wins']) ? (int)$_COOKIE['wins'] : 0;
        setcookie('wins', $wins + 1, time() + (86400*365), "/");
    }

    echo json_encode([
        'word' => $_SESSION['word'],
        'guessed' => $_SESSION['guessed'],
        'wrong' => $_SESSION['wrong'],
        'finished' => $_SESSION['finished'],
        'won' => $_SESSION['won'],
        'wins' => isset($_COOKIE['wins']) ? ((int)$_COOKIE['wins'] + ($_SESSION['won'] ? 1 : 0)) : ($_SESSION['won'] ? 1 : 0)
    ]);
    exit;
}

echo json_encode(['error' => 'Nieznana akcja']);
exit;

if (!function_exists('mb_str_split')) {
    function mb_str_split($string) {
        return preg_split('//u', $string, -1, PREG_SPLIT_NO_EMPTY);
    }
}
?>
