<?php
session_start();

$words = [
    "komputer", "pies", "wakacje", "samochód", "szkoła",
    "czekolada", "programowanie", "książka", "telefon", "muzyka",
    "rower", "lampa", "drzewo", "kawa", "herbata",
    "butelka", "zegarek", "kwiat", "samolot", "gwiazda",
    "kanapa", "poduszka", "lód", "miód", "marchewka"
];

if (!isset($_SESSION['word'])) {
    $_SESSION['word'] = $words[array_rand($words)];
    $_SESSION['guessed'] = [];
    $_SESSION['wrong'] = 0;
    $_SESSION['finished'] = false;
    $_SESSION['won'] = false;
}

$wins = isset($_COOKIE['wins']) ? (int)$_COOKIE['wins'] : 0;
?>

<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Wisielec PHP</title>
<style>
  body {
    margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #8e2de2, #4a00e0);
    color: white; display: flex; justify-content: center; align-items: center;
    min-height: 100vh; padding: 10px;
  }
  .container {
    max-width: 400px; width: 100%; text-align: center; position: relative;
  }
  canvas {
    background: white; margin: 10px auto; display: block;
    border-radius: 8px;
  }
  .word {
    font-size: 2rem; letter-spacing: 8px; margin: 20px 0; word-wrap: break-word;
  }
  .keyboard {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(30px, 1fr));
    gap: 6px; margin-top: 10px;
  }
  button.key {
    padding: 10px; background-color: white; color: #4a00e0;
    font-weight: bold; border: none; border-radius: 5px; cursor: pointer;
    user-select: none;
  }
  button.key:disabled {
    background-color: #999; color: white; cursor: not-allowed;
  }
  #message {
    margin-top: 15px; font-size: 1.2rem;
  }
  #restart {
    margin-top: 20px; padding: 10px;
    background: #ffffff22; border: 1px solid white; color: white;
    cursor: pointer; border-radius: 4px;
  }
  #restart:hover {
    background: #ffffff44;
  }
  #score {
    position: fixed;
    top: 12px;
    right: 12px;
    background: rgba(75,0,224,0.75);
    padding: 8px 16px;
    border-radius: 12px;
    font-weight: 700;
    font-size: 1.1rem;
    user-select: none;
    z-index: 999;
  }
  @media(max-width: 450px){
    .word {
      font-size: 1.5rem;
      letter-spacing: 5px;
    }
    button.key {
      padding: 8px;
      font-size: 0.9rem;
    }
  }
</style>
</head>
<body>
  <div id="score">Score: <span id="wins"><?= $wins ?></span></div>
  <div class="container">
    <h1>Wisielec PHP</h1>
    <canvas id="hangman" width="220" height="280"></canvas>
    <div id="word" class="word"></div>
    <div id="keyboard" class="keyboard"></div>
    <p id="message"></p>
    <button id="restart">Zagraj ponownie</button>
  </div>

<script>
const canvas = document.getElementById('hangman');
const ctx = canvas.getContext('2d');
const wordDiv = document.getElementById('word');
const keyboardDiv = document.getElementById('keyboard');
const messageP = document.getElementById('message');
const restartBtn = document.getElementById('restart');
const winsSpan = document.getElementById('wins');

let word = "";
let guessed = [];
let wrong = 0;
let finished = false;

function drawGallows() {
  ctx.lineWidth = 5;
  ctx.strokeStyle = "#4a00e0";
  ctx.clearRect(0, 0, canvas.width, canvas.height);

  // Szubienica
  ctx.beginPath();
  ctx.moveTo(30, 270);
  ctx.lineTo(190, 270);
  ctx.moveTo(60, 270);
  ctx.lineTo(60, 20);
  ctx.lineTo(150, 20);
  ctx.lineTo(150, 50);
  ctx.stroke();
}

