document.addEventListener( "DOMContentLoaded", function( event ) {

    let select     = document.querySelector( "#sitemap .xml-wrapper-hidden .select-dropdown" );
    let page_input = select.querySelector( "input" );

    // Поиск при каждом нажатии кнопки
    page_input.addEventListener( "keyup", function( e ) {
        api( { fn: "get_search_pages_list", search: this.value }, function( r ) {
            select.classList.add( "open" );
            if ( r.html ) {
                select.querySelector( ".list-search" ).innerHTML = r.html;
                select.querySelector( ".list-search li[data-id='0']" ).remove();
                select.querySelectorAll( ".list-search li" ).forEach( function( li ) {
                    li.addEventListener( "click", li_click );
                } );
            }
        } );
    } );

    // Щелчок по выбранной странице
    function li_click() {
        let url   = this.getAttribute( "data-url" );

        let textarea = document.querySelector( "#sitemap textarea[name='exclude']" );
        let val = textarea.value.trim();
        let n = val ? "\n" : "";
        textarea.value = val + n + url;
        
        select.classList.remove( "open" );
    }

    page_input.addEventListener( "click", function( e ) {
        e.stopPropagation();
        page_input.dispatchEvent( new Event( "keyup" ) );
        select.classList.add( "open" );
    } );

    // collapse select dropdown
    document.body.addEventListener( "click", function( e ) {
        select.classList.remove( "open" );
    } );

} );