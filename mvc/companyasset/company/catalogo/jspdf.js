const jsPDF = window.jspdf.jsPDF
var generateData = function(data) {
    
    var result = [];
            
    for (let value of data) {
        
        result.push({      
            "Product": value.name,
            "SKU": value.sku,
            "Image":"http://localhost:8080/fullvendor/uploads/noimg.png?v=20230908183259477477",
        });
    }
    
    console.log(result) 
    return result;
};

function createHeaders(keys) {
    var result = [];
    for (var i = 0; i < keys.length; i += 1) {
        result.push({
            id: keys[i],
            name: keys[i],
            prompt: keys[i],
            width: 65,
            align: "center",
            padding: 0
        });
    }
    //console.log(result)
    return result;
}

fetch(options.url + '/catalog/download/' + 38,{method: 'GET',})
.then(response => response.json())
.then(data => {


    const doc = new jsPDF({ putOnlyUsedFonts: true, orientation: "portrait" });

    const data_ejemplo = [
        { name: "Item 1", description: "Description 1", image: "http://localhost:8080/fullvendor/uploads/noimg.png" },
        { name: "Item 2", description: "Description 2", image: "http://localhost:8080/fullvendor/uploads/noimg.png" },
        // Agrega más filas según sea necesario
    ];
      
      // Define las opciones de la tabla, incluyendo el estilo de las celdas
    const options = {
        margin: { top: 20 },
        bodyStyles: {
          image: { cellWidth: 30, cellHeight: 30 }, // Ajusta el tamaño de la celda de la imagen
        },
        columns: [
          { header: "Name", dataKey: "name" },
          { header: "Description", dataKey: "description" },
          { 
            header: "Image",dataKey: "image",
          }, // Columna para la imagen
        ],
        body: data_ejemplo.map(row => ({
          name: row.name,
          description: row.description,
          image: row.image, // Mantén la referencia a la ruta de la imagen aquí
        })),
        didDrawCell: async (ejemplo) => {
            if (ejemplo.section === 'body' && ejemplo.column.dataKey === 'image') {
                
                const imageUrl = ejemplo.row.raw.image;
                if (imageUrl && typeof imageUrl === 'string') {
                    ejemplo.cell.text = null;
                    doc.addImage(ejemplo.row.raw.image, 'JPEG', ejemplo.cell.x + 2, ejemplo.cell.y + 2, 10, 10);
                }
                //ejemplo.row.raw.image = ''
                //console.log(ejemplo)     
                
            }           
        },
        
    };

    //doc.autoTable(options);
    //doc.save("tabla_imagenes.pdf");
    return   

    const new_headers = createHeaders([
        "Product",
        "SKU",
        "Image"
    ]);
    
    const doc1 = new jsPDF({ putOnlyUsedFonts: true, orientation: "landscape" });
    //doc1.table(1, 1, generateData(data), new_headers, { autoSize: true });
    doc1.autoTable({        
        head: [['ID', 'Name', 'Email', 'Country', 'IP-address']],
        body: [
          ['1', 'Donna', 'dmoore0@furl.net', 'China', '211.56.242.221'],
          ['2', 'Janice', 'jhenry1@theatlantic.com', 'Ukraine', '<img src="http://localhost:8080/fullvendor/uploads/noimg.png?v=20230908183259477477">'],
          [
            '3',
            'Ruth',
            'rwells2@constantcontact.com',
            'Trinidad and Tobago',
            '19.162.133.184',
          ],
          ['4', 'Jason', 'jray3@psu.edu', 'Brazil', '10.68.11.42'],
          ['5', 'Jane', 'jstephens4@go.com', 'United States', '47.32.129.71'],
          ['6', 'Adam', 'anichols5@com.com', 'Canada', '18.186.38.37'],
        ],
    })

    // doc1.autoTable({
    //     head: [new_headers],
    //     body: generateData(data),
    //     startY: 10,
    //     showHead: 'firstPage', // Muestra el encabezado solo en la primera página
    // });        
    doc1.save()

})