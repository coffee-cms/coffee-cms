// Две функции для чтения и установки cookie.
// Cookie используются для авторизации на сайте,
// для запоминания количества отображаемых страниц в пейджере
// и выбранной темы.
// Другие модули так же могут их использовать
// для запоминания своих настроек.
function get_cookie( name ) {
    let cookies = document.cookie.split( ";" );
    for ( let line of cookies ) {
        let cookie = line.split( "=" );
        // TODO: Нужно ли decodeURIComponent( cookie[ 0 ].trim() )?
        // Имена указываются обычно латиницей, без специальных знаков...
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

// Уведомления, отображающиеся в правом верхнем. Обычно в течении 5 сек.
// Через 5 секунд к уведомлению добавляется класс timeout,
// благодаря ему происходит исчезновение уведомления.
function notify( message, classes, msec ) {
    let bulb = document.createElement( "div" );
    bulb.innerHTML = message;
    bulb.className = classes;
    document.querySelector( ".log-info-box" ).appendChild( bulb );
    let h = bulb.clientHeight;
    // Чтобы анимировать схлопывание
    bulb.setAttribute( "style", `height:${h}px` );
    if ( msec ) {
        setTimeout( function() {
            bulb.classList.add( "timeout" );
        }, msec);
    }
}

// Translate
// module: "admin.mod.php" or "pages.mod.php" etc

// Шаблон admin добавляет глобальную js-переменную
// в которой содержится текущая локаль и переводы,
// загруженные из файлов .cms/lang/...
// Поэтому ими можно пользоваться и на стороне админки.
function __( str, module ) {
    if ( cms && cms.locale && cms.lang && cms.lang[module] && cms.lang[module][cms.locale] && cms.lang[module][cms.locale][str] ) {
        return cms.lang[module][cms.locale][str];
    } else {
        api( { fn: "no_translation", str: str, module: module } );
        return str;
    }
}

// Call server side API

// Передаваемые на сервер данные упаковываются как положено
// и можно передавать даже массивы.
// Массивы вложенные в массивы воспринимаются как объекты
// и кодируются в JSON.
// После того, как сервер вернет ответ, вызывается функция rfn
// И ответ передается ей в качестве параметра.
function api( data, rfn ) {
    const formData = new FormData();
    buildFormData( formData, data );
    // send data
    // По умолчанию запросы отправляются асинхронно,
    // но если нужно дождаться ответа и затем изменить полученные данные,
    // то перед вызовом обновляющих функций нужно дописать строчку
    // cms.async_api = false;
    let ajax = new XMLHttpRequest();
    ajax.addEventListener( "load", function( event ) {
        let data = {};
        try {
            data = JSON.parse( event.target.responseText );
        } catch {
            notify( __( "server_error", "admin.mod.php" ), "info-error", 5000 );
        }
        if ( rfn ) {
            rfn( data );
        }
    } );
    ajax.addEventListener( "error", function( event ) {
        notify( __( "network_error", "admin.mod.php" ), "info-error", 5000 );
    } );
    ajax.open( "POST", cms.api, cms.async_api );
	ajax.send( formData );
    cms.async_api = true;
}
function api2( data, rfn ) {
    api( data, rfn );
}
function buildFormData( formData, data, parentKey ) {
    if ( data && typeof data === 'object' && ! ( data instanceof Date ) && ! ( data instanceof File ) ) {
        Object.keys( data ).forEach( key => {
            buildFormData( formData, data[key], parentKey ? `${parentKey}[${key}]` : key );
        } );
    } else {
        const value = data == null ? '' : data;
        formData.append( parentKey, value );
    }
}


// Create and connect Codemirror
function codemirror_connect( selector, name, options = {} ) {

    if ( window[name] ) return;

    let default_options = {
        mode: "application/x-httpd-php",
        styleActiveLine:   true,
        lineNumbers:       true,
        lineWrapping:      true,
        autoCloseBrackets: true,
        smartIndent:       true,
        indentUnit:        4,
        matchBrackets:     true,
        autoCloseTags: {
            whenClosing: true,
            whenOpening: true,
            indentTags:  [ "div", "ul", "ol", "script", "style" ],
        },
        phrases: {
            "Search:":                              __( "codemirror_search", "admin.mod.php" ),
            "(Use /re/ syntax for regexp search)" : __( "codemirror_re", "admin.mod.php" ),
            "Replace all:":                         __( "codemirror_replace_all", "admin.mod.php" ),
            "With:":                                __( "codemirror_replace_with", "admin.mod.php" ),
            "Replace:":                             __( "codemirror_replace_replace", "admin.mod.php" ),
            "Replace?":                             __( "codemirror_replace_confirm", "admin.mod.php" ),
            "Yes":                                  __( "codemirror_yes", "admin.mod.php" ),
            "No":                                   __( "codemirror_no", "admin.mod.php" ),
            "All":                                  __( "codemirror_all", "admin.mod.php" ),
            "Stop":                                 __( "codemirror_stop", "admin.mod.php" ),
        },
        extraKeys: { "Ctrl-Space": "autocomplete" }
    }

    let txtarea = document.querySelector( selector );
    options = Object.assign( default_options, options );
    window[name] = CodeMirror.fromTextArea( txtarea, options );

    if ( window[name].showHint ) {
        window[name].on( "keydown", function( editor, event ) {
            if ( event.ctrlKey == true ) { return } // Ctrl+S call Hint
            let isAlphaKey = /^[a-zA-Z]$/.test( event.key );
            if ( window[name].state.completionActive && isAlphaKey ) {
                return;
            }

            // Prevent autocompletion in string literals or comments
            let cursor = window[name].getCursor();
            let token = window[name].getTokenAt( cursor );
            if ( token.type === "string" || token.type === "comment" ) {
                return;
            }
            
            let lineBeforeCursor = window[name].doc.getLine( cursor.line );
            if ( typeof lineBeforeCursor !== "string" ) {
                return;
            }
            lineBeforeCursor = lineBeforeCursor.substring( 0, cursor.ch );

            // disable autoclose tag before text
            let charAfterCursor  = window[name].doc.getLine( cursor.line );
            charAfterCursor = charAfterCursor.substring( cursor.ch, cursor.ch + 1 );
            window[name].options.autoCloseTags.dontCloseTags = null;
            if ( charAfterCursor.match( /\S/ ) && charAfterCursor != "<" ) {
                if ( lineBeforeCursor.match( /<[^>]+$/ ) ) {
                    let tag = lineBeforeCursor.match( /<(\w+)\b[^>]*$/ );
                    if ( tag ) {
                        tag = tag[1];
                        window[name].options.autoCloseTags.dontCloseTags = [tag];
                    }
                }
            }
            
            let m = CodeMirror.innerMode( window[name].getMode(), token.state );
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
            } else if ( innerMode === "clike" && window[name].options.mode === "php" ) {
                shouldAutocomplete = token.type === "keyword" || token.type === "variable";
            }
            if ( shouldAutocomplete ) {
                window[name].showHint( { completeSingle: false } );
            }
        } );
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
            document.documentElement.classList.remove( admin_styles[n] );
            n = (+n+1) % admin_styles.length;
            document.documentElement.classList.add( admin_styles[n] );
            notify( admin_styles[n], "info-success", 5000 );
            set_cookie( "theme" , n );
        } );
    } );

    // Initial set theme
    let n = get_cookie( "theme" );
    if ( n == undefined || isNaN( n ) || n === "" ) {
        n = 0; // dark
        set_cookie( "theme" , n );
    }
    let theme = admin_styles[n];
    document.documentElement.classList.add( theme );

    // Logout
    document.querySelectorAll( "[data-logout]" ).forEach( function ( logoutBtn ) {
        logoutBtn.addEventListener( "click", function() {
            api( { fn: "logout" }, function() {
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
        } else if ( document.querySelector( "#start a[href='#base']" ) ) {
            window.location.hash = "#start";
        } else {
            // Чтобы было выделение, нужен именно клик
            let el = document.querySelector( 'a[href="#pages"]' );
            if ( el ) {
                el.click();
            }
        }
    }

    // Login
    document.querySelectorAll( ".login-and-password .password div" ).forEach( function( loginBtn ) {
        loginBtn.addEventListener( "click", function() {
            let data = {
                fn:       "login",
                login:    document.querySelector( "input[name=login]" ).value,
                password: document.querySelector( "input[name=password]" ).value,
                url:      window.location.pathname,
                href:     window.location.href,
                locale:   document.querySelector( ".lang-select-grid .field-select" ).getAttribute( "data-lang" )
            }
            api( data, function( r ) {

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
            api( { fn: "clear_cache" }, function( r ){
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
            let section = document.querySelector( `${selector} .section-select-grid .field-select` );
            if ( section ) {
                section = section.getAttribute( "data-section" );
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
            api( data, function( r ) {
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
                notify( _( "not_empty_section" ), "info-error", 2000 );
                return;
            }
            if ( ! confirm( _( "confirm_delete" ) ) ) return;
            let data = {
                fn: "admin_menu_del",
                item: item,
            }
            api( data, function( r ) {
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
                if ( ! confirm( _( "hide_admin_settings" ) ) ) return false;
            }
            api( data, function( r ) {
                if ( r.ok == "true" ) {
                    window.location.reload( true );
                }
            } );
        } );
    } );

    // Admin section, Add Section
    document.querySelectorAll( "#admin_menu .main-footer .add-section" ).forEach( function( button ) {
        button.addEventListener( "click", function( e ) {
            api( { fn: "admin_menu_add_section" }, function( r ) {
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


    // Reset all items in Admin Menu
    document.querySelectorAll( "#admin_menu .main-footer .reset-all" ).forEach( function( button ) {
        button.addEventListener( "click", function( e ) {
            api( { fn: "reset_admin_menu_items" }, function( r ) {
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
            api( data, function( r ) {
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
            api( data, function( r ) {
                if ( r.info_text ) {
                    notify( r.info_text, r.info_class, r.info_time );
                }
            } );
        } );
    } );

    // Close Sessions
    document.querySelectorAll( "#auth [data-login]" ).forEach( function( button ) {
        button.addEventListener( "click", function( e ) {
            e.preventDefault();
            if ( ! confirm( _( "confirm_logout" ) ) ) return;
            let parent = button.parentElement;
            var data = {
                fn: "logout",
                sess: this.getAttribute( "data-login" ),
            }
            api( data, function( r ) {
                if ( r.info_text ) {
                    notify( r.info_text, r.info_class, r.info_time );
                    if ( r.result == "refresh" ) {
                        // refresh приходит если мы закрываем свою сессию
                        window.location.reload( true );
                    } else if ( r.result == "ok" ) {
                        // Перемещаем сессию в закрытые путем копирования html,
                        // предварительно удалив класс del-sess чтобы не отображался крестик.
                        // А старый элемент удаляем.
                        // При копировании html так же удаляется привязанная функция.
                        button.classList.remove( "del-sess" );
                        let html = parent.outerHTML;
                        parent.remove();
                        document.querySelector( ".history-sess .sess-table" ).insertAdjacentHTML( "afterbegin", html );
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

    // Install module (upload module)
    let input = document.querySelector( "#module-upload" );
    if ( input ) {
        input.addEventListener( "change", async function( e ) {
            const formData = new FormData();
            formData.append( "fn", "install_module" );
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
    }

    // БД. Открытие дополнительных настроек.
    document.querySelectorAll( "#base .pro-btn" ).forEach( function( pro ) {
        pro.addEventListener( "click", function( e ) {
            document.querySelector( "#base .pro" ).classList.toggle( "hidden" );
        } );
    } );

    // Выбор языков при входе. Select
    // Выбор секции Админского меню. Select
    document.querySelectorAll( ".login .field-select, #admin_menu .field-select" ).forEach( function( select ) {
        select.addEventListener( "click", function( e ) {
            e.stopPropagation();
            select.nextElementSibling.classList.toggle( "open" );
        } );
    } );
    // Выбор языков при входе. Option
    document.querySelectorAll( ".login .field-options option" ).forEach( function( select ) {
        select.addEventListener( "click", function( e ) {
            let input = this.closest( ".lang-select-grid" ).querySelector( ".field-select" );
            input.innerText = this.innerText;
            input.setAttribute( "data-lang", this.getAttribute( "value" ) );
            //e.stopPropagation(); убираем чтобы закрылось автоматически
            let locale = this.getAttribute( "value" );
            let search = window.location.search.replace( /&*locale=[^&]+/, "" );
            if ( search == "" ) { 
                search += "?locale=" + locale;
            } else if ( search == "?" ) {
                search += "locale=" + locale;
            } else {
                search += "&locale=" + locale;
            }
            window.location.search = search;
        } );
    } );
    // Выбор секции Админского меню. Option
    document.querySelectorAll( "#admin_menu .field-options option" ).forEach( function( select ) {
        select.addEventListener( "click", function( e ) {
            let input = this.closest( ".section-select-grid" ).querySelector( ".field-select" );
            input.innerText = this.innerText;
            input.setAttribute( "data-section", this.getAttribute( "value" ) );
            //e.stopPropagation(); убираем чтобы закрылось автоматически
        } );
    } );
    // Select
    // Закрытие выпадающих списков при кликах вне их, а так же по ним
    document.body.addEventListener( "click", function( e ) {
        document.querySelectorAll( ".login .field-options, #admin_menu .field-options" ).forEach( function( list ) {
            list.classList.remove( "open" );
        } );
    } );

} );
