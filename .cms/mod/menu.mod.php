<?php

$cms["modules"]["menu.mod.php"] = array(
    "name"        => __( "module_name" ),
    "description" => __( "module_description" ),
    "version"     => "",
    "files"       => array(
        ".cms/mod/menu.mod.php",
        ".cms/css/menu.css",
        ".cms/js/menu.js",
        ".cms/lang/ru_RU.UTF-8/menu.mod.php",
        ".cms/lang/en_US.UTF-8/menu.mod.php",
        ".cms/lang/uk_UA.UTF-8/menu.mod.php",
    ),
);

// Return if module disabled
if ( ! empty( $cms["config"]["menu.mod.php"]["disabled"] ) ) {

    return;

} else {

    if ( is_admin() ) {
        cms_add_function( "create_tables", "cms_menu_create_table" );
        cms_add_function( "admin",         "cms_menu_admin", 20 );
        cms_add_function( "admin_header",  "cms_menu_admin_header" );
        cms_add_function( "api",           "cms_menu_api", 20 );
    }

}

function cms_menu_admin_header() {
    global $cms;
    echo "<link rel=stylesheet href='{$cms['base_path']}css/menu.css'>";
    echo "<script src='{$cms['base_path']}js/menu.js'></script>";
}


function cms_menu_api() {
    global $cms;

    if ( ! empty( $_POST["fn"] ) ) {
        
        switch ( $_POST["fn"] ) {

            case "create_menu_item":

                if ( empty( $cms["base"] ) ) {
                    echo( json_encode( array(
                        "info_text"  => __( "no_connect_db" ),
                        "info_class" => "info-error",
                        "info_time"  => 5000,
                    ) ) );
                    return;
                }

                $pid = (int) $_POST["pid"];
                $q = "INSERT INTO menu SET pid={$pid}";
                if ( $r = mysqli_query( $cms["base"], $q ) ) {

                    $mid = mysqli_insert_id( $cms["base"] );

                } else {

                    echo( json_encode( array(
                        "info_text"  => __( "sql_error" ) . mysqli_error( $cms["base"] ),
                        "info_class" => "info-error",
                        "info_time"  => 5000,
                    ) ) );
                    return;

                }

                // Create title, url and update
                if ( $pid ) {
                    $title = "";
                } else {
                    $title = __( "menu_default_title" );
                }
                $sort      = $mid * 10;
                $q = "UPDATE menu SET title='{$title}', url='', sort={$sort} WHERE mid={$mid}";
                if ( $r = mysqli_query( $cms["base"], $q ) ) {
                    echo( json_encode( array(
                        "info_text"  => __( "created" ),
                        "info_class" => "info-success",
                        "info_time"  => 5000,
                        "list"       => cms_menu_get_items_list(),
                        "parents"    => cms_menu_get_parents_list(),
                        "mid"        => $mid,
                    ) ) );
                    return;
                } else {
                    echo( json_encode( array(
                        "info_text"  => __( "sql_error" ) . mysqli_error( $cms["base"] ),
                        "info_class" => "info-error",
                        "info_time"  => 5000,
                    ) ) );
                    return;
                }
            break;

            case "get_menu_items":

                echo( json_encode( array(
                    "list"       => cms_menu_get_items_list(),
                    "parents"    => cms_menu_get_parents_list(),
                ) ) );
                return;

            break;

            case "del_menu_item":

                if ( $mid = (int) $_POST["mid"] ) {
                    cms_menu_del( $mid );
                    
                    cms_clear_cache();
                    
                    echo( json_encode( array(
                        "info_text"  => __( "deleted" ),
                        "info_class" => "info-success",
                        "info_time"  => 5000,
                        "list"       => cms_menu_get_items_list(),
                        "parents"    => cms_menu_get_parents_list(),
                    ) ) );
                    return;
                }
            break;

            case "save_menu_item":
                
                $mid       = (int) $_POST["mid"];
                $pid       = (int) $_POST["pid"];
                $id        = (int) $_POST["id"];
                $sort      = (int) $_POST["sort"];
                $title     = mysqli_real_escape_string( $cms["base"], $_POST["title"] );
                $tag_title = mysqli_real_escape_string( $cms["base"], $_POST["tag_title"] );
                $classes   = mysqli_real_escape_string( $cms["base"], $_POST["classes"] );
                $url       = mysqli_real_escape_string( $cms["base"], $_POST["url"] );
                $area      = mysqli_real_escape_string( $cms["base"], $_POST["area"] );
                if ( $_POST["target"] === "true" ) {
                    $target_blank = 1;
                } else {
                    $target_blank = 0;
                }

                $q = "UPDATE menu SET
                        pid={$pid},
                        id={$id},
                        sort={$sort},
                        title='{$title}',
                        tag_title='{$tag_title}',
                        class='{$classes}',
                        url='{$url}',
                        area='{$area}',
                        target_blank={$target_blank}
                      WHERE mid={$mid}";
                if ( $r = mysqli_query( $cms["base"], $q ) ) {

                    cms_clear_cache();
                    
                    $r = array(
                        "ok"         => "true",
                        "info_text"  => __( "updated" ), // FIXME: never fire
                        "info_class" => "info-success",
                        "info_time"  => 5000,
                        "list"       => cms_menu_get_items_list(),
                        "parents"    => cms_menu_get_parents_list(),
                    );
                    echo ( json_encode( $r ) );
                    return;
                } else {
                    echo( json_encode( array(
                        "ok"         => "false",
                        "info_text"  => __( "update_properties_error" ),
                        "info_class" => "info-error",
                        "info_time"  => 5000,
                    ) ) );
                    return;
                }

            break;

            case "get_search_pages_list":
                //$s = mysqli_real_escape_string( $cms["base"], $_POST["search"] );
                //$s = str_replace( " ", "%", $s );
                $s = preg_replace( "/\s/u", "", $_POST["search"] );
                $s = preg_split( '//u', $s, -1, PREG_SPLIT_NO_EMPTY );
                foreach( $s as $n => $ch ) {
                    $s[$n] = mysqli_real_escape_string( $cms["base"], $ch );
                }
                $s = implode( "%", $s );
                $html = "<li data-id=0 data-url>" . __( "no_page" ) . "</li>" ;
                $q = "SELECT id, title, url FROM pages WHERE title LIKE '%{$s}%' OR url LIKE '%{$s}%' ORDER BY ( id + pin * 1000000000 ) DESC LIMIT 10";
                $res = mysqli_query( $cms["base"], $q );
                while ( $page = mysqli_fetch_assoc( $res ) ) {
                    $title = htmlspecialchars( $page["title"] );
                    $html .= "<li data-id={$page['id']} data-url='{$page['url']}'>{$title}</li>";
                }
                echo( json_encode( array(
                    "html" => $html,
                ) ) );
                return;
            break;

        }
    }
}

