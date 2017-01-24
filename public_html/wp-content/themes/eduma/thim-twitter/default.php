<div class="twitter-wrapper">
	<div class="twitter-inner">
		<?php
		if ( !empty( $instance['title'] ) ) {
			echo ent2ncr( $args['before_title'] . $instance['title'] . $args['after_title'] );
		}

		if ( $twitter && is_array( $twitter ) ) : ?>
			<div class="thim-tweets">
				<div class="twitter-item-wrapper">
					<?php foreach ( $twitter as $tweet ):
						$twitterTime = strtotime( $tweet['created_at'] );
						$avatar = $tweet['user']['profile_image_url'];
						$username = $tweet['user']['screen_name'];
						$location = $tweet['user']['location'];
						$latestTweet = $tweet['text'];
						$latestTweet = preg_replace( '/https:\/\/([a-z0-9_\.\-\+\&\!\#\~\/\,]+)/i', '', $latestTweet );
						$latestTweet = preg_replace( '/@([a-z0-9_]+)/i', '', $latestTweet );
						$urls = $tweet['entities']['urls'];
						?>
						<div class="tweet-item">
							<div class="content">
								<?php echo ent2ncr( $latestTweet ); ?>
							</div>
							<?php
							if ( !empty( $urls ) ) {
								echo '<div class="links">';
								foreach ( $urls as $key => $value ) {
									if ( !empty( $value[url] ) ) {
										echo '<a href="' . $value[url] . '" >' . $value[url] . '</a>';
									}
								}
								echo '</div>';
							}
							?>
							<div class="date">
								<?php echo date( "F j, Y", $twitterTime ); ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endif; ?>
		<a class="link-follow" href="https://twitter.com/<?php echo esc_attr( $username ); ?>"><i class="fa fa-twitter"></i></a>
	</div>
</div>