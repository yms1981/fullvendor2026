
const get_language = async () => {

    const response = await fetch(options.url + '/language',{
        method: 'GET'
    });

    const data_response = await response.json();
    options.lang = {...data_response}
}

function updateFileInputLabel(input) {
    // var label = document.getElementById('validatedCustomFile');
    const label = input.nextElementSibling

    if (input.files.length > 0) {
        label.innerText = input.files[0].name;
    }
}

