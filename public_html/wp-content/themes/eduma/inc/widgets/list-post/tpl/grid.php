<?php
global $post;
$number_posts = 3;
if ( $instance['number_posts'] != '' ) {
	$number_posts = $instance['number_posts'];
}
$items_vertical = ( !empty( $instance['item_vertical'] ) && $instance['item_vertical'] > 0 ) ? $instance['item_vertical'] : 0;

$style = '';
if ( $instance['style'] != '' ) {
	$style = $instance['style'];
}
$image_size = 'none';
if ( $instance['image_size'] && $instance['image_size'] <> 'none' ) {
	$image_size = $instance['image_size'];
}
$query_args = array(
	'post_type'           => 'post',
	'posts_per_page'      => $number_posts,
	'order'               => ( 'asc' == $instance['order'] ) ? 'asc' : 'desc',
	'ignore_sticky_posts' => true
);
if ( $instance['cat_id'] && $instance['cat_id'] != 'all' ) {
	$query_args['cat'] = $instance['cat_id'];
}
switch ( $instance['orderby'] ) {
	case 'recent' :
		$query_args['orderby'] = 'post_date';
		break;
	case 'title' :
		$query_args['orderby'] = 'post_title';
		break;
	case 'popular' :
		$query_args['orderby'] = 'comment_count';
		break;
	default : //random
		$query_args['orderby'] = 'rand';
}

$posts_display = new WP_Query( $query_args );
$box_class     = $items_vertical < $number_posts ? ' has-horizontal' : '';
$box_class .= $items_vertical > 0 ? ' has-vertical' : '';

if ( $posts_display->have_posts() ) {
	if ( $instance['title'] ) {
		echo ent2ncr( $args['before_title'] . $instance['title'] . $args['after_title'] );
	}
	$index = 1;
	echo '<div class="thim-grid-posts' . $box_class . '">';
	while ( $posts_display->have_posts() ) {
		$posts_display->the_post();
		$class = 'item-post';
		if ( $index == 1 && ( $items_vertical < $number_posts ) ) {
			//Open div grid-horizontal
			echo '<div class="grid-horizontal">';
		}
		if ( ( ( $index - 1 == $number_posts - $items_vertical ) && $items_vertical > 0 ) || ( $items_vertical >= $number_posts && $index == 1 ) ) {
			//Open div grid-vertical
			echo '<div class="grid-vertical">';
		}
		?>
		<div <?php post_class( $class ); ?>>
			<?php
			if ( $image_size <> 'none' && has_post_thumbnail() ) {
				echo '<div class="article-image">';
				echo the_post_thumbnail( $image_size );
				echo '</div>';
			}
			echo '<div class="article-wrapper">';
			echo '<div class="date">' . get_the_date( 'F d, Y' ) . '</div>';
			echo '<h5 class="title"><a href="' . esc_url( get_permalink( get_the_ID() ) ) . '" class="article-title">' . esc_attr( get_the_title() ) . '</a></h5>';
			if ( $instance['show_description'] && $instance['show_description'] <> 'no' ) {
				echo '<div class="desc">' . thim_excerpt( '13' ) . '</div>';
			}
			echo '<a class="read-more" href="' . get_the_permalink() . '" >' . esc_html__( 'Read More', 'eduma' ) . '<i class="fa fa-long-arrow-right"></i></a>';
			echo '</div>';
			?>
		</div>
		<?php
		if ( $index == $number_posts - $items_vertical ) {
			//Close div grid-horizontal
			echo '</div>';
		}
		if ( ( $index - 1 == $number_posts - $items_vertical ) && $items_vertical > 0 ) {
			//Close div grid-vertical
			echo '</div>';
		}

		$index ++;
	}
	//Link All Posts
	if ( $instance['link'] <> '' ) {
		echo '<div class="link_read_more"><a href="' . $instance['link'] . '">' . $instance['text_link'] . '</a></div>';
	}
	echo '</div>';

}
wp_reset_postdata();

?>