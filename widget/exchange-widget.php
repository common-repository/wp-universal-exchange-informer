<?php

/* Currency Widget */

class uci_exchange_widget extends WP_Widget {
    function __construct() {
		parent::__construct(
			'uci_exchange_widget', esc_html_x('Exchange rates widget', 'widget name', 'wp-universal-exchange-informer'),
			array('uci_exchange_widget' => 'uci_affiliate', 'description' => esc_html__('Display exchange rates for selected bank.', 'wp-universal-exchange-informer'))
		);
	}
	function widget($args,$instance) {
		extract($args);
		$title=apply_filters('widget_title',empty($instance['title'])?'':$instance['title'],$instance,$this->id_base);
		$informer_id=$instance['informer'];
		echo $before_widget;
		if(!empty($title)) { echo $before_title.esc_attr($title).$after_title; }
		echo uci_generate_informer($informer_id);
		echo $after_widget;
    }
	function update($new_instance,$old_instance) {
		$instance=$old_instance;
		$instance['title'] = sanitize_text_field($new_instance['title']);
		$instance['informer'] = absint($new_instance['informer']);
		return $instance;
	}
	function form($instance) {
		global $wpdb;
		$defaults=array('title'=>'');
		$instance=wp_parse_args((array)$instance,$defaults);
	?>
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php echo __('Title:','wp-universal-exchange-informer'); ?></label>
			<input class="widefat" type="text" value="<?php echo esc_attr($instance['title']); ?>" name="<?php echo $this->get_field_name('title'); ?>" id="<?php echo $this->get_field_id('title'); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('informer'); ?>"><?php echo __('Select informer:', 'wp-universal-exchange-informer'); ?></label>
			<select id="<?php echo $this->get_field_id('informer'); ?>" class="widefat" name="<?php echo $this->get_field_name('informer'); ?>">
				<option value="0" <?php if (!$instance['informer']) echo 'selected="selected"'; ?>><?php echo __('Not selected', 'wp-universal-exchange-informer'); ?></option>
				<?php
				$table_name=$wpdb->prefix."uci_widgets";
				$informers=$wpdb->get_results("SELECT * FROM `".$table_name."`");
				foreach($informers as $informer) {
					echo '<option value="'.$informer->id.'"';
					if ($informer->id==$instance['informer']) { echo ' selected="selected"'; }
					echo '>'.$informer->name;
					echo '</option>';
				}
				?>
			</select>
		</p>
		<?php
    }
}
?>