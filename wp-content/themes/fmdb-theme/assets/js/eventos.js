(function () {
    const calRoot = document.getElementById('fmdb-calendar');
    const events  = (window.fmdbEvents || []).map(e => ({
        ...e,
        start: new Date(e.start),
        end:   new Date(e.end),
    }));

    const monthNames = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
    const dayNames   = ['Lun','Mar','Mié','Jue','Vie','Sáb','Dom'];

    let current = new Date();
    current.setDate(1);
    current.setHours(0,0,0,0);

    function isSameDay(a, b) {
        return a.getFullYear() === b.getFullYear()
            && a.getMonth()    === b.getMonth()
            && a.getDate()     === b.getDate();
    }

    function dayOverlapsEvent(day, evt) {
        const dayStart = new Date(day); dayStart.setHours(0,0,0,0);
        const dayEnd   = new Date(day); dayEnd.setHours(23,59,59,999);
        return evt.start <= dayEnd && evt.end >= dayStart;
    }

    function escapeHtml(s) {
        const div = document.createElement('div');
        div.textContent = s;
        return div.innerHTML;
    }

    function render() {
        if (!calRoot) return;
        const year  = current.getFullYear();
        const month = current.getMonth();
        const firstDay     = new Date(year, month, 1);
        const lastDay      = new Date(year, month + 1, 0);
        const startCol     = (firstDay.getDay() + 6) % 7; // Mon = 0
        const daysInMonth  = lastDay.getDate();
        const today        = new Date();

        let html = '';
        html += '<div class="fmdb-cal__nav">';
        html += '  <button type="button" class="fmdb-cal__btn" data-prev aria-label="Mes anterior">‹</button>';
        html += '  <h2 class="fmdb-cal__title">' + monthNames[month] + ' ' + year + '</h2>';
        html += '  <button type="button" class="fmdb-cal__btn" data-next aria-label="Mes siguiente">›</button>';
        html += '</div>';

        html += '<div class="fmdb-cal__grid">';
        dayNames.forEach(d => html += '<div class="fmdb-cal__weekday">' + d + '</div>');

        for (let i = 0; i < startCol; i++) {
            html += '<div class="fmdb-cal__cell fmdb-cal__cell--empty"></div>';
        }

        for (let day = 1; day <= daysInMonth; day++) {
            const cellDate    = new Date(year, month, day);
            const todayClass  = isSameDay(cellDate, today) ? ' is-today' : '';
            const cellEvents  = events.filter(e => dayOverlapsEvent(cellDate, e));

            html += '<div class="fmdb-cal__cell' + todayClass + '">';
            html += '<span class="fmdb-cal__day">' + day + '</span>';
            cellEvents.forEach(e => {
                const cls = e.category ? 'fmdb-cal__event fmdb-cat--' + e.category : 'fmdb-cal__event fmdb-cat--miscelaneo';
                html += '<a href="' + e.url + '" class="' + cls + '" title="' + escapeHtml(e.title).replace(/"/g, '&quot;') + '">'
                     +  escapeHtml(e.title)
                     +  '</a>';
            });
            html += '</div>';
        }
        html += '</div>';

        // Legend
        html += '<div class="fmdb-cal__legend">';
        html += '  <span class="fmdb-cal__legend-item"><i class="fmdb-cat--torneo"></i>Torneo</span>';
        html += '  <span class="fmdb-cal__legend-item"><i class="fmdb-cat--liga"></i>Liga</span>';
        html += '  <span class="fmdb-cal__legend-item"><i class="fmdb-cat--campamento"></i>Campamento</span>';
        html += '  <span class="fmdb-cal__legend-item"><i class="fmdb-cat--entrenamiento"></i>Entrenamiento</span>';
        html += '  <span class="fmdb-cal__legend-item"><i class="fmdb-cat--anuncio"></i>Anuncio</span>';
        html += '  <span class="fmdb-cal__legend-item"><i class="fmdb-cat--miscelaneo"></i>Misceláneo</span>';
        html += '</div>';

        calRoot.innerHTML = html;
        calRoot.querySelector('[data-prev]').addEventListener('click', () => {
            current.setMonth(current.getMonth() - 1);
            render();
        });
        calRoot.querySelector('[data-next]').addEventListener('click', () => {
            current.setMonth(current.getMonth() + 1);
            render();
        });
    }

    // View toggle
    document.querySelectorAll('[data-view-toggle]').forEach(btn => {
        btn.addEventListener('click', () => {
            const view = btn.dataset.viewToggle;
            document.querySelectorAll('[data-view-toggle]').forEach(b => b.classList.remove('is-active'));
            btn.classList.add('is-active');
            const list = document.getElementById('fmdb-eventos-list');
            const cal  = document.getElementById('fmdb-calendar');
            if (list) list.style.display = view === 'list'     ? '' : 'none';
            if (cal)  cal.style.display  = view === 'calendar' ? '' : 'none';
            if (view === 'calendar' && cal && !cal.dataset.rendered) {
                render();
                cal.dataset.rendered = '1';
            }
        });
    });
})();
