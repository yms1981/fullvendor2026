function newpayment(catalog_id) {
    var w = 1200;
    var h = 800;
    var left = (screen.width / 2) - (w / 2);
    var top = (screen.height / 2) - (h / 2);


   var childWindow = window.open(options.url + '/catalog/nueva_ventana/' + catalog_id, 'NewWindow3', 'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=1, resizable=0, copyhistory=no, width=' + w + ', height=' + h + ', top=' + top + ', left=' + left);

        // Manejar evento cuando la ventana hijo se cierre
        childWindow.onbeforeunload = function() {
        // Refrescar la ventana padre
        location.reload();
    };
}


