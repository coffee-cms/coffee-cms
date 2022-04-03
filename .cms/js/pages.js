"use strict";
document.addEventListener( "DOMContentLoaded", function( event ) {

    function _( str ) {
        return __( str, "pages.mod.php" );
    }

    api2( { fn: "get_pages_list" }, set_pages_list );

    function set_pages_list( r ) {

        if ( r.no_database ) {
            document.querySelector( "#pages .pages-grid" ).innerText = _( "Please connect to database" );
            return;
        }

        if ( r.overloaded ) {
            let m = _( "Server overloaded. Sent xxx pages." );
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
                let m = _( "The browser is overloaded. Inserted xxx pages out of nnn." );
                m = m.replace( "xxx", i + 1 );
                m = m.replace( "nnn", r.pages.length );
                notify( m, "info-error", 5000 );
                loaded.value = i + 1;
                break;
            }
        }

        create_pager();

        // for "create_page" function
        if ( cms.call_fn ) {
            cms.call_fn();
            cms.call_fn = null;
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
                    api2( data, set_pages_list );
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
            api2( { fn: "get_pages_list", search: document.querySelector( "#pages .page-search" ).value }, set_pages_list );
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
                    fn:          "save_prop",
                    id:          id,
                    title:       item.querySelector( '[name="title"]' ).value,
                    seo_title:   item.querySelector( '[name="seo_title"]' ).value,
                    url:         item.querySelector( '[name="url"]' ).value,
                    date:        item.querySelector( '[name="date"]' ).value,
                    time:        item.querySelector( '[name="time"]' ).value,
                    template:    item.querySelector( '[name="template"]' ).value,
                    description: item.querySelector( '[name="description"]' ).value
                }
                api2( data, function( r ) {
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

                        // update event for menu
                        if ( r.update_menu == "true" ) {
                            let event = new Event( "update_menu" );
                            document.body.dispatchEvent( event );
                        }
                    }
                    
                } );
            } );
        } );

        selector.querySelectorAll( ".pages-grid > div .pin" ).forEach( function( pin ) {
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
                api2( data, function( r ) {
                    if ( r.ok == "true" ) {
                        document.querySelector( `#pages .pages-grid [data-id="${id}"]` ).setAttribute( "data-pin", pin );
                    }
                } );
            } );
        } );

        // Edit page
        selector.querySelectorAll( ".page-edit-btn" ).forEach( function( button ) {
            button.addEventListener( "click", function( e ) {
                let id = this.closest( "[data-id]" ).getAttribute( "data-id" );
                // get page from server
                api2( { fn: "get_page", id: id }, function( r ) {
                    if ( r.result == "ok" ) {
                        
                        document.querySelector( "#pages .page-editor-title" ).innerHTML = r.page.title;
                        document.querySelector( "#pages .page-editor-title" ).setAttribute( "href", r.page.url );
                        document.querySelector( "#pages .page-editor > textarea" ).value = r.page.text;
                        if ( r.page.modified != null ) { // prevent delete attribute
                            document.querySelector( "#pages .page-editor > textarea" ).setAttribute( "data-modified", r.page.modified );
                        }
                        document.querySelector( "#pages .save-page-button" ).setAttribute( "data-id", r.page.id );
                        
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

                        // get theme
                        let n = get_cookie( "theme" );
                        let theme = admin_styles[n][1];
                        
                        // Connect Editor
                        let txtarea = document.querySelector( "#pages .page-editor > textarea" );
                        window.cm = CodeMirror.fromTextArea( txtarea, {
                            mode: "application/x-httpd-php",
                            styleActiveLine:   true,
                            lineNumbers:       true,
                            lineWrapping:      true,
                            autoCloseBrackets: true,
                            smartIndent:       true,
                            matchBrackets:     true,
                            theme:             theme,
                            autoCloseTags: {
                                whenClosing: true,
                                whenOpening: true,
                                indentTags:  [ "div", "ul", "ol", "script", "style" ],
                            },
                            phrases: {
                                "Search:":                              _( "Search:" ),
                                "(Use /re/ syntax for regexp search)" : _( "(Use /re/ syntax for regexp search)" ),
                                "Replace all:":                         _( "Replace all:" ),
                                "With:":                                _( "With:" ),
                                "Replace:":                             _( "Replace:" ),
                                "Replace?":                             _( "Replace?" ),
                                "Yes":                                  _( "Yes" ),
                                "No":                                   _( "No" ),
                                "All":                                  _( "All" ),
                                "Stop":                                 _( "Stop" ),
                            },
                            extraKeys: { "Ctrl-Space": "autocomplete" }
                        } );
                        if ( cm.showHint ) {
                            cm.on( "keydown", function( editor, event ) {
                                if ( event.ctrlKey == true ) { return } // Ctrl+S call Hint
                                let isAlphaKey = /^[a-zA-Z]$/.test( event.key );
                                if ( cm.state.completionActive && isAlphaKey ) {
                                    return;
                                }

                                // Prevent autocompletion in string literals or comments
                                let cursor = cm.getCursor();
                                let token = cm.getTokenAt( cursor );
                                if ( token.type === "string" || token.type === "comment" ) {
                                    return;
                                }
                                
                                let lineBeforeCursor = cm.doc.getLine( cursor.line );
                                if ( typeof lineBeforeCursor !== "string" ) {
                                    return;
                                }
                                lineBeforeCursor = lineBeforeCursor.substring( 0, cursor.ch );

                                // disable autoclose tag before text
                                let charAfterCursor  = cm.doc.getLine( cursor.line );
                                charAfterCursor = charAfterCursor.substring( cursor.ch, cursor.ch + 1 );
                                cm.options.autoCloseTags.dontCloseTags = null;
                                if ( charAfterCursor.match( /\S/ ) && charAfterCursor != "<" ) {
                                    if ( lineBeforeCursor.match( /<[^>]+$/ ) ) {
                                        let tag = lineBeforeCursor.match( /<(\w+)\b[^>]*$/ );
                                        if ( tag ) {
                                            tag = tag[1];
                                            cm.options.autoCloseTags.dontCloseTags = [tag];
                                        }
                                    }
                                }
                                
                                let m = CodeMirror.innerMode( cm.getMode(), token.state );
                                let innerMode = m.mode.name;
                                let shouldAutocomplete;
                                if ( innerMode === "html" || innerMode === "xml" ) {
                                    shouldAutocomplete = event.key === "<" ||
                                        event.key === "/" && token.type === "tag" ||
                                        isAlphaKey && token.type === "tag" ||
                                        isAlphaKey && token.type === "attribute" ||
                                        token.string === "=" && token.state.htmlState && token.state.htmlState.tagName;
                                } else if ( innerMode === "css" ) {
                                    shouldAutocomplete = isAlphaKey ||
                                        event.key === ":" ||
                                        event.key === " " && /:\s+$/.test( lineBeforeCursor );
                                } else if ( innerMode === "javascript" ) {
                                    shouldAutocomplete = isAlphaKey || event.key === ".";
                                } else if ( innerMode === "clike" && cm.options.mode === "php" ) {
                                    shouldAutocomplete = token.type === "keyword" || token.type === "variable";
                                }
                                if ( shouldAutocomplete ) {
                                    cm.showHint( { completeSingle: false } );
                                }
                            } );
                        }

                        // track changes
                        document.querySelector( "#pages .close-page-button" ).setAttribute( "data-changed", "false" );
                        cm.on( "change", function( cm, change ) {
                            document.querySelector( "#pages .close-page-button" ).setAttribute( "data-changed", "true" );
                        } );

                        // set cursor to editor
                        cm.focus();

                        // Save Page Ctrl+S
                        document.documentElement.addEventListener( "keydown", CtrlS );

                        // hide mediateka in mobile version
                        cm.on( "focus", function() {
                            let mediateka = ! document.querySelector( "#pages .page-editor-panel" ).classList.contains( "hidden" );
                            if ( mediateka && window.innerWidth < 1024 ) {
                                document.querySelector( "#pages .open-mediateka" ).click();
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

    }

    function CtrlS( e ) {
        if ( e.code == "KeyS" && e.ctrlKey == true ) {
            e.preventDefault(); // don't save page
            if ( window.location.hash == "#pages" ) {
                document.querySelector( "#pages .save-page-button" ).click();
            }
        }
    }

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
        api2( data, set_pages_list );
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
            if ( ids.length === 0 || !confirm( _( "Delete selected pages? Files attached to these pages will be deleted." ) ) ) {
                return;
            }
            let data = {
                fn: "del_pages",
                ids: ids
            };
            api2( data, function( r ) {
                if ( r.info_text ) {
                    notify( r.info_text, r.info_class, 5000 );
                    if ( r.info_class == "info-success" ) {
                        data.ids.forEach( function( id ) {
                            document.querySelector( `#pages .pages-grid [data-id="${id}"]` ).remove();
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
                        api2( data2, set_pages_list );
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
                notify( _( "Copyed" ), "info-success", 5000 );
            } else {
                notify( _( "Please select file" ), "info-error", 5000 );
            }
        } else {
            notify( _( "Copy Error" ), "info-error", 5000 );
        }
    };

    // Save Page
    document.querySelectorAll( "#pages .save-page-button" ).forEach( function( button ) {
        button.onclick = function( e ) {
            window.cm.save(); // drop changes to textarea
            let data = {
                fn: "save_page",
                id: document.querySelector( "#pages .save-page-button" ).getAttribute( "data-id" ),
                modified: document.querySelector( "#pages .page-editor > textarea" ).getAttribute( "data-modified" ),
                text: document.querySelector( "#pages .page-editor > textarea" ).value,
                url: document.querySelector( "#pages .page-editor-title" ).getAttribute( "href" ) // for delete old page
            }
            api2( data, function( r ) {
                if ( r.info_text ) {
                    notify( r.info_text, r.info_class, r.info_time );
                }
                if ( r.ok == "true" ) {
                    document.querySelector( "#pages .page-editor > textarea" ).setAttribute( "data-modified", r.modified );
                    document.querySelector( "#pages .close-page-button" ).setAttribute( "data-changed", "false" );
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
                }
                if ( r.ok == "false" ) {
                    // highlight save button
                    document.querySelector( "#pages .save-page-button" ).classList.add( "error" );
                    setTimeout( function() {
                        document.querySelector( "#pages .save-page-button" ).classList.remove( "error" );
                    }, 1000 );
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
    let input = document.querySelector( "#pages .upload-files input[type=file]" );
    input.onchange = async function( event ) {
        const formData = new FormData();
        let id = document.querySelector( "#pages .save-page-button" ).getAttribute( "data-id" );
        formData.append( "id", id );
        formData.append( "fn", "upload_files" );
        let n = 0;
        for ( let i = 0; i < input.files.length; i++ ) {
            formData.append( "myfile[]", input.files[i] );
            let f = `/uploads/${id}/` + __tr_file( input.files[i].name );
            let f_exists = document.querySelector( `#pages .file-block [data-src="${f}"]` );
            if ( f_exists ) { n++; }
        }
        if ( n )  {
            let c = confirm( _( "Files with the same names found on the server" ) + ` - ${n} ` + _( "pc." ) + "\n" + _( "Overwrite them or cancel the upload?" ) );
            if ( !c ) {
                input.value = ""; // google chrome fix
                return c;
            }
        }
        let bar = document.querySelector( "#pages .upload-progress" );
        bar.style.width = "100%";
        try {
            const response = await fetch( cms.api, { method: "POST", body: formData } );
            const r        = await response.json();
            input.value = ""; // google chrome fix
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
                    // hide progress bar
                    bar.style = "";
                }
            }
        } catch ( error ) {
            console.error( _( "Error:" ), error );
        }
    };

    // Close Editor
    document.querySelectorAll( "#pages .close-page-button" ).forEach( function( button ) {
        button.onclick = function( e ) {
            document.documentElement.removeEventListener( "keydown", CtrlS );
            // detach
            if ( window.cm !== undefined ) {
                if ( this.getAttribute( "data-changed" ) === "true" ) {
                    if ( confirm( _( "Save changes?" ) ) ) {
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
        };
    } );

    // Create Page
    document.querySelector( "#pages .add-page-btn" ).addEventListener( "click", function ( e ) {
        document.querySelector( "#pages .page-search" ).value = "";
        document.querySelector( "#pages .page-search-button" ).click();
        cms.call_fn = function() {
            api2( { fn: "create_page" }, function( r ) {
                if ( r.info_text ) {
                    notify( r.info_text, r.info_class, r.info_time );
                    if ( r.info_class == "info-success" ) {
                        let grid = document.querySelector( "#pages .pages-grid" );
                        grid.insertAdjacentHTML( "afterbegin", r.pages[0].html );

                        let page_box = grid.querySelector( `[data-id="${r.pages[0].id}"]` );
                        set_controls( page_box );

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
                }
            } );
        }
    } );

    // Open Mediateka
    document.querySelector( "#pages .open-mediateka" ).onclick = function( e ) {
        document.querySelector( "#pages .page-editor-panel" ).classList.toggle( "hidden" );
        document.querySelector( "#pages .page-editor-grid" ).classList.toggle( "mediateka" );
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
            api2( data, function( r ) {
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

    document.documentElement.addEventListener( "theme", function( e ) {
        if ( window.cm ) {
            let n = get_cookie( "theme" );
            window.cm.setOption( "theme", admin_styles[n][1] );
        }
    } );

    document.querySelector( "#pages .tags-helper" ).addEventListener( "click", function( e ) {
        document.querySelector( "#pages .page-editor-grid" ).classList.toggle( "tags-opened" );
        cm.focus();
        cm.refresh();
    } );

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

    function tag_panel_collapse() {
        let w = document.querySelector( "#pages .page-editor" ).offsetWidth;
        if ( document.documentElement.offsetWidth < 1024 ) {
            document.querySelector( "#pages .tags-helper" ).click();
        }
    }

} );
