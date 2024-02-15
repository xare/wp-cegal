document.addEventListener("DOMContentLoaded", function() {
    document.querySelector( "[name='scan_product']" ).addEventListener( "click" , async (event) => {
        event.preventDefault();
        console.info('cegal scan product');
        const formData = new FormData();
        formData.append( 'action', 'cegal_scan_product' );
        formData.append( 'isbn', document.querySelector( '[name = "isbn"]' ).value );
        formData.append( 'cegal_nonce', document.querySelector("#cegal_nonce").value );
        const response = await fetch( ajaxurl, {
            method: "POST",
            credentials: "same-origin",
            body: formData,
        });
        try {
            const jsonResponse = await response.json();
            console.info( jsonResponse.data.message );
            document.querySelector( '[data-container="cegal_display_cover"]' ).innerHTML = jsonResponse.data.message;
        } catch ( error ) {
            console.error( "Error parsing JSON: ", error );
        }
    });
});