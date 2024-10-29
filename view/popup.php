<!-- create new sidebar -->

<form class="acquaintui-form">
	<input type="hidden" name="do" value="save" />
	<input type="hidden" name="sb" id="acqb-id" value="" />

	<div>
		<div class="col-3">
			<label for="acqb-name"><?php _e( 'Name', 'sidebars-generator' ); ?></label>
			<input type="text" name="name" id="acqb-name" maxlength="40"/>
		</div>
		<div class="col-5">
			<label for="acqb-description"><?php _e( 'Description', 'sidebars-generator' ); ?></label>
			<textarea type="text" name="description" id="acqb-description"></textarea>
		</div>
	</div>
	<div class="buttons">
		<button type="button" class="button-link btn-cancel"><?php _e( 'Cancel', 'sidebars-generator' ); ?></button>
		<button type="button" class="button-primary btn-save"><?php _e( 'Create', 'sidebars-generator' ); ?></button>
	</div>
</form>