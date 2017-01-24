<?php

$title = $description = $placeholder = '';
if($instance['title'] && $instance['title'] <> ''){
	$title = $instance['title'];
}
if($instance['description'] && $instance['description'] <> ''){
	$description = $instance['description'];
}
if($instance['placeholder'] && $instance['placeholder'] <> ''){
	$placeholder = $instance['placeholder'];
}

?>
<?php
if('' != $title){
	echo '<h3 class="search-course-title">'.esc_attr($title).'</h3>';
}
if('' != $description){
	echo '<div class="search-course-description">'.esc_attr($description).'</div>';
}
?>
<div class="courses-searching">
	<!-- <form method="get" action="<?php echo esc_url( get_post_type_archive_link('lp_course') ); ?>"> -->
<?php
 //add_action( 'bbp_init', 'bps_activate');
//do_action ('bps_display_form'); 
/*
$F = bps_escaped_form_data ();
$F->id = 6920;
 $F->location ='bps_directory';
 $F->action = 'http://localhost:8080/msp/members/';
	$toggle_id = 'bps_toggle'. $F->id;
	$form_id = 'bps_'. $F->location. $F->id;
        echo "<form action='$F->action' method='post' id='$form_id' class='standard-form'>\n";
        echo "<input type='hidden' name='bp_profile_search' value='$F->id'>\n";*/
?>	
<form action='http://localhost:8080/msp/members/' method='post' id='' class='standard-form'> 
    
            <input type="text" value="" name="s" placeholder="What exam are you preparing for?" class="thim-s form-control courses-search-input" autocomplete="off" />
		<input type="hidden" value="course" name="ref" />
		 <input type="text" value="" name="place" placeholder="Where do you live?" class="thim-s form-control places-search-input" autocomplete="off" />
                <button type="submit"><i class="fa fa-search"></i></button>
		<span class="widget-search-close"></span>
	</form>
	<ul class="courses-list-search list-unstyled"></ul>
        <ul class="places-list-search list-unstyled"></ul>
</div>