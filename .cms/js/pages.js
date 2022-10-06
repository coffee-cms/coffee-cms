"use strict";

document.addEventListener( "DOMContentLoaded", function( event ) {

    function _( str ) {
        return __( str, "pages.mod.php" );
    }

    api( { fn: "get_pages_list" }, set_pages_list );

    function set_pages_list( r ) {

        if ( r.no_database ) {
            document.querySelector( "#pages .pages-grid" ).innerHTML = r.no_database;
            return;
        }

        if ( r.overloaded ) {
            let m = _( "Сервер перегружен. Прислал xxx страниц." );
            m = m.replace( "xxx", r.pages.length );
            notify( m, "info-error", 5000 );
        }

        let grid = document.querySelector( "#pages .pages-grid" );
        let count = document.querySelector( "#pages .main-footer .count" );
        let loaded = document.querySelector( "#pages .main-footer .loaded" );
        if ( cms.clear_pages_list ) {
            cms.clear_pages_list = false;
            grid.innerHTML = "";
            loaded.value = "0";
        }

        loaded.value = parseInt( loaded.value ) + parseInt( r.pages.length );
        count.innerText = r.count;
        loaded.setAttribute( "data-offset", r.offset );

        // insert pages
        let start = Date.now();
        for ( let i = 0; i < r.pages.length; i++ ) {
            grid.insertAdjacentHTML( "beforeend", r.pages[i].html );
            let page = document.querySelector( `#pages .pages-grid [data-id="${r.pages[i].id}"]` );
            set_controls( page );
            if ( Date.now() - start > 1000 ) {
                let m = _( "Браузер перегружен. Вставил xxx страниц из nnn." );
                m = m.replace( "xxx", i + 1 );
                m = m.replace( "nnn", r.pages.length );
                notify( m, "info-error", 5000 );
                loaded.value = i + 1;
                break;
            }
        }

        create_pager();

        // for "create_page" function
        if ( cms.create_page_fn ) {
            cms.create_page_fn();
            cms.create_page_fn = null;
        }

    }

    function create_pager() {
        let loaded = document.querySelector( "#pages .main-footer .loaded" );
        let offset = parseInt( loaded.getAttribute( "data-offset" ) );
        let count  = parseInt( document.querySelector( "#pages .main-footer .count" ).innerText );
        if ( count === 0 ) { count++; }
        let p = get_cookie( "pages_pager" );
        let pages  = Math.ceil( count / p );
        let pager  = document.querySelector( "#pages .main-footer .pager" );
        pager.innerHTML = "";
        if ( pages > 1 ) {
            for ( let i = 1; i <= pages; i++ ) {
                let p = document.createElement( "div" );
                p.innerText = i;
                p.setAttribute( "data-offset", i - 1 );
                pager.appendChild( p );
            }
            document.querySelector( `#pages .main-footer .pager [data-offset="${offset}"]` ).classList.add( "active" );
            document.querySelectorAll( "#pages .main-footer .pager > div" ).forEach( function( el ) {
                el.addEventListener( "click", function( e ) {
                    let offset = this.getAttribute( "data-offset" );
                    let search = document.querySelector( "#pages .page-search" ).value;
                    let data = {
                        fn: "get_pages_list",
                        offset: offset,
                        search: search
                    }
                    document.querySelector( "#pages .main-main" ).scrollTop = 0;
                    cms.clear_pages_list = true;
                    api( data, set_pages_list );
                } );
            } );
            // scroll
            pager.onmousedown = function( e ) {
                let pageX = 0;
                let pageY0 = 0;
              
                document.onmousemove = function( e ) {
                    if ( pageX !== 0 ) {
                        pager.scrollLeft = pager.scrollLeft + ( pageX - e.pageX );
                    }
                    pageX = e.pageX;
                    // fix for google chrome
                    if ( pageY0 === 0 ) {
                        pageY0 = e.pageY;
                    }
                    if ( Math.abs( pageY0 - e.pageY ) > 64 ) {
                        const event = new Event( "mouseup" );
                        pager.dispatchEvent( event );
                    }
                }
              
                // end drag
                pager.onmouseup = function() {
                    document.onmousemove = null;
                    pager.onmouseup = null;
                }
              
                // disable browser drag
                pager.ondragstart = function() {
                    return false;
                }
            }
        }
    }

    // pager counter
    document.querySelector( "#pages .main-footer input" ).addEventListener( "keydown", function( e ) {
        if ( e.keyCode === 13 ) { // keyCode work on mobile
            let p = document.querySelector( "#pages .main-footer input" ).value;
            set_cookie( "pages_pager", p );
            cms.clear_pages_list = true;
            api( { fn: "get_pages_list", search: document.querySelector( "#pages .page-search" ).value }, set_pages_list );
        }
    } );

    function set_controls( selector ) {

        // Open properties
        selector.querySelectorAll( ".page-prop-btn" ).forEach( function( button ) {
            button.addEventListener( "click", function( e ) {
                let id = this.closest( "[data-id]" ).getAttribute( "data-id" );
                document.querySelector( `#pages .pages-grid [data-id="${id}"]` ).classList.toggle( "open" );
            } );
        } );

        // Save properties
        selector.querySelectorAll( ".page-prop-save-btn" ).forEach( function( button ) {
            button.addEventListener( "click", function( e ) {
                let id       = this.closest( "[data-id]" ).getAttribute( "data-id" );
                let item = document.querySelector( `#pages .pages-grid [data-id="${id}"] ` );
                let data = {
                    fn:           "save_prop",
                    id:           id,
                    title:        item.querySelector( '[name="title"]' ).value,
                    seo_title:    item.querySelector( '[name="seo_title"]' ).value,
                    url:          item.querySelector( '[name="url"]' ).value,
                    date:         item.querySelector( '[name="date"]' ).value,
                    time:         item.querySelector( '[name="time"]' ).value,
                    template:     item.querySelector( '.template-select-grid .field-select' ).getAttribute( "data-template" ),
                    old_template: item.querySelector( '.template-select-grid .field-select' ).getAttribute( "data-old-template" ),
                    description:  item.querySelector( '[name="description"]' ).value,
                    tags:  item.querySelector( '[name="tags"]' ).value
                }
                api( data, function( r ) {
                    if ( r.ok == "false" ) { // FIXME: never fire
                        notify( r.info_text, r.info_class, 5000 );
                    }
                    if ( r.ok == "true" ) {

                        // Update Title, URL and Date
                        item.querySelector( ".page-name" ).innerHTML = r.title;
                        item.querySelector( ".page-name" ).setAttribute( "href", r.url );
                        item.querySelector( "[name=url]" ).value = r.url;
                        let date = item.querySelector( ".page-date" );
                        date.innerHTML = r.created;
                        if ( r.planned ) {
                            date.classList.add( "planned" );
                        } else {
                            date.classList.remove( "planned" );
                        }

                        // update old template
                        item.querySelector( '.template-select-grid .field-select' ).setAttribute( "data-old-template", data.template );

                        // edit marker
                        document.querySelectorAll( "#pages .pages-grid > div" ).forEach( function( el ) {
                            el.classList.remove( "last-edited" );
                        } );
                        setTimeout( function() {
                            item.classList.add( "last-edited" );
                        }, 200 );

                        // highlight save button
                        button.classList.add( "saved" );
                        setTimeout( function() {
                            button.classList.remove( "saved" );
                        }, 200 );

                        notify( r.info_text, r.info_class, 5000 );

                        // update event for menu
                        if ( r.update_menu == "true" ) {
                            let event = new Event( "update_menu" );
                            document.body.dispatchEvent( event );
                        }
                    }
                    
                } );
            } );
        } );

        // Pin Page
        selector.querySelectorAll( ".pin" ).forEach( function( pin ) {
            pin.addEventListener( "click", function( e ) {
                let box  = this.closest( "[data-id]" );
                let id   = box.getAttribute( "data-id" );
                let pin  = box.getAttribute( "data-pin" );
                if ( pin === "1" ) {
                    pin = "0";
                } else {
                    pin = "1";
                }
                let data = {
                    fn:  "page_pin",
                    id:  id,
                    pin: pin
                }
                api( data, function( r ) {
                    if ( r.ok == "true" ) {
                        box.setAttribute( "data-pin", pin );
                    }
                } );
            } );
        } );

        // Edit page
        selector.querySelectorAll( ".page-edit-btn" ).forEach( function( button ) {
            button.addEventListener( "click", function( e ) {
                let id = this.closest( "[data-id]" ).getAttribute( "data-id" );
                // get page from server
                api( { fn: "get_page", id: id }, function( r ) {
                    if ( r.result == "ok" ) {
                        
                        document.querySelector( "#pages .page-editor-grid" ).setAttribute( "data-changed", "false" );
                        document.querySelector( "#pages .page-editor-title" ).innerHTML = r.page.title;
                        document.querySelector( "#pages .page-properties input[name='title']" ).value = r.page.title;
                        document.querySelector( "#pages .page-editor-title" ).setAttribute( "href", r.page.url );
                        document.querySelector( "#pages .page-properties input[name='url']" ).value = r.page.url;
                        document.querySelector( "#pages .page-properties input[name='seo_title']" ).value = r.page.seo_title;
                        document.querySelector( "#pages .page-properties textarea[name='description']" ).value = r.page.description;
                        document.querySelector( "#pages .page-properties textarea[name='tags']" ).value = r.page.tags;
                        document.querySelector( "#pages .page-properties input[name='date']" ).value = r.date;
                        document.querySelector( "#pages .page-properties input[name='time']" ).value = r.time;
                        document.querySelector( "#pages .page-properties .template-select-grid .field-options" ).innerHTML = r.options;
                        document.querySelector( "#pages .page-properties .template-select-grid .field-select" ).setAttribute( "data-template", r.option );
                        document.querySelector( "#pages .page-properties .template-select-grid .field-select" ).setAttribute( "data-old-template", r.option );
                        let val = document.querySelector( `#pages .page-properties .template-select-grid .field-options [value="${r.option}"]` ).innerText;
                        document.querySelector( "#pages .page-properties .template-select-grid .field-select" ).innerText = val;
                        document.querySelector( "#pages .page-editor > textarea" ).value = r.page.text;
                        if ( r.page.modified != null ) { // prevent delete attribute
                            document.querySelector( "#pages .page-editor > textarea" ).setAttribute( "data-modified", r.page.modified );
                        }
                        document.querySelector( "#pages .save-page-button" ).setAttribute( "data-id", r.page.id );

                        // Option
                        document.querySelectorAll( ".page-properties .field-options option" ).forEach( function( select ) {
                            select.addEventListener( "click", function( e ) {
                                let input = this.closest( ".template-select-grid" ).querySelector( ".field-select" );
                                input.innerText = this.innerText;
                                input.setAttribute( "data-template", this.getAttribute( "value" ) );
                                //e.stopPropagation(); убираем чтобы закрылось автоматически
                            } );
                        } );
                        
                        // Images
                        document.querySelector( "#pages .link-file-tag" ).innerHTML = "";
                        document.querySelector( "#pages .del-uploaded-files" ).classList.add( "disabled" );
                        document.querySelector( "#pages .mediateka-files-grid" ).innerHTML = r.flist;
                        document.querySelectorAll( "#pages .mediateka-files-grid input[type=checkbox]" ).forEach( function( checkbox ) {
                            checkbox.addEventListener( "change", img_rechecked );
                        } );
                        document.querySelectorAll( "#pages .file-block" ).forEach( function( block ) {
                            block.addEventListener( "click", img_click );
                        } );
                        document.querySelectorAll( "#pages .mediateka-files-grid img" ).forEach( function( img ) {
                            img.addEventListener( "dblclick", img_lbox );
                        } );

                        // Show Editor
                        document.querySelector( "#pages .page-editor-bg" ).classList.remove( "hidden" );
                        document.body.classList.add( "editor" ); // for notifications

                        // Connect Editor
                        codemirror_connect( "#pages .page-editor > textarea", "cm" );

                        // restore scroll and cursor position
                        let cursor = localStorage.getItem( "cursor_page_" + id );
                        if ( cursor ) {
                            cursor = JSON.parse( cursor );
                            window.cm.scrollTo( cursor.left, cursor.top );
                            window.cm.setCursor( { line:cursor.line, ch:cursor.ch } );
                            window.cm.refresh();
                            //window.cm.scrollIntoView( { line:cursor.line, ch:cursor.ch } ); // fix glitch
                        }

                        // track changes
                        document.querySelector( "#pages .close-page-button" ).setAttribute( "data-changed", "false" );
                        document.querySelector( "#pages .page-editor-grid" ).setAttribute( "data-changed", "false" );
                        cm.on( "change", function( cm, change ) {
                            document.querySelector( "#pages .close-page-button" ).setAttribute( "data-changed", "true" );
                            document.querySelector( "#pages .page-editor-grid" ).setAttribute( "data-changed", "true" );
                        } );
                        // save scroll and cursor position
                        [ "cursorActivity", "scroll" ].forEach( function( event ) {
                            cm.on( event, function() {
                                let cursor = window.cm.getCursor();
                                let scroll = window.cm.getScrollInfo();
                                localStorage.setItem( "cursor_page_" + id, JSON.stringify( { line:cursor.line, ch:cursor.ch, left: scroll.left, top: scroll.top } ) );
                            } );
                        } );

                        // set focus to editor
                        cm.focus();

                        // Save Page Ctrl+S
                        document.documentElement.addEventListener( "keydown", CtrlS );

                        // hide mediateka in mobile version
                        cm.on( "focus", function() {
                            let mediateka = ! document.querySelector( "#pages .page-editor-panel" ).classList.contains( "hidden" );
                            if ( mediateka && window.innerWidth < 1024 ) {
                                document.querySelector( "#pages .open-mediateka" ).click();
                            }
                            let properties = ! document.querySelector( "#pages .page-properties" ).classList.contains( "hidden" );
                            if ( properties && window.innerWidth < 1024 ) {
                                document.querySelector( "#pages .open-properties" ).click();
                            }
                        } );

                        // open tags panel
                        if ( document.documentElement.offsetWidth >= 1024 ) {
                            document.querySelector( "#pages .page-editor-grid" ).classList.add( "tags-opened" );
                        }
                    }
                } );
            } );
        } );

        // Select
        selector.querySelectorAll( ".field-select" ).forEach( function( select ) {
            select.addEventListener( "click", function( e ) {
                e.stopPropagation();
                select.nextElementSibling.classList.toggle( "open" );
            } );
        } );
        // Option
        selector.querySelectorAll( ".field-options option" ).forEach( function( select ) {
            select.addEventListener( "click", function( e ) {
                let input = this.closest( ".template-select-grid" ).querySelector( ".field-select" );
                input.innerText = this.innerText;
                input.setAttribute( "data-template", this.getAttribute( "value" ) );
                //e.stopPropagation(); убираем чтобы закрылось автоматически
            } );
        } );

    }

    // Select for editor
    document.querySelectorAll( ".page-properties .field-select" ).forEach( function( select ) {
        select.addEventListener( "click", function( e ) {
            e.stopPropagation();
            select.nextElementSibling.classList.toggle( "open" );
        } );
    } );

    // Select
    // Закрытие выпадающих списков при кликах вне их, а так же по ним
    document.body.addEventListener( "click", function( e ) {
        document.querySelectorAll( "#pages .field-options" ).forEach( function( list ) {
            list.classList.remove( "open" );
        } );
    } );

    // Search page
    document.querySelector( "#pages .page-search" ).addEventListener( "keydown", function( e ) {
        if ( e.keyCode === 13 ) {
            search_pages();
        }
    } );

    document.querySelector( "#pages .page-search-button" ).addEventListener( "click", search_pages );

    function search_pages() {
        let search_string = document.querySelector( "#pages .page-search" ).value;
        let data = {
            fn: "get_pages_list",
            search: search_string
        };
        cms.clear_pages_list = true;
        api( data, set_pages_list );
    }

    // Reset Search
    document.querySelector( "#pages .reset" ).addEventListener( "click", function() {
        document.querySelector( "#pages .page-search" ).value = "";
        document.querySelector( "#pages .page-search-button" ).click();
    } );

    // Delete pages
    document.querySelectorAll( "#pages .del-pages-btn" ).forEach( function( button ) {
        button.addEventListener( "click", function( e ) {
            let ids = [];
            document.querySelectorAll( "#pages .pages-grid input[type=checkbox]:checked" ).forEach( function( ch ) {
                let id = ch.closest( "[data-id]" ).getAttribute( "data-id" );
                ids.push( id );
            } );
            if ( ids.length === 0 ) {
                notify( _( "Сначала выберите страницы." ), "info-error", 5000 );
                return;
            }
            if ( ! confirm( _( "Удалить выбранные страницы? Прикрепленные к этим страницам файлы будут удалены." ) ) ) {
                return;
            }
            let data = {
                fn: "del_pages",
                ids: ids
            };
            api( data, function( r ) {
                if ( r.info_text ) {
                    notify( r.info_text, r.info_class, 5000 );
                    if ( r.info_class == "info-success" ) {
                        data.ids.forEach( function( id ) {
                            document.querySelector( `#pages .pages-grid [data-id="${id}"]` ).remove();
                            localStorage.removeItem( "cursor_page_" + id );
                        } );
                        let count = document.querySelector( "#pages .main-footer .count" );
                        let loaded = document.querySelector( "#pages .main-footer .loaded" );
                        loaded.value = parseInt( loaded.value ) - data.ids.length;
                        count.innerText = parseInt( count.innerText ) - data.ids.length;
                        // load pages
                        let limit = `LIMIT ${data.ids.length}`;
                        let last_child = document.querySelector( "#pages .pages-grid > div:last-child" );
                        let where;
                        if ( last_child ) {
                            let last_id = last_child.getAttribute( "data-id" );
                            let pin = last_child.getAttribute( "data-pin" );
                            where = `(id+pin*1000000000)<(${last_id}+${pin}*1000000000)`;
                        } else {
                            where = "";
                        }
                        let offset = document.querySelector( "#pages .main-footer .loaded" ).getAttribute( "data-offset" );
                        let search = document.querySelector( "#pages .page-search" ).value;
                        let data2 = {
                            fn: "get_pages_list",
                            limit: limit,
                            where: where,
                            offset: offset,
                            search: search
                        };
                        api( data2, set_pages_list );
                    }
                }
            } );
        } );
    } );

    // copy file link button
    document.querySelector( "#pages .link-file-copy-btn" ).onclick = function( e ) {
        let img = document.querySelector( "#pages .link-file-tag" ).innerText;
        let tmp = document.createElement( "textarea" );
        document.body.appendChild( tmp );
        tmp.value = img;
        tmp.select();
        let r = document.execCommand( "copy" );
        tmp.remove();
        if ( r ) {
            if ( img ) {
                notify( _( "Скопировано" ), "info-success", 5000 );
            } else {
                notify( _( "Выберите файл" ), "info-error", 5000 );
            }
        } else {
            notify( _( "Ошибка копирования" ), "info-error", 5000 );
        }
    };

    function CtrlS( e ) {
        // ы and і - fix for librewolf
        if ( ( e.code == "KeyS" || e.key == "ы" || e.key == "і" ) && e.ctrlKey == true ) {
            e.preventDefault(); // don't save page
            if ( window.location.hash == "#pages" ) {
                document.querySelector( "#pages .save-page-button" ).click();
            }
        }
    }

    // Save Page
    document.querySelectorAll( "#pages .save-page-button" ).forEach( function( button ) {
        button.onclick = function( e ) {
            window.cm.save(); // drop changes to textarea
            let data = {
                fn: "save_page",
                id: document.querySelector( "#pages .save-page-button" ).getAttribute( "data-id" ),
                modified: document.querySelector( "#pages .page-editor > textarea" ).getAttribute( "data-modified" ),
                text: document.querySelector( "#pages .page-editor > textarea" ).value,
                title: document.querySelector( "#pages .page-properties input[name='title']" ).value,
                url: document.querySelector( "#pages .page-properties input[name='url']" ).value,
                seo_title: document.querySelector( "#pages .page-properties input[name='seo_title']" ).value,
                description: document.querySelector( "#pages .page-properties textarea[name='description']" ).value,
                tags: document.querySelector( "#pages .page-properties textarea[name='tags']" ).value,
                date: document.querySelector( "#pages .page-properties input[name='date']" ).value,
                time: document.querySelector( "#pages .page-properties input[name='time']" ).value,
                template: document.querySelector( "#pages .page-properties .template-select-grid .field-select" ).getAttribute( "data-template" ),
                old_template: document.querySelector( "#pages .page-properties .template-select-grid .field-select" ).getAttribute( "data-old-template" ),
            }
            api( data, function( r ) {
                if ( r.ok == "true" ) {
                    // set text
                    if ( r.new_text ) {
                        window.cm.setValue( r.new_text );
                    }

                    // Update Title and URL
                    document.querySelector( "#pages .page-editor-title" ).innerHTML = r.title;
                    document.querySelector( "#pages .page-editor-title" ).setAttribute( "href", r.url );
                    document.querySelector( "#pages .page-properties input[name='url']" ).value = r.url;

                    // update old template
                    document.querySelector( "#pages .page-properties .template-select-grid .field-select" ).setAttribute( "data-old-template", data.template );

                    // Update item in page list
                    document.querySelector( `#pages .pages-grid [data-id='${data.id}'] .page-name` ).innerHTML = r.title;
                    document.querySelector( `#pages .pages-grid [data-id='${data.id}'] .page-name` ).setAttribute( "href", r.url );
                    document.querySelector( `#pages .pages-grid [data-id='${data.id}'] input[name='title']` ).value = r.title;
                    document.querySelector( `#pages .pages-grid [data-id='${data.id}'] input[name='url']` ).value = r.url;
                    document.querySelector( `#pages .pages-grid [data-id='${data.id}'] input[name='seo_title']` ).value = data.seo_title;
                    document.querySelector( `#pages .pages-grid [data-id='${data.id}'] textarea[name='description']` ).value = data.description;
                    document.querySelector( `#pages .pages-grid [data-id='${data.id}'] textarea[name='tags']` ).value = data.tags;
                    document.querySelector( `#pages .pages-grid [data-id='${data.id}'] .template-select-grid .field-select` ).setAttribute( "data-template", data.template );
                    document.querySelector( `#pages .pages-grid [data-id='${data.id}'] .template-select-grid .field-select` ).setAttribute( "data-old-template", data.template );
                    let val = document.querySelector( `#pages .pages-grid [data-id='${data.id}'] .template-select-grid .field-options [value="${data.template}"]` ).innerText;
                    document.querySelector( `#pages .pages-grid [data-id='${data.id}'] .template-select-grid .field-select` ).innerText = val;
                    document.querySelector( `#pages .pages-grid [data-id='${data.id}'] input[name='date']` ).value = data.date;
                    document.querySelector( `#pages .pages-grid [data-id='${data.id}'] input[name='time']` ).value = data.time;

                    document.querySelector( "#pages .page-editor > textarea" ).setAttribute( "data-modified", r.modified );
                    document.querySelector( "#pages .close-page-button" ).setAttribute( "data-changed", "false" );
                    document.querySelector( "#pages .page-editor-grid" ).setAttribute( "data-changed", "false" );
                    // edit marker
                    document.querySelectorAll( "#pages .pages-grid > div" ).forEach( function( item ) {
                        item.classList.remove( "last-edited" );
                    } );
                    document.querySelector( `#pages .pages-grid [data-id="${data.id}"]` ).classList.add( "last-edited" );
                    // close editor after save
                    if ( document.querySelector( "#pages .save-page-button" ).getAttribute( "data-close" ) === "true" ) {
                        document.querySelector( "#pages .save-page-button" ).setAttribute( "data-close", "false" );
                        document.querySelector( "#pages .close-page-button" ).click();
                    }
                    // highlight save button
                    document.querySelector( "#pages .save-page-button" ).classList.add( "saved" );
                    setTimeout( function() {
                        document.querySelector( "#pages .save-page-button" ).classList.remove( "saved" );
                    }, 1000 );

                    notify( r.info_text, r.info_class, r.info_time );
                }
                if ( r.ok == "false" ) {
                    // highlight save button
                    document.querySelector( "#pages .save-page-button" ).classList.add( "error" );
                    setTimeout( function() {
                        document.querySelector( "#pages .save-page-button" ).classList.remove( "error" );
                    }, 1000 );

                    notify( r.info_text, r.info_class, r.info_time );
                }
            } );
        };
    } );

    // transliterate file name
    function __tr_file( str ) {
        let ext = str.match( /\.[^\.]+$/, "" );
        str = str.replace( /\.[^\.]+$/, "" );
        let sp = cms.tr[" "];
        cms.tr[" "] = "_";
        for ( let i in cms.tr ) {
            let re = new RegExp( i, "g" );
            str = str.replace( re, cms.tr[i] );
        }
        if ( sp === undefined ) {
            delete cms.tr[" "];
        } else {
            cms.tr[" "] = sp;
        }
        str = str.replace( /[^-A-Za-z0-9_]+/g, "" );
        if ( ext[0] ) {
            str = str + ext[0];
        }
        str = str.toLowerCase();
        return str;
    }

    // Upload files
    document.querySelector( "#pages .upload-files input[type=file]" ).addEventListener( "change", async function( event ) {
        const formData = new FormData();
        let id = document.querySelector( "#pages .save-page-button" ).getAttribute( "data-id" );
        formData.append( "id", id );
        formData.append( "fn", "upload_files" );
        let n = 0;
        for ( let i = 0; i < this.files.length; i++ ) {
            formData.append( "myfile[]", this.files[i] );
            let f = `/uploads/${id}/` + __tr_file( this.files[i].name );
            let f_exists = document.querySelector( `#pages .file-block [data-src="${f}"]` );
            if ( f_exists ) { n++; }
        }
        let google_chrome_fix = this;
        if ( n )  {
            let c = confirm( _( "На сервере найдены файлы с такими же именами" ) + ` - ${n} ` + _( "шт." ) + "\n" + _( "Перезаписать их или отменить загрузку?" ) );
            if ( !c ) {
                google_chrome_fix.value = "";
                return c;
            }
        }
        let bar = document.querySelector( "#pages .upload-progress" );

        
        let ajax = new XMLHttpRequest();
        
        ajax.upload.addEventListener( "progress", function( event ) {
            let percent = Math.round( (event.loaded / event.total) * 100 );
            bar.style.width = percent + "%";
        }, false );

        ajax.addEventListener( "error", function( event ) {
            notify( _( "Ошибка загрузки файла" ), "info-error", 3600000 );
            bar.style = "";
        }, false );
        
        ajax.addEventListener( "abort", function( event ) {
            notify( _( "Ошибка загрузки файла" ), "info-error", 3600000 );
            bar.style = "";
        }, false );

        ajax.addEventListener( "load", function( event ) {
            bar.style = "";
            google_chrome_fix.value = "";
            let r = JSON.parse( event.target.responseText );
            if ( r.info_text ) {
                notify( r.info_text, r.info_class, r.info_time );
                if ( r.info_class == "info-success" ) {
                    let tmp = document.createElement( "div" );
                    tmp.innerHTML = r.flist;
                    let imgs = tmp.querySelectorAll( "img" );
                    imgs.forEach( function( img ) {
                        let file = img.getAttribute( "data-src" );
                        let exists_file = document.querySelector( `#pages .file-block [data-src="${file}"]` );
                        if ( exists_file ) {
                            exists_file.parentElement.remove();
                        }
                    } );
                    let container = document.querySelector( "#pages .mediateka-files-grid" );
                    container.innerHTML = r.flist + container.innerHTML;
                    // images checkboxes
                    container.querySelectorAll( "input[type=checkbox]" ).forEach( function( checkbox ) {
                        checkbox.addEventListener( "change", img_rechecked );
                    } );
                    // open lightbox
                    container.querySelectorAll( "img" ).forEach( function( img ) {
                        img.addEventListener( "dblclick", img_lbox );
                    } );
                    // generate link
                    document.querySelectorAll( "#pages .file-block" ).forEach( function( file_block ) {
                        file_block.addEventListener( "click", img_click );
                    } );
                    // select last uploaded
                    container.querySelector( ".file-block" ).click();
                }
            }
        }, false );

        ajax.open( "POST", cms.api );
	    ajax.send( formData );
        
    } );

    // Close Editor
    document.querySelectorAll( "#pages .close-page-button" ).forEach( function( button ) {
        button.addEventListener( "click", function( e ) {

            document.documentElement.removeEventListener( "keydown", CtrlS );
            // detach
            if ( window.cm !== undefined ) {
                if ( this.getAttribute( "data-changed" ) === "true" ) {
                    if ( confirm( _( "Сохранить изменения?" ) ) ) {
                        document.querySelector( "#pages .save-page-button" ).setAttribute( "data-close", "true" );
                        document.querySelector( "#pages .save-page-button" ).click();
                        return;
                    }
                }
                window.cm.toTextArea();
                window.cm = null;
            }

            // hide editor
            document.querySelector( "#pages .page-editor-bg" ).classList.add( "hidden" );
            document.body.classList.remove( "editor" );

            // hide mediateka
            if ( ! document.querySelector( "#pages .page-editor-panel" ).classList.contains( "hidden" ) ) {
                document.querySelector( "#pages .open-mediateka" ).click();
            }
            
        } );
    } );

    // Create Page
    document.querySelectorAll( "#pages .add-page-btn" ).forEach( function( btn ) {
        btn.addEventListener( "click", function ( e ) {
            // deffered call
            cms.create_page_fn = function() {
                api( { fn: "create_page" }, function( r ) {
                    if ( r.info_text ) {
                        notify( r.info_text, r.info_class, r.info_time );
                    }
                    if ( r.pages ) {
                        let grid = document.querySelector( "#pages .pages-grid" );
                        grid.insertAdjacentHTML( "afterbegin", r.pages[0].html );

                        let page_box = grid.querySelector( `[data-id="${r.pages[0].id}"]` );
                        set_controls( page_box );
                        page_box.querySelector( ".page-prop-btn" ).click();

                        let counter = document.querySelector( "#pages .main-footer .count" );
                        counter.innerText = parseInt( counter.innerText ) + 1;

                        let limit = parseInt( get_cookie( "pages_pager" ) );
                        let count = parseInt( document.querySelector( "#pages .main-footer .counters input" ).value );
                        if ( count === limit ) {
                            document.querySelector( "#pages .pages-grid > div:last-child" ).remove();
                        } else {
                            document.querySelector( "#pages .main-footer .counters input" ).value = count + 1;
                        }
                    }
                } );
            }
            document.querySelector( "#pages .reset" ).click();
        } );
    } );

    // Open Properties
    document.querySelector( "#pages .open-properties" ).onclick = function( e ) {
        document.querySelector( "#pages .page-editor-grid" ).classList.toggle( "properties" );
        document.querySelector( "#pages .page-properties" ).classList.toggle( "hidden" );
        if ( window.cm ) {
            let cursor = window.cm.getCursor();
            window.cm.scrollIntoView( { line:cursor.line, ch:cursor.ch } );
        }
    };
    
    // Open Mediateka
    document.querySelector( "#pages .open-mediateka" ).onclick = function( e ) {
        document.querySelector( "#pages .page-editor-grid" ).classList.toggle( "mediateka" );
        document.querySelector( "#pages .page-editor-panel" ).classList.toggle( "hidden" );
        if ( window.cm ) {
            let cursor = window.cm.getCursor();
            window.cm.scrollIntoView( { line:cursor.line, ch:cursor.ch } );
        }
    };

    // Replace Dialog Toggle
    document.querySelector( "#pages .codemirror-replace" ).onclick = function( e ) {
        let dialog = document.querySelector( "#pages .CodeMirror-dialog" );
        if ( dialog ) {
            dialog.remove();
        } else {
            let mediateka = ! document.querySelector( "#pages .page-editor-panel" ).classList.contains( "hidden" );
            if ( mediateka && window.innerWidth < 1024 ) {
                document.querySelector( "#pages .open-mediateka" ).click();
            }
            window.cm.execCommand( "replace" );
        }
    };

    // generate link to clicked file
    function img_click() {
        document.querySelectorAll( "#pages .file-block" ).forEach( function( block ) {
            block.classList.remove( "active-file" );
        } );
        this.classList.add( "active-file" );
        let i = this.querySelector( "img" );
        let t = i.getAttribute( "data-type" );
        let l = i.getAttribute( "data-src" );
        let w = i.getAttribute( "width" );
        let h = i.getAttribute( "height" );
        let e = l.replace( /.*\./, "" );
        let img = [ "webp", "tiff", "jpeg", "jpg", "png", "svg", "gif", "bmp", "ico" ];
        let mus = [ "mp3", "ogg", "m4a", "flac" ];
        let vid = [ "mp4", "mkv" ];
        let a = `<a href="${l}" target=_blank>${l}</a>`;
        if ( img.indexOf( e ) >= 0 ) {
            l = `&lt;img alt="" src="${a}"`;
            if ( w ) {
                l += ` width="${w}"`;
            }
            if ( h ) {
                l += ` height="${h}"`;
            }
            l += "&gt;";
            document.querySelector( "#pages .link-file-tag" ).innerHTML = l;
        } else if ( mus.indexOf( e ) >= 0 ) {
            l = `&lt;audio src="${a}" controls>&lt;/audio>`;
            document.querySelector( "#pages .link-file-tag" ).innerHTML = l;
        } else if ( vid.indexOf( e ) >= 0 ) {
            l = `&lt;video src="${a}" controls>&lt;/video>`;
            document.querySelector( "#pages .link-file-tag" ).innerHTML = l;
        } else {
            l = `&lt;a href="${a}"&gt;TEXT&lt;/a&gt;`;
            document.querySelector( "#pages .link-file-tag" ).innerHTML = l;
        }
    }

    // enable or disable delete files button
    function img_rechecked() {
        let checked = document.querySelectorAll( "#pages .mediateka-files-grid input[type=checkbox]:checked" );
        if ( checked.length ) {
            document.querySelector( "#pages .del-uploaded-files" ).classList.remove( "disabled" );
        } else {
            document.querySelector( "#pages .del-uploaded-files" ).classList.add( "disabled" );
        }
    }
    
    // view in lightbox
    function img_lbox() {
        let src = this.getAttribute( "data-src" );
        let e = src.replace( /.*\./, "" );
        let img = [ "webp", "tiff", "jpeg", "jpg", "png", "svg", "gif", "bmp", "ico" ];
        let mus = [ "mp3", "ogg", "m4a", "flac" ];
        let vid = [ "mp4", "mkv" ];
        let t;
        if ( document.querySelector( "#lbox-window" ) == null ) {
            if ( img.indexOf( e ) >= 0 ) {
                t = document.createElement( "img" );
            } else if ( mus.indexOf( e ) >= 0 ) {
                t = document.createElement( "audio" );
                t.setAttribute( "controls", true );
            } else if ( vid.indexOf( e ) >= 0 ) {
                t = document.createElement( "video" );
                t.setAttribute( "controls", true );
            }
            if ( t !== undefined ) {
                t.src = src;
                let d = document.createElement( "div" );
                d.id = "lbox-window";
                d.appendChild( t );
                document.body.appendChild( d );
                d.addEventListener( "click", function( e ) {
                    this.remove();
                } );
            }
        }
    }


    // Delete files
    document.querySelector( "#pages .del-uploaded-files" ).onclick = function( e ) {
        if ( !this.classList.contains( "disabled" ) ) {
            let flist = [];
            document.querySelectorAll( "#pages .mediateka-files-grid input[type=checkbox]:checked" ).forEach( function( e ) {
                let f = e.closest( ".file-block" ).querySelector( "img" ).getAttribute( "data-src" );
                flist.push( f );
            } );
            let data = {
                fn: "del_files",
                flist: flist
            };
            api( data, function( r ) {
                if ( r.info_text ) {
                    document.querySelector( "#pages .link-file-tag" ).innerHTML = "";
                    notify( r.info_text, r.info_class, r.info_time );
                    if ( r.info_class == "info-success" ) {
                        for ( let f in flist ) {
                            document.querySelector( `#pages .mediateka-files-grid img[data-src="${flist[f]}"]` ).parentElement.remove();
                        }
                        document.querySelector( "#pages .del-uploaded-files" ).classList.add( "disabled" );
                    }
                }
            } );
        }
    };

    // prevent hide cursor when window resize
    window.addEventListener( "resize", function() {
        if ( window.cm ) {
            let cursor = window.cm.getCursor();
            window.cm.scrollIntoView( { line:cursor.line, ch:cursor.ch } );
        }
    } );

    // switch theme
    document.documentElement.addEventListener( "theme", function( e ) {
        if ( window.cm ) {
            let n = get_cookie( "theme" );
            window.cm.setOption( "theme", admin_styles[n][1] );
        }
    } );

    // show/hide tags
    document.querySelector( "#pages .tags-helper" ).addEventListener( "click", function( e ) {
        document.querySelector( "#pages .page-editor-grid" ).classList.toggle( "tags-opened" );
        cm.focus();
        cm.refresh();
    } );

    // for tags
    document.querySelectorAll( "#pages .tags-grid [data-type='wrap']" ) .forEach( function( btn ) {
        btn.addEventListener( "click", function( e ) {
            let otag = this.getAttribute( "data-otag" );
            let ctag = this.getAttribute( "data-ctag" );
            let len  = this.getAttribute( "data-len" );
            let ch   = this.getAttribute( "data-ch" );
            let line = this.getAttribute( "data-line" );
            wrap_selections( otag, ctag, len, line, ch );
        } );
    } );
     
    // for tags
    function wrap_selections( open_tag, close_tag, len, line, ch ) {
        let cursor = cm.getCursor();
        let selections = cm.getSelections();
        let replacements = [];
        for ( let i = 0; i < selections.length; i++ ) {
            replacements[i] = open_tag + selections[i] + close_tag;
        }
        cm.replaceSelections( replacements );
        tag_panel_collapse();
        if ( selections.length < 2 ) {
            if ( line ) {
                cursor.line += +line;
                cursor.ch = +ch;
            } else if ( len ) {
                cursor.ch += +len;
            }
            cm.setCursor( cursor );
        }
        cm.focus();
        cm.refresh();
    }

    // for tags
    function tag_panel_collapse() {
        let w = document.querySelector( "#pages .page-editor" ).offsetWidth;
        if ( document.documentElement.offsetWidth < 1024 ) {
            document.querySelector( "#pages .tags-helper" ).click();
        }
    }

    // fix glitches codemirror
    document.querySelector( "aside a[href='#pages']" ).addEventListener( "click", function( e ) {
        setTimeout( function( e ) {
            if ( window.cm ) {
                cm.refresh();
                cm.focus();
            }
        }, 50 );
    } );

    

} );
