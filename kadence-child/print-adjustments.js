window.onbeforeprint = function() {
    document.body.style.width = '100%';
    document.body.style.margin = '0';
    document.body.style.padding = '0';
    var rapport = document.querySelector('.rapport-ecole-print');
    if (rapport) {
        rapport.style.width = '100%';
        rapport.style.position = 'absolute';
        rapport.style.left = '0';
        rapport.style.top = '0';
    }
};