function cms_menu_del( $mid ) {
    global $cms;

    // Recursively delete children items
    $q = "SELECT mid FROM menu WHERE pid={$mid}";
    if ( $res = mysqli_query( $cms["base"], $q ) ) {
        while ( $r = mysqli_fetch_assoc( $res ) ) {
            cms_menu_del( $r["mid"] );
        }
    }
    
    mysqli_query( $cms["base"], "DELETE FROM menu WHERE mid=$mid" );

}

function cms_menu_get_items_list( $pid = 0 ) {
    global $cms;

    if ( empty( $cms["base"] ) ) {
        return "<span class=no-database>" . __( "no_connect_db" ) . "</span>";
    }

    $list = "";

    $q = "SELECT * FROM menu WHERE pid={$pid} ORDER BY sort, title";
    if ( ! $res = mysqli_query( $cms["base"], $q ) ) {

        return array( mysqli_error( $cms["base"] ), -1 );

    } else {

        // Translations
        $tr_title              = __( "title" );
        $tr_area               = __( "area" );
        $tr_no_area            = __( "no_area" );
        $tr_classes            = __( "classes" );
        $tr_sort               = __( "sort" );
        $tr_save               = __( "save" );
        $tr_delete             = __( "delete" );
        $tr_create             = __( "create_item" );
        $tr_prop               = __( "properties" );
        $tr_parent             = __( "parent" );
        $tr_no_parent          = __( "no_parent" );
        $tr_placeholder        = __( "placeholder_hint" );
        $tr_no_page            = __( "no_page" );
        $tr_search             = __( "search" );
        $tr_tag_title          = __( "hint" );
        $tr_new_window         = __( "target_blank" );
        $tr_page               = __( "page" );
        $tr_page_deleted       = __( "deteled_page" );
        $tr_menu_item          = __( "menu_item" );

        while ( $item = mysqli_fetch_assoc( $res ) ) {

            // Children Items
            $sublist = cms_menu_get_items_list( $item["mid"] );
            if ( $sublist ) {
                $sublist = "<div class=items-grid>{$sublist}</div>";
            }

            if ( $item["pid"] == 0 ) {

                // Menu Areas <option>
                $areas   = "";
                if ( ! empty( $cms["menu_areas"] ) )
                foreach ( $cms["menu_areas"] as $area => $a ) {
                    $areas .= "<option value='{$area}'>{$a['title']}</option>";
                }

                @$list   .= "
<div>

    <div class=menu data-item={$item['mid']}>

        <input name=title type=text value='{$item['title']}'>
        <input name=sort type=text value='{$item['sort']}'>

        <div class=menu-buttons>
            <div class=prop>{$tr_prop}</div>
            <div class=create>{$tr_create}</div>
            <div class=save>{$tr_save}</div>
            <div class=del>{$tr_delete}</div>
        </div>

        <div class=menu-prop>
            <div class=area-title>{$tr_area}:</div>

            <div class=area-select-grid>
                <div class=field-select-menu data-menu-area='{$item['area']}'>{$cms["menu_areas"][ $item['area'] ]['title']}</div>
                <div class=field-options>
                    <option value>{$tr_no_area}</option>
                    {$areas}
                </div>
            </div>

            <div class=classes-title>{$tr_classes}:</div>
            <input name=classes type=text value='{$item['class']}'>
        </div>

    </div>

    {$sublist}

</div>";

            } else {
                
                $disabled_url = "disabled";

                if ( $item["id"] == 0 ) {
                    $select_title = $tr_no_page;
                    $disabled_url = "";
                    // free item
                    if ( ! empty( $item["title"] ) ) {
                        $title = $item["title"];
                    } else {
                        $title = $tr_menu_item;
                    }
                } else {
                    // page
                    $q = "SELECT id, title FROM pages WHERE id={$item['id']}";
                    $res_page = mysqli_query( $cms["base"], $q );
                    $page = mysqli_fetch_assoc( $res_page );
                    if ( ! $page ) {
                        $title        = $tr_page_deleted;
                        $select_title = $tr_page_deleted;
                    } else {
                        $title        = $page["title"];
                        $select_title = $page["title"];
                    }
                }

                // Wrap title in URL
                if ( $item['url'] ) {
                    $title_link = "<a href='{$item['url']}' target=_blank>{$title}</a>";
                } else {
                    $title_link = "<a>$title</a>";
                }

                // Checkbox "Open in new Tab"
                if ( $item["target_blank"] ) {
                    $checked = "checked";
                } else {
                    $checked = "";
                }

                $e_tag_title = htmlspecialchars( $item["tag_title"] );

                $list .= "
<div>

    <div class=item data-item={$item['mid']}>

        {$title_link}

        <div class=menu-buttons>
            <div class=prop>{$tr_prop}</div>
            <div class=create>{$tr_create}</div>
            <div class=save>{$tr_save}</div>
            <div class=del>{$tr_delete}</div>
        </div>

        <div class=menu-prop>
            <div class=title>{$tr_title}:</div>
            <input name=title type=text value='{$item['title']}' placeholder='{$tr_placeholder}'>
            <div class=sort-title>{$tr_sort}:</div>
            <input name=sort type=text value='{$item['sort']}'>
            <div class=parent-title>{$tr_parent}:</div>

            <div class=parent-select-grid>
                <div class=field-select data-parent='{$item['pid']}'></div>
                <div class=field-options>
                    <option value=0>{$tr_no_parent}</option>
                </div>
            </div>

            <div class=classes-title>{$tr_classes}:</div>
            <input name=classes type=text value='{$item['class']}'>
            <div class=page-title>{$tr_page}:</div>
            <div class=select-grid>
                <div class=field-select name=id data-id={$item['id']}>{$select_title}</div>
                <div class=field-options>
                    <div class=select-dropdown>
                        <div class=field-search>
                            <input class=search-field type=text placeholder='{$tr_search}'>
                        </div>
                        <ul class=list-search>
                            <li data-id=0 data-url>{$tr_no_page}</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class=tag-title>{$tr_tag_title}:</div>
            <input name=tag_title type=text value='{$e_tag_title}'>
            <div class=url-title>URL:</div>
            <div class=target-blank>
                <input name=url type=text value='{$item['url']}' {$disabled_url}>
                <input name=targetblank type=checkbox {$checked} title='{$tr_new_window}'>
            </div>
        </div>
    </div>
    
    {$sublist}

</div>";
            }
            
        }
    }
    return $list;
}

