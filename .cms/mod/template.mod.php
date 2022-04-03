<?php

$cms["modules"]["template.mod.php"] = array(
    "name"        => __( "Template", "template.mod.php" ),
    "description" => __( "Module for switch templates and configure template settings", "template.mod.php" ),
    "version"     => "22.04",
    "files"       => array(
        ".cms/mod/template.mod.php",
        ".cms/css/template.css",
        ".cms/js/template.js",
        ".cms/lang/ru_RU.UTF-8/template.mod.php",
        ".cms/lang/uk_UA.UTF-8/template.mod.php",
    ),
    "sort"        => 20,
);

// Return if module disabled
if ( !empty( $cms["config"]["template.mod.php"]["disabled"] ) ) {

    return;

} else {

    // Install
    if (empty($cms["config"]["template.mod.php"]["template"])) {
        $cms["config"]["template.mod.php"]["template"] = "mini";
    }
    if (empty($cms["config"]["template.mod.php"]["scripts"])) {
        $cms["config"]["template.mod.php"]["scripts"] = "";
    }

    $cms["template"] = $cms["config"]["template.mod.php"]["template"];

    if ( is_admin() ) {
        cms_add_function( "admin", "cms_template_admin" );
        cms_add_function( "admin_header",  "cms_template_admin_header" );
        cms_add_function( "api", "cms_template_load_settings", 5 );
        cms_add_function( "api", "cms_template_api" );
    }
    cms_add_function( "template", "cms_template_template" );
    cms_add_function( "echo", "cms_template_echo" );
    cms_add_function( "write", "cms_template_write" );

}

function cms_template_admin_header() {
    global $cms;
    echo "<link rel=stylesheet href='{$cms['base_path']}css/template.css'>";
    echo "<script src='{$cms['base_path']}js/template.js'></script>";
}

function cms_template_admin() {
    global $cms;

    // Save settings
    if ( ! empty( $_POST["save_template"] ) ) {
        $cms["config"]["template.mod.php"]["template"] = $_POST["template"];
        cms_save_config();
        cms_clear_cache();
        header( "Location: {$cms['config']['admin.mod.php']['admin_url']}" );
        exit;
    }
    if ( ! empty( $_POST["save_template_scripts"] ) ) {
        $cms["config"]["template.mod.php"]["scripts"] = $_POST["scripts"];
        cms_save_config();
        cms_clear_cache();
        header( "Location: {$cms['config']['admin.mod.php']['admin_url']}" );
        exit;
    }

    // Create menu item
    if ( empty( $cms["config"]["template.mod.php"]["menu"]["template"] ) ) {
        $cms["config"]["template.mod.php"]["menu"]["template"] = array(
            "title"    => "Template",
            "sort"     => 70,
            "class"    => "",
            "section"  => "Settings",
        );
        cms_save_config();
    }

    $options = "";
    foreach( glob( "{$cms['cms_dir']}/*/html.php" ) as $index ) {
        $template = preg_replace( "/.*\/([^\/]+)\/html\.php/", "$1", $index );
        if ( ! preg_match( "/^admin/", $template ) ) {
            if ( $template === $cms["config"]["template.mod.php"]["template"] ) {
                $selected = "selected";
            } else {
                $selected = "";
            }
            $options .= "<option value='{$template}' {$selected}>{$template}</option>";
        }
    }

    cms_template_load_settings();
    $template_files_title = "<div class=title>" . __( "Editable files for this template", "template.mod.php" ) . "</div>";
    $template_files = "";
    foreach( $cms["templates"][ $cms["config"]["template.mod.php"]["template"] ]["files"] as $file ) {
        $template_files .= "<div class=file>{$file}</div>";
    }
    if ( empty( $template_files ) ) {
        $template_files = "<div class=no-files>" . __( "No files for edit", "template.mod.php" ) . "</div>";
    }
    
    $tr_title   = __( "Help", "template.mod.php" );
    $tr_p1      = __( "You can create your own theme or choose from existing ones.", "template.mod.php" );
    $tr_current = __( "Current Template", "template.mod.php" );
    $tr_save    = __( "Save", "template.mod.php" );
    // Display settings
    $page = "
<div class=settings>
    <form method=post>
        <div>{$tr_current}</div>
        <select name=template autocomplete=off>
            {$options}
        </select>
        <input type=submit name=save_template value='{$tr_save}'>
    </form>
    <div class=template-manual>
        <div>{$tr_title}</div>
        <p>{$tr_p1}</p>
        <p><a href='https://coffee-cms.com/' target=_blank>https://coffee-cms.com/</a></p>
    </div>
    <div class=template-files>
        {$template_files_title}
        {$template_files}
    </div>
</div>
<div class='template-editor-bg hidden'>
    <div class=template-editor-grid>
        <div class=template-editor-header>
            <div class=close-template-button></div>
            <div class=save-template-button></div>
            <span class=template-editor-title></span>
        </div>
        <div class=template-editor>
            <textarea></textarea>
        </div>
    </div>
</div>
";

    $cms["admin_pages"]["template"] = $page;

    // Create menu item
    if ( empty( $cms["config"]["template.mod.php"]["menu"]["counters"] ) ) {
        $cms["config"]["template.mod.php"]["menu"]["counters"] = array(
            "title"    => "Counters",
            "sort"     => 80,
            "class"    => "",
            "section"  => "Settings",
        );
        cms_save_config();
    }

    $tr_title = __( "Scripts and styles in footer", "template.mod.php" );
    $content  = htmlspecialchars( $cms["config"]["template.mod.php"]["scripts"] );
    $tr_save  = __( "Save", "template.mod.php" );
    $page = "
<form method=post>
    <div>{$tr_title}</div>
    <textarea name=scripts rows=15>{$content}</textarea>
    <input type=submit name=save_template_scripts value='{$tr_save}'>
</form>";
    $cms["admin_pages"]["counters"] = $page;

}

