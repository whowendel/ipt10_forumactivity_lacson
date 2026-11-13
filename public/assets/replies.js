

(function () {
    'use strict';

    var SVG_NS = 'http:

    function chevron() {
        var svg = document.createElementNS(SVG_NS, 'svg');
        svg.setAttribute('viewBox', '0 0 16 16');
        svg.setAttribute('width', '16');
        svg.setAttribute('height', '16');
        svg.setAttribute('aria-hidden', 'true');
        svg.setAttribute('focusable', 'false');
        var path = document.createElementNS(SVG_NS, 'path');
        path.setAttribute('d', 'M3 6l5 5 5-5');
        path.setAttribute('fill', 'none');
        path.setAttribute('stroke', 'currentColor');
        path.setAttribute('stroke-width', '2');
        path.setAttribute('stroke-linecap', 'round');
        path.setAttribute('stroke-linejoin', 'round');
        svg.appendChild(path);
        return svg;
    }

    document.querySelectorAll('ul.replies[id]').forEach(function (list) {
        var actions = list.parentElement.querySelector(':scope > article .comment-actions');
        if (!actions) {
            return;
        }

        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'toggle-replies';
        button.setAttribute('aria-controls', list.id);
        button.appendChild(chevron());

function setState(expanded) {
            var label = expanded ? 'Hide replies' : 'Show replies';
            list.hidden = !expanded;
            button.setAttribute('aria-expanded', expanded ? 'true' : 'false');
            button.setAttribute('aria-label', label);
            button.title = label;
        }

        button.addEventListener('click', function () {
            setState(button.getAttribute('aria-expanded') !== 'true');
        });

        setState(true);
        actions.insertBefore(button, actions.firstChild);
    });
}());
