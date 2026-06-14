(function () {
    'use strict';

    var DEFAULT_SIZE = 10;
    var PAGE_SIZES = [10, 25, 50, 100];

    function injectStyles() {
        if (document.getElementById('ent-table-pagination-styles')) return;
        var style = document.createElement('style');
        style.id = 'ent-table-pagination-styles';
        style.textContent = [
            '.ent-table-tools{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin:0 0 12px}',
            '.ent-table-info{color:#64748b;font-size:.78rem;font-weight:600}',
            '.ent-table-size{display:flex;align-items:center;gap:8px;color:#64748b;font-size:.78rem;font-weight:600}',
            '.ent-table-size select{width:auto;min-width:76px;padding:4px 28px 4px 8px;font-size:.78rem}',
            '.ent-table-pager{display:flex;align-items:center;justify-content:flex-end;gap:6px;flex-wrap:wrap;margin:12px 0 0}',
            '.ent-table-pager button{border:1px solid #cbd5e1;background:#fff;color:#334155;border-radius:6px;padding:5px 9px;font-size:.78rem;font-weight:700;line-height:1.2}',
            '.ent-table-pager button:hover:not(:disabled){background:#eff6ff;border-color:#93c5fd;color:#1d4ed8}',
            '.ent-table-pager button.is-active{background:#1d4ed8;border-color:#1d4ed8;color:#fff}',
            '.ent-table-pager button:disabled{opacity:.45;cursor:not-allowed}',
            '@media(max-width:640px){.ent-table-tools{align-items:flex-start}.ent-table-pager{justify-content:flex-start}.ent-table-pager button{padding:5px 8px}}'
        ].join('');
        document.head.appendChild(style);
    }

    function shouldPaginate(table) {
        if (!table || table.dataset.paginated === '1') return false;
        if (table.matches('[data-no-pagination], .no-pagination, .portal-payslip-table')) return false;
        if (table.closest('[data-no-pagination], .no-pagination, .table-no-pagination')) return false;
        if (table.closest('.contract-document, .payslip-paper, .email-template')) return false;
        var tbody = table.tBodies && table.tBodies[0];
        if (!tbody) return false;
        var rows = Array.prototype.slice.call(tbody.rows);
        if (rows.length === 0) return false;
        if (rows.length === 1 && rows[0].querySelector('[colspan]')) return false;
        return true;
    }

    function makeButton(label, title, disabled, active) {
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.textContent = label;
        btn.title = title || label;
        if (disabled) btn.disabled = true;
        if (active) btn.classList.add('is-active');
        return btn;
    }

    function pageList(current, total) {
        var pages = [];
        if (total <= 7) {
            for (var i = 1; i <= total; i++) pages.push(i);
            return pages;
        }
        pages.push(1);
        if (current > 4) pages.push('...');
        var start = Math.max(2, current - 1);
        var end = Math.min(total - 1, current + 1);
        for (var p = start; p <= end; p++) pages.push(p);
        if (current < total - 3) pages.push('...');
        pages.push(total);
        return pages;
    }

    function paginateTable(table) {
        var tbody = table.tBodies[0];
        var rows = Array.prototype.slice.call(tbody.rows);
        var state = { page: 1, size: DEFAULT_SIZE };
        var wrapper = table.parentElement || table;

        table.dataset.paginated = '1';

        var tools = document.createElement('div');
        tools.className = 'ent-table-tools';

        var info = document.createElement('div');
        info.className = 'ent-table-info';

        var sizeWrap = document.createElement('label');
        sizeWrap.className = 'ent-table-size';
        sizeWrap.appendChild(document.createTextNode('Rows per page'));
        var select = document.createElement('select');
        select.className = 'form-select form-select-sm';
        PAGE_SIZES.forEach(function (size) {
            var option = document.createElement('option');
            option.value = String(size);
            option.textContent = String(size);
            if (size === DEFAULT_SIZE) option.selected = true;
            select.appendChild(option);
        });
        sizeWrap.appendChild(select);

        tools.appendChild(info);
        tools.appendChild(sizeWrap);
        wrapper.parentNode.insertBefore(tools, wrapper);

        var pager = document.createElement('div');
        pager.className = 'ent-table-pager';
        wrapper.parentNode.insertBefore(pager, wrapper.nextSibling);

        function render() {
            var totalRows = rows.length;
            var totalPages = Math.max(1, Math.ceil(totalRows / state.size));
            if (state.page > totalPages) state.page = totalPages;

            var start = (state.page - 1) * state.size;
            var end = Math.min(start + state.size, totalRows);
            rows.forEach(function (row, index) {
                row.style.display = index >= start && index < end ? '' : 'none';
            });

            info.textContent = 'Showing ' + (start + 1) + '-' + end + ' of ' + totalRows + ' rows';
            pager.innerHTML = '';

            var prev = makeButton('Prev', 'Previous page', state.page === 1, false);
            prev.addEventListener('click', function () { state.page--; render(); });
            pager.appendChild(prev);

            pageList(state.page, totalPages).forEach(function (page) {
                if (page === '...') {
                    pager.appendChild(makeButton('...', 'More pages', true, false));
                    return;
                }
                var pageBtn = makeButton(String(page), 'Page ' + page, false, page === state.page);
                pageBtn.addEventListener('click', function () { state.page = page; render(); });
                pager.appendChild(pageBtn);
            });

            var next = makeButton('Next', 'Next page', state.page === totalPages, false);
            next.addEventListener('click', function () { state.page++; render(); });
            pager.appendChild(next);
        }

        select.addEventListener('change', function () {
            state.size = parseInt(select.value, 10) || DEFAULT_SIZE;
            state.page = 1;
            render();
        });

        render();
    }

    function init() {
        injectStyles();
        document.querySelectorAll('table').forEach(function (table) {
            if (shouldPaginate(table)) paginateTable(table);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
