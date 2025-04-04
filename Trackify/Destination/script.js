// Timer
let timerInterval;
let elapsedTime = 0; // in milliseconds

function updateTimer() {
    elapsedTime += 10; // increment by 10 milliseconds

    const hours = Math.floor((elapsedTime / (1000 * 60 * 60)) % 24);
    const minutes = Math.floor((elapsedTime / (1000 * 60)) % 60);
    const seconds = Math.floor((elapsedTime / 1000) % 60);
    const milliseconds = Math.floor((elapsedTime % 1000) / 10);

    document.getElementById("hours").textContent = String(hours).padStart(2, '0');
    document.getElementById("minutes").textContent = String(minutes).padStart(2, '0');
    document.getElementById("seconds").textContent = String(seconds).padStart(2, '0');
    document.getElementById("milliseconds").textContent = String(milliseconds).padStart(2, '0');
}

function startTimer() {
    if (!timerInterval) {
        timerInterval = setInterval(updateTimer, 10); // update every 10 milliseconds
    }
}

function stopTimer() {
    clearInterval(timerInterval);
    timerInterval = null; // reset the interval
}

function resetTimer() {
    stopTimer();
    elapsedTime = 0; // reset elapsed time
    updateTimer(); // update display
}

// Event listeners for buttons
document.getElementById("startButton").addEventListener("click", startTimer);
document.getElementById("stopButton").addEventListener("click", stopTimer);
document.getElementById("resetButton").addEventListener("click", resetTimer);

