<?php
//this page is for sidebar location

$sidebars = ACS_Class::get_sidebars( 'theme' );

function _show_replaceable( $sidebar, $prefix, $cat_name, $class = '' ) {
	$base_id = 'acq-' . $prefix;
	$inp_id = $base_id . '-' . $sidebar['id'];
	$inp_name = '___acq___' . $prefix . '___' . $sidebar['id'];
	$sb_id = $sidebar['id'];
	$class = (empty( $class ) ? '' : ' ' . $class);

	?>
	<div
		class="replaceable <?php echo esc_attr( $sb_id . $class ); ?>"
		data-lbl-used="<?php _e( 'Replaced by another sidebar:', 'sidebars-generator' ); ?>"
		>
		<label for="<?php echo esc_attr( $inp_id ); ?>">
			<input type="checkbox"
				id="<?php echo esc_attr( $inp_id ); ?>"
				class="detail-toggle"
				/>
			<?php printf(
				__( 'As <strong>%1$s</strong> for selected %2$s', 'sidebars-generator' ),
				$sidebar['name'],
				$cat_name
			); ?>
		</label>
		<div class="details">
			<select
				class="acq-datalist <?php echo esc_attr( $base_id ); ?>"
				name="<?php echo esc_attr( $inp_name ); ?>[]"
				multiple="multiple"
				placeholder="<?php echo esc_attr(
					sprintf(
						__( 'Click here to pick available %1$s', 'sidebars-generator' ),
						$cat_name
					)
				); ?>"
			>
			</select>
		</div>
	</div>
	<?php
}

?>

<form class="frm-location acquaintui-form">
	<input type="hidden" name="do" value="set-location" />
	<input type="hidden" name="sb" class="sb-id" value="" />

	<div class="acq-title">
		<h3 class="no-pad-top">
			<span class="sb-name">...</span>
		</h3>
	</div>
	<p>
		<i class="dashicons dashicons-info light"></i>
		<?php
		printf(
			__(
				'To attach this sidebar to a unique Post or Page please visit ' .
				'that <a href="%1$s">Post</a> or <a href="%2$s">Page</a> & set it ' .
				'up via the sidebars metabox.', 'sidebars-generator'
			),
			admin_url( 'edit.php' ),
			admin_url( 'edit.php?post_type=page' )
		);
		?>
	</p>

	<?php
	/* single pages and category */
	?>
	<div class="acquaintui-box">
		<h3>
			<a href="#" class="toggle" title="<?php _e( 'Click to toggle' ); /* This is a Wordpress default language */ ?>"><br></a>
			<span><?php _e( 'For all Single Entries matching selected criteria', 'sidebars-generator' ); ?></span>
		</h3>
		<div class="inside">
			<p><?php _e( 'These replacements will be applied to every single post that matches a certain post type or category.', 'sidebars-generator' ); ?>

			<div class="acq-half">
			<?php
			/* single categories */
			foreach ( $sidebars as $sb_id => $details ) {
				$cat_name = __( 'categories', 'sidebars-generator' );
				_show_replaceable( $details, 'cat', $cat_name );
			}
			?>
			</div>

			<div class="acq-half">
			<?php
			/*  single post-type */
			foreach ( $sidebars as $sb_id => $details ) {
				$cat_name = __( 'Post Types', 'sidebars-generator' );
				_show_replaceable( $details, 'pt', $cat_name );
			}
			?>
			</div>

		</div>
	</div>

	<?php
	/* ARCHIVE pages */
	?>
	<div class="acquaintui-box closed">
		<h3>
			<a href="#" class="toggle" title="<?php _e( 'Click to toggle' ); /* This is a Wordpress default language */ ?>"><br></a>
			<span><?php _e( 'For Archives', 'sidebars-generator' ); ?></span>
		</h3>
		<div class="inside">
			<p><?php _e( 'These replacements will be applied to Archive Type posts and pages.', 'sidebars-generator' ); ?>

			<h3 class="acquaintui-tab">
				<a href="#tab-arch" class="tab active"><?php _e( 'Archive Types', 'sidebars-generator' ); ?></a>
				<a href="#tab-catg" class="tab"><?php _e( 'Category Archives', 'sidebars-generator' ); ?></a>
				<a href="#tab-aut" class="tab"><?php _e( 'Authors', 'sidebars-generator' ); ?></a>
			</h3>
			<div class="acquaintui-tab-content">
				<div id="tab-arch" class="tab active">
					<?php
					foreach ( $sidebars as $sb_id => $details ) {
						$cat_name = __( 'Archive Types', 'sidebars-generator' );
						_show_replaceable( $details, 'arc', $cat_name );
					}
					?>
				</div>
				<div id="tab-catg" class="tab">
					<?php
					foreach ( $sidebars as $sb_id => $details ) {
						$cat_name = __( 'Category Archives', 'sidebars-generator' );
						_show_replaceable( $details, 'arc-cat', $cat_name );
					}
					?>
				</div>
				<div id="tab-aut" class="tab">
					<?php
					foreach ( $sidebars as $sb_id => $details ) {
						$cat_name = __( 'Author Archives', 'sidebars-generator' );
						_show_replaceable( $details, 'arc-aut', $cat_name );
					}
					?>
				</div>
			</div>
		</div>
	</div>

	<div class="buttons">
		<button type="button" class="button-link btn-cancel"><?php _e( 'Cancel', 'sidebars-generator' ); ?></button>
		<button type="button" class="button-primary btn-save"><?php _e( 'Save Changes', 'sidebars-generator' ); ?></button>
	</div>
</form>
