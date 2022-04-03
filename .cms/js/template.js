"use strict";
document.addEventListener( "DOMContentLoaded", function( event ) {

    function _( str ) {
        return __( str, "template.mod.php" );
    }

    // Edit file
    document.querySelectorAll( "#template .template-files .file" ).forEach( function( button ) {
        button.addEventListener( "click", function( e ) {
            let file = this.innerText;
            // get file from server
            api2( { fn: "get_template_file", file: file }, function( r ) {
                if ( r.ok == "true" ) {
                    document.querySelector( ".template-editor-title" ).innerText = file;
                    document.querySelector( ".template-editor > textarea" ).value = r.file;

                    // Show Editor
                    document.querySelector( ".template-editor-bg" ).classList.remove( "hidden" );
                    document.body.classList.add( "editor" ); // for notifications

                    // get theme
                    let n = get_cookie( "theme" );
                    let theme = admin_styles[n][1];

                    let ext = file.match( /\.[^\.]+$/, "" );
                    let aext = {
                        ".php" : "application/x-httpd-php",
                        ".css" : "text/css"
                    }

                    // Connect Editor
                    let txtarea = document.querySelector( ".template-editor > textarea" );
                    window.cmt = CodeMirror.fromTextArea( txtarea, {
                        mode: aext[ext],
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
                    if ( cmt.showHint ) {
                        cmt.on( "keydown", function( editor, event ) {
                            if ( event.ctrlKey == true ) { return } // Ctrl+S call Hint
                            let isAlphaKey = /^[a-zA-Z]$/.test( event.key );
                            if ( cmt.state.completionActive && isAlphaKey ) {
                                return;
                            }

                            // Prevent autocompletion in string literals or comments
                            let cursor = cmt.getCursor();
                            let token = cmt.getTokenAt( cursor );
                            if ( token.type === "string" || token.type === "comment" ) {
                                return;
                            }
                            
                            let lineBeforeCursor = cmt.doc.getLine( cursor.line );
                            if ( typeof lineBeforeCursor !== "string" ) {
                                return;
                            }
                            lineBeforeCursor = lineBeforeCursor.substring( 0, cursor.ch );

                            // disable autoclose tag before text
                            let charAfterCursor  = cmt.doc.getLine( cursor.line );
                            charAfterCursor = charAfterCursor.substring( cursor.ch, cursor.ch + 1 );
                            cmt.options.autoCloseTags.dontCloseTags = null;
                            if ( charAfterCursor.match( /\S/ ) && charAfterCursor != "<" ) {
                                if ( lineBeforeCursor.match( /<[^>]+$/ ) ) {
                                    let tag = lineBeforeCursor.match( /<(\w+)\b[^>]*$/ );
                                    if ( tag ) {
                                        tag = tag[1];
                                        cmt.options.autoCloseTags.dontCloseTags = [tag];
                                    }
                                }
                            }
                            
                            let m = CodeMirror.innerMode( cmt.getMode(), token.state );
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
                            } else if ( innerMode === "clike" && cmt.options.mode === "php" ) {
                                shouldAutocomplete = token.type === "keyword" || token.type === "variable";
                            }
                            if ( shouldAutocomplete ) {
                                cmt.showHint( { completeSingle: false } );
                            }
                        } );
                    }

                    // track changes
                    document.querySelector( ".close-template-button" ).setAttribute( "data-changed", "false" );
                    cmt.on( "change", function( cmt, change ) {
                        document.querySelector( ".close-template-button" ).setAttribute( "data-changed", "true" );
                    } );

                    // set cursor to editor
                    cmt.focus();

                    // Save Page Ctrl+S
                    document.querySelector( "body" ).addEventListener( "keydown", CtrlS );
                }
            } );
        } );
    } );

    // Close Editor
    document.querySelectorAll( ".close-template-button" ).forEach( function( button ) {
        button.onclick = function( e ) {
            document.querySelector( "body" ).removeEventListener( "keydown", CtrlS );
            // detach
            if ( window.cmt !== undefined ) {
                if ( this.getAttribute( "data-changed" ) === "true" ) {
                    if ( confirm( _( "Save changes?" ) ) ) {
                        document.querySelector( ".save-template-button" ).setAttribute( "data-close", "true" );
                        document.querySelector( ".save-template-button" ).click();
                        return;
                    }
                }
                window.cmt.toTextArea();
                window.cmt = null;
            }
            // hide editor
            document.querySelector( ".template-editor-bg" ).classList.add( "hidden" );
            document.body.classList.remove( "template_editor" );
            //window.prevent_reload = false;
        };
    } );

    // Save File
    document.querySelectorAll( ".save-template-button" ).forEach( function( button ) {
        button.onclick = function( e ) {
            window.cmt.save(); // drop changes to textarea
            let data = {
                fn: "save_template_file",
                file: document.querySelector( ".template-editor-title" ).innerText,
                content: document.querySelector( ".template-editor > textarea" ).value
            }
            api2( data, function( r ) {
                if ( r.info_text ) {
                    notify( r.info_text, r.info_class, r.info_time );
                    document.querySelector( ".close-template-button" ).setAttribute( "data-changed", "false" );
                    // close editor after save
                    if ( document.querySelector( ".save-template-button" ).getAttribute( "data-close" ) === "true" ) {
                        document.querySelector( ".save-template-button" ).setAttribute( "data-close", "false" );
                        document.querySelector( ".close-template-button" ).click();
                    }
                }
                if ( r.ok == "true" ) {
                    // highlight save button
                    document.querySelector( ".save-template-button" ).classList.add( "saved" );
                    setTimeout( function() {
                        document.querySelector( ".save-template-button" ).classList.remove( "saved" );
                    }, 1000 );
                }
            } );
        };
    } );

    function CtrlS( e ) {
        if ( e.code == "KeyS" && e.ctrlKey == true ) {
            e.preventDefault(); // don't save page
            if ( window.location.hash == "#template" ) {
                document.querySelector( ".save-template-button" ).click();
            }
        }
    }

    document.documentElement.addEventListener( "theme", function( e ) {
        if ( window.cmt ) {
            let n = get_cookie( "theme" );
            window.cmt.setOption( "theme", admin_styles[n][1] );
        }
    } );

} );