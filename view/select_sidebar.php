<?php
/**
 * sidebar selection in different post or page.
 */

$sidebars = ACS_Class::get_options( 'modifiable' );

$is_front = get_option( 'page_on_front' ) == $post_id;
$is_blog = get_option( 'page_for_posts' ) == $post_id;

if ( $is_front || $is_blog ) : ?>

<?php else : ?>

	<p>
		<?php _e(
			'Select which sidebar you want to show for this post!', 'sidebars-generator'
		); ?>
	</p>
<?php
		if ( ! empty( $sidebars ) ) :
			global $wp_registered_sidebars;
			$available = ACS_Class::sort_sidebars_by_name( $wp_registered_sidebars );
			foreach ( $sidebars as $s ) : ?>
			<?php $sb_name = $available[ $s ]['name']; ?>
			<p>
				<label for="acq_replacement_<?php echo esc_attr( $s ); ?>">
					<b><?php echo esc_html( $sb_name ); ?></b>:
				</label>
				<select name="acq_replacement_<?php echo esc_attr( $s ); ?>"
					id="acq_replacement_<?php echo esc_attr( $s ); ?>"
					class="acq-replacement-field <?php echo esc_attr( $s ); ?>">
					<option value=""></option>
					<?php foreach ( $available as $a ) : ?>
					<option value="<?php echo esc_attr( $a['id'] ); ?>" <?php selected( $selected[ $s ], $a['id'] ); ?>>
						<?php echo esc_html( $a['name'] ); ?>
					</option>
					<?php endforeach; ?>
				</select>
			</p>
		<?php endforeach; ?>
	
	<?php endif;
endif;
