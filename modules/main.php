<?php
class WP_One_Metric {
	private $target_num = 40;
	private $posts = array();
	private $ga_index = 0;
	private $twitter_index = 0;
	private $facebook_index = 0;
	private $results = array();
	private $metrics = array();

	public function __construct() {
		$args = array( 
					'post_type' => 'post',
					'posts_per_page' => $this->target_num
					 );
		$posts = get_posts($args);
		
		if ( empty($posts) ) {
			return;
		}
		
		$posts = array_reverse($posts);
		foreach ( $posts as $post ) {
			$this->metrics[$post->ID] = get_post_meta( $post->ID, '_wp_one_metric', true );
		}
		
		$this->posts = $posts;

		add_action( 'admin_footer', array( $this, 'admin_footer' ) );
		add_action( 'wp_one_metric', array( $this, 'analyze' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'admin_print_scripts',  array( $this, 'admin_print_scripts' ) );
		add_filter( 'manage_posts_columns', array( $this, 'manage_posts_columns' ) );
		add_filter( 'manage_edit-post_sortable_columns', array( $this, 'manage_edit_sortable_columns' ) );
		add_action( 'restrict_manage_posts', array( $this, 'restrict_manage_posts' ) );
		add_action( 'admin_init', array( $this, 'csv' ) );
	}
	
	public function csv() {
		if ( !isset($_REQUEST['wp-one-metric-csv']) ) {
			return;
		}
	
		check_admin_referer('bulk-posts');
		
		$file_name = 'wp_one_metric_'.time().'.csv';
		$title = array();
		$title[] = __('ID');
		$title[] = __('Title');
		$title[] = __('One Metric', WPOMC_DOMAIN);
		$mime_type = 'text/csv;charset=UTF-8';

		header('Content-Disposition: inline; filename="'.$file_name.'"');
		header('Content-Type: '.$mime_type);
		echo implode(',', $title) . "\r\n";
			
			
		$posts = get_posts(
					array(
						'posts_per_page' => 1000
					));

		foreach ( $posts as $post ) {
			$csv = array();
			$csv[] = '"' . $post->ID . '"';
			$csv[] = '"' . get_the_title($post->ID) . '"';
			$csv[] = '"' . intval(get_post_meta( $post->ID, '_wp_one_metric', true )) . '"';
			
			echo implode(',', $csv) . "\r\n";
		}
		unset($csv);
		exit;
	}

	public function restrict_manage_posts() {
		if ( get_post_type() != 'post' ) {
			return;
		}
?>
<input type="submit" name="wp-one-metric-csv" id="wp-one-metric-csv" class="button button-secondary" value="<?php _e( 'One Metric CSV Download', WPOMC_DOMAIN ) ?>"  />
<?php
	}

	public function admin_init() {
		if ( isset($_POST['wp_metric_analyze']) ) {
			check_admin_referer('wp_metric_analyze');
			$this->analyze();
		}
	}
	
	public function admin_menu() {
		add_dashboard_page( __( 'WP One Metric', WPOMC_DOMAIN ), __( 'WP One Metric', WPOMC_DOMAIN ), 'manage_options', 'wpomc-dash', array($this, 'dashboard') );
	}
	
	public function dashboard() {
?>
<div class="wrap">
<?php screen_icon(); ?>

<h2><?php _e( 'WP One Metric', WPOMC_DOMAIN ); ?></h2>

<div id="one-metric" style="height: 400px;"></div>
<form action="" method="post">
<?php wp_nonce_field('wp_metric_analyze'); ?>
<?php submit_button(__('Analyze', WPOMC_DOMAIN), 'primary', 'wp_metric_analyze'); ?>
</form>
</div>
<?php
	}
	
	public function admin_print_scripts() {
		wp_enqueue_style('morris_css', '//cdnjs.cloudflare.com/ajax/libs/morris.js/0.5.1/morris.css');
		wp_enqueue_script('raphael_js', '//cdnjs.cloudflare.com/ajax/libs/raphael/2.1.0/raphael-min.js', false, '1.0');
		wp_enqueue_script('morris_js', '//cdnjs.cloudflare.com/ajax/libs/morris.js/0.5.1/morris.min.js', false, '1.0');
	}
	
	public function admin_footer() {
?>
<script>
new Morris.Bar({
  // ID of the element in which to draw the chart.
  element: 'one-metric',
  // Chart data records -- each entry in this array corresponds to a point on
  // the chart.
  data: [
  <?php foreach( $this->metrics as $key => $val ): ?>
    { post_id: '<?php echo get_the_title($key); ?>', value: <?php echo esc_js(intval($val)); ?> },
  <?php endforeach; ?>
  ],
  // The name of the data record attribute that contains x-values.
  xkey: 'post_id',
  // A list of names of data record attributes that contain y-values.
  ykeys: ['value'],
  // Labels for the ykeys -- will be displayed when you hover over the
  // chart.
  labels: ['One Metric'],

});
</script>
<?php
	}
	
	public function set_event() {
		if ( wp_next_scheduled( 'wp_one_metric' ) )
			wp_clear_scheduled_hook( 'wp_one_metric' );

		wp_schedule_event( time(), 'daily', 'wp_one_metric' );
	}
	
	public function delete_event() {
		if ( wp_next_scheduled( 'wp_one_metric' ) )
			wp_clear_scheduled_hook( 'wp_one_metric' );
	}
	
	public function get_ga_index() {
		global $wp_one_metric_ga_api;

		$ga_index = 0;
		
		$ids = array();
		$uniquepageviews = array();
		foreach ( $this->posts as $post ) {
			$ids[] = $post->ID;
			$uniquepageviews[$post->ID] = 0;
		}	

   		$args = array(
   			'start-index' => 1,
			'max-results' => 1000,
			'dimensions'  => 'ga:pagePath',
			'sort' => '-ga:uniquePageviews',
		);
		$results = $wp_one_metric_ga_api->fetch(date_i18n('Y-m-d', strtotime("-60 day")), date_i18n('Y-m-d'), 'ga:uniquePageviews', $args );

    	$sum = 0;
    	if ( !is_wp_error( $results ) ) {
    		foreach($results->rows as $result) {
    			$post_id = url_to_postid(esc_url($result[0]));

				if ( $post_id == 0 )
    				continue;

				if ( in_array( $post_id, $ids ) ) {
					$result[1] = intval($result[1]);
    				$sum = $sum + $result[1];
					if ( isset($uniquepageviews[$post_id]) ) {
    					$uniquepageviews[$post_id] = $uniquepageviews[$post_id] + $result[1];
					} else {
	    				$uniquepageviews[$post_id] = $result[1];
					}
				}
			}
    	} else {
	    	if ( is_super_admin() ) {
	    		echo '<pre>';
				var_dump($results);
				echo '</pre>';
			}
    	}
    	foreach ( $this->posts as $post ) {
			$this->results[$post->ID]['uniquepageviews'] = $uniquepageviews[$post->ID];
    	}
 		$this->ga_index = $sum / $this->target_num;
	}
	
	public function get_twitter_index() {
		$twitter_index = 0;
		
		$urls = array();
		$sum = 0;
		foreach ( $this->posts as $post ) {
			$url = get_permalink($post->ID);
			$response = wp_remote_get('http://urls.api.twitter.com/1/urls/count.json?url='.$url);
			if( !is_wp_error( $response ) && $response["response"]["code"] === 200 ) {
				$response_body = json_decode($response["body"]);
				$sum = $sum + intval($response_body->count);
				$this->results[$post->ID]['twitter'] = intval($response_body->count);

			} else {
				// Handle error here.
			}
		}
		$this->twitter_index = $sum / $this->target_num;
	}
	
	public function get_facebook_index() {
		$facebook_index = 0;
		
		$urls = array();
		$sum = 0;
		foreach ( $this->posts as $post ) {
			$url = get_permalink($post->ID);
			$response = wp_remote_get('http://graph.facebook.com/'.$url);
			if( !is_wp_error( $response ) && $response["response"]["code"] === 200 ) {
				$response_body = json_decode($response["body"]);
				if ( property_exists($response_body, 'shares') ) {
					$sum = $sum + intval($response_body->shares);
					$this->results[$post->ID]['facebook'] = intval($response_body->shares);
				} else {
					$this->results[$post->ID]['facebook'] = 0;
				}
				
			} else {
				// Handle error here.
			}
		}
		$this->facebook_index = $sum / $this->target_num;
	}
	
	public function analyze() {
		$this->get_ga_index();
		$this->get_twitter_index();
		$this->get_facebook_index();
		
		foreach ( $this->results as $key => $val ) {
			$data = (0.5)*($val['uniquepageviews']/$this->ga_index) + (0.5)*(($val['twitter']/$this->twitter_index)+($val['facebook']/$this->facebook_index))*(0.5);
			$this->results[$key]['metric'] = 27*log($data)+50;
			update_post_meta( $key, '_wp_one_metric', $this->results[$key]['metric'] );
		}
		
	}
	
	public function manage_posts_columns( $posts_columns ) {
		$new_columns = array();
		foreach ( $posts_columns as $column_name => $column_display_name ) {
			if ( $column_name == 'date' ) {
				$new_columns['wp_one_metric'] = __( 'One Metric', WPOMC_DOMAIN );
				add_action( 'manage_posts_custom_column', array($this, 'add_column'), 10, 2 );
			}
			$new_columns[$column_name] = $column_display_name;
		}
		return $new_columns;
	}
	
	public function add_column($column_name, $post_id) {

		if ( $column_name == 'wp_one_metric') {
			echo intval(get_post_meta( $post_id, '_wp_one_metric', true ));
		}
	}
	
	public function manage_edit_sortable_columns($sortable_column) {
		$sortable_column['wp_one_metric'] = 'wp_one_metric';
		return $sortable_column;
	}
}

add_action( 'admin_notices', 'wp_one_metric_admin_notice' );
function wp_one_metric_admin_notice() {
	$token = get_option('gapiwp_token');
	
	if ( $token == '' ) {
		echo '<div class="error">WP One Metric is available OAuth2 authorization. Please set on <a href="'.admin_url('/options-general.php?page=gapiwp-analytics').'" >setting panel</a>. ClientLogin is no longer available. Please see <a href="https://developers.google.com/identity/protocols/AuthForInstalledApps" >this link</a></div>';
	}
}
