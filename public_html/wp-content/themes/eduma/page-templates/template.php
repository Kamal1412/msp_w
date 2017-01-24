<?php
/**
 * Template Name: Full-Width page member template
 *
 **/
 

get_header();?>

<?php while (have_posts()) : the_post(); ?>

    <?php get_template_part('content', 'page'); ?>

    <?php
    // If comments are open or we have at least one comment, load up the comment template
    if (comments_open() || get_comments_number()) :
        comments_template();
    endif;
    ?>

<?php endwhile; // end of the loop.  ?>

<style>
.col-sm-9{width:100%;}
#sidebar{display:none;}
</style>
<?php wp_footer(); ?>