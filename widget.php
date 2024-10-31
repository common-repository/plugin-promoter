<?php

class Plugin_Promoter extends WP_Widget {

	public function __construct() {
		parent::__construct(
	 		'plugin_promoter', // Base ID
			'Plugin Promoter', // Name
			array( 'description' => __( 'Plugin Promoter Badge', 'plugin_promoter' ) )
		);
	}
        
	public function widget( $args, $instance ) {
		extract( $args );
		$plugin = $instance['plugin'];
		if(!empty( $plugin))
                    echo pp_badge(array('plugin' => $plugin));
	}

	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['plugin'] = strip_tags( $new_instance['plugin'] );

		return $instance;
	}

	public function form( $instance ) {
		if ( $instance ) 
		    $plugin = esc_attr( $instance[ 'plugin' ] );
		?>
		<p>
		<label for="<?php echo $this->get_field_id( 'plugin' ); ?>"><?php _e( 'Plugin Slug:' ); ?></label> 
		<input class="widefat" id="<?php echo $this->get_field_id( '$plugin' ); ?>" name="<?php echo $this->get_field_name( 'plugin' ); ?>" type="text" value="<?php echo $plugin; ?>" />
		</p>
		<?php 
	}

}
add_action( 'widgets_init', create_function( '', 'register_widget( "plugin_promoter" );' ) );