// ============================================================
// Food Faces — foodfaces.js
// ============================================================

(function() {

    // -- Random face ------------------------------------------

    var randomSection = document.querySelector('.ff-hero__random');
    var randomFig     = document.querySelector('.ff-hero__random-fig');
    var newRandomBtn  = document.getElementById('js-new-random');

    var currentRandom = FF_RANDOM_CURRENT;

    function renderRandomFig(face) {
        var caption = face.caption ? '<span>' + escHtml(face.caption) + '</span>' : '';
        randomFig.innerHTML =
            '<img src="' + FF_IMAGES_PATH + escHtml(face.filename) + '" alt="' + escHtml(face.title) + '">' +
            '<figcaption>' +
                '<strong>' + escHtml(face.title) + '</strong>' +
                caption +
            '</figcaption>';
        renderShareCard(face);
    }

    if (newRandomBtn) {
        newRandomBtn.addEventListener('click', function() {
            if (!FF_FACES || FF_FACES.length === 0) return;
            // Pick a different face from current
            var candidates = FF_FACES.filter(function(f) {
                return f.filename !== currentRandom.filename;
            });
            if (candidates.length === 0) candidates = FF_FACES;
            var pick = candidates[Math.floor(Math.random() * candidates.length)];
            currentRandom = pick;
            renderRandomFig(pick);
        });
    }


    // -- Share card -------------------------------------------

    var shareCard = document.getElementById('js-share-card');

    function renderShareCard(face) {
        if (!shareCard) return;
        var caption = face.caption || '';
        shareCard.innerHTML =
            '<img class="ff-share__card-img" src="' + FF_IMAGES_PATH + escHtml(face.filename) + '" alt="' + escHtml(face.title) + '">' +
            '<div class="ff-share__card-body">' +
                '<p class="ff-share__card-title">' + escHtml(face.title) + '</p>' +
                (caption ? '<p class="ff-share__card-caption">' + escHtml(caption) + '</p>' : '') +
                '<p class="ff-share__card-meta">' +
                    'Food Faces &mdash; an archive of edible portraits &mdash; ' +
                    '<a class="ff-share__card-link" href="https://projects.tobyziegler.com/foodfaces/" target="_blank">' +
                    'projects.tobyziegler.com/foodfaces</a>' +
                '</p>' +
            '</div>';
    }

    // Initialize share card with the PHP-rendered random face
    if (FF_RANDOM_CURRENT) {
        renderShareCard(FF_RANDOM_CURRENT);
    }

    // Copy card as image using html2canvas (CDN-loaded below if available)
    var copyBtn     = document.getElementById('js-copy-card');
    var downloadBtn = document.getElementById('js-download-card');

    function captureCard(callback) {
        if (typeof html2canvas === 'undefined') {
            alert('Image capture not available. Try the Download button.');
            return;
        }
        html2canvas(shareCard, { useCORS: true, scale: 2 }).then(function(canvas) {
            callback(canvas);
        });
    }

    if (copyBtn) {
        copyBtn.addEventListener('click', function() {
            captureCard(function(canvas) {
                canvas.toBlob(function(blob) {
                    var item = new ClipboardItem({ 'image/png': blob });
                    navigator.clipboard.write([item]).then(function() {
                        copyBtn.textContent = 'Copied!';
                        setTimeout(function() { copyBtn.textContent = 'Copy image'; }, 2000);
                    }).catch(function() {
                        alert('Copy failed. Try Download instead.');
                    });
                });
            });
        });
    }

    if (downloadBtn) {
        downloadBtn.addEventListener('click', function() {
            captureCard(function(canvas) {
                var link = document.createElement('a');
                link.download = 'foodface-' + (currentRandom.filename || 'share') + '.png';
                link.href = canvas.toDataURL('image/png');
                link.click();
            });
        });
    }


    // -- Load more gallery ------------------------------------

    var loadMoreBtn   = document.getElementById('js-load-more');
    var hiddenWrapper = document.querySelector('.ff-gallery__hidden');
    var remainingSpan = document.getElementById('js-remaining');

    if (loadMoreBtn && hiddenWrapper) {
        loadMoreBtn.addEventListener('click', function() {
            // Move all hidden cards into the grid
            var grid = document.getElementById('js-gallery');
            while (hiddenWrapper.firstChild) {
                grid.appendChild(hiddenWrapper.firstChild);
            }
            hiddenWrapper.remove();
            loadMoreBtn.parentElement.remove();
        });
    }


    // -- Utility ----------------------------------------------

    function escHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

})();
