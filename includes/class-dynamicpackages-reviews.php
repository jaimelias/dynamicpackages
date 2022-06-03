<?php 

class Dynamic_Packages_Reviews
{

	function __construct()
	{
		$this->init();
	}
	public function init()
	{
		add_shortcode('package_reviews', array(&$this, 'print_total_reviews'));
		add_action('comment_form_logged_in_after', array(&$this, 'field'));
		add_action('comment_form_after_fields', array(&$this, 'field'));
		add_action('comment_post', array(&$this, 'save_comment'), 10);
		add_action('edit_comment', array(&$this, 'edit_comment'), 10);
		add_filter('preprocess_comment', array(&$this, 'require_comment'));
		add_action('admin_init', array(&$this, 'run'));
		add_filter('comments_template', array(&$this, 'template'));
		add_filter('comment_reply_link', array(&$this, 'reply_link'));
		add_filter('wp_list_comments_args', array(&$this, 'add_callback'));
		add_filter('comments_array', array(&$this, 'ppl_merge_comments'), 10, 2);
		add_filter('comments_array', array(&$this, 'order_by_date'), 200, 2);
		add_filter('get_comments_number', array(&$this, 'ppl_merge_comment_count'), 100, 2);
		add_action('wp', array(&$this, 'ppl_remove_comments_filter'));
		add_filter('comment_form_defaults', array(&$this, 'comment_defaults'));
		add_action( 'wp_enqueue_scripts', array(&$this, 'dashicons'));
		add_action('wp_head', array(&$this, 'css'));
		add_filter('minimal_ld_json', array(&$this, 'add_reviews'), 10);
		add_filter('dy_reviews_stars', array(&$this, 'stars'), 10);
		add_filter('dy_reviews_get_rating', array(&$this, 'get_rating'), 10);
		add_filter('dy_reviews_wp_star_rating', array(&$this, 'get_rating'), 10);

	}
	
	public function dashicons()
	{
		if(has_package())
		{
			wp_enqueue_style( 'dashicons' );
		}
	}
	
	public function stars($the_id)
	{
		$output = ''; 

		if(has_package())
		{
			if(get_comments_number() > 0)
			{
				require_once(ABSPATH . 'wp-admin/includes/template.php');

				if(is_singular('packages'))
				{
					$stars = $this->wp_star_rating($this->get_rating($the_id)).' '.esc_html(get_comments_number()).' '.esc_html(__('reviews', 'dynamicpackages'));
					$output .= '<a href="#dy_reviews">'.$stars.'</a>';
				}
				else
				{

					$schema = '';
					
					if(dy_validators::is_valid_schema())
					{
						$schema = 'itemprop="aggregateRating" itemscope itemtype="http://schema.org/AggregateRating"';
					}					
					
					$stars = $this->wp_star_rating($this->get_rating($the_id)).' <span itemprop="reviewCount">'.esc_html(get_comments_number()).'</span> '.esc_html(__('reviews', 'dynamicpackages'));
					$stars .= '<meta itemprop="ratingValue" content = "'.esc_html($this->get_rating($the_id)).'">';
					$output = '<span '.$schema.'>'.$stars.'</span>';
				}
			}			
		}

		return $output;
	}
	
	public function template($template)
	{		
		if(has_package())
		{
			$template = plugin_dir_path(dirname( __FILE__ )) . 'public/partials/reviews-template.php';
		}
				
		return $template;
	}
	
	public function run()
	{
		add_meta_box('dy_rating', __('Dynamic Packages'), array(&$this, 'field'), 'comment', 'normal');
	}
	
	public function field($field) {
		
		$selected = 0;
		
		if(is_admin())
		{
			$comment_meta = get_comment_meta($field->comment_ID, 'dy_rating', true);
			
			if($comment_meta)
			{
				if($comment_meta > 0)
				{
					$selected = $comment_meta;
				}
			}
			
		}
		
		?>
			<?php if(!is_admin() && is_user_logged_in()): ?>
				<?php $user = wp_get_current_user(); $allowed_roles = array('editor', 'administrator', 'author', 'contributor'); ?>
				<?php if( array_intersect($allowed_roles, $user->roles ) ): ?> 
					<div>
						<div>
							<label><?php echo (esc_html__('Alias', 'dynamicpackages')); ?></label>
						</div>
						<div>
							<input type="text" name="dy_alias" value="<?php echo $this->get_random_name(); ?>" />
						</div>
					</div><br/>
					<?php endif; ?>
			<?php endif; ?>

			<div>
				<div>
					<label><?php echo (esc_html__('Rating', 'dynamicpackages')); ?></label>
				</div>
				<div>
					<select name="dy_rating" class="bottom-20">
						<option value="5" <?php selected($selected, 5); ?>><?php echo $this->get_stars(5); ?> - <?php echo (esc_html__('Excellent', 'dynamicpackages')); ?></option>
						<option value="4" <?php selected($selected, 4); ?>><?php echo $this->get_stars(4); ?> - <?php echo (esc_html__('Good', 'dynamicpackages')); ?></option>
						<option value="3" <?php selected($selected, 3); ?>><?php echo $this->get_stars(3); ?> - <?php echo (esc_html__('Average', 'dynamicpackages')); ?></option>
						<option value="2" <?php selected($selected, 2); ?>><?php echo $this->get_stars(2); ?> - <?php echo (esc_html__('Bad', 'dynamicpackages')); ?></option>
						<option value="1" <?php selected($selected, 1); ?>><?php echo $this->get_stars(1); ?> - <?php echo (esc_html__('Very Bad', 'dynamicpackages')); ?></option>
					</select>
				</div>
			</div>
		<?php
	}
	
