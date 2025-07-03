<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Wisielec</title>
  <style>
    * {
      box-sizing: border-box;
    }
    body {
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
      background: linear-gradient(135deg, #8e2de2, #4a00e0);
      color: white;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      padding: 10px;
    }
    .container {
      text-align: center;
      max-width: 400px;
      width: 100%;
    }
    canvas {
      background: white;
      margin: 10px auto;
      display: block;
    }
    .word {
      font-size: 2rem;
      letter-spacing: 8px;
      margin: 20px 0;
      word-wrap: break-word;
    }
    .keyboard {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(30px, 1fr));
      gap: 6px;
      margin-top: 10px;
    }
    button {
      padding: 10px;
      background-color: #ffffff22;
      border: 1px solid white;
      color: white;
      margin-top: 20px;
      cursor: pointer;
      border-radius: 4px;
    }
    button:hover {
      background-color: #ffffff44;
    }
    .key {
      padding: 10px;
      background-color: white;
      color: #4a00e0;
      font-weight: bold;
      border: none;
      border-radius: 5px;
      cursor: pointer;
    }
    .key:disabled {
      background-color: #999;
      color: white;
      cursor: not-allowed;
    }
    .message {
      font-size: 1.2rem;
      margin-top: 15px;
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>Wisielec</h1>
    <canvas id="hangman" width="200" height="250"></canvas>
    <div id="word" class="word"></div>
    <div id="keyboard" class="keyboard"></div>
    <p id="message" class="message"></p>
    <button id="restart">Zagraj ponownie</button>
    <audio id="correct" src="data:audio/mp3;base64,//uQxAAADhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWF..."></audio>
    <audio id="wrong" src="data:audio/mp3;base64,//uQxAAAAAA"></audio>
    <audio id="win" src="data:audio/mp3;base64,//uQxAAAAAA"></audio>
    <audio id="lose" src="data:audio/mp3;base64,//uQxAAAAAA"></audio>
  </div>
  <script>
    const words = ["komputer", "pies", "wakacje", "samochód", "szkoła", "czekolada", "programowanie", "książka", "telefon", "muzyka"];
    const correctSound = document.getElementById("correct");
    const wrongSound = document.getElementById("wrong");
    const winSound = document.getElementById("win");
    const loseSound = document.getElementById("lose");

    const canvas = document.getElementById("hangman");
    const ctx = canvas.getContext("2d");

    const wordDisplay = document.getElementById("word");
    const keyboard = document.getElementById("keyboard");
    const message = document.getElementById("message");
    const restartBtn = document.getElementById("restart");

    let word = "";
    let guessed = [];
    let wrong = 0;

    function drawGallows() {
      ctx.lineWidth = 4;
      ctx.beginPath();
      ctx.moveTo(10, 240);
      ctx.lineTo(190, 240);
      ctx.moveTo(40, 240);
      ctx.lineTo(40, 20);
      ctx.lineTo(140, 20);
      ctx.lineTo(140, 40);
      ctx.stroke();
    }

    function drawPart(step) {
      ctx.beginPath();
      switch(step) {
        case 1: ctx.arc(140, 60, 20, 0, Math.PI * 2); break;
        case 2: ctx.moveTo(140, 80); ctx.lineTo(140, 140); break;
        case 3: ctx.moveTo(140, 100); ctx.lineTo(110, 120); break;
        case 4: ctx.moveTo(140, 100); ctx.lineTo(170, 120); break;
        case 5: ctx.moveTo(140, 140); ctx.lineTo(120, 180); break;
        case 6: ctx.moveTo(140, 140); ctx.lineTo(160, 180); break;
      }
      ctx.stroke();
    }

    function updateWord() {
      wordDisplay.innerHTML = word.split("").map(l => guessed.includes(l) ? l : "_").join(" ");
    }

    function setMessage(text, win = false) {
      message.textContent = text;
      message.style.color = win ? "#00ffae" : "#ff5a5a";
    }

    function handleGuess(letter, button) {
      if (word.includes(letter)) {
        guessed.push(letter);
        correctSound.play();
        updateWord();
        if (word.split("").every(l => guessed.includes(l))) {
          setMessage("🎉 Brawo! Odgadłeś hasło!", true);
          winSound.play();
          disableKeyboard();
        }
      } else {
        wrong++;
        drawPart(wrong);
        wrongSound.play();
        if (wrong === 6) {
          setMessage(`😢 Przegrałeś! Hasło to: ${word}`);
          loseSound.play();
          disableKeyboard();
        }
      }
      button.disabled = true;
    }

    function disableKeyboard() {
      document.querySelectorAll(".key").forEach(key => key.disabled = true);
    }

    function startGame() {
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      drawGallows();
      word = words[Math.floor(Math.random() * words.length)];
      guessed = [];
      wrong = 0;
      message.textContent = "";
      updateWord();
      keyboard.innerHTML = "";
      const letters = "aąbcćdeęfghijklłmnńoópqrsśtuvwxyzźż";
      letters.split("").forEach(letter => {
        const btn = document.createElement("button");
        btn.textContent = letter;
        btn.className = "key";
        btn.onclick = () => handleGuess(letter, btn);
        keyboard.appendChild(btn);
      });
    }

    restartBtn.onclick = startGame;
    window.onload = startGame;
  </script>
</body>
</html>