// Load template settings
function cms_template_load_settings() {
    global $cms;
    $settings = "{$cms['cms_dir']}/{$cms['config']['template.mod.php']['template']}/settings.php";
    if ( file_exists( $settings ) ) {
        // ob_start();
        include( $settings );
        // $ts = ob_get_clean();
        // return $ts;
    }
}


function cms_template_api() {
    global $cms;
    if ( ! empty( $_POST["fn"] ) ) {
        
        switch ( $_POST["fn"] ) {

            case "get_template_file":
                $file = file_get_contents( $cms["site_dir"] . "/" . $_POST["file"] );
                if ( $file !== false ) {
                    $ok = "true";
                } else {
                    $ok = "false";
                }
                exit( json_encode( array(
                    "ok"   => $ok,
                    "file" => $file,
                ) ) );
            break;

            case "save_template_file":
                $file = file_put_contents( $cms["site_dir"] . "/" . $_POST["file"], $_POST["content"] );
                if ( $file !== false ) {
                    $ok    = "true";
                    $msg   = __( "Saved", "template.mod.php" );
                    $class = "info-success";
                } else {
                    $ok    = "false";
                    $msg   = __( "Save error", "template.mod.php" );
                    $class = "info-error";
                }
                cms_clear_cache();
                exit( json_encode( array(
                    "ok"         => $ok,
                    "info_text"  => $msg,
                    "info_class" => $class,
                    "info_time"  => 5000,
                ) ) );
            break;

        }
    }
}


function cms_template_template() {
    global $cms;
    if ( file_exists( $cms["cms_file"] ) && 
         is_file( $cms["cms_file"] ) && 
         substr( $cms["cms_file"], -4, 4 ) !== ".php" &&
         strpos( str_replace( $cms["cms_dir"], "", $cms["cms_file"] ), "/." ) === false ) {
        $cms["output"]["from"] = $cms["cms_file"];
        $cms["output"]["to"]   = $cms["site_dir"] . $cms["url"]["path"];
        $cms["status"]         = "200";
    } else {
        ob_start();
        include( "{$cms['cms_dir']}/{$cms['template']}/html.php" );
        $cms["output"] = ob_get_clean();
    }
}


