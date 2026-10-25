$(function () {
    const btnImport = document.getElementById("btnImport");
    const btnPickFile = document.getElementById("btnPickFile");
    const fileInput = document.getElementById('ifilepicker');
    const tableBody = document.querySelector('#csvTable tbody');
    const searchInput = document.getElementById('searchInput');
    const form = document.getElementById('frmcsvfile');

    // Función para obtener el separador seleccionado
    function getSelectedSeparator() {
        const sepValue = document.querySelector('input[name="fsep"]:checked').value;
        return sepValue === "0" ? "," : ";";
    }

    // Leer CSV y generar tabla
    fileInput.addEventListener('change', function (e) {
        const file = e.target.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = function (event) {
            const separator = getSelectedSeparator();
            const lines = event.target.result.split(/\r?\n/).filter(l => l.trim() !== "");

            tableBody.innerHTML = ""; // limpiar
            lines.forEach(line => {
                const cols = line.split(separator);
                cols.forEach(col => {
                    const row = document.createElement('tr');
                    const cell = document.createElement('td');
                    cell.contentEditable = true; // editable al click
                    cell.textContent = col.trim();
                    row.appendChild(cell);
                    tableBody.appendChild(row);
                });
            });
        };
        reader.readAsText(file, 'UTF-8');
    });

    // Buscador
    searchInput.addEventListener('input', function () {
        const filter = this.value.toLowerCase();
        document.querySelectorAll('#csvTable tbody tr').forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    });

    // Botón de importar
    btnImport.addEventListener('click', function () {
        const categories = [];
        document.querySelectorAll('#csvTable tbody td').forEach(cell => {
            categories.push(cell.textContent.trim());
        });

        if (categories.length === 0) {
            alert("No hay categorías para importar.");
            return;
        }

        // Enviar al back
        fetch(form.action, {
            method: form.method || 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({categories})
        })
            .then(async res => {
                const data = await res.json();
                if (!res.ok) throw data;
                return data;
            })
            .then(data => {
                swal({
                    title: data.message,
                    icon: 'success'
                }).then(() => location.reload());
            })
            .catch(err => {
                let title = err.message ?? 'Ocurrió un error';
                const errors = err.errors;

                const errorsMessages = Object.values(errors || {})
                    .reduce((acc, curr) => acc.concat(curr), [])
                    .join('\n') || null;

                swal({
                    title: title,
                    text: errorsMessages,
                    icon: 'error'
                });
            });
    });

    btnPickFile.addEventListener("click", function () {
        document.getElementById("ifilepicker").click();
    });
})
