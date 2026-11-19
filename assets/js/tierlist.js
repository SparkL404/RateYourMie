/**
 * tierlist.js — Manajemen Daftar Tierlist (tierlists.php) & Drag-and-Drop / Layar Sentuh (tierlist.php)
 */
(function () {
    'use strict';

    var baseUrl = (document.body.getAttribute('data-base-url') || '').replace(/\/$/, '');
    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
    var csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

    /* ======================================================================
       BAGIAN 1: Halaman Manajemen Koleksi Tierlist (tierlists.php)
       ====================================================================== */
    var createForm = document.getElementById('js-create-tierlist-form');
    var feedbackContainer = document.getElementById('tierlists-feedback');

    function showListFeedback(type, msg) {
        if (!feedbackContainer) return;
        feedbackContainer.style.display = 'flex';
        feedbackContainer.innerHTML = '';
        var div = document.createElement('div');
        div.className = 'flash-item flash-' + (type === 'success' ? 'success' : 'error');
        div.textContent = msg;
        feedbackContainer.appendChild(div);
    }

    if (createForm) {
        createForm.addEventListener('submit', function (ev) {
            ev.preventDefault();
            var titleInput = document.getElementById('new-tierlist-title');
            var catInput = document.getElementById('new-tierlist-category');
            var btn = document.getElementById('btn-create-tierlist');

            var title = titleInput ? titleInput.value.trim() : '';
            var category = catInput ? catInput.value.trim() : '';

            if (!title) {
                showListFeedback('error', 'Judul tierlist wajib diisi.');
                return;
            }

            if (btn) btn.disabled = true;

            fetch(baseUrl + '/api/tierlist_create.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify({ title: title, category: category })
            })
                .then(function (r) { return r.json(); })
                .then(function (json) {
                    if (!json || !json.ok) {
                        showListFeedback('error', (json && json.error) ? json.error : 'Gagal membuat tierlist.');
                        if (btn) btn.disabled = false;
                        return;
                    }
                    window.location.href = json.data.url || (baseUrl + '/tierlist.php?id=' + json.data.tierlist_id);
                })
                .catch(function () {
                    showListFeedback('error', 'Gangguan jaringan saat membuat tierlist.');
                    if (btn) btn.disabled = false;
                });
        });
    }

    document.querySelectorAll('.js-rename-tierlist-form').forEach(function (renameForm) {
        renameForm.addEventListener('submit', function (ev) {
            ev.preventDefault();
            var tid = parseInt(renameForm.getAttribute('data-tierlist-id') || '0', 10);
            var input = renameForm.querySelector('input[name="title"]');
            var newTitle = input ? input.value.trim() : '';

            if (!newTitle) {
                showListFeedback('error', 'Judul tierlist tidak boleh kosong.');
                return;
            }

            fetch(baseUrl + '/api/tierlist_rename.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify({ tierlist_id: tid, title: newTitle })
            })
                .then(function (r) { return r.json(); })
                .then(function (json) {
                    if (!json || !json.ok) {
                        showListFeedback('error', (json && json.error) ? json.error : 'Gagal mengubah judul.');
                        return;
                    }
                    var card = document.querySelector('[data-tierlist-card="' + tid + '"]');
                    if (card) {
                        var titleLink = card.querySelector('.js-tierlist-title-link');
                        if (titleLink) titleLink.textContent = json.data.title;
                    }
                    showListFeedback('success', 'Judul tierlist berhasil diperbarui menjadi "' + json.data.title + '".');
                })
                .catch(function () {
                    showListFeedback('error', 'Gagal menghubungi server.');
                });
        });
    });

    document.querySelectorAll('[data-tierlist-card]').forEach(function (card) {
        var triggerBtn = card.querySelector('.js-delete-tierlist-trigger');
        var stepBox = card.querySelector('.js-delete-tierlist-step');
        var cancelBtn = card.querySelector('.js-delete-tierlist-cancel');
        var delBtn = card.querySelector('.js-delete-tierlist-btn');

        if (triggerBtn && stepBox) {
            triggerBtn.addEventListener('click', function () {
                triggerBtn.hidden = true;
                stepBox.hidden = false;
            });
        }

        if (cancelBtn && triggerBtn && stepBox) {
            cancelBtn.addEventListener('click', function () {
                stepBox.hidden = true;
                triggerBtn.hidden = false;
            });
        }

        if (delBtn) {
            delBtn.addEventListener('click', function () {
                var tid = parseInt(delBtn.getAttribute('data-tierlist-id') || '0', 10);
                delBtn.disabled = true;

                fetch(baseUrl + '/api/tierlist_delete.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfToken
                    },
                    body: JSON.stringify({ tierlist_id: tid })
                })
                    .then(function (r) { return r.json(); })
                    .then(function (json) {
                        if (!json || !json.ok) {
                            showListFeedback('error', (json && json.error) ? json.error : 'Gagal menghapus tierlist.');
                            delBtn.disabled = false;
                            return;
                        }
                        window.location.reload();
                    })
                    .catch(function () {
                        showListFeedback('error', 'Gagal menghubungi server untuk menghapus tierlist.');
                        delBtn.disabled = false;
                    });
            });
        }
    });

    /* ======================================================================
       BAGIAN 2: Penyusun Papan Tierlist Interaktif (tierlist.php)
       ====================================================================== */
    var tierboard = document.getElementById('js-tierboard');
    if (!tierboard) {
        return;
    }

    var tierlistId = parseInt(tierboard.getAttribute('data-tierlist-id') || '0', 10);
    var dropzones = Array.prototype.slice.call(document.querySelectorAll('.js-tier-dropzone'));
    var statusTop = document.getElementById('js-save-status');
    var statusBottom = document.getElementById('js-save-status-bottom');
    var saveBtnTop = document.getElementById('js-btn-save-tierlist');
    var saveBtnBottom = document.getElementById('js-btn-save-tierlist-bottom');
    var unrankedCountEl = document.getElementById('js-unranked-count');

    var draggedCard = null;
    var autoSaveTimer = null;

    var tierClasses = {
        S: 'tier-s',
        A: 'tier-a',
        B: 'tier-b',
        C: 'tier-c',
        D: 'tier-d',
        F: 'tier-f'
    };

    function setSaveStatus(state, text) {
        [statusTop, statusBottom].forEach(function (el) {
            if (!el) return;
            el.className = 'save-status-badge status-' + state;
            el.textContent = text;
        });
    }

    function updateCountersAndSelects() {
        dropzones.forEach(function (zone) {
            var zoneTier = zone.getAttribute('data-tier') || 'UNRANKED';
            var cards = zone.querySelectorAll('.tier-item-card');
            cards.forEach(function (card) {
                var select = card.querySelector('.js-tier-select');
                if (select && select.value !== zoneTier) {
                    select.value = zoneTier;
                }
                card.querySelectorAll('.js-tier-quick-btn').forEach(function (qBtn) {
                    var target = qBtn.getAttribute('data-target-tier') || 'UNRANKED';
                    var isActive = (target === zoneTier);
                    qBtn.classList.remove('is-active', 'tier-s', 'tier-a', 'tier-b', 'tier-c', 'tier-d', 'tier-f');
                    if (isActive) {
                        qBtn.classList.add('is-active');
                        if (tierClasses[target]) {
                            qBtn.classList.add(tierClasses[target]);
                        }
                    }
                });
            });
            if (zoneTier === 'UNRANKED' && unrankedCountEl) {
                unrankedCountEl.textContent = String(cards.length);
            }
        });
    }

    function collectTierlistPayload() {
        var items = [];
        var validTiers = ['S', 'A', 'B', 'C', 'D', 'F'];

        validTiers.forEach(function (tier) {
            var zone = document.querySelector('.js-tier-dropzone[data-tier="' + tier + '"]');
            if (!zone) return;
            var cards = zone.querySelectorAll('.tier-item-card');
            cards.forEach(function (card, index) {
                var nid = parseInt(card.getAttribute('data-noodle-id') || '0', 10);
                if (nid > 0) {
                    items.push({
                        noodle_id: nid,
                        tier: tier,
                        position: index
                    });
                }
            });
        });

        return {
            tierlist_id: tierlistId,
            items: items
        };
    }

    function saveTierlistNow() {
        if (autoSaveTimer) {
            window.clearTimeout(autoSaveTimer);
            autoSaveTimer = null;
        }

        setSaveStatus('dirty', 'Menyimpan...');
        var payload = collectTierlistPayload();

        fetch(baseUrl + '/api/tierlist_save.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify(payload)
        })
            .then(function (r) { return r.json(); })
            .then(function (json) {
                if (!json || !json.ok) {
                    setSaveStatus('error', (json && json.error) ? json.error : 'Gagal menyimpan, coba lagi');
                    return;
                }
                setSaveStatus('saved', 'Tersimpan (' + (json.data.saved_at || 'baru saja') + ')');
            })
            .catch(function () {
                setSaveStatus('error', 'Gagal menyimpan, coba lagi');
            });
    }

    function scheduleAutoSave() {
        setSaveStatus('dirty', 'Menyimpan perubahan...');
        if (autoSaveTimer) {
            window.clearTimeout(autoSaveTimer);
        }
        autoSaveTimer = window.setTimeout(saveTierlistNow, 450);
    }

    function moveCardToTier(card, targetTier) {
        var targetZone = document.querySelector('.js-tier-dropzone[data-tier="' + (targetTier || 'UNRANKED') + '"]');
        if (targetZone) {
            targetZone.appendChild(card);
            updateCountersAndSelects();
            scheduleAutoSave();
        }
    }

    document.querySelectorAll('.tier-item-card').forEach(function (card) {
        card.addEventListener('dragstart', function (ev) {
            draggedCard = card;
            card.classList.add('is-dragging');
            if (ev.dataTransfer) {
                ev.dataTransfer.effectAllowed = 'move';
                ev.dataTransfer.setData('text/plain', card.getAttribute('data-noodle-id') || '');
            }
        });

        card.addEventListener('dragend', function () {
            card.classList.remove('is-dragging');
            draggedCard = null;
            dropzones.forEach(function (z) {
                z.classList.remove('is-dragover');
            });
        });

        var selectEl = card.querySelector('.js-tier-select');
        if (selectEl) {
            selectEl.addEventListener('change', function () {
                moveCardToTier(card, selectEl.value || 'UNRANKED');
            });
        }

        card.querySelectorAll('.js-tier-quick-btn').forEach(function (qBtn) {
            qBtn.addEventListener('click', function () {
                moveCardToTier(card, qBtn.getAttribute('data-target-tier') || 'UNRANKED');
            });
        });
    });

    function getDragAfterElement(zone, x, y) {
        var draggableElements = Array.prototype.slice.call(
            zone.querySelectorAll('.tier-item-card:not(.is-dragging)')
        );

        var closest = null;
        var closestDistance = Number.POSITIVE_INFINITY;

        draggableElements.forEach(function (child) {
            var box = child.getBoundingClientRect();
            var centerX = box.left + box.width / 2;
            var centerY = box.top + box.height / 2;
            var dist = Math.hypot(x - centerX, y - centerY);
            if (dist < closestDistance && x < centerX) {
                closestDistance = dist;
                closest = child;
            }
        });

        return closest;
    }

    dropzones.forEach(function (zone) {
        zone.addEventListener('dragover', function (ev) {
            ev.preventDefault();
            if (ev.dataTransfer) {
                ev.dataTransfer.dropEffect = 'move';
            }
            zone.classList.add('is-dragover');
        });

        zone.addEventListener('dragleave', function () {
            zone.classList.remove('is-dragover');
        });

        zone.addEventListener('drop', function (ev) {
            ev.preventDefault();
            zone.classList.remove('is-dragover');
            if (!draggedCard) return;

            var afterElement = getDragAfterElement(zone, ev.clientX, ev.clientY);
            if (afterElement) {
                zone.insertBefore(draggedCard, afterElement);
            } else {
                zone.appendChild(draggedCard);
            }

            updateCountersAndSelects();
            scheduleAutoSave();
        });
    });

    if (saveBtnTop) {
        saveBtnTop.addEventListener('click', saveTierlistNow);
    }
    if (saveBtnBottom) {
        saveBtnBottom.addEventListener('click', saveTierlistNow);
    }
})();
