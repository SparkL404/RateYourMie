/**
 * rating.js — Interaksi Form Rating 4 Aspek (noodle.php)
 */
(function () {
    'use strict';

    var form = document.getElementById('rating-form');
    if (!form) {
        return;
    }

    var baseUrl = (document.body.getAttribute('data-base-url') || '').replace(/\/$/, '');
    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
    var csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

    var liveReadout = document.getElementById('js-live-user-score');
    var feedbackBox = document.getElementById('rating-feedback-box');
    var saveBtn = document.getElementById('btn-save-rating');
    var deleteBtn = document.getElementById('btn-delete-rating');

    var aspects = ['taste', 'texture', 'portion', 'value'];

    function scoreToTierLabel(score) {
        var s = Math.round(score * 100) / 100;
        if (s >= 4.50) return 'S';
        if (s >= 4.00) return 'A';
        if (s >= 3.50) return 'B';
        if (s >= 3.00) return 'C';
        if (s >= 2.00) return 'D';
        return 'F';
    }

    function updateLiveCalculation() {
        var sum = 0;
        var count = 0;

        aspects.forEach(function (aspect) {
            var checked = form.querySelector('input[name="' + aspect + '"]:checked');
            var labelEl = document.getElementById('js-label-' + aspect);
            if (checked) {
                var val = parseInt(checked.value, 10);
                sum += val;
                count += 1;
                if (labelEl) {
                    labelEl.textContent = val + ' / 5';
                }
            }
        });

        if (count === 4 && liveReadout) {
            var avg = sum / 4;
            liveReadout.textContent = avg.toFixed(2) + ' (' + scoreToTierLabel(avg) + ')';
        }
    }

    function showFeedback(type, message) {
        if (!feedbackBox) return;
        feedbackBox.style.display = 'flex';
        feedbackBox.innerHTML = '';

        var item = document.createElement('div');
        item.className = 'flash-item flash-' + (type === 'success' ? 'success' : 'error');
        item.textContent = message;
        feedbackBox.appendChild(item);
    }

    form.addEventListener('change', updateLiveCalculation);

    form.addEventListener('submit', function (ev) {
        ev.preventDefault();

        var noodleId = parseInt(form.getAttribute('data-noodle-id') || '0', 10);
        var payload = {
            noodle_id: noodleId,
            review: (form.querySelector('[name="review"]') || {}).value || ''
        };

        for (var i = 0; i < aspects.length; i++) {
            var key = aspects[i];
            var checked = form.querySelector('input[name="' + key + '"]:checked');
            if (!checked) {
                showFeedback('error', 'Harap pilih skor 1 sampai 5 untuk seluruh aspek penilaian.');
                return;
            }
            payload[key] = parseInt(checked.value, 10);
        }

        if (saveBtn) {
            saveBtn.disabled = true;
        }

        fetch(baseUrl + '/api/rating_save.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify(payload)
        })
            .then(function (res) {
                return res.json();
            })
            .then(function (json) {
                if (!json || !json.ok) {
                    showFeedback('error', (json && json.error) ? json.error : 'Gagal menyimpan rating.');
                    if (saveBtn) saveBtn.disabled = false;
                    return;
                }

                showFeedback('success', json.data.message || 'Rating berhasil disimpan. Memuat ulang ulasan...');
                window.setTimeout(function () {
                    window.location.reload();
                }, 450);
            })
            .catch(function () {
                showFeedback('error', 'Gangguan jaringan saat mengirim rating. Coba lagi.');
                if (saveBtn) saveBtn.disabled = false;
            });
    });

    if (deleteBtn) {
        deleteBtn.addEventListener('click', function () {
            if (!window.confirm('Yakin ingin menghapus rating dan ulasan Anda pada mie ini?')) {
                return;
            }

            var noodleId = parseInt(form.getAttribute('data-noodle-id') || '0', 10);
            deleteBtn.disabled = true;

            fetch(baseUrl + '/api/rating_delete.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify({ noodle_id: noodleId })
            })
                .then(function (res) {
                    return res.json();
                })
                .then(function (json) {
                    if (!json || !json.ok) {
                        showFeedback('error', (json && json.error) ? json.error : 'Gagal menghapus rating.');
                        deleteBtn.disabled = false;
                        return;
                    }
                    showFeedback('success', json.data.message || 'Rating dihapus. Memuat ulang halaman...');
                    window.setTimeout(function () {
                        window.location.reload();
                    }, 400);
                })
                .catch(function () {
                    showFeedback('error', 'Gagal menghubungi server untuk menghapus rating.');
                    deleteBtn.disabled = false;
                });
        });
    }
})();
