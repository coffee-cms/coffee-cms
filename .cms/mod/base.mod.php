<?php

$cms["modules"]["base.mod.php"] = array(
    "name" => __( "Data Base", "base.mod.php" ),
    "description" => __( "Data Base Module", "base.mod.php" ),
    "version" => "22.04",
    "files" => array(
        ".cms/css/base.css",
        ".cms/mod/base.mod.php",
        ".cms/lang/ru_RU.UTF-8/base.mod.php",
        ".cms/lang/uk_UA.UTF-8/base.mod.php",
    ),
    "sort" => 10,
);

// Return if module disabled
if ( ! empty( $cms["config"]["base.mod.php"]["disabled"] ) ) {

    return;

} else {

    // Install
    if ( empty( $cms["config"]["base.mod.php"]["user"] ) ) {
        $cms["config"]["base.mod.php"]["host"]     = "localhost";
        $cms["config"]["base.mod.php"]["port"]     = "3306";
        $cms["config"]["base.mod.php"]["disabled"] = false;
    }

    if ( is_admin() ) {
        cms_add_function( "admin", "cms_base_admin" );
        cms_add_function( "admin_header",  "cms_base_admin_header" );
        cms_add_function( "api", "cms_base_connect", 5 );
    }
    cms_add_function( "query", "cms_base_connect", 5 );

}

function cms_base_connect() {
    global $cms;
    // Return if module disabled
    if ( ! empty( $cms["config"]["base.mod.php"]["disabled"] ) ) {
        return;
    }

    if ( ! empty( $cms["config"]["base.mod.php"]["user"] ) ) {
        // return false on failure
        mysqli_report( MYSQLI_REPORT_OFF ); // PHP >= 8.1 FIX https://php.watch/versions/8.1/mysqli-error-mode
        $cms["base"] = mysqli_connect(
            $cms["config"]["base.mod.php"]["host"],
            $cms["config"]["base.mod.php"]["user"],
            $cms["config"]["base.mod.php"]["password"],
            $cms["config"]["base.mod.php"]["base"],
            $cms["config"]["base.mod.php"]["port"]
        );
    } else {
        return false;
    }

    if ( $cms["base"] === false ) {
        return false;
    } else {
        mysqli_query( $cms["base"], "SET NAMES UTF8;" );
        return true;
    }
}

function cms_base_admin_header() {
    global $cms;
    echo "<link rel=stylesheet href='{$cms['base_path']}css/base.css'>";
}

function cms_base_admin() {
    global $cms;

    // Save settings
    if ( ! empty( $_POST["save_base_config"] ) ) {
        if ( $_POST["save_base_config"] === __( "Create", "base.mod.php" ) ) {
            mysqli_report( MYSQLI_REPORT_OFF ); // PHP >= 8.1 FIX https://php.watch/versions/8.1/mysqli-error-mode
            $base = mysqli_connect( $_POST["host"], $_POST["admin_login"], $_POST["admin_password"], "mysql", $_POST["port"] );
            if ( $base ) {
                mysqli_query( $base, "CREATE DATABASE IF NOT EXISTS {$_POST['base']};" );
                mysqli_query( $base, "CREATE USER IF NOT EXISTS '{$_POST['user']}'@'localhost' IDENTIFIED BY '{$_POST['password']}';" );
                mysqli_query( $base, "GRANT ALL PRIVILEGES ON {$_POST['base']}.* TO '{$_POST['user']}'@'localhost';" );
                mysqli_query( $base, "FLUSH PRIVILEGES;" );
            }
        }

        $cms["config"]["base.mod.php"] = array(
            "host" => $_POST["host"],
            "port" => $_POST["port"],
            "base" => $_POST["base"],
            "user" => $_POST["user"],
            "password" => $_POST["password"],
        );
        cms_save_config();
        if ( cms_base_connect() ) {
            cms_do_stage( "create_tables" );
        }
        header( "Location: {$cms['config']['admin.mod.php']['admin_url']}" );
        exit;
    }

    $tr_title = __( "Setting up a database connection", "base.mod.php" );
    $tr_text  = __( 'You can create a database directly from here if you have DBMS administrator rights. To do this, fill in all the fields and in the "Create Database" section, specify the username and password of the DBMS administrator and click the Create button.', "base.mod.php" );
    $tr_host         = __( "Host", "base.mod.php" );
    $tr_port         = __( "Port", "base.mod.php" );
    $tr_base         = __( "Base", "base.mod.php" );
    $tr_user         = __( "User", "base.mod.php" );
    $tr_pass         = __( "Password", "base.mod.php" );
    $tr_save         = __( "Save" );
    $tr_create_db    = __( "Create database:", "base.mod.php" );
    $tr_admin_login  = __( "DBMS Admin", "base.mod.php" );
    $tr_admin_passwd = __( "DBMS Admin Password", "base.mod.php" );
    $tr_create_btn   = __( "Create", "base.mod.php" );

    @$page = "
<div class=db-manual>
    <div>{$tr_title}</div>
    <p>{$tr_text}</p>
</div>

<div class=db-settings>
    <form method=post>
        <div>
            <div>{$tr_host}</div>
            <input name=host type=text value='{$cms['config']['base.mod.php']['host']}' placeholder=localhost>
        </div>
        <div>
            <div>{$tr_port}</div>
            <input name=port type=text value='{$cms['config']['base.mod.php']['port']}' placeholder=3306>
        </div>
        <div>
            <div>{$tr_base}</div>
            <input name=base type=text value='{$cms['config']['base.mod.php']['base']}'>
        </div>
        <div>
            <div>{$tr_user}</div>
            <input name=user type=text value='{$cms['config']['base.mod.php']['user']}'>
        </div>
        <div>
            <div>{$tr_pass}</div>
            <input name=password type=password value='{$cms['config']['base.mod.php']['password']}'>
            <div class=password-eye></div>
        </div>
        <div></div>
        <input type=submit name=save_base_config value='{$tr_save}'>

        <div></div>
        <div></div>
        <div class=create>{$tr_create_db}</div class=more>
        <div>
            <div>{$tr_admin_login}</div>
            <input name=admin_login type=text value>
        </div>
        <div>
            <div>{$tr_admin_passwd}</div>
            <input name=admin_password type=password value>
            <div class=password-eye></div>
        </div>
        <div></div>
        <input type=submit name=save_base_config value='{$tr_create_btn}'>
    </form>
</div>
    ";

    // Create menu item if not exists
    if ( empty( $cms["config"]["base.mod.php"]["menu"]["base"] ) ) {
        $cms["config"]["base.mod.php"]["menu"]["base"] = array(
            "title"    => "Data Base",
            "sort"     => 20,
            "class"    => "",
            "section"  => "Settings",
        );
        cms_save_config();
    }

    if ( cms_base_connect() === false ) {
        $cms["config"]["base.mod.php"]["menu"]["base"]["class"] = "red";
    }

    $cms["admin_pages"]["base"] = $page;
}
