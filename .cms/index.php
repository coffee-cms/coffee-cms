<?php

$cms["cms_dir"] = dirname( __FILE__ ); // PHP 5.2 compability
// fix for windows
if ( DIRECTORY_SEPARATOR === "\\" ) {
    $cms["cms_dir"] = strtolower( str_replace( "\\", "/", $cms["cms_dir"] ) );
}
$cms["site_dir"]  = dirname( $cms["cms_dir"] );
if ( empty( $_SERVER["HTTPS"] ) ) {
    $protocol = "http";
} else {
    $protocol = "https";
}
$cms["url"]    = parse_url( "{$protocol}://{$_SERVER['SERVER_NAME']}{$_SERVER['REQUEST_URI']}" );
$cms["status"] = "404";
$cms["tr"]     = array(); // transliteration array

// fix DocumentRoot
$_SERVER["DOCUMENT_ROOT"] = rtrim( $_SERVER["DOCUMENT_ROOT"], "/\\" );
if ( DIRECTORY_SEPARATOR === "\\" ) {
    $_SERVER["DOCUMENT_ROOT"] = strtolower( str_replace( "\\", "/", $_SERVER["DOCUMENT_ROOT"] ) );
}

// https://coffee-cms.com/base_path/
//                       ^         ^
$cms["base_path"] = str_replace( $_SERVER["DOCUMENT_ROOT"], "", $cms["site_dir"] ) . "/";

$esc  = str_replace( "/", "\\/", $cms["base_path"] ); // escape / for regexp
// file in .cms/ directory (for prevent sql query in pages module)
$cms["cms_file"] = $cms["cms_dir"] . "/" . preg_replace( "/^{$esc}/", "", $cms["url"]["path"] );


// Load config.php
$cms["config_file"] = "{$cms['cms_dir']}/config.php";
if ( file_exists( $cms["config_file"] ) ) {
    $config_file = fopen( $cms["config_file"], "r" );
    flock( $config_file, LOCK_SH );
    include( $cms["config_file"] );
    flock( $config_file, LOCK_UN );
}

// Set locale
if ( ! empty( $cms["config"]["locale"] ) ) {
    setlocale( LC_ALL, $cms["config"]["locale"] );
}
// Set timezone
if ( ! empty( $cms["config"]["timezone"] ) ) {
    date_default_timezone_set ( $cms["config"]["timezone"] );
}
    
// Init Stages
$cms["stage"]  = "query"; // Default Stage
$cms["stages"] = array(
    "query"    => array( "disabled" => false, "next" => "template"),
    "template" => array( "disabled" => false, "next" => "echo"    ),
    "echo"     => array( "disabled" => false, "next" => "write"   ),
    "write"    => array( "disabled" => false, "next" => ""        ),
    "admin"    => array( "disabled" => false, "next" => "template"),
    "api"      => array( "disabled" => false, "next" => ""        ),
    "cron"     => array( "disabled" => false, "next" => ""        ),
);

// route array
$cms["urls"] = array();

// Load functions files
foreach( glob( "*.fn.php" ) as $fn_file ) {
    include_once( $fn_file );
}

// Load modules
foreach( glob( "mod/*.mod.php" ) as $module ) {
    include_once( $module );
}

// select route
krsort( $cms["urls"] );
foreach( $cms["urls"] as $url => $stage ) {
    // Escape / for regexp
    $url = preg_replace( "/\//", "\\/", $url );
    if ( preg_match( "/{$url}/", $cms["url"]["path"], $cms["url"]["match"] ) ) {
        $cms["stage"] = $stage;
        break;
    }
}

// RUN
while ( ! empty( $cms["stage"] ) ) {
    cms_do_stage( $cms["stage"] );
    $cms["stage"] = $cms["stages"][ $cms["stage"] ]["next"];
}

// Сброс накопленной отладочной информации в файл
if ( ! empty( $cms["debug"] ) ) {
    $debug_file = $cms["cms_dir"] . "/debug.log.php";
    if ( file_exists( $debug_file ) ) {
        $new_debug = $cms["debug"];
        include( $debug_file );
        $cms["debug"] = array_merge( $cms["debug"], $new_debug );
    }
    file_put_contents( $debug_file, '<?php
$cms["debug"] = ' . var_export( $cms["debug"], true) . ";\n", LOCK_EX );
}