function cms_menu_get_parents_list( $pid = 0, $lvl = 0 ) {
    global $cms;
    $parents = "";
    if ( empty( $cms["base"] ) ) {
        return "";
    }
    $q = "SELECT mid, pid, title, id FROM menu WHERE pid={$pid} ORDER BY sort";
    if ( $res = mysqli_query( $cms["base"], $q ) ) {
        while ( $item = mysqli_fetch_assoc( $res ) ) {
            if ( empty( $item["title"] ) and $item["id"] !== "0" ) {
                $q = "SELECT title FROM pages WHERE id={$item['id']}";
                if ( $res_page = mysqli_query( $cms["base"], $q ) and $page = mysqli_fetch_assoc( $res_page ) ) {
                    $item["title"] = $page["title"];
                } else {
                    $item["title"] = __( "deteled_page" );
                }
            }
            $title = htmlspecialchars( $item["title"] );
            $ident = str_repeat( "&emsp;", $lvl );
            $parents .= "<option value={$item['mid']}>{$ident}{$title}</option>";
            $parents .= cms_menu_get_parents_list( $item["mid"], $lvl + 1 );
        }
    }
    return $parents;
}

function cms_menu_admin() {
    global $cms;

    $page = "
<div class=main-main>
    <div class=menu-grid>
    </div>
</div>
<div class=main-footer>
    <a class=create data-item=0>" . __( "add_menu" ) . "</a>
</div>";

    // Create menu item if not exists
    if ( empty( $cms["config"]["menu.mod.php"]["menu"]["menu"] ) ) {
        $cms["config"]["menu.mod.php"]["menu"]["menu"] = array(
            "title"    => "module_name",
            "sort"     => 20,
            "section"  => "navigation",
        );
        cms_save_config();
    }

    $cms["admin_pages"]["menu"] = $page;
}

