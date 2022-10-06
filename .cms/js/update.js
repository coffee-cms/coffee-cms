"use strict";

document.addEventListener( "DOMContentLoaded", function( event ) {

    document.querySelectorAll( "[data-fn]" ).forEach( function( button ) {
        button.addEventListener( "click", function() {
            let fn = this.getAttribute( "data-fn" );
            let data = {
                fn: fn
            }
            api( data, function( r ) {
                if ( r.answer ) {
                    switch ( fn ) {
                        case "create_backup":
                            document.querySelectorAll( "#update .backup-window p" ).forEach( function( p ) { p.remove(); } );
                            document.querySelector( "#update .backup-window" ).insertAdjacentHTML( "beforeend", r.answer );
                            if ( r.ok === "true" ) { document.querySelector( "#update [data-fn='remove_backup']" ).classList.remove( "hidden" ); }
                            break;
                        case "remove_backup":
                            document.querySelectorAll( "#update .backup-window p" ).forEach( function( p ) { p.remove(); } );
                            document.querySelector( "#update .backup-window" ).insertAdjacentHTML( "beforeend", r.answer );
                            if ( r.ok === "true" ) { document.querySelector( "#update [data-fn='remove_backup']" ).classList.add( "hidden" ); }
                            break;
                        case "cms_update":
                            document.querySelector( "#update .update-window" ).insertAdjacentHTML( "beforeend", r.answer );
                            break;
                        case "create_zip":
                            document.querySelector( "#update .dev-window" ).insertAdjacentHTML( "beforeend", r.answer );
                            break;
                        case "cms_check_update":
                        case "cms_check_dev_update":
                            document.querySelector( "#update .check-answer" ).innerHTML = r.answer;
                            break;
                    }
                }
                if ( r.info_text ) {
                    notify( r.info_text, r.info_class, r.info_time );
                }
                if ( r.reload ) {
                    setTimeout( function() {
                        window.location.reload( true );
                    }, r.info_time );
                }
            } );
        } );
    } );

    // show update from dev buttons
    document.querySelector( "[data-show-dev]" ).addEventListener( "click", function() {
        let dev = document.querySelector( "#update .developers_only" );
        if ( dev ) {
            dev.classList.remove( "developers_only" );
            if ( window.location.host == "dev.coffee-cms.ru" ) {
                dev.querySelector( "[data-fn='create_zip']" ).removeAttribute( "style" );
            }
        }
        this.remove();
    } );

} );
