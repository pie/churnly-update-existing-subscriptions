jQuery( document ).ready( function( $ ) {
    // Add import button at top of page
    $( '<span class="spinner"></span><a href="#" class="page-title-action" id="update-churnly-events">' + churnly_fix_data.import_button_text + '</a>' ).insertBefore( '.wp-header-end' );

    // Run AJAX to add events for existing subscriptions
    $( '.wrap' ).on( 'click', '#update-churnly-events', function() {
        $( this ).attr( 'disabled', 'disabled' ).text( churnly_fix_data.updating_text ).prev( '.spinner' ).css( { 'visibility': 'visible', 'float': 'none', 'margin': '-5px 10px 5px' } );
        getSubscriptions();
    } );
    // Loop to update Churnly events avoiding server timeouts
    function updateChurnly( subscriptions ) {
        var subs_total = subscriptions.length;
        var current_total = 0;

        // Run ajax request
        function runRequest() {
            // Check to make sure there are more subscriptions to update
            if( subscriptions.length > 0 ) {

                var current = subscriptions.splice( 0, 50 );

                // Make the AJAX request with the given subscriptions
                var data = {
                    action: 'update_churnly_events',
                    subscriptions: current
                };
                $.post( ajaxurl, data, function( response ) {
                    current_total = parseInt( current.length ) + parseInt( current_total );
                    if( current_total == subs_total ) {
                        window.location.reload();
                    }
                } ).done( function() {
                    runRequest();
                } );
            }
        }

        runRequest();
    }

    function getSubscriptions() {
        var data = {
            action: 'get_subscriptions'
        };
        $.post( ajaxurl, data, function( response ) {
            var subscriptions = response.data;
            if( subscriptions.length > 0 ) {
                updateChurnly( subscriptions );
            } else {
                console.log( 'no subscriptions found:', subscriptions );
            }
        } );
    }
} );
