<!doctype html>
<html lang="<?php echo $cms['config']['lang']; ?>">
<head>
<meta charset="utf-8">
<title><?php if (!empty($cms['page']['seo_title'])) { print $cms['page']['seo_title']; } else { print @$cms['page']['title']; } ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<link rel="canonical" href="<?php @print $cms['url']['scheme'].'://'.$cms['url']['host'].$cms['page']['url']; ?>">
<?php if (!empty($cms['page']['description'])) { echo '<meta name="description" content="'.htmlspecialchars($cms['page']['description']).'">'; } ?>
<?php if ( file_exists( "favicon.png" ) ) {
    $mini = $cms["config"]["template.mod.php"]["template"];
    echo "<link rel='shortcut icon' href='/$mini/favicon.png' type='image/png'>"; }
?>
<style>
<?php include(__DIR__.'/style.css'); ?>

</style>
</head>

<body id="id-<?php echo $cms["page"]["id"]; ?>">
	<header>
		<div class="header-content">
			<div class="lrtop">
			    <div class="button-mobimenu-container">
					<div class="menu-icon">
						<span class="line-1"></span>
						<span class="line-2"></span>
					</div>
				</div>
			</div>

			<div class="logo">
				<a href="/">&lt;/mini&gt;</a>
			</div>

			<div class="lrtop">
			    <div class="icons-container">
    			    <div class="phone-number-container">
    			        <a href="tel:+77777777777">+7 777 777 7777</a>
    			    </div>
    				    
    				<div class="icon-box">
    					<a href="tel:+77777777777"><svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="phone" class="svg-inline--fa fa-phone fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M493.4 24.6l-104-24c-11.3-2.6-22.9 3.3-27.5 13.9l-48 112c-4.2 9.8-1.4 21.3 6.9 28l60.6 49.6c-36 76.7-98.9 140.5-177.2 177.2l-49.6-60.6c-6.8-8.3-18.2-11.1-28-6.9l-112 48C3.9 366.5-2 378.1.6 389.4l24 104C27.1 504.2 36.7 512 48 512c256.1 0 464-207.5 464-464 0-11.2-7.7-20.9-18.6-23.4z"></path></svg></a>
    				</div>
        				
    				<div class="icon-whatsapp-box">
    					<a target="_blank" href="https://api.whatsapp.com/send?phone=+77777777777"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 187.59 187.49"><defs><style>.a-whatsapp{fill:#05bf3c;}.b-whatsapp{fill:#fff;}</style></defs><path class="a-whatsapp" d="M453.6,322.7a91.53,91.53,0,0,0-156.3,64.71,92,92,0,0,0,10.77,43.17l-14.45,52.76,53.93-14.16a92.13,92.13,0,0,0,106.36-17,90.43,90.43,0,0,0,27.3-64.75C481.21,363.54,471.4,340.54,453.6,322.7Z" transform="translate(-293.62 -295.85)"></path><path class="b-whatsapp" d="M448.76,327.48a84.58,84.58,0,0,0-133.08,102l-12,43.8,44.83-11.77a84.33,84.33,0,0,0,40.41,10.28h0c46.58,0,85.36-37.93,85.36-84.55C474.32,364.69,464.72,343.47,448.76,327.48ZM389,457.59a70.15,70.15,0,0,1-35.8-9.79l-2.55-1.53-26.59,7,7.09-25.94-1.68-2.67a70.29,70.29,0,0,1,109.24-87.07c13.25,13.29,21.4,30.93,21.36,49.71C460,426.05,427.7,457.59,389,457.59ZM427.51,405c-2.1-1.07-12.5-6.17-14.44-6.86s-3.35-1.06-4.76,1.07-5.45,6.86-6.7,8.3-2.48,1.6-4.57.54c-12.42-6.21-20.57-11.09-28.76-25.14-2.17-3.73,2.17-3.47,6.21-11.54a4,4,0,0,0-.19-3.7c-.54-1.06-4.76-11.46-6.52-15.69s-3.46-3.54-4.76-3.62-2.62-.07-4-.07a7.83,7.83,0,0,0-5.64,2.62C351.41,353,346,358.1,346,368.5s7.58,20.45,8.61,21.86,14.89,22.74,36.11,31.92c13.4,5.79,18.66,6.28,25.36,5.29,4.08-.61,12.5-5.1,14.25-10.05s1.75-9.18,1.22-10.06S429.6,406,427.51,405Z" transform="translate(-293.62 -295.85)"></path></svg></a>
    				</div>

					<div class="icon-tg-box">
    					<a target="_blank" href="https://t.me/+77777777777"><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 1000 1000"><defs><style>.a-tg{fill:url(#a);}.b-tg{fill:#fff;fill-rule:evenodd;}</style><linearGradient id="a" x1="111.39" y1="888.11" x2="111.39" y2="887.12" gradientTransform="matrix(1000, 0, 0, -1000, -110889, 888111)" gradientUnits="userSpaceOnUse"><stop offset="0" stop-color="#2aabee"/><stop offset="1" stop-color="#229ed9"/></linearGradient></defs><circle class="a-tg" cx="500" cy="500" r="500"/><path class="b-tg" d="M226.33,494.72Q445,399.47,517.92,369.12C656.77,311.37,685.63,301.33,704.43,301c4.14-.07,13.39,1,19.38,5.82,5.05,4.1,6.45,9.65,7.11,13.54a87.82,87.82,0,0,1,.84,19.68C724.23,419.1,691.68,611,675.11,699.52c-7,37.46-20.81,50-34.17,51.26-29,2.67-51.08-19.19-79.21-37.63-44-28.84-68.86-46.8-111.58-74.95-49.37-32.53-17.36-50.41,10.77-79.63,7.36-7.65,135.3-124,137.77-134.57.31-1.32.6-6.24-2.33-8.84s-7.23-1.71-10.35-1q-6.62,1.5-210.9,139.4-29.94,20.55-54.22,20c-17.86-.39-52.2-10.1-77.73-18.4-31.31-10.18-56.2-15.56-54-32.84Q190.83,508.84,226.33,494.72Z"/></svg></a>
    				</div>
				</div>
			</div>
		</div>
	</header>

	<nav>
		<?php echo menu( "header" ); ?>
	</nav>
	