	public function save_comment($comment_id)
	{
		if(isset($_POST['dy_rating']))
		{
			if($_POST['dy_rating'] > 0)
			{
				add_comment_meta($comment_id, 'dy_rating', intval($_POST['dy_rating']));
			}
		}
	}
	
	public function edit_comment($comment_id)
	{
		if(isset($_POST['dy_rating']))
		{
			if($_POST['dy_rating'] > 0)
			{
				update_comment_meta( $comment_id, 'dy_rating', intval($_POST['dy_rating']));
			}
		}		
	}
	
	public function require_comment($commentdata) {
		if (!is_admin() && !isset($_POST['dy_rating']))
		{
			if(intval($_POST['dy_rating']) < 1)
			{
				wp_die(__( 'Error: You did not add a rating. Hit the Back button on your Web browser and resubmit your comment with a rating.', 'dynamicpackages'));			
			}
		}
		
		if(isset($_POST['dy_alias']))
		{
			$commentdata['comment_author'] = sanitize_text_field($_POST['dy_alias']);
			
			
			//random date
			$start = strtotime('-4 months');
			$end = strtotime('now');
			$random = mt_rand($start, $end);
			$commentdata['comment_date'] = esc_html(date('Y-m-d H:i:s', $random));
		}
		
		return $commentdata;
	}

	public function get_rating($id)
	{
		$output = false;
		$comments = get_approved_comments($id);
		
		global $polylang;
		
		if(isset($polylang))
		{
			$translationIds = PLL()->model->post->get_translations($id);
			foreach ( $translationIds as $key=>$translationID ){
				if( $translationID != $id ) {
					$translatedPostComments = get_approved_comments($translationID);
					if ( $translatedPostComments ) {
						$comments = array_merge($comments, $translatedPostComments);
					}
				}
			}			
		}

		if ( $comments )
		{
			$i = 0;
			$total = 0;
			
			foreach( $comments as $comment )
			{
				$rate = get_comment_meta( $comment->comment_ID, 'dy_rating', true );
				
				if( isset( $rate ) && '' !== $rate )
				{
					$i++;
					$total += $rate;
				}
			}

			if ( $i !== 0  )
			{
				return number_format($total / $i, 2, '.', '');
			}
		}

		return $output;
		
	}
	public function reply_link($args)
	{
		return null;
	}
	public function add_callback($args)
	{
		if(has_package())
		{
			$args['callback'] = array(&$this, 'review_callback');
		}
		return $args;
	}
	public function get_stars($number)
	{
		if($number > 0)
		{
			if($number == 1)
			{
				return '&#9733;';
			}
			else if($number == 2)
			{
				return '&#9733;&#9733;';
			}
			else if($number == 3)
			{
				return '&#9733;&#9733;&#9733;';
			}
			else if($number == 4)
			{
				return '&#9733;&#9733;&#9733;&#9733;';
			}
			else if($number == 5)
			{
				return '&#9733;&#9733;&#9733;&#9733;&#9733;';
			}			
		}
	}
	
