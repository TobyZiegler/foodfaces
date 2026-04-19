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
            var candidates = FF_FACES.filter(function(f) {
                return f.filename !== currentRandom.filename;
            });
            if (candidates.length === 0) candidates = FF_FACES;
            var pick = candidates[Math.floor(Math.random() * candidates.length)];
            currentRandom = pick;
            renderRandomFig(pick);
            // Keep the random share card in sync with the hero random face
            renderShareCard(shareCardRandom, pick);
        });
    }


    // -- Share card rendering ---------------------------------

    function renderShareCard(cardEl, face) {
        if (!cardEl || !face) return;
        var caption = face.caption || '';
        cardEl.innerHTML =
            '<img class="ff-share__card-img" src="' + FF_IMAGES_PATH + escHtml(face.filename) + '" alt="' + escHtml(face.title) + '">' +
            '<div class="ff-share__card-body">' +
                '<p class="ff-share__card-title">' + escHtml(face.title) + '</p>' +
                (caption ? '<p class="ff-share__card-caption">' + escHtml(caption) + '</p>' : '') +
                '<p class="ff-share__card-meta">' +
                    'Food Faces - an archive of edible portraits - ' +
                    'projects.tobyziegler.com/foodfaces' +
                '</p>' +
            '</div>';
    }

    // Today's card (left)
    var shareCardToday  = document.getElementById('js-share-card-today');

    // Random card (right)
    var shareCardRandom = document.getElementById('js-share-card-random');

    if (FF_TODAY)          renderShareCard(shareCardToday,  FF_TODAY);
    if (FF_RANDOM_CURRENT) renderShareCard(shareCardRandom, FF_RANDOM_CURRENT);


    // -- Canvas rendering (replaces html2canvas) --------------
    //
    // Draws the share card directly onto a canvas using the already-loaded
    // img element inside cardEl. No DOM cloning, no CORS issues, no scroll
    // offset problems. Layout: photo on top, white body with title/caption/meta below.

    var CARD_WIDTH   = 900;   // output canvas px
    var CARD_PADDING = 36;
    var BODY_BG      = '#FAF7F2';   // --white-soft
    var TEXT_DARK    = '#2C1F14';   // --text
    var TEXT_MUTED   = '#6B5744';   // --text-muted
    var FONT_DISPLAY = 'bold 28px Lora, Georgia, serif';
    var FONT_CAPTION = '22px "DM Sans", sans-serif';
    var FONT_META    = '18px "DM Sans", sans-serif';

    function buildCanvas(cardEl, face) {
        // Returns a Promise resolving to a canvas.
        return new Promise(function(resolve, reject) {
            var imgEl = cardEl.querySelector('img');
            if (!imgEl) return reject('no img in card');

            // Use the already-decoded image element directly - no fetch, no CORS.
            var photo = new Image();
            photo.crossOrigin = 'anonymous';

            photo.onload = function() {
                // Photo dimensions scaled to CARD_WIDTH
                var photoH = Math.round(CARD_WIDTH * photo.naturalHeight / photo.naturalWidth);

                // Measure text to calculate body height
                var cv0  = document.createElement('canvas');
                var ctx0 = cv0.getContext('2d');

                ctx0.font = FONT_DISPLAY;
                var titleLines  = wrapText(ctx0, face.title || '', CARD_WIDTH - CARD_PADDING * 2);

                ctx0.font = FONT_CAPTION;
                var captionLines = face.caption
                    ? wrapText(ctx0, face.caption, CARD_WIDTH - CARD_PADDING * 2)
                    : [];

                ctx0.font = FONT_META;
                var metaLines = wrapText(ctx0, 'Food Faces - projects.tobyziegler.com/foodfaces', CARD_WIDTH - CARD_PADDING * 2);

                var lineH      = 36;
                var sectionGap = 16;
                var bodyH      = CARD_PADDING
                    + titleLines.length   * lineH
                    + (captionLines.length ? sectionGap + captionLines.length * lineH : 0)
                    + sectionGap
                    + metaLines.length    * lineH
                    + CARD_PADDING;

                var totalH = photoH + bodyH;

                var cv  = document.createElement('canvas');
                cv.width  = CARD_WIDTH;
                cv.height = totalH;
                var ctx = cv.getContext('2d');

                // Photo
                ctx.drawImage(photo, 0, 0, CARD_WIDTH, photoH);

                // Body background
                ctx.fillStyle = BODY_BG;
                ctx.fillRect(0, photoH, CARD_WIDTH, bodyH);

                var y = photoH + CARD_PADDING;

                // Title
                ctx.fillStyle = TEXT_DARK;
                ctx.font      = FONT_DISPLAY;
                titleLines.forEach(function(line) {
                    ctx.fillText(line, CARD_PADDING, y);
                    y += lineH;
                });

                // Caption
                if (captionLines.length) {
                    y += sectionGap;
                    ctx.fillStyle = TEXT_MUTED;
                    ctx.font      = FONT_CAPTION;
                    captionLines.forEach(function(line) {
                        ctx.fillText(line, CARD_PADDING, y);
                        y += lineH;
                    });
                }

                // Meta
                y += sectionGap;
                ctx.fillStyle = TEXT_MUTED;
                ctx.font      = FONT_META;
                metaLines.forEach(function(line) {
                    ctx.fillText(line, CARD_PADDING, y);
                    y += lineH;
                });

                resolve(cv);
            };

            photo.onerror = function() {
                reject('image load failed');
            };

            // src must be set after onload/onerror are assigned
            photo.src = imgEl.src;

            // If the image is already decoded (cached), onload may not fire in some
            // browsers - nudge it by checking complete after assigning src.
            if (photo.complete && photo.naturalWidth) photo.onload();
        });
    }

    // Wraps text to fit maxWidth, returns array of lines.
    function wrapText(ctx, text, maxWidth) {
        var words = text.split(' ');
        var lines = [];
        var line  = '';
        for (var i = 0; i < words.length; i++) {
            var test = line ? line + ' ' + words[i] : words[i];
            if (ctx.measureText(test).width > maxWidth && line) {
                lines.push(line);
                line = words[i];
            } else {
                line = test;
            }
        }
        if (line) lines.push(line);
        return lines.length ? lines : [''];
    }

    function canvasToBlob(canvas) {
        return new Promise(function(resolve, reject) {
            canvas.toBlob(function(blob) {
                blob ? resolve(blob) : reject('toBlob failed');
            }, 'image/png');
        });
    }


    // -- Copy / Download shared logic -------------------------

    function wireButtons(copyBtnId, downloadBtnId, cardEl, getFace) {
        var copyBtn     = document.getElementById(copyBtnId);
        var downloadBtn = document.getElementById(downloadBtnId);

        if (copyBtn) {
            copyBtn.addEventListener('click', function() {
                // Disable immediately to prevent queued clicks during async work
                copyBtn.disabled    = true;
                copyBtn.textContent = 'Working...';

                var face = getFace();
                if (!face) {
                    copyBtn.disabled    = false;
                    copyBtn.textContent = 'Copy image';
                    return;
                }

                buildCanvas(cardEl, face).then(canvasToBlob).then(function(blob) {

                    if (!navigator.clipboard || !navigator.clipboard.write || typeof ClipboardItem === 'undefined') {
                        // Clipboard API unavailable - show fallback modal
                        return buildCanvas(cardEl, face).then(showCopyFallback);
                    }

                    var item = new ClipboardItem({ 'image/png': blob });
                    return navigator.clipboard.write([item]).then(function() {
                        copyBtn.textContent = 'Copied!';
                        setTimeout(function() { copyBtn.textContent = 'Copy image'; }, 2000);
                    }).catch(function(err) {
                        console.error('clipboard.write failed:', err);
                        return buildCanvas(cardEl, face).then(showCopyFallback);
                    });

                }).catch(function(err) {
                    console.error('canvas build failed:', err);
                    alert('Could not prepare image. Try Download instead.');
                }).then(function() {
                    copyBtn.disabled    = false;
                    if (copyBtn.textContent === 'Working...') {
                        copyBtn.textContent = 'Copy image';
                    }
                });
            });
        }

        if (downloadBtn) {
            downloadBtn.addEventListener('click', function() {
                downloadBtn.disabled    = true;
                downloadBtn.textContent = 'Working...';

                var face = getFace();
                if (!face) {
                    downloadBtn.disabled    = false;
                    downloadBtn.textContent = 'Download';
                    return;
                }

                buildCanvas(cardEl, face).then(function(canvas) {
                    var filename = 'foodface-' + escHtml(face.filename) + '.png';
                    var link     = document.createElement('a');
                    link.download = filename;
                    link.href     = canvas.toDataURL('image/png');
                    link.click();
                }).catch(function(err) {
                    console.error('canvas build failed:', err);
                    alert('Could not prepare image for download.');
                }).then(function() {
                    downloadBtn.disabled    = false;
                    downloadBtn.textContent = 'Download';
                });
            });
        }
    }


    // -- Copy fallback modal ----------------------------------
    // Shown when clipboard.write is unavailable or rejected.
    // Receives an already-built canvas - no second capture needed.

    function showCopyFallback(canvas) {
        var dataUrl = canvas.toDataURL('image/png');

        var overlay = document.createElement('div');
        overlay.className = 'ff-copy-overlay';
        overlay.innerHTML =
            '<div class="ff-copy-modal">' +
                '<p class="ff-copy-modal__hint">Your browser blocked direct copy.<br>' +
                'Long-press or right-click the image below, then choose <strong>Copy Image</strong>.</p>' +
                '<img class="ff-copy-modal__img" src="' + dataUrl + '" alt="Share card">' +
                '<button class="btn btn-secondary ff-copy-modal__close">Close</button>' +
            '</div>';

        document.body.appendChild(overlay);

        overlay.querySelector('.ff-copy-modal__close').addEventListener('click', function() {
            document.body.removeChild(overlay);
        });

        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) document.body.removeChild(overlay);
        });
    }

    // Wire today's card buttons
    wireButtons(
        'js-copy-today',
        'js-download-today',
        shareCardToday,
        function() { return FF_TODAY; }
    );

    // Wire random card buttons - getFace returns currentRandom, which updates with "Another one"
    wireButtons(
        'js-copy-random',
        'js-download-random',
        shareCardRandom,
        function() { return currentRandom; }
    );


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