function cms_menu_create_table() {
    global $cms;

    mysqli_query( $cms["base"], "
    CREATE TABLE IF NOT EXISTS `menu` (
        `mid` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        `pid` int(11) DEFAULT 0,
        `id` int(11) NOT NULL DEFAULT 0,
        `title` varchar(255) NOT NULL DEFAULT '',
        `tag_title` varchar(255) NOT NULL DEFAULT '',
        `url` varchar(255) NOT NULL DEFAULT '',
        `sort` int(11) NOT NULL DEFAULT 1000,
        `class` varchar(255) NOT NULL DEFAULT '',
        `area` varchar(255) NOT NULL DEFAULT '',
        `target_blank` tinyint(1) NOT NULL DEFAULT 0,
        UNIQUE KEY `mid` (`mid`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

}

// One or more Menus
function menu( $area ) {
    global $cms;
    if ( ! $cms["base"] ) { return ""; } // fix php 8.0
    $area = mysqli_real_escape_string( $cms["base"], $area );
    $q = "SELECT * FROM menu WHERE pid=0 AND area='{$area}' ORDER BY sort";
    $html = "";
    if ( $res = mysqli_query( $cms["base"], $q ) ) {
        while ( $menu = mysqli_fetch_assoc( $res ) ) {
            $html .= menu1( $menu["mid"] );
        }
    }
    return $html;
}

function menu1( $pid = 0 ) {
    global $cms;
    
    $html = "";

    $q = "SELECT m.url as 'm.url',
                 m.title as 'm.title',
                 m.tag_title as 'm.tag_title', 
                 m.id as 'm.id', 
                 p.url as 'p.url',
                 p.title as 'p.title',
                 m.target_blank as 'm.target_blank',
                 m.mid as 'm.mid',
                 m.class as 'm.class'
                 FROM menu m LEFT JOIN pages p ON m.id=p.id 
                 WHERE m.pid={$pid} ORDER BY m.sort";
    
    // Child Items
    if ( $res2 = mysqli_query( $cms["base"], $q ) ) {
        while ( $item = mysqli_fetch_assoc( $res2 ) ) {

            if ( $item["m.id"] !== "0" ) {
                if ( $item["p.url"] !== NULL ) {
                    $item["m.url"] = $item["p.url"];
                }
                if ( empty( $item["m.title"] ) ) {
                    if ( $item["p.title"] === NULL ) {
                        $item["m.title"] = __( "deteled_page" );
                    } else {
                        $item["m.title"] = $item["p.title"];
                    }
                }
            }

            if ( ! empty( $cms["page"]["url"] ) && $item["m.url"] === $cms["page"]["url"] ) {
                $class = "active";
            } else {
                $class = "";
            }
            if ( $item["m.target_blank"] ) {
                $target_blank = " target=_blank";
            } else {
                $target_blank = "";
            }
            if ( $item["m.tag_title"] ) {
                $e_tag_title = htmlspecialchars( $item["m.tag_title"] );
                $tag_title = " title='{$e_tag_title}'";
            } else {
                $tag_title = "";
            }

            // submenu
            $sub = menu1( $item["m.mid"] );
            if ( ! empty( $sub ) ) {
                $class .= " has-sub-menu";
            }
            if ( ! empty( $class ) || ! empty( $item["m.class"] ) ) {
                $class = trim( "{$class} {$item['m.class']}" );
                $class = " class='{$class}'";
            }
            $html .= "<li{$class}><a href='{$item['m.url']}'{$target_blank}{$tag_title}>{$item['m.title']}</a>{$sub}</li>";
        }
    }

    $q = "SELECT * FROM menu WHERE mid=$pid";
    // If exists childs items
    if ( ! empty( $html ) and $res = mysqli_query( $cms["base"], $q ) and $menu = mysqli_fetch_assoc( $res ) ) {
        if ( ! empty( $menu["class"] ) && $menu["pid"] == 0 ) {
            $class = " class='{$menu['class']}'";
        } elseif ( $menu["pid"] != 0 ) {
            $class = " class='sub-menu'";
        } else {
            $class = "";
        }
        $html = "<ul{$class}>{$html}</ul>";
    }

    return $html;
}
