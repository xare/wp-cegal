import swal from 'sweetalert';

async function showAlert( actionType, buttonValue ) {
    const alertConfig = {
        icon: "warning",
        dangerMode: true,
        buttons: {
            cancel: "Cancelar",
            confirm: "¡Adelante!"
        }
    };

    if (actionType === 'delete') {
        alertConfig.text = "Ojo cuidau que se borra todo!";
    } else {
        alertConfig.text = `A continuación vas a a ${buttonValue}`;
    }

    const willProceed = await swal(alertConfig).then(willDelete => willDelete);
    return willProceed;
}

async function makeAjaxRequest( action, additionalData = {} ) {
    const formData = new FormData();
    formData.append( 'action', action );
    console.info(document.querySelector("#cegal_nonce"));
    formData.append( 'cegal_nonce', document.querySelector("#cegal_nonce").value );
    if ( document.querySelector("[name='isbn']").value != '' )
        formData.append('cegal_scan_product', document.querySelector("[name='isbn']").value)

    console.info( 'inside makeAjaxRequest' );
    for ( const [ key, value ] of Object.entries( additionalData ) ) {
        formData.append( key, value );
    }
    console.info( formData );
    const response = await fetch( ajaxurl, {
        method: "POST",
        credentials: "same-origin",
        body: formData
    });
    try {
        const jsonResponse = await response.json();
        console.info( jsonResponse );
        if ( jsonResponse.success ) {
            return jsonResponse;
        } else {
            console.error( "Request was not successful" );
            return null;
        }
    } catch ( error ) {
        console.error( "Error parsing JSON: ", error );
        console.error( "Raw response: ", await response.text() );
    }
}

async function updateProgress( action, cegalContainer ) {
    let offset = 0;
    const batchSize = 10; // Process 1 records at a time

    while (true) {
        const additionalData = {
            'offset': offset,
            'batch_size': batchSize
        };
        const response = await makeAjaxRequest(action, additionalData);
        console.info(response.data);
        const JsonData = typeof response.data === 'string' ? JSON.parse(response.data) :
                (response.data instanceof Object ? response.data : '');

        // Logging the type for debugging
        const dataType = typeof response.data === 'string' ? 'is string' :
                        (response.data instanceof Object ? 'is Object' : 'is neither');
        console.info(dataType);

        console.info(JsonData);
        if ( !response.success ) {
            console.error('Error');
            cegalContainer.innerHTML = 'Error!';
            break;
        }
        if ( JsonData.message ) {
            cegalContainer.innerHTML += `<div>${JsonData.message}</div>`;
        }
        cegalContainer.innerHTML += `<div>Batch processed.${JsonData[0].id} - ${JsonData.eans[0]} Current offset: ${offset} // - Progress ${JsonData.progress}</div>`;
        cegalContainer.scrollTop = cegalContainer.scrollHeight;
        if ( action == "cegal_scan_products" || action == "cegal_hello_world" ) {
            if ( !JsonData.hasMore || JsonData.hasMore == 0 ) {
                cegalContainer.innerHTML += `<div>All products processed.</div>`;
                break;
            }
            offset += batchSize;
        }
        if (action == 'cegal_scan_product') break;
    }
}

document.addEventListener("DOMContentLoaded", function() {
    const cegalContainer = document.querySelector("[data-container='cegal']");
    const terminalElement = document.querySelector(".terminal");

    if(terminalElement) terminalElement.style.display = "none";

    const actions = [
        { buttonName: 'scan_products', action: 'cegal_scan_products', type: '' },
        { buttonName: 'scan_product', action: 'cegal_scan_product', type: '' },
        { buttonName: 'hello_world', action: 'cegal_hello_world', type: '' },
        { buttonName: 'assign_to_product', action: 'cegal_assign_to_product', type: '' },
    ];
    console.info(actions);
    actions.forEach( async ({ buttonName, action, type }) => {
        const button = document.querySelector( `[name='${buttonName}']` );
        if (button) {
            button.addEventListener( "click" , async (event) => {
                event.preventDefault();
                console.info('clicked');
                alert('clicked');
                if(buttonName == 'scan_product') {
                    const response = await makeAjaxRequest(action, {'isbn': document.querySelector("[name='isbn']").value});
                    console.info(response.data);
                    return;
                }
                const buttonElement = document.querySelector( `[name='${buttonName}']` );
                const willProceed = await showAlert( type, buttonElement.value );

                if (willProceed) {
                    terminalElement.style.display = "block";
                    updateProgress( action, cegalContainer );
                }
            });
        }

    });
    // Select the table by its ID
    var table = document.querySelector('[data-wp-lists="list:cegal_lines"]');

    // Add click event listener to the table
    if(table){
        table.addEventListener('click', async function(e) {
            console.info(e.target);
            // Check if the clicked element is a button with the data-action attribute
            if (e.target && e.target.nodeName === 'BUTTON'
                && e.target.getAttribute('data-action') === 'assign-to-product') {
                e.preventDefault(); // Prevent the default button action

                var button = e.target; // The button that was clicked
                var isbn = button.getAttribute('data-isbn'); // Get the ISBN from the button's data-isbn attribute

                try {
                    const response = await fetch(ajaxurl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=assign_to_product&isbn=${encodeURIComponent(isbn)}`
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    const data = await response.json(); // Parse the JSON from the response

                    // Handle the response data
                    console.log(data);
                    button.innerText = 'Assigned';
                    button.disabled = true;
                } catch (error) {
                    console.error('There has been a problem with your fetch operation:', error);
                    button.disabled = false; // Optionally re-enable the button on error
                }
            }
        });
    }
});