	public function review_callback($comment, $args, $depth) {
		if ( 'div' === $args['style'] ) {
			$tag       = 'div';
			$add_below = 'review';
		} else {
			$tag       = 'li';
			$add_below = 'div-review';
		}?>
		<<?php echo $tag; ?> <?php comment_class( empty( $args['has_children'] ) ? '' : 'parent' ); ?> id="review-<?php comment_ID() ?>"><?php 
		if ( 'div' != $args['style'] ) { ?>
			<div id="comment-<?php comment_ID() ?>" class="dy_review"><?php
		} ?>
		
		<?php global $post; ?>
			
		
			<?php if($comment->comment_approved == '0' ){ ?>
				<em class="review-awaiting-moderation"><?php echo esc_html(__( 'Your review is awaiting moderation.', 'dynamicpackages' )); ?></em><br/><?php 
			} ?>
			
			<p class="semibold large">
				<span><?php echo get_comment_author_link().' <small>'.$this->wp_star_rating(get_comment_meta($comment->comment_ID, 'dy_rating', true)).'</small>'; ?></span>
				<span class="text-muted"><?php echo get_comment_date(); ?></span> <small><?php edit_comment_link(__('Edit', 'dynamicpackages'), ' ', ' ' ); ?></small>
			</p>

			<div><?php comment_text(); ?></div>

			<div class="reply"><?php 
					comment_reply_link( 
						array_merge( 
							$args, 
							array( 
								'add_below' => $add_below, 
								'depth'     => $depth, 
								'max_depth' => $args['max_depth'] 
							) 
						) 
					); ?>
			</div><?php 
		if ( 'div' != $args['style'] ) : ?>
			</div><hr/><?php 
		endif;
	}
	
	public function order_by_date($comments, $post_ID)
	{
		if(!is_admin() && has_package())
		{
			global $review_order_by_date;
			
			if(isset($review_order_by_date))
			{
				return $review_order_by_date;
			}
			else
			{
				if(is_array($comments))
				{
					if(count($comments) > 0)
					{
						$comments = $this->array_sort($comments);
					}
				}
				
				$GLOBALS['review_order_by_date'] = $comments;
			}
		}
		
		return $comments;
	}
	
	public function array_sort($comments)
	{
		$output = array();
		
		for($x = 0; $x < count($comments); $x++)
		{
			$item = $comments[$x];
			$item->int_date = strtotime($item->comment_date);
			array_push($output, $item);
		}
		
		usort($output, function($a, $b) { return $b->int_date - $a->int_date; });
		
		return $output;
	}	
	
	public function ppl_merge_comments($comments, $post_ID)
	{
		global $polylang;
		
		if(!is_admin() && has_package() && isset($polylang))
		{
			$translationIds = PLL()->model->post->get_translations($post_ID);
			
			foreach ( $translationIds as $key=>$translationID )
			{
				if( $translationID != $post_ID )
				{					
					$translatedPostComments = get_approved_comments($translationID);
					
					if ( $translatedPostComments )
					{
						$comments = array_merge($comments, $translatedPostComments);
						
					}
				}
			}
			
			if ( count($translationIds) >1 ) {
				usort($comments, array(&$this, 'ppl_sort_merged_comments'));
			}
		}
		return $comments;
	}
	public function ppl_sort_merged_comments($a, $b)
	{
		return $a->comment_ID - $b->comment_ID;
	}
	public function ppl_merge_comment_count($count, $post_ID)
	{
		global $polylang;
		
		if(!is_admin() && has_package() && isset($polylang))
		{
			$translationIds = PLL()->model->post->get_translations($post_ID);
		
			foreach ( $translationIds as $key=>$translationID ){
				if( $translationID != $post_ID ) {
					$translatedPost = get_post($translationID);
					if ( $translatedPost ) {
						$count = $count + $translatedPost->comment_count;
					}
				}
			}
		}
		return $count;
	}
	public function ppl_remove_comments_filter()
	{
		global $polylang;
		
		if(!is_admin() && has_package() && isset($polylang))
		{
			remove_filter('comments_clauses', array(&$polylang->filters, 'comments_clauses'));			
		}
	}
	public function comment_defaults($args)
	{
		if(!is_admin() && has_package())
		{
			$args['title_reply']  = __( 'Leave a Review', 'dynamicpackages');
			$args['label_submit'] = __( 'Post a Review', 'dynamicpackages');
			
			//production	
			if(!is_user_logged_in())
			{
				$args['must_log_in'] = null;
				$args['title_reply'] = null;
			}
		}

		return $args;
	}
	
