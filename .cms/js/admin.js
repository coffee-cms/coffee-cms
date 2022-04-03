"use strict";

function get_cookie( name ) {
    let cookies = document.cookie.split( ";" );
    for ( let line of cookies ) {
        let cookie = line.split( "=" );
        if ( name == cookie[ 0 ].trim() ) {
            return decodeURIComponent( cookie[ 1 ] );
        }
    }
    return "";
}

function set_cookie( name, value ) {
    document.cookie = encodeURIComponent( name ) + "=" + encodeURIComponent( value ) + ";SameSite=Lax;Path=/";
}

// Notifications
function notify( message, classes, msec ) {
    let bulb = document.createElement( "div" );
    bulb.innerHTML = message;
    bulb.className = classes;
    document.querySelector( ".log-info-box" ).appendChild( bulb );
    let h = bulb.clientHeight;
    bulb.setAttribute( "style", `height:${h}px` );
    if ( msec )
    setTimeout( function() {
        bulb.classList.add( "timeout" );
    }, msec);
}

// Translate
// context: "admin.mod.php" or "pages.mod.php" etc
function __( str, context ) {
    if ( !cms ) return str;
    if ( !cms.locale ) return str;
    if ( !cms.lang ) return str;
    if ( !cms.lang[context] ) return str;
    if ( !cms.lang[context][cms.locale] ) return str;
    if ( !cms.lang[context][cms.locale][str] ) return str;
    return cms.lang[context][cms.locale][str];
}

// Call server side API
async function api2( data, rfn ) {
    const formData = new FormData();
    // push data to formData
    for ( let key in data ) {
        if ( ! data.hasOwnProperty( key ) ) continue;
        if ( Array.isArray( data[key] ) ) {
            for ( let key2 in data[key] ) {
                formData.append( key + "[]", data[key][key2] );
            }
        } else {
            formData.append( key, data[key] );
        }
    }
    // send data
    try {
        let response = await fetch( cms.api, {
            method: "POST",
            body: formData
        } );
        // get response and call callback function
        if ( response.ok ) { // HTTP-status: 200-299
            let rdata = await response.json();
            if ( rfn ) {
                rfn( rdata );
            }
        } else {
            alert( __( "Error HTTP: ", "admin.mod.php" ) + response.status );
        }
    } catch( error ) {
        notify( __( error, "admin.mod.php" ), "info-error", 10000 );
        throw error;
    }
}


document.addEventListener( "DOMContentLoaded", function( event ) {

    function _( str ) {
        return __( str, "admin.mod.php" );
    }

    // Mob Menu
    document.querySelectorAll( "header .burger, .milk" ).forEach( function( el ) {
        el.onclick = function() {
            document.body.classList.toggle( "mobile-menu-open" );
        }
    } );

    // Navigation
    document.querySelectorAll( "aside a" ).forEach( function( page ) {
        page.addEventListener( "click", function ( e ) {
            document.querySelectorAll( "aside a" ).forEach( function( page ) {
                page.classList.remove( "active" );
            } );
            this.classList.add( "active" );
            document.body.classList.remove( "mobile-menu-open" );
        } );
    } );
    
    // Theme switcher
    document.querySelectorAll( ".theme-switcher" ).forEach( function( el ) {
        el.addEventListener( "click", function( event ) {
            event.preventDefault();
            let n = get_cookie( "theme" );
            document.documentElement.classList.remove( admin_styles[n][0] );
            n = (+n+1) % admin_styles.length;
            document.documentElement.classList.add( admin_styles[n][0] );
            notify( admin_styles[n][0], "info-success", 5000 );
            set_cookie( "theme" , n );
            // switch theme in codemirror
            let theme_event = new Event( "theme" );
            document.documentElement.dispatchEvent( theme_event );
        } );
    } );

    // Initial set theme
    let n = get_cookie( "theme" );
    if ( n == undefined || isNaN( n ) || n === "" ) {
        n = 0; // dark
        set_cookie( "theme" , n );
    }
    let theme = admin_styles[n][0];
    document.documentElement.classList.add( theme );

    // Lang Selector
    if ( window.lang_selector ) {
        lang_selector.onchange = function( e ) {
            let search = window.location.search.replace( /&*locale=[^&]+/, "" );
            if ( search == "" ) { 
                search += "?locale=" + e.currentTarget.value;
            } else if ( search == "?" ) {
                search += "locale=" + e.currentTarget.value;
            } else {
                search += "&locale=" + e.currentTarget.value;
            }
            window.location.search = search;
        }
    }

    // Logout
    document.querySelectorAll( "[data-logout]" ).forEach( function ( logoutBtn ) {
        logoutBtn.addEventListener( "click", function() {
            api2( { fn: "logout" }, function() {
                window.location.reload( true );
            } );
            return false;
        });
    } );
    
    // Highlight active menu
    if ( document.body.classList.contains( "logged" ) ) {
        let page = window.location.hash;
        if ( page && page != "#start" ) {
            let el = document.querySelector( 'a[href="' + page + '"]' );
            if ( el ) {
                el.click();
            }
        } else {
            window.location.hash = "#start";
        }
    }

    // Login
    document.querySelectorAll( ".login-and-password .password div" ).forEach( function( loginBtn ) {
        loginBtn.addEventListener( "click", function() {
            let data = {
                fn:       "login",
                login:    document.querySelector( "input[name=login]" ).value,
                password: document.querySelector( "input[name=password]" ).value,
                url: window.location.pathname,
                href: window.location.href,
                locale: lang_selector.value
            }
            api2( data, function( r ) {

                if ( r.reload ) {
                    if ( window.location.pathname === r.reload ) {
                        window.location.reload( true );
                    } else {
                        window.location.pathname = r.reload; // after install
                    }
                }

                if ( r.info_text ) {
                    notify( r.info_text, r.info_class, r.info_time );
                }

            } );
        } );
        document.querySelector( "input[name=login]" ).addEventListener( "keyup", function( e ) {
            if ( e.key == "Enter" ) {
                document.querySelector( ".login-and-password .password div" ).click();
            }
        } );
        document.querySelector( "input[name=password]" ).addEventListener( "keyup", function( e ) {
            if ( e.key == "Enter" ) {
                document.querySelector( ".login-and-password .password div" ).click();
            }
        } );
    } );

    // Clear Cache
    document.querySelectorAll( ".clear-cache" ).forEach( function( el ) {
        el.addEventListener( "click", function( e ){
            e.preventDefault();
            api2( { fn: "clear_cache" }, function( r ){
                if ( r.info_text ) {
                    notify( r.info_text, r.info_class, r.info_time );
                }
            });
        });
    } );

    
    // Admin section, Save properties
    document.querySelectorAll( "[data-am-save]" ).forEach( function( saveButton ) {
        saveButton.addEventListener( "click", function( e ) {
            let el       = this.closest( "[data-am-item]" );
            let item     = el.getAttribute( "data-am-item" );
            let selector = `#admin_menu [data-am-item="${item}"]`;
            let title = document.querySelector( `${selector} [name=title]` );
            if ( title ) {
                title = title.value;
            }
            let section = document.querySelector( `${selector} [name=section]` );
            if ( section ) {
                section = section.value;
            }
            let data = {
                fn:      "admin_menu_save",
                type:    el.getAttribute( "data-am-type" ),
                module:  el.getAttribute( "data-am-module" ),
                item:    item,
                title:   title,
                sort:    document.querySelector( `${selector} [name=sort]` ).value,
                section: section,
                reset:   this.hasAttribute( "data-am-reset" ),
            }
            api2( data, function( r ) {
                if ( r.ok == "true" ) {
                    window.location.reload( true );
                }
            } );
        } );
    } );

    // Admin section, Delete Container
    document.querySelectorAll( "[data-am-delete]" ).forEach( function( button ) {
        button.addEventListener( "click", function( e ){
            let item = this.closest( "[data-am-item]" ).getAttribute( "data-am-item" );
            let childs = document.querySelectorAll( `[data-am-childs="${item}"] > div` ).length;
            if ( childs ) {
                notify( _( "Not Empty Container" ), "info-error", 2000 );
                return;
            }
            if ( ! confirm( _( "Delete?" ) ) ) return;
            let data = {
                fn: "admin_menu_del",
                item: item,
            }
            api2( data, function( r ) {
                if ( r.info_text ) {
                    notify( r.info_text, r.info_class, r.info_time );
                    if ( r.info_time )
                    setTimeout( function() {
                        window.location.reload( true );
                    }, r.info_time );
                }
            } );
        } );
    } );

    // Admin section, Hide
    document.querySelectorAll( "[data-am-sw]" ).forEach( function( button ) {
        button.addEventListener( "click", function( e ) {
            let el   = this.closest( "[data-am-item]" );
            let data = {
                fn:      "admin_menu_hide",
                type:    el.getAttribute( "data-am-type" ),
                module:  el.getAttribute( "data-am-module" ),
                item:    el.getAttribute( "data-am-item" ),
                hide:    el.classList.contains( "showed" ),
            }
            if ( data.item == "admin_menu" ) {
                if ( ! confirm( _( "Hide Admin settings?" ) ) ) return false;
            }
            api2( data, function( r ) {
                if ( r.ok == "true" ) {
                    window.location.reload( true );
                }
            } );
        } );
    } );

    // Admin section, Add Section
    document.querySelectorAll( "#admin_menu .main-footer .add-section" ).forEach( function( button ) {
        button.addEventListener( "click", function( e ) {
            api2( { fn: "admin_menu_add_section" }, function( r ) {
                if ( r.info_text ) {
                    notify( r.info_text, r.info_class, r.info_time );
                    if ( r.info_time )
                    setTimeout( function() {
                        window.location.reload( true );
                    }, r.info_time );
                }
            } );
        } );
    } );


    // Disable Modules
    document.querySelectorAll( "#modules .module-sw-btn" ).forEach( function( button ) {        
        button.addEventListener( "click", function( e ) {
            let closest = this.closest( "[data-module]" );
            let data = {
                fn: "module_disable",
                disable: closest.classList.contains( "enabled" ),
                module: closest.getAttribute( "data-module" ),
            }
            api2( data, function( r ) {
                if ( r.info_text ) {
                    notify( r.info_text, r.info_class, r.info_time );
                } else {
                    window.location.reload( true );
                }
            } );
        } );
    } );

    // Delete Module
    document.querySelectorAll( "#modules .module-del-btn" ).forEach( function( button ) {
        button.addEventListener( "click", function( e ) {
            let module = this.closest( "[data-module]" ).getAttribute( "data-module" );
            let data = {
                fn: "module_del",
                module:  module,
            }
            api2( data, function( r ) {
                if ( r.info_text ) {
                    notify( r.info_text, r.info_class, r.info_time );
                }
            } );
        } );
    } );

    // Close Sessions
    document.querySelectorAll( "[data-logged] [data-login]" ).forEach( function( button ) {
        button.addEventListener( "click", function( e ) {
            e.preventDefault();
            if ( !confirm( _( "Close this session?" ) ) ) return;
            var data = {
                fn: "logout",
                sess: this.getAttribute( "data-login" ),
            }
            api2( data, function( r ) {
                if ( r.info_text ) {
                    notify( r.info_text, r.info_class, r.info_time );
                    if ( r.result == "refresh" ) {
                        window.location.reload( true );
                    } else if ( r.result == "ok" ) {
                        document.querySelector( '[data-login="'+data.sess+'"]' ).closest( "[data-logged]" ).remove();
                    }
                }
            } );
        } );
    } );

    // Show/Hide password
    document.querySelectorAll( ".password-eye" ).forEach( function( eye ) {
        eye.addEventListener( "click", function( e ) {
            this.classList.toggle( "showed" );
            let inp = this.previousElementSibling;
            let t   = inp.getAttribute( "type" );
            if ( t == "password" ) {
                inp.setAttribute( "type", "text" );
            } else {
                inp.setAttribute( "type", "password" );
            }
        } );
    } );

} );
