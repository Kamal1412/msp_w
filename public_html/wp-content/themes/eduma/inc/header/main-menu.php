<ul class="nav navbar-nav menu-main-menu">
	<?php
	if ( has_nav_menu( 'primary' ) ) {
		wp_nav_menu( array(
			'theme_location' => 'primary',
			'container'      => false,
			'items_wrap'     => '%3$s'
		) );
	} else {
		echo '<li><a class="thim-create-menu" href="' . esc_url( home_url( '/' ) ) . 'wp-admin/nav-menus.php?action=locations">' . esc_html__( 'Click here to select or create a menu', 'eduma' ) . '</a></li>';
	}
	//sidebar menu_right
	if ( is_active_sidebar( 'menu_right' ) ) {
		echo '<li class="menu-right"><ul>';
		dynamic_sidebar( 'menu_right' );
		echo '</ul></li>';
	}
	?>
</ul>
<!--</div>-->