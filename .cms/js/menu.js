document.addEventListener( "DOMContentLoaded", function( event ) {

    function _( str ) {
        return __( str, "menu.mod.php" );
    }

    function select3( selects ) {
        selects.forEach( function( select ) {
            select.querySelector( "input" ).addEventListener( "keyup", function( e ) {
                api( { fn: "get_search_pages_list", search: this.value }, function( r ) {
                    if ( r.html ) {
                        select.querySelector( ".list-search" ).innerHTML = r.html;
                        select.querySelectorAll( ".list-search li" ).forEach( function( li ) {
                            li.addEventListener( "click", select3_li );
                        } );
                    }
                } );
            } );
            // prevent close dropdown when click on input
            select.querySelector( "input" ).addEventListener( "click", function( e ) {
                e.stopPropagation();
            } );
            // show/hide dropdown list
            select.querySelector( ".field-select" ).addEventListener( "click", function ( event ) {
                event.stopPropagation();
                let list = event.currentTarget.nextElementSibling;
                list.classList.toggle( "open" );
                if ( list.classList.contains( "open" ) ) {
                    let input = select.querySelector( "input" );
                    input.focus();
                    input.dispatchEvent( new Event( "keyup" ) );
                }
            } );
        } );
    }

    // click selected item
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

    api( { fn: "get_menu_items" }, set_menu_items );

    function set_menu_items( r ) {

        document.querySelector( "#menu .menu-grid" ).innerHTML = r.list;

        // set parents for each menu item
        document.querySelectorAll( "#menu [data-parent]" ).forEach( function( el ) {
            el.nextElementSibling.insertAdjacentHTML( "beforeend", r.parents );
            let pid = el.getAttribute( "data-parent" );
            let parent = el.nextElementSibling.querySelector( `[value="${pid}"]` ).innerText.trim();
            el.innerText = parent;
            // remove self
            let self   = el.closest( "[data-item]" ).getAttribute( "data-item" );
            self = el.nextElementSibling.querySelector( `[value="${self}"]` );
            if ( self ) {
                self.remove();
            }
        } );

        // selects
        let selects = document.querySelectorAll( "#menu .select-grid" );
        select3( selects );

        // Select
        document.querySelectorAll( "#menu .area-select-grid .field-select-menu, #menu .parent-select-grid .field-select" ).forEach( function( select ) {
            select.addEventListener( "click", function( e ) {
                e.stopPropagation();
                select.nextElementSibling.classList.toggle( "open" );
            } );
        } );
        // Option for menu
        document.querySelectorAll( "#menu .area-select-grid .field-options option" ).forEach( function( option ) {
            option.addEventListener( "click", function( e ) {
                let input = this.closest( ".area-select-grid" ).querySelector( ".field-select-menu" );
                input.innerText = this.innerText;
                input.setAttribute( "data-menu-area", this.getAttribute( "value" ) );
                //e.stopPropagation(); убираем чтобы закрылось автоматически
            } );
        } );
        // Option for item
        document.querySelectorAll( "#menu .parent-select-grid .field-options option" ).forEach( function( option ) {
            option.addEventListener( "click", function( e ) {
                let input = this.closest( ".parent-select-grid" ).querySelector( ".field-select" );
                input.innerText = this.innerText.trim();
                input.setAttribute( "data-parent", this.getAttribute( "value" ) );
                //e.stopPropagation(); убираем чтобы закрылось автоматически
            } );
        } );

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

                let area = item.querySelector( "[data-menu-area]" );
                if ( area ) {
                    area = area.getAttribute( "data-menu-area" );
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

                let pid = item.querySelector( "[data-parent]" );
                if ( pid ) {
                    pid = pid.getAttribute( "data-parent" );
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
                api( data, function( r ) {
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
                if ( ! confirm( __( "confirm_delete", "menu.mod.php" ) ) ) return;
                let data = {
                    fn: "del_menu_item",
                    mid: this.closest( "[data-item]" ).getAttribute( "data-item" )
                };
                api( data, function( r ) {
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

    // collapse select dropdown
    document.body.addEventListener( "click", function( e ) {
        document.querySelectorAll( "#menu .select-grid .field-options" ).forEach( function( el ) {
            el.classList.remove( "open" );
        } );
    } );

    // Select
    // Закрытие выпадающих списков при кликах вне их, а так же по ним
    document.body.addEventListener( "click", function( e ) {
        document.querySelectorAll( "#menu .field-options" ).forEach( function( list ) {
            list.classList.remove( "open" );
        } );
    } );

    function modMenuCreate( e ) {
        let pid = this.closest( "[data-item]" ).getAttribute( "data-item" );
        api( { fn : "create_menu_item", pid : pid }, function( r ) {
            if ( r.info_text ) {
                notify( r.info_text, r.info_class, r.info_time );
                if ( r.info_class == "info-success" ) {
                    set_menu_items( r );
                    document.querySelector( `#menu [data-item="${r.mid}"] .prop` ).click();
                }
            }
        } );
    }
    
    // Create Menu
    document.querySelector( "#menu .main-footer .create" ).addEventListener( "click", modMenuCreate );
    
    // update page used in menu
    document.body.addEventListener( "update_menu", function( e ) {
        api( { fn: "get_menu_items" }, set_menu_items );
    } );

} );
