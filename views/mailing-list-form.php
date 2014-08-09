<?php
/**
 * Mailing list subscription form
 *
 * @version 1.0
 */

$desc_tag = ( !empty( $desc_tag ) ) ? $desc_tag : 'p';

?>
<form id="mailing-list-form" class="mailing-list-form" method="GET" action="<?php echo get_template_directory_uri() . '/api/mailing-list.php'; ?>">
	<?php echo $desc = ( !empty( $desc ) ) ? "<$desc_tag>$desc</$desc_tag>" : ''; ?>
	<input id="mailing-list-form-action" class="action" type="hidden" name="action" value="save">
	<input id="mailing-list-form-redirect" class="redirect" type="hidden" name="redirect" value="<?php bloginfo( 'url' ); ?>">
	<input id="mailing-list-form-email" class="email" type="text" name="email" value="" placeholder="Email" required>
	<input id="mailing-list-form-submit" type="submit" value="Subscribe">
	<?php echo $message = ( !empty( $_GET['user'] ) ) ? '<p class="message">' . urldecode( $_GET['user'] ) . '</p>' : ''; ?>
</form>

<?php if( get_option( 'mailing_list_settings_ajax' ) ) : ?>
<script>
$(function() {
	var form = $('#mailing-list-form');
	form.on('submit', function(e) {
		e.preventDefault();
		form.find('.redirect').remove();
		form.find('.message').remove();
		$.ajax({
			url: $(this).attr('action'),
			type: 'GET',
			dataType: 'json',
			data: $(this).serialize(),
			error: function() {
				form.append('<p class="message">Sorry, something went wrong.  Please try again later.</p>');
			},
			success: function(resp) {
				if(resp.status === 'success') {
					form.find('.email').val('');
					form.append('<p class="message">' + resp.user + '</p>');
				} else {
					form.append('<p class="message">' + resp.user + '</p>');
				}
			}
		});
	});
});
</script>
<?php endif; ?>
