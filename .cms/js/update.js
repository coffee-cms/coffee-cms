"use strict";

document.addEventListener( "DOMContentLoaded", function( event ) {

    let updateBtns = document.querySelectorAll( "[data-fn]" );
    updateBtns.forEach( function( button ) {
        button.addEventListener( "click", function() {
            let fn = this.getAttribute( "data-fn" );
            let data = {
                fn: fn
            }
            api2( data, function( r ) {
                if ( r.answer != null ) {
                    if ( fn === "create_backup" || fn === "remove_backup" ) {
                        document.querySelectorAll( "#update .backup-window p" ).forEach( function( p ) {
                            p.remove();
                        } );
                        document.querySelector( "#update .backup-window" ).insertAdjacentHTML( "beforeend", r.answer );
                        if ( fn === "create_backup" ) {
                            document.querySelector( "#update [data-fn='remove_backup']" ).classList.remove( "hidden" );
                        } else {
                            document.querySelector( "#update [data-fn='remove_backup']" ).classList.add( "hidden" );
                        }
                    } else if ( fn === "cms_update" || fn === "cms_dev_update" ) {
                        document.querySelector( "#update .update-window" ).insertAdjacentHTML( "beforeend", r.answer );
                    } else if ( fn === "create_filelist" || fn === "create_zip" ) {
                        document.querySelector( "#update .dev-window" ).insertAdjacentHTML( "beforeend", r.answer );
                    } else {
                        document.querySelector( `#update [data-${data.fn}-answer]` ).innerHTML = r.answer;
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
        }
        this.remove();
    } );

} );