function cms_template_echo() {
    global $cms;
    if ( is_array( $cms["output"] ) ) {
        $types = array(
            "css" => "text/css",
            "js" => "application/javascript",
            "svg" => "image/svg+xml",
        );
        $ext = preg_replace( "/.*\./", "", $cms["output"]["to"] );
        if ( isset( $types[$ext] ) ) {
            $mime = $types[$ext];
        } else {
            $mime = "";
        }
        header( "Content-Type: {$mime}" );
        header( "{$_SERVER['SERVER_PROTOCOL']} 200 OK"); // fix server 404
        cms_readfile( $cms["output"]["from"], false );
    } elseif ( $cms["status"] === "200" ) {
        header( "{$_SERVER['SERVER_PROTOCOL']} 200 OK"); // fix server 404
        echo $cms["output"];
    } elseif ( is_admin() ) {
        header( "{$_SERVER['SERVER_PROTOCOL']} 404 Not Found");
        echo $cms["output"];
    } else {
        header( "{$_SERVER['SERVER_PROTOCOL']} 404 Not Found");
        echo $cms["output"]; // fix empty page
    }
}


function cms_template_write() {
    global $cms;

    if ( $cms["status"] === "404" ) {
        return;
    }
    
    // write to disk
    umask( 0 );

    if ( is_array( $cms["output"] ) ) {
        
        $dirs = explode( "/", str_replace( $cms["site_dir"], "", $cms["output"]["to"] ) );
        $dir  = $cms["site_dir"];
        array_shift( $dirs );
        $file_name = array_pop( $dirs );
        foreach ( $dirs as $path ) {
            $dir .= "/" . $path;
            if ( ! file_exists( $dir ) ) {
                mkdir( $dir, 0777, false );
            } elseif( is_file( $dir ) ) {
                unlink( $dir );
                mkdir( $dir, 0777, false );
            }
        }

        if ( ! file_exists( $cms["output"]["to"] ) ) {
            copy( $cms["output"]["from"], $cms["output"]["to"] );
        }

    } else {

        // create dirs
        $dirs = explode( "/", $cms["url"]["path"] );
        $dir  = $cms["site_dir"];
        array_shift( $dirs );
        $file_name = array_pop( $dirs );
        foreach ( $dirs as $path ) {
            $dir .= "/" . $path;
            if ( ! file_exists( $dir ) ) {
                mkdir( $dir, 0777, false );
            } elseif( is_file( $dir ) ) {
                unlink( $dir );
                mkdir( $dir, 0777, false );
            }
        }

        if ( ! $file_name ) {
            $file = $dir . "/index.html";
        } else {
            $file = $dir . "/" . $file_name;
        }

        if ( ! file_exists( $file ) ) {
            file_put_contents( $file, $cms["output"] );
            chmod( $file, 0666 );
        }

    }
}


function cms_clear_cache() {
    global $cms;

    cms_do_stage( "clear_cache" );

    // array for delete dirs
    $dirs = array();

    // search all pages in database
    if ( ! empty( $cms["base"] ) ) {
        if ( $res = mysqli_query( $cms["base"], "SELECT url FROM pages" ) ) {
            while ( $page = mysqli_fetch_assoc( $res ) ) {
                $file = $cms["site_dir"] . $page["url"];
                if ( substr( $file, -1 ) === "/" ) {
                    $file  .= "index.html";
                } elseif ( is_dir( $file ) ) {
                    // for
                    // /parent/child
                    // /parent
                    $file  .= "/index.html";
                }
                if ( is_file( $file ) ) unlink( $file );
                // Add dirs to remove queue
                $dir = $page["url"];
                while ( $dir != "/" ) {
                    $dirs[] = $cms["site_dir"] .  $dir;
                    $dir    = dirname( $dir );
                }
            }
        }
    }

    // delete template files
    $queue[] = $cms["cms_dir"];
    while ( $cur = array_shift( $queue ) ) {
        if ( is_dir( $cur ) ) {
            // add current dir to array for delete
            array_push( $dirs, str_replace( ".cms/", "", $cur ) );
            // search children files and dirs
            $queue = array_merge( $queue, glob( $cur . "/*" ) );
        } else {
            $file = str_replace( ".cms/", "", $cur );
            if ( is_file( $file ) ) unlink( $file );
        }
    }

    // delete dirs
    rsort( $dirs );
    foreach ( $dirs as $dir ) {
        if ( is_dir( $dir ) && $dir !== $cms["cms_dir"] && $dir !== $cms["site_dir"] ) {
            @rmdir( $dir );
        }
    }

}
