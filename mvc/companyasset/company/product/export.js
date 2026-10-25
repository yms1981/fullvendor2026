function export_product() {
    let productExportForm = d.getElementById("form-exportar");
    const formdata_filter = new FormData(d.getElementById("form-buscar-filtro"))
    const formdata = new FormData(productExportForm);

    // union of two form data
    for (const [key, value] of formdata_filter.entries()) {
        formdata.append(key, value);
    }

    let products = JSON.stringify($("input.checkBoxClass[type=checkbox]:checked").map(function () {return $(this).val();}).get());
    formdata.append("products",products);
    let type = formdata.get('type_file');
    const today = new Date();
    const dateStamp = String(today.getFullYear())
        + String(today.getMonth() + 1).padStart(2, '0')
        + String(today.getDate()).padStart(2, '0');
    let downloadFileName = 'product_list' + dateStamp;
    downloadFileName += (type === 'csv' || type === 'pdf') ? '.' + type : '.xlsx';
    fetch('products/export/', {
        method: 'POST',
        body: formdata
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('export_failed');
            }
            const contentDisposition = response.headers.get('Content-Disposition');
            if (contentDisposition) {
                // Extract filename (if present)
                const filenameMatch = contentDisposition.match(/filename="?(.+)"?/);
                const filename = filenameMatch ? filenameMatch[1] : null;

                console.log('Content-Disposition:', contentDisposition,filenameMatch);
                if (filename) {
                    downloadFileName = filename;
                }
            } else {
                console.log('Content-Disposition header not found.');
            }
            return response.blob();
        })
        .then(blob => {
            console.log(downloadFileName);
            var file = window.URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = file;
            a.download = downloadFileName;
            document.body.appendChild(a); // we need to append the element to the dom -> otherwise it will not work in firefox
            a.click();
            a.remove();
        })
        .catch(function () {
            console.error('Product export failed');
        });
}