// Rysowanie kolejnych części wisielca (7 kroków)
function drawPart(step) {
  ctx.strokeStyle = "#4a00e0";
  ctx.lineWidth = 4;
  ctx.beginPath();

  switch(step) {
    case 1: // głowa
      ctx.arc(150, 80, 30, 0, Math.PI * 2);
      ctx.stroke();
      break;
    case 2: // tułów
      ctx.moveTo(150, 110);
      ctx.lineTo(150, 190);
      ctx.stroke();
      break;
    case 3: // lewa ręka
      ctx.moveTo(150, 130);
      ctx.lineTo(110, 160);
      ctx.stroke();
      break;
    case 4: // prawa ręka
      ctx.moveTo(150, 130);
      ctx.lineTo(190, 160);
      ctx.stroke();
      break;
    case 5: // lewa noga
      ctx.moveTo(150, 190);
      ctx.lineTo(120, 240);
      ctx.stroke();
      break;
    case 6: // prawa noga
      ctx.moveTo(150, 190);
      ctx.lineTo(180, 240);
      ctx.stroke();
      break;
    case 7: // lina wisielca
      ctx.moveTo(150, 50);
      ctx.lineTo(150, 20);
      ctx.stroke();
      break;
  }
}

function updateWordDisplay(letters, guessed) {
  let display = letters.map(l => guessed.includes(l) ? l : "_").join(" ");
  wordDiv.textContent = display;
}

function disableKeyboard() {
  document.querySelectorAll('button.key').forEach(b => b.disabled = true);
}

function setMessage(text, win=false) {
  messageP.textContent = text;
  messageP.style.color = win ? "#00ffae" : "#ff5a5a";
}

function createKeyboard(guessed) {
  const letters = "aąbcćdeęfghijklłmnńoópqrsśtuvwxyzźż".split("");
  keyboardDiv.innerHTML = "";
  letters.forEach(l => {
    const btn = document.createElement("button");
    btn.textContent = l;
    btn.className = "key";
    btn.disabled = guessed.includes(l) || finished;
    btn.onclick = () => guessLetter(l);
    keyboardDiv.appendChild(btn);
  });
}

function guessLetter(letter) {
  if (finished) return;
  fetch("game.php", {
    method: "POST",
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: "action=guess&letter=" + encodeURIComponent(letter)
  })
  .then(res => res.json())
  .then(data => {
    if(data.error) {
      setMessage(data.error);
      return;
    }
    word = data.word;
    guessed = data.guessed;
    wrong = data.wrong;
    finished = data.finished;
    updateWordDisplay(word.split(""), guessed);
    drawGallows();
    // Rysuj szubienicę i wisielca zgodnie z ilością błędów (od 0 do 7)
    // Zaczynamy od kroku 7, czyli lina, potem głowa itd.
    if (wrong > 0) {
      for(let i=7; i > 7 - wrong; i--) {
        drawPart(i);
      }
    }
    createKeyboard(guessed);
    if (finished) {
      disableKeyboard();
      if(data.won) {
        setMessage("🎉 Brawo! Odgadłeś hasło!", true);
        winsSpan.textContent = data.wins;
      } else {
        setMessage("😢 Przegrałeś! Hasło to: " + word);
      }
    } else {
      setMessage("");
    }
  });
}

function restartGame() {
  fetch("game.php", {
    method: "POST",
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: "action=restart"
  })
  .then(res => res.json())
  .then(data => {
    word = data.word;
    guessed = data.guessed;
    wrong = data.wrong;
    finished = data.finished;
    updateWordDisplay(word.split(""), guessed);
    drawGallows();
    createKeyboard(guessed);
    setMessage("");
  });
}

restartBtn.onclick = restartGame;

// Obsługa klawiatury fizycznej
window.addEventListener("keydown", e => {
  const letter = e.key.toLowerCase();
  if ("aąbcćdeęfghijklłmnńoópqrsśtuvwxyzźż".includes(letter)) {
    guessLetter(letter);
  }
});

// Inicjalizacja
restartGame();
</script>
</body>
</html>
