function randomIntFromInterval(min, max) { // min and max included
    return Math.floor(Math.random() * (max - min + 1) + min);
}

function customOpenUrl(url) {
    let win = window.open(url, '_blank');
    win.focus();
}