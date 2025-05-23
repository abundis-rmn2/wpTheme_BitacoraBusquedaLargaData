<!DOCTYPE html>
<!--[if IE 7]><html class="ie ie7" <?php language_attributes(); ?>><![endif]-->
<!--[if IE 8]><html class="ie ie8" <?php language_attributes(); ?>><![endif]-->
<!--[if !(IE 7) | !(IE 8)  ]><!--><html <?php language_attributes(); ?>><!--<![endif]-->
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>" />
<meta name="viewport" content="width=device-width,initial-scale=1" />

<title><?php wp_title( '|', true, 'right' ); ?> <?php bloginfo( 'sitename' ) ?> | <?php bloginfo( 'description' ); ?></title>

<link rel="profile" href="http://gmpg.org/xfn/11" />
<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>" />

<!--[if lt IE 9]>
<script src="<?php echo get_template_directory_uri(); ?>/js/html5.js" type="text/javascript"></script>
<![endif]-->

<?php wp_head(); ?>
<link href="<?php bloginfo( "template_url" ) ?>/css/main.css" rel="stylesheet" type="text/css">

</head>
<body <?php body_class(); ?>>
<div class="container">
<header>

	<div class="wrap">
	
		<div class="logo">
			<a href="/" title="<?php echo esc_attr( get_bloginfo( 'name', 'display' ) ); ?>" rel="home">
				<?php 
				$custom_logo_id = get_option('theme_logo');
				$custom_logo_url = $custom_logo_id ? wp_get_attachment_url($custom_logo_id) : '';
				if ($custom_logo_url): ?>
					<img src="<?php echo esc_url($custom_logo_url); ?>" alt="<?php bloginfo( 'name' ); ?>" style="max-width: 200px; height: auto;">
				<?php else: ?>
					<img src="<?php bloginfo( "template_url" ) ?>/img/logo.png" alt="<?php bloginfo( 'name' ); ?>" style="max-width: 200px; height: auto;">
				<?php endif; ?>
			</a>
		</div>

		<div class="header-widget-area">
			<?php if ( is_active_sidebar( 'header-widget' ) ) : ?>
				<?php dynamic_sidebar( 'header-widget' ); ?>
			<?php endif; ?>
		</div>

	</div>

	<nav>
		<button class="menu-toggle">Show/hide Menu</button>
		<?php wp_nav_menu( array( 'theme_location' => 'menu_principal', 'menu_class' => 'nav-menu' ) ); ?>
	</nav>
	
</header>

<section class="content">
	<a name="content"></a>
	<p>header -> template:</p>
	<?php echo basename( get_page_template() ); ?>
