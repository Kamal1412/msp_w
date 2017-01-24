<?php
/**
 * BuddyPress - Members
 *
 * @package BuddyPress
 * @subpackage bp-legacy
 */

/**
 * Fires at the top of the members directory template file.
 *
 * @since 1.5.0
 */
do_action( 'bp_before_directory_members_page' ); ?>

<div id="buddypress">
	<?php

	/**
	 * Fires before the display of the members.
	 *
	 * @since 1.1.0
	 */
	do_action( 'bp_before_directory_members' ); ?>

	 <div class="buddypress-left">
		<div class="item-list-tabs nav-sidebar" role="navigation">
			<!-- <ul>
				<li class="selected" id="members-all"><a href="<?php bp_members_directory_permalink(); ?>"><?php printf( __( 'All Members %s', 'eduma' ), '<span>' . bp_get_total_member_count() . '</span>' ); ?></a></li>

				<?php if ( is_user_logged_in() && bp_is_active( 'friends' ) && bp_get_total_friend_count( bp_loggedin_user_id() ) ) : ?>
					<li id="members-personal"><a href="<?php echo bp_loggedin_user_domain() . bp_get_friends_slug() . '/my-friends/'; ?>"><?php printf( __( 'My Friends %s', 'eduma' ), '<span>' . bp_get_total_friend_count( bp_loggedin_user_id() ) . '</span>' ); ?></a></li>
				<?php endif; ?>

				
			</ul> -->
			<nav class="nav-sidebar">
<form method="post" action="http://mspstg.mystudypartner.com/members/" name="filters">	 

<input type="hidden" id="location" name="location" value="<?php   echo (isset($_REQUEST["location"])) ? $_REQUEST["location"] : $_REQUEST["place"] ; ?>">
<input type="hidden" id="course" name="course" value="<?php echo (isset($_REQUEST["course"])) ? $_REQUEST["course"] : $_REQUEST["s"] ;?>">
<input type="hidden" id="search"  name="search" value="filter_search">
    <h4 class="head">Refine Your Search</h4>
                <ul class="nav">
				<h4 class="sub">Study Hours</h4>
                  
                    <li><a><input type="checkbox" id="filter_am" name="filter_am">AM</li></a><li class="nav-divider"></li>
                    <li><a><input type="checkbox" id="filter_pm" name="filter_pm">PM</li></a></li><li class="nav-divider"></li>
                    <li><a><input type="checkbox" id="filter_no_pref" name="filter_no_pref">No Preference</li></a></li>
					 <li class="nav-divider"></li>
              
                </ul>
				 <ul class="nav">
				<h4 class="sub">Study Days</h4>
                   
                    <li><a><input type="checkbox" id="filter_study_all_days" name="filter_study_all_days">All Days </a></li><li class="nav-divider"></li>
                    <li><a><input type="checkbox" id="filter_study_weekend" name="filter_study_weekend">Weekend Only</a></li><li class="nav-divider"></li>
                    <li><a><input type="checkbox" id="filter_study_weekdays" name="filter_study_weekdays">Weekdays Only</a></li><li class="nav-divider"></li>
					 <li><a><input type="checkbox" id="filter_study_no_pref" name="filter_study_no_pref">No Preference</a></li>
                    <li class="nav-divider"></li>
               
                </ul>
				 <ul class="nav">
				<h4 class="sub">Occupation</h4>
              
                    <li><a><input type="checkbox" id="filter_work_full_time" name="filter_work_full_time">Working Full-Time</a></li><li class="nav-divider"></li>
                    <li><a><input type="checkbox" id="filter_work_part_time" name="filter_work_part_time">Working Part-Time</a></li><li class="nav-divider"></li>
                    <li><a><input type="checkbox" id="filter_work_no_pref" name="filter_work_no_pref">No Preference</a></li>
                    <li class="nav-divider"></li>
           
                </ul>
		<ul class="nav">
                     
                    <li><a><input type="submit" name="submit"></a></li>
				 
                </ul>
				<?php

				/**
				 * Fires inside the members directory member types.
				 *
				 * @since 1.2.0
				 */
				do_action( 'bp_members_directory_member_types' ); ?>
</form>
            </nav>
	
		</div><!-- .item-list-tabs -->
	</div>

	<div class="buddypress-content">
	<?php

	/**
	 * Fires before the display of the members content.
	 *
	 * @since 1.1.0
	 */
	do_action( 'bp_before_directory_members_content' ); ?>

	<!-- <div id="members-dir-search" class="dir-search" role="search">
		<?php bp_directory_members_search_form(); ?>
	</div> -->
	
	<!-- #members-dir-search -->

	<?php

	/**
	 * Fires before the display of the members list tabs.
	 *
	 * @since 1.8.0
	 */
	do_action( 'bp_before_directory_members_tabs' ); ?>

	<form action="" method="post" id="members-directory-form" class="dir-form">



		 

		<div id="members-dir-list" class="members dir-list">
			<?php bp_get_template_part( 'members/members-loop' ); ?>
		</div><!-- #members-dir-list -->

		<?php

		/**
		 * Fires and displays the members content.
		 *
		 * @since 1.1.0
		 */
		do_action( 'bp_directory_members_content' ); ?>



		<?php wp_nonce_field( 'directory_members', '_wpnonce-member-filter' ); ?>

		<?php

		/**
		 * Fires after the display of the members content.
		 *
		 * @since 1.1.0
		 */
		do_action( 'bp_after_directory_members_content' ); ?>

	</form><!-- #members-directory-form -->

	<?php

	/**
	 * Fires after the display of the members.
	 *
	 * @since 1.1.0
	 */
	do_action( 'bp_after_directory_members' ); ?>

	</div>

</div><!-- #buddypress -->

<?php

/**
 * Fires at the bottom of the members directory template file.
 *
 * @since 1.5.0
 */
do_action( 'bp_after_directory_members_page' );
