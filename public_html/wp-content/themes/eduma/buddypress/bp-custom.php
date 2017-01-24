<?php
define ( 'BP_AVATAR_THUMB_WIDTH', 70 );
define ( 'BP_AVATAR_THUMB_HEIGHT', 70 );
define ( 'BP_AVATAR_FULL_WIDTH', 150 );
define ( 'BP_AVATAR_FULL_HEIGHT', 150 );

function thim_buddypress_search_form() {
	$query_arg = bp_core_get_component_search_query_arg( 'members' );

	if ( ! empty( $_REQUEST[ $query_arg ] ) ) {
		$search_value = stripslashes( $_REQUEST[ $query_arg ] );
	} else {
		$search_value = bp_get_search_default_text( 'members' );
	}

	$search_form_html = '<form action="" method="get" id="search-members-form">
		<input type="text" name="' . esc_attr( $query_arg ) . '" id="members_search" placeholder="'. esc_attr( $search_value ) .'" />
		<input type="submit" id="members_search_submit" name="members_search_submit" value="' . esc_attr__( 'Search', 'eduma' ) . '" />
	</form>';

	return $search_form_html;
}
add_filter( 'bp_directory_members_search_form', 'thim_buddypress_search_form' );


function bp_custom_signup_fields( $user_id) {
    #var_dump($user_id); print_r($_POST);exit;
    $profile_field_ids = explode( ',', $_POST['signup_profile_field_ids'] );
    foreach($profile_field_ids as $field_id ) {
        if (isset( $_POST['field_' . $field_id] ) ) {
             if ( !empty( $_POST['field_' . $field_id] ) )
                    xprofile_set_field_data( $field_id, $user_id, $_POST['field_' . $field_id] );
        }
    }

    return $user_id;
}

add_action( 'user_register', 'bp_custom_signup_fields' );






class BP_Custom_User_Ids {
 
    private $custom_ids = array();
 
    public function __construct() {
 
        $this->custom_ids = $this->get_custom_ids();
         
		//if(!empty($this->custom_ids)){
			add_action( 'bp_pre_user_query_construct',  array( $this, 'custom_members_query' ), 1, 1 );
			add_filter( 'bp_get_total_member_count',    array( $this, 'custom_members_count' ), 1, 1 );
		//}
	 
    }
     
    private function get_custom_ids() {
        global $wpdb;
        $place = explode(',', $_REQUEST['place']);
		$place = array_map('trim', $place);
		 
		
        if($_REQUEST['search'] == 'SEARCH'){
               // collection based on an xprofile field
                        $custom_id_course = $wpdb->get_col("SELECT user_id FROM {$wpdb->prefix}bp_xprofile_data WHERE field_id = 3 AND value LIKE '%{$_REQUEST['s']}%' ");
	 
			$custom_id_city = $wpdb->get_col("SELECT user_id FROM {$wpdb->prefix}bp_xprofile_data WHERE field_id = 2 AND value LIKE '%{$place[0]}%'");
			 
			$custom_id_country = $wpdb->get_col("SELECT user_id FROM {$wpdb->prefix}bp_xprofile_data WHERE field_id = 5 AND value LIKE '%{$place[1]}%' ");
			
			foreach($custom_id_course as $v){
				if(in_array($v, $custom_id_city) && in_array($v, $custom_id_country)){
					$final_custom_ids[] = $v;
				}
			}

 
            return $final_custom_ids;
        }else if($_REQUEST['search'] == 'home_search'){
			$final_custom_ids = $wpdb->get_col("SELECT user_id FROM {$wpdb->prefix}bp_xprofile_data WHERE field_id = 3 AND value LIKE '%{$_REQUEST['course']}%' ");
 			return $final_custom_ids;
			
 	}else if($_REQUEST['search'] == 'filter_search' && $_REQUEST['location'] && $_REQUEST['course'] ){
            $place = explode(',', $_REQUEST['location']);
	    $place = array_map('trim', $place);
            
            $custom_id_course = $wpdb->get_col("SELECT user_id FROM {$wpdb->prefix}bp_xprofile_data WHERE field_id = 3 AND value LIKE '%{$_REQUEST['course']}%' ");
	 
            $custom_id_city = $wpdb->get_col("SELECT user_id FROM {$wpdb->prefix}bp_xprofile_data WHERE field_id = 2 AND value LIKE '%{$place[0]}%'");

            $custom_id_country = $wpdb->get_col("SELECT user_id FROM {$wpdb->prefix}bp_xprofile_data WHERE field_id = 5 AND value LIKE '%{$place[1]}%' ");
            foreach($custom_id_course as $v){
                    if(in_array($v, $custom_id_city) && in_array($v, $custom_id_country)){
                            $final_custom_ids[] = $v;
                    }
            }
            $where_study_hours  = (($_REQUEST['filter_am'] && $_REQUEST['filter_pm']) || $_REQUEST['filter_no_pref']) ? "(field_id = 800 AND value LIKE '%AM%') OR  (field_id = 800 AND value LIKE '%PM%')": ( $_REQUEST['filter_pm']  ? "field_id = 800 AND value LIKE '%PM%'" : ($_REQUEST['filter_am']  ? "field_id = 800 AND value LIKE '%AM%'": "") );
            
            if($where_study_hours){
                $custom_id_study_hours = $wpdb->get_col("SELECT user_id FROM {$wpdb->prefix}bp_xprofile_data WHERE  $where_study_hours ");
                foreach($final_custom_ids  as $v){
                    if(in_array($v, $custom_id_study_hours)){
                            $study_hours_custom_ids[] = $v;
                    }
                    $final_custom_ids = array();
                    $final_custom_ids = $study_hours_custom_ids;
                 }
            }
            return $final_custom_ids;
        }
		return null;
    }   
     
    function custom_members_query( $query_array ) {
         $query_array->query_vars['exclude'] = $this->custom_ids;
		 $query_array->query_vars['user_ids'] = $this->custom_ids;
                 
        //in case there are other items like widgets using the members loop on the members page
        remove_action( 'bp_pre_user_query_construct', array( $this, 'custom_members_query' ), 1, 1 );
 
    }   
     
    function custom_members_count ( $count ) {
 
        $new_count = count( $this->custom_ids );
        return $count - $new_count; 
 
    }
}
 
function custom_user_ids( ) { 
     
	if($_REQUEST['search'] == 'SEARCH' || $_REQUEST['search'] == 'home_search' || $_REQUEST['search'] == 'filter_search'){
             new BP_Custom_User_Ids (); 
	}
}
add_action( 'bp_before_directory_members', 'custom_user_ids' );