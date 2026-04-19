// ============================================================
// Food Faces - foodfaces.js
// ============================================================

(function() {

    // -- Random face ------------------------------------------

    var randomFig    = document.querySelector('.ff-hero__random-fig');
    var newRandomBtn = document.getElementById('js-new-random');

    var currentRandom = FF_RANDOM_CURRENT;

    function renderRandomFig(face) {
        if (!randomFig) return;
        var caption = face.caption ? '<span>' + escHtml(face.caption) + '</span>' : '';
        randomFig.innerHTML =
            '<img src="' + FF_IMAGES_PATH + escHtml(face.filename) + '" alt="' + escHtml(face.title) + '">' +
            '<figcaption>' +
                '<strong>' + escHtml(face.title) + '</strong>' +
                caption +
            '</figcaption>';
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


    // -- Share card (today's face) ----------------------------

    var shareCard = document.getElementById('js-share-card');

    function renderShareCard(face) {
        if (!shareCard || !face) return;
        var caption = face.caption || '';
        shareCard.innerHTML =
            '<img class="ff-share__card-img" src="' + FF_IMAGES_PATH + escHtml(face.filename) + '" alt="' + escHtml(face.title) + '">' +
            '<div class="ff-share__card-body">' +
                '<p class="ff-share__card-title">' + escHtml(face.title) + '</p>' +
                (caption ? '<p class="ff-share__card-caption">' + escHtml(caption) + '</p>' : '') +
                '<p class="ff-share__card-meta">' +
                    'Food Faces - an archive of edible portraits - ' +
                    '<a class="ff-share__card-link" href="https://projects.tobyziegler.com/foodfaces/" target="_blank">' +
                    'projects.tobyziegler.com/foodfaces</a>' +
                '</p>' +
            '</div>';
    }

    // Initialize share card with today's face
    if (FF_TODAY) {
        renderShareCard(FF_TODAY);
    }


    // -- Copy / Download (html2canvas) ------------------------

    var copyBtn     = document.getElementById('js-copy-card');
    var downloadBtn = document.getElementById('js-download-card');

    function captureCard(callback) {
        if (typeof html2canvas === 'undefined') {
            alert('Image capture not available - try the Download button.');
            return;
        }
        html2canvas(shareCard, { useCORS: true, scale: 2 }).then(function(canvas) {
            callback(canvas);
        }).catch(function() {
            alert('Capture failed. The image may be blocked by CORS. Try Download instead.');
        });
    }

    if (copyBtn) {
        copyBtn.addEventListener('click', function() {
            captureCard(function(canvas) {
                canvas.toBlob(function(blob) {
                    if (!navigator.clipboard || !navigator.clipboard.write) {
                        alert('Clipboard API not available in this browser. Try Download instead.');
                        return;
                    }
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
                var filename = FF_TODAY ? 'foodface-' + escHtml(FF_TODAY.filename) + '.png' : 'foodface-share.png';
                var link = document.createElement('a');
                link.download = filename;
                link.href = canvas.toDataURL('image/png');
                link.click();
            });
        });
    }


    // -- Load more gallery ------------------------------------

    var loadMoreBtn   = document.getElementById('js-load-more');
    var hiddenWrapper = document.querySelector('.ff-gallery__hidden');

    if (loadMoreBtn && hiddenWrapper) {
        loadMoreBtn.addEventListener('click', function() {
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
