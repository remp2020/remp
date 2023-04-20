// https://blog.nette.org/cs/zavisle-selectboxy-elegantne-v-nette-a-cistem-javascriptu

window.addEventListener("DOMContentLoaded", (event) => {
    function updateSelectbox(select, items) {
        let promptOption;
        if (select.firstChild && select.firstChild.value === '') {
            promptOption = select.firstChild.cloneNode(true);
        }

        select.innerHTML = '';
        if (promptOption) {
            select.appendChild(promptOption);
        }

        for (let id in items) {
            let el = document.createElement('option');
            el.setAttribute('value', id);
            el.innerText = items[id];
            select.appendChild(el);
        }
        select.disabled = items.length === 0;

        $(select).selectpicker('refresh');
    }

    document.querySelectorAll('select[data-depends]').forEach((childSelect) => {
        let parentSelect = childSelect.form[childSelect.dataset.depends];
        let url = childSelect.dataset.url;
        if (url.indexOf(encodeURIComponent('%value%')) < 0) {
            console.warn("Missing '%value%' placeholder in 'data-url' Nette attribute of the select-box '" + childSelect.name + "'");
            return;
        }

        let items = JSON.parse(childSelect.dataset.items || 'null');

        parentSelect.addEventListener('change', () => {
            if (items) {
                updateSelectbox(childSelect, items[parentSelect.value]);
            }

            if (url) {
                fetch(url.replace(encodeURIComponent('%value%'), encodeURIComponent(parentSelect.value)))
                    .then((response) => response.json())
                    .then((data) => {
                        updateSelectbox(childSelect, data);
                        if (childSelect.dataset.mirrorTo) {
                            updateSelectbox(childSelect.form[childSelect.dataset.mirrorTo], data);
                        }
                    });
            }
        });
    });
});