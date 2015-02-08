<?php
class WP_One_Metric {
	private $target_num = 40;
	private $posts = array();
	private $ga_index = 0;
	private $twitter_index = 0;
	private $facebook_index = 0;

	public function __construct() {
		$args = array( 
					'post_type' => 'post',
					'posts_per_page' => $this->target_num
					 );
		$posts = get_posts($args);
		
		if ( empty($posts) ) {
			return;
		}
		
		foreach ( $posts as $post ) {
			$this->posts[$post->ID] = array();
		}
		add_action( 'admin_init', array( $this, 'get_index' ) );
		add_action( 'admin_footer', array( $this, 'admin_head' ) );
	}
	
	public function admin_head() {
?>
<script>
new Morris.Bar({
  // ID of the element in which to draw the chart.
  element: 'one-metric',
  // Chart data records -- each entry in this array corresponds to a point on
  // the chart.
  data: [
  <?php foreach( $this->posts as $key => $val ): ?>
    { post_id: <?php echo esc_js(intval($key)); ?>, value: <?php echo esc_js(intval($val['metric'])); ?> },
  <?php endforeach; ?>
  ],
  // The name of the data record attribute that contains x-values.
  xkey: 'post_id',
  // A list of names of data record attributes that contain y-values.
  ykeys: ['value'],
  // Labels for the ykeys -- will be displayed when you hover over the
  // chart.
  labels: ['Value'],

});
</script>
<?php
	}
	
	public function get_ga_index() {
		$ga_index = 0;
		$args = array( 
					'post_type' => 'post',
					'posts_per_page' => $this->target_num
					 );
		$posts = get_posts($args);
		
		if ( empty($posts) ) {
			return;
		}
		
		$ids = array();
		foreach ( $posts as $post ) {
			$ids[] = $post->ID;
		}
		
		$options = get_option('wpomc_options');
		try {
    		$ga = new gapi( $options['email'], $options['pass'] );
    		$ga->requestReportData(
    			$options['profile_id'],
    			array('pagePath'),
    			array('pageviews', 'uniquePageviews', 'exits' ),
    			'-pageviews',
    			'',
    			date_i18n('Y-m-d', strtotime("-60 day")),
    			date_i18n('Y-m-d'),
    			1,
    			1000
    		);

    		$sum = 0;
    		$cnt = 0;
    		foreach($ga->getResults() as $result) {
    			$post_id = url_to_postid(esc_url($result->getPagepath()));
    			
    			if ( $post_id == 0 )
    				continue;
			
    			if ( array_search( $post_id, $ids ) ) {
    				$cnt++;
    				$sum = $sum + $result->getUniquepageviews();
    				$this->posts[$post_id]['uniquepageviews'] = $result->getUniquepageviews();
    			}
    		}
    		
    		if ( $cnt != 0 ) {
    			$this->ga_index = $sum / $cnt;
    		}

    	} catch (Exception $e) {
    		echo( 'Analytics API Error: ' . $e->getMessage() );
    	}		
	}
	
	public function get_twitter_index() {
		$twitter_index = 0;
		$args = array( 
					'post_type' => 'post',
					'posts_per_page' => $this->target_num
					 );
		$posts = get_posts($args);
		
		if ( empty($posts) ) {
			return;
		}
		
		$urls = array();
		$sum = 0;
		foreach ( $posts as $post ) {
			$url = get_permalink($post->ID);

			$response = wp_remote_get('http://urls.api.twitter.com/1/urls/count.json?url='.$url);
			if( !is_wp_error( $response ) && $response["response"]["code"] === 200 ) {
				$response_body = json_decode($response["body"]);
				$sum = $sum + intval($response_body->count);
				$this->posts[$post->ID]['twitter'] = intval($response_body->count);
			} else {
				// Handle error here.
			}
		}
		$this->twitter_index = $sum / $this->target_num;
	}
	
	public function get_facebook_index() {
		$facebook_index = 0;
		$args = array( 
					'post_type' => 'post',
					'posts_per_page' => $this->target_num
					 );
		$posts = get_posts($args);
		
		if ( empty($posts) ) {
			return;
		}
		
		$urls = array();
		$sum = 0;
		$cnt = 0;
		foreach ( $posts as $post ) {
			$url = get_permalink($post->ID);

			$response = wp_remote_get('http://graph.facebook.com/'.$url);
			if( !is_wp_error( $response ) && $response["response"]["code"] === 200 ) {
				$response_body = json_decode($response["body"]);
				if ( property_exists($response_body, 'shares') ) {
					$sum = $sum + intval($response_body->shares);
					$cnt++;
					$this->posts[$post->ID]['facebook'] = intval($response_body->shares);
				}
				
			} else {
				// Handle error here.
			}
		}
		$this->facebook_index = $sum / $cnt;
	}
	
	public function get_index() {
		$this->get_ga_index();
		$this->get_twitter_index();
		$this->get_facebook_index();
		
		foreach ( $this->posts as $key => $val ) {
			$data = (1/2)*($val['uniquepageviews']/$this->ga_index) + (1/2)*((($val['twitter']/$this->twitter_index)+($val['twitter']/$this->twitter_index)))/2;
			$this->posts[$key]['metric'] = 27*log($data)+50;
		}
		
	}
}
new WP_One_Metric();
