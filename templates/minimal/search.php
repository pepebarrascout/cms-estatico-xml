<?php
/**
 * Search page template (client-side JSON search) — Minimal Theme
 * Variables:
 *   $categories - array of all category arrays
 *   $settings   - array: site settings
 */
if (!isset($settings))   { $settings   = []; }
if (!isset($categories)) { $categories = []; }

$siteName = $settings['site_name'] ?? 'Mi Sitio';

$pageTitle       = 'Buscar — ' . $siteName;
$pageDescription = 'Busca articulos en ' . $siteName;
$canonicalUrl    = '/buscar';

$themePartialsDir = CMS_ROOT . '/templates/' . ($settings['theme'] ?? 'default') . '/partials';

include $themePartialsDir . '/head.php';
?>

<body>
<?php include $themePartialsDir . '/header.php'; ?>

<main class="site-main" role="main">
    <div class="container">
        <div class="search-page">
            <h1 class="search-title">Buscar</h1>

            <div class="search-form">
                <label for="search-input" class="sr-only">Buscar</label>
                <input
                    type="search"
                    id="search-input"
                    class="search-input"
                    placeholder="Escribe para buscar..."
                    autocomplete="off"
                    spellcheck="false"
                    aria-label="Buscar articulos"
                >
            </div>

            <div id="search-status" class="search-status" aria-live="polite"></div>

            <div id="search-results" class="search-results" aria-label="Resultados de busqueda"></div>
        </div>
    </div>
</main>

<?php include $themePartialsDir . '/footer.php'; ?>

<script>
(function () {
    'use strict';

    var input       = document.getElementById('search-input');
    var resultsEl   = document.getElementById('search-results');
    var statusEl    = document.getElementById('search-status');
    var indexData   = null;
    var debounceTimer = null;
    var MAX_RESULTS  = 50;

    /* Pre-fill from URL ?q= parameter */
    var urlParams = new URLSearchParams(window.location.search);
    var initialQuery = urlParams.get('q') || '';
    if (initialQuery) {
        input.value = initialQuery;
    }

    /* Escape HTML */
    function esc(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    /* Truncate text */
    function truncate(text, maxLen) {
        if (text.length <= maxLen) return text;
        return text.substring(0, maxLen).replace(/\s+\S*$/, '') + '...';
    }

    /* Highlight matching terms */
    function highlight(text, query) {
        if (!query) return esc(text);
        var escaped = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        var regex = new RegExp('(' + escaped + ')', 'gi');
        var parts = text.split(regex);
        var out = '';
        for (var i = 0; i < parts.length; i++) {
            if (parts[i].toLowerCase() === query.toLowerCase()) {
                out += '<mark>' + esc(parts[i]) + '</mark>';
            } else {
                out += esc(parts[i]);
            }
        }
        return out;
    }

    /* Render results */
    function renderResults(results, query) {
        if (!results.length) {
            if (query) {
                resultsEl.innerHTML = '<p class="search-empty">No se encontraron resultados para <strong>' + esc(query) + '</strong>.</p>';
            } else {
                resultsEl.innerHTML = '<p class="search-empty">Escribe algo para comenzar la busqueda.</p>';
            }
            return;
        }

        var html = '<p class="search-count">' + results.length + ' resultado' + (results.length === 1 ? '' : 's') + '</p>';
        html += '<div class="search-list">';

        for (var i = 0; i < results.length; i++) {
            var item = results[i];
            var slug = item.slug || '';
            var title = item.title || 'Sin titulo';
            var excerpt = item.excerpt || '';

            html += '<article class="search-item">';
            html += '<h2 class="search-item-title"><a href="/' + esc(slug) + '">' + highlight(title, query) + '</a></h2>';
            if (excerpt) {
                html += '<p class="search-item-excerpt">' + highlight(truncate(excerpt, 200), query) + '</p>';
            }
            html += '</article>';
        }

        html += '</div>';
        resultsEl.innerHTML = html;
    }

    /* Perform search */
    function doSearch(query) {
        query = (query || '').trim().toLowerCase();

        if (!indexData) return;

        if (!query) {
            statusEl.textContent = '';
            renderResults([], '');
            return;
        }

        var results = [];
        for (var i = 0; i < indexData.length; i++) {
            var item = indexData[i];
            var titleMatch = (item.title || '').toLowerCase().indexOf(query) !== -1;
            var excerptMatch = (item.excerpt || '').toLowerCase().indexOf(query) !== -1;

            if (titleMatch || excerptMatch) {
                results.push(item);
                if (results.length >= MAX_RESULTS) break;
            }
        }

        statusEl.textContent = '';
        renderResults(results, query);
    }

    /* Fetch search index */
    function fetchIndex(callback) {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', '/data/index/search-index.json', true);
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    try {
                        indexData = JSON.parse(xhr.responseText);
                        callback(null);
                    } catch (e) {
                        callback('Error al analizar el indice de busqueda.');
                    }
                } else {
                    callback('No se pudo cargar el indice de busqueda (HTTP ' + xhr.status + ').');
                }
            }
        };
        xhr.send();
    }

    /* Init */
    fetchIndex(function (err) {
        if (err) {
            resultsEl.innerHTML = '<p class="search-error">' + esc(err) + '</p>';
            return;
        }
        if (initialQuery.trim()) {
            doSearch(input.value);
        }
    });

    /* Debounced input listener (300ms) */
    input.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function () {
            doSearch(input.value);
        }, 300);
    });

    /* Update URL on Enter */
    input.addEventListener('keyup', function (e) {
        if (e.key === 'Enter') {
            var q = input.value.trim();
            if (q) {
                history.replaceState(null, '', '/buscar?q=' + encodeURIComponent(q));
            } else {
                history.replaceState(null, '', '/buscar');
            }
        }
    });
})();
</script>
</body>
</html>
