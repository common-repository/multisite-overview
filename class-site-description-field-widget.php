<?php

class Site_Description_Field_Widget extends WP_Widget
{
	public function __construct() {
		parent::__construct(
			'description_widget',
			'Description',
			array('description' => __( 'Displays the site\'s description.', 'text_domain' ),)
		);
	}

	public function widget( $args, $instance ) {
		echo $args['before_widget'];
		$title = apply_filters( 'widget_title', $instance['title'] );
		if ( ! empty($title) ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}
		echo wpautop( apply_filters( 'widget_text', Multisite_Overview::get_plugin_option('site_description') ) );
		echo $args['after_widget'];
	}

	public function form( $instance ) {
		if ( isset($instance['title']) ) {
			$title = $instance['title'];
		} else {
			$title = __( 'Description', 'text_domain' );
		}
		?>
		<p>
			<label for="<?php echo $this->get_field_name( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>"
			       name="<?php echo $this->get_field_name( 'title' ); ?>" type="text"
			       value="<?php echo esc_attr( $title ); ?>"/>
		</p>
	<?php
	}

	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = (! empty($new_instance['title'])) ? strip_tags( $new_instance['title'] ) : '';
		return $instance;
	}
}