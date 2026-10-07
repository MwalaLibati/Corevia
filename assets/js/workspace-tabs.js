(function () {
    'use strict';

    document.querySelectorAll('[data-workspace-tabs]').forEach(function (tabList) {
        var storageKey = 'corevia-tabs:' + window.location.pathname + ':' + (tabList.id || 'workspace');
        var triggers = Array.prototype.slice.call(tabList.querySelectorAll('[data-bs-toggle="tab"]'));

        if (!triggers.length || typeof bootstrap === 'undefined' || !bootstrap.Tab) {
            return;
        }

        triggers.forEach(function (trigger) {
            var target = trigger.getAttribute('data-bs-target') || '';
            trigger.setAttribute('aria-selected', trigger.classList.contains('active') ? 'true' : 'false');
            if (target.charAt(0) === '#') {
                trigger.setAttribute('aria-controls', target.substring(1));
            }
        });

        var requested = window.location.hash;
        if (!requested) {
            try {
                requested = window.sessionStorage.getItem(storageKey) || '';
            } catch (error) {
                requested = '';
            }
        }

        if (requested && /^#[A-Za-z][\w:.-]*$/.test(requested)) {
            var selected = triggers.find(function (trigger) {
                return trigger.getAttribute('data-bs-target') === requested;
            });
            if (selected) {
                bootstrap.Tab.getOrCreateInstance(selected).show();
            }
        }

        triggers.forEach(function (trigger) {
            trigger.addEventListener('shown.bs.tab', function (event) {
                var target = event.target.getAttribute('data-bs-target');
                if (!target) return;

                try {
                    window.sessionStorage.setItem(storageKey, target);
                } catch (error) {
                    // Session storage may be unavailable in privacy-restricted browsers.
                }

                if (window.history && window.history.replaceState) {
                    window.history.replaceState(null, '', window.location.pathname + window.location.search + target);
                }
            });
        });
    });
})();