<?php
    if ( $cms['status'] === '404' ) {
        if ( ! empty( $cms['page']['text'] ) && is_admin() ) {
            $f = __DIR__ . '/' . $cms["template_prefix"] . $cms['page']['tpl'] . $cms["template_suffix"];
            if ( file_exists( $f ) ) { include( $f ); }
        } else {
            $f404 = __DIR__ . '/404.' . $cms['config']['locale'] . $cms["template_suffix"];
			if ( file_exists( $f404 ) ) {
				include( $f404 );
			} else {
				include( __DIR__.'/404.en_US.UTF-8.php' );
			}
        }
    } else {
		$f = __DIR__ . '/' . $cms["template_prefix"] . $cms['page']['tpl'] . $cms["template_suffix"];
        if ( file_exists( $f ) ) { include( $f ); }
    }
?>
	
	<footer>
		<div class="minmax-footer">
			<div class="left-footer">
			    <?php echo menu( "footer" ); ?>
			    <div class="copyright">Powered by <a target="_blank" href="https://coffee-cms.ru/">Coffee CMS</a></div>
			</div>
			<div class="right-footer">
			    <div><!--Your Code ;)--></div>
			</div>
		</div>
	</footer>

    <aside>
		<?php echo menu( "side" ); ?>
	</aside>
	<div class="bg-reset"></div>
    
    <script>
		document.addEventListener( "DOMContentLoaded", function( event ) {
			let toggle_btns = document.querySelectorAll( ".button-mobimenu-container, .bg-reset, aside li" );
			toggle_btns.forEach( function( toggle_btn ) {
				toggle_btn.addEventListener( "click", function() {
            		document.body.classList.toggle( "mobile-menu-open" );
        		} );
			} );
		} );
    </script>
	<?php echo $cms["config"]["template.mod.php"]["scripts"]; ?>
</body>
</html>