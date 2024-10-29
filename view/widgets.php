<?php
/* edit default widget page */
?>

<div id="widgets-extra">

	<div id="title-options">
		<h2><?php _e( 'Sidebars', 'sidebars-generator' ); ?></h2>
		<div>
			<button type="button" class="button button-primary action btn-create-sidebar">
				<?php _e( 'Create Acquaint Sidebar', 'sidebars-generator' ); ?>
			</button>
			<?php
			/**
			 * Show additional functions in the widget header.
			 */
			do_action( 'acq_widget_header' );
			?>
		</div>	
	</div>


	<!--languages-->
	<script>
	SidebarsData = {
		'title_edit': "<?php _e( 'Edit Acquaint Sidebar', 'sidebars-generator' ); ?>",
		'title_new': "<?php _e( 'New Acquaint Sidebar', 'sidebars-generator' ); ?>",
		'btn_edit': "<?php _e( 'Save', 'sidebars-generator' ); ?>",
		'btn_new': "<?php _e( 'Create Sidebar', 'sidebars-generator' ); ?>",
		'title_delete': "<?php _e( 'Delete Sidebar', 'sidebars-generator' ); ?>",
		'title_location': "<?php _e( 'Define where you want this sidebar to appear.', 'sidebars-generator' ); ?>",
		'custom_sidebars': "<?php _e( 'Acquaint sidebars list', 'sidebars-generator' ); ?>",
		'theme_sidebars': "<?php _e( 'Default Sidebars', 'sidebars-generator' ); ?>",
	
		'replaceable': <?php echo json_encode( (object) ACS_Class::get_options( 'modifiable' ) ); ?>
	};
	</script>


	<!--Custom sidebars   -->
	<div class="display-custom-sidebar toolbar">
		<a
			class="tool delete-sidebar"
			data-action="delete"
			href="#"
			title="<?php _e( 'Delete', 'sidebars-generator' ); ?>"
			>
			<i class="dashicons dashicons-trash"></i>
		</a>
		<span class="separator">|</span>
		<a
			class="tool"
			data-action="edit"
			href="#"
			title="<?php _e( 'Edit this sidebar.', 'sidebars-generator' ); ?>"
			>
			<?php _e( '<i class="dashicons dashicons-edit"></i>', 'sidebars-generator' ); ?>
		</a>
	</div>

   <!-- Theme Sidebars -->
	<div class="display-theme-sidebar toolbar">
		<label
			for="replaceable"
			class="tool btn-replaceable"
			data-action="replaceable"
			data-on="<?php _e( 'This sidebar can be replaced on certain pages', 'sidebars-generator' ); ?>"
			data-off="<?php _e( 'This sidebar will always be same on all pages', 'sidebars-generator' ); ?>"
			>
			<span class="icon"></span>
			<input
				type="checkbox"
				id=""
				class="has-label chk-replaceable"
				/>
			<span class="is-label">
				<?php _e( 'Allow this sidebar to be replaced', 'sidebars-generator' ); ?>
			</span>
		</label>
	</div>

   <!--delete sidebar-->
	<div class="delete">
	<?php include CSB_VIEWS_DIR . 'delete.php'; ?>
	</div>

   <!--edit sidebar-->
	<div class="editor">
	<?php include CSB_VIEWS_DIR . 'popup.php'; ?>
	</div>
    
    <!--location-->
	<div class="place">
	<?php include CSB_VIEWS_DIR . 'sidebar.php'; ?>
	</div>

 </div>
