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
            api( { fn: "get_template_file", file: file }, function( r ) {
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

                    // Подключаем редактор Codemirror функцией расположенной в admin.js
                    codemirror_connect( "#template .template-editor > textarea", "cmt", aext[ext] );

                    // track changes
                    document.querySelector( ".close-template-button" ).setAttribute( "data-changed", "false" );
                    cmt.on( "change", function( cmt, change ) {
                        document.querySelector( ".close-template-button" ).setAttribute( "data-changed", "true" );
                    } );

                    // set cursor to editor
                    cmt.focus();

                    // Save Teplate Ctrl+S
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
                    if ( confirm( _( "Сохранить изменения?" ) ) ) {
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
            api( data, function( r ) {
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

    // Install template (upload template)
    let input = document.querySelector( "#template-upload" );
    input.addEventListener( "change", async function( e ) {
        const formData = new FormData();
        formData.append( "fn", "install_template" );
        for ( let i = 0; i < input.files.length; i++ ) {
            formData.append( "myfile[]", input.files[i] );
        }
        try {
            const response = await fetch( cms.api, { method: "POST", body: formData } );
            const r        = await response.json();
            input.value = ""; // chrome fix
            if ( r.info_text ) {
                notify( r.info_text, r.info_class, r.info_time );
                setTimeout( function() {
                    window.location.reload( true );
                }, r.info_time );
            }
        } catch ( error ) {
            console.error( "Error:", error );
        }
    } );

    // prevent hide cursor when window resize
    window.addEventListener( "resize", function() {
        if ( window.cmt ) {
            let cursor = window.cmt.getCursor();
            window.cmt.scrollIntoView( { line:cursor.line, ch:cursor.ch } );
        }
    } );

    // Select
    document.querySelectorAll( "#template .field-select" ).forEach( function( select ) {
        select.addEventListener( "click", function( e ) {
            e.stopPropagation();
            select.nextElementSibling.classList.toggle( "open" );
        } );
    } );
    // Option
    document.querySelectorAll( "#template .field-options option" ).forEach( function( option ) {
        option.addEventListener( "click", function( e ) {
            let input = this.closest( ".template-select-grid" ).querySelector( ".field-select" );
            input.innerText = this.innerText;
            document.querySelector( "#template input[name='template']" ).value = this.innerText;
        } );
    } );
    // Select
    // Закрытие выпадающих списков при кликах вне их, а так же по ним
    document.body.addEventListener( "click", function( e ) {
        document.querySelectorAll( "#template .field-options" ).forEach( function( list ) {
            list.classList.remove( "open" );
        } );
    } );

} );