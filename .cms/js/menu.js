"use strict";
document.addEventListener( "DOMContentLoaded", function( event ) {

    function _( str ) {
        return __( str, "menu.mod.php" );
    }

    function select3( selects ) {
        selects.forEach( function( select ) {
            select.querySelector( "input" ).addEventListener( "keyup", function( e ) {
                if ( this.value.length > 1 ) {
                    api2( { fn: "get_search_pages_list", search: this.value }, function( r ) {
                        if ( r.html ) {
                            select.querySelector( ".list-search" ).innerHTML = r.html;
                            select.querySelectorAll( ".list-search li" ).forEach( function( li ) {
                                li.addEventListener( "click", select3_li );
                            } );
                        }
                    } );
                }
            } );
            // show/hide dropdown list
            select.querySelector( ".field-select" ).addEventListener( "click", function ( event ) {
                event.currentTarget.nextElementSibling.classList.toggle( "open" );
                select.querySelector( "input" ).focus();
            } );
            // click on <li>
            select.querySelector( "li" ).addEventListener( "click", select3_li );
        } );
    }

    function select3_li( e ) {
        let id    = this.getAttribute( "data-id" );
        let url   = this.getAttribute( "data-url" );
        let title = this.innerText;
        let input_title = this.closest( ".select-grid" ).querySelector( ".field-select" );
        input_title.innerText = title;
        input_title.setAttribute( "data-id", id );
        let input_url = this.closest( ".menu-prop" ).querySelector( "input[name='url']" );
        input_url.value = url;
        if ( id === "0" ) {
            input_url.removeAttribute( "disabled" );
        } else {
            input_url.setAttribute( "disabled", true );
        }
        // remove li except data-id=0
        Array.from( this.parentElement.children ).forEach( function( li ) {
            if ( li.getAttribute( "data-id" ) !== "0" ) {
                li.remove();
            }
        } );
        // close dropdown list
        input_title.click();
    }

    api2( { fn: "get_menu_items" }, set_menu_items );

    function set_menu_items( r ) {

        document.querySelector( "#menu .menu-grid" ).innerHTML = r.list;

        // set parents for each menu item
        document.querySelectorAll( "#menu [data-parent]" ).forEach( function( el ) {
            let parent = el.getAttribute( "data-parent" );
            let self   = el.closest( "[data-item]" ).getAttribute( "data-item" );
            el.innerHTML = el.innerHTML + r.parents;
            // remove self
            self = el.querySelector( `[value="${self}"]` );
            if ( self ) {
                self.remove();
            }
            el.value = parent;
        } );

        // selects
        let selects = document.querySelectorAll( "#menu .select-grid" );
        select3( selects );

        // Toggle Menu Properties
        document.querySelectorAll( "#menu .menu-buttons .prop" ).forEach( function ( button ) {
            button.addEventListener( "click", function( e ) {
                this.closest( "[data-item]" ).classList.toggle( "open" );
            } );
        } );

        // Save Properties
        document.querySelectorAll( "#menu .menu-buttons .save" ).forEach( function( button ) {
            button.addEventListener( "click", function( e ) {

                let item = this.closest( "[data-item]" );
                let mid  = item.getAttribute( "data-item" );

                let area = item.querySelector( "[name='area']" );
                if ( area ) {
                    area = area.value;
                }

                let tag_title = item.querySelector( "[name='tag_title']" );
                if ( tag_title ) {
                    tag_title = tag_title.value;
                }

                let url = item.querySelector( "[name='url']" );
                if ( url ) {
                    url = url.value;
                }

                let id = item.querySelector( "[name='id']" );
                if ( id ) {
                    id = id.getAttribute( "data-id" );
                }

                let pid = item.querySelector( "[name='pid']" );
                if ( pid ) {
                    pid = pid.value;
                }

                let target = item.querySelector( "[name='targetblank']" );
                if ( target ) {
                    target = target.checked;
                }

                let data = {
                    fn:        "save_menu_item",
                    mid:       mid,
                    title:     item.querySelector( "[name='title']" ).value,
                    tag_title: tag_title,
                    url:       url,
                    id:        id,
                    pid:       pid,
                    classes:   item.querySelector( "[name='classes']" ).value,
                    sort:      item.querySelector( "[name='sort']" ).value,
                    area:      area,
                    target:    target
                }
                api2( data, function( r ) {
                    if ( r.ok == "false" ) {
                        notify( r.info_text, r.info_class, r.info_time );
                    }
                    if ( r.ok == "true" ) {
                        if ( r.list ) {
                            set_menu_items( r );
                        }
                        // Last Edited Marker
                        setTimeout( function() {
                            document.querySelector( `#menu [data-item="${data.mid}"]` ).classList.add( "last-edited" );
                        }, 200 );
                    }
                } );
            } );
        } );

        // Delete Menu or Item
        document.querySelectorAll( "#menu .menu-buttons .del" ).forEach( function( button ) {
            button.addEventListener( "click", function( e ) {
                if ( ! confirm( __( "Delete?", "menu.mod.php" ) ) ) return;
                let data = {
                    fn: "del_menu_item",
                    mid: this.closest( "[data-item]" ).getAttribute( "data-item" )
                };
                api2( data, function( r ) {
                    if ( r.info_text ) {
                        notify( r.info_text, r.info_class, r.info_time );
                        if ( r.info_class == "info-success" ) {
                            set_menu_items( r );
                        }
                    }
                } );
            } );
        } );

        // Create Item
        document.querySelectorAll( "#menu .main-main .create" ).forEach( function( button ) {
            button.addEventListener( "click", modMenuCreate );
        } );

    }

    function modMenuCreate( e ) {
        let pid = this.closest( "[data-item]" ).getAttribute( "data-item" );
        api2( { fn : "create_menu_item", pid : pid }, function( r ) {
            if ( r.info_text ) {
                notify( r.info_text, r.info_class, r.info_time );
                if ( r.info_class == "info-success" ) {
                    set_menu_items( r );
                }
            }
        } );
    }
    
    // Create Menu
    document.querySelector( "#menu .main-footer .create" ).addEventListener( "click", modMenuCreate );
    
    // update page used in menu
    document.body.addEventListener( "update_menu", function( e ) {
        api2( { fn: "get_menu_items" }, set_menu_items );
    } );

} );