	public function css()
	{
		ob_start();
		?>
		<style type="text/css">
			@font-face {
			font-family: "dashicons";
			src: url("<?php echo includes_url(); ?>fonts/dashicons.eot");
			}

			@font-face {
			font-family: "dashicons";
			src: url(data:application/x-font-woff;charset=utf-8;base64, format("woff"),
			url("<?php echo includes_url(); ?>fonts/dashicons.ttf") format("truetype"),
			url("<?php echo includes_url(); ?>fonts/dashicons.svg#dashicons") format("svg");
			font-weight: normal;
			font-style: normal;
			}

			.star-rating .star-full:before {
			content: "\f155";
			}

			.star-rating .star-half:before {
			content: "\f459";
			}

			.star-rating .star-empty:before {
			content: "\f154";
			}

			.star-rating .star{
			color: orange;
			display: inline-block;
			font-family: dashicons;
			font-style: normal;
			line-height: 1;
			text-align: center;
			text-decoration: inherit;
			width: 20px;
			text-shadow: 1px 1px 1px rgba(0,0,0,0.2);
			}
		</style>
		<?php
		$output = ob_get_contents();
		ob_end_clean();
		echo $output;	
	}
	
	public function wp_star_rating($rating) {

		$output = '';
		
		if($rating)
		{
			$rating = (float) $rating;
			$full_stars  = floor( $rating );
			$half_stars  = ceil( $rating - $full_stars );
			$empty_stars = 5 - $full_stars - $half_stars;
			
			$output  .= '<span class="star-rating">';
			$output .= str_repeat( '<span class="star star-full" aria-hidden="true"></span>', $full_stars );
			$output .= str_repeat( '<span class="star star-half" aria-hidden="true"></span>', $half_stars );
			$output .= str_repeat( '<span class="star star-empty" aria-hidden="true"></span>', $empty_stars );
			$output .= '</span>';
		}

		return $output;
	}	
	
	public function get_random_name()
	{
		$output = null;
		$url = 'https://randomuser.me/api/';
		$lang = substr(get_locale(), 0, -3);
		
		if($lang == 'en')
		{
			$url .= '?nat=us,gb';
		}
		else if($lang == 'es')
		{
			$url .= '?nat=es';
		}
		else if($lang == 'nl')
		{
			$url .= '?nat=nl';
		}
		else if($lang == 'DA')
		{
			$url .= '?nat=DK';
		}		
		
        $ch = curl_init(); 
        curl_setopt($ch, CURLOPT_URL, $url); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        $output = curl_exec($ch); 
        curl_close($ch);
		
		$output = json_decode($output, true);
		
		if(is_array($output))
		{
			if(count($output) > 0)
			{
				if(array_key_exists('results', $output))
				{
					$output = $output['results'][0];
					$output = $output['name'];
					$output = ucfirst($output['first']).' '.ucfirst($output['last']);
				}
				
				return esc_html($output);				
			}
		}		
	}
	
	public function total_reviews()
	{
		global $dy_total_reviews;
		global $polylang;
		$output = array();
		
		if(isset($dy_total_reviews))
		{
			$output = $dy_total_reviews;
		}
		else
		{
			$merged_comments = array();
			$rating = array();
			$count = 0;
			$args = array();
			$args['post_parent'] = 0;
			$args['post_type'] = 'packages';
			$args['posts_per_page'] = -1;	
			
			if(isset($polylang))
			{
				$language_list = array();
				$languages = PLL()->model->get_languages_list();
				
				for($x = 0; $x < count($languages); $x++)
				{
					foreach($languages[$x] as $key => $value)
					{
						if($key == 'slug')
						{
							array_push($language_list, $value);
						}
					}	
				}

				if(count($language_list) > 0)
				{
					$args['lang'] = $language_list;
				}
			}
			
			$total_reviews = new WP_Query($args);	

			if($total_reviews->have_posts())
			{
				while ($total_reviews->have_posts())
				{
					$total_reviews->the_post();
					global $post;
					$comments = get_approved_comments($post->ID);
					
					if(count($comments) > 0)
					{	
						for($x = 0; $x < count($comments); $x++ )
						{
							$count++;
							$get_rating = get_comment_meta($comments[$x]->comment_ID, 'dy_rating', true);
							array_push($rating, $get_rating);
						}
						
						array_push($merged_comments, $comments);
					}	
				}
				
				wp_reset_postdata();
			}
			
			
			if($rating > 0 && $count > 0)
			{
				$average = number_format((array_sum($rating)/$count), 2, '.', '');
				$output['ratingValue'] = $average;
				$output['reviewCount'] = $count;
				$output['@type'] = 'AggregateRating';
				$GLOBALS['dy_total_reviews'] = $output;
			}			
		}
		
		return $output;
	}
	public function add_reviews($json)
	{
		if(is_front_page())
		{
			$reviews = $this->total_reviews();
			
			if(is_array($reviews))
			{
				$json['aggregateRating'] = $reviews;
			}
		}
		return $json;
	}
	
	public function print_total_reviews()
	{
		$reviews = $this->total_reviews();
		ob_start();
		
		?>
			<div class="dy_total_reviews">
					<div>
						<?php bloginfo('name'); ?> <?php echo (esc_html__('is rated', 'dynamicpackages')).' <span class="rating">'.esc_html($reviews['ratingValue']).'</span>'; ?>
					</div>
					<div>
						<?php echo $this->wp_star_rating($reviews['ratingValue']).' <span class="count">'.esc_html($reviews['reviewCount']).'</span> '.esc_html(__('reviews', 'dynamicpackages')); ?>
					</div>
			</div>
		<?php
		$output = ob_get_contents();
		ob_end_clean();		
		return $output;
	}
}

?>