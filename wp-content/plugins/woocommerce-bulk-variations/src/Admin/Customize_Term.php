<?php

namespace Barn2\Plugin\WC_Bulk_Variations\Admin;

use Barn2\WBV_Lib\Registerable,
	Barn2\WBV_Lib\Plugin\Licensed_Plugin;

/**
 * Customize the attribute term adding a thumbnail field
 *
 * @package   Barn2\woocommerce-bulk-variations
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Customize_Term implements Registerable {

	public function __construct( Licensed_Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	public function register() {
		$p_taxs = wc_get_attribute_taxonomies();

		foreach ( $p_taxs as $p_tax ) {
			add_action( "pa_{$p_tax->attribute_name}_add_form_fields", [ $this, 'add_term_field' ] );
			add_action( "pa_{$p_tax->attribute_name}_edit_form_fields", [ $this, 'edit_term_field' ] );
			add_filter( "manage_edit-pa_{$p_tax->attribute_name}_columns", [ $this, 'get_columns' ] );
			add_filter( "manage_pa_{$p_tax->attribute_name}_custom_column", [ $this, 'custom_column_content' ], 10, 3 );
			add_action( 'edit_term', [ $this, 'save_term_fields' ], 10, 3 );
			add_action( 'create_term', [ $this, 'save_term_fields' ], 10, 3 );
		}
	}

	public function get_columns( $columns ) {
		unset( $columns['cb'] );
		$columns = array_merge(
			[
				'cb'              => '<input type="checkbox" />',
				'wcbvp-thumbnail' => '',
			],
			$columns
		);
		return $columns;
	}

	public function custom_column_content( $blank, $column, $term_id ) {
		if ( 'wcbvp-thumbnail' === $column ) {
			$thumbnail_url = wc_placeholder_img_src();
			$thumbnail_id  = get_term_meta( $term_id, 'wcbvp-thumbnail', true );

			if ( wp_attachment_is_image( $thumbnail_id ) ) {
				$thumbnail_url = wp_get_attachment_url( $thumbnail_id );
			}

			?>
				<img src="<?php echo esc_url( $thumbnail_url ); ?>" width="60px" height="60px" />
			<?php
		}
	}

	public function save_term_fields( $term_id, $tt_id, $taxonomy ) {
		if ( 0 !== strpos( $taxonomy, 'pa_' ) ) {
			return;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		if ( isset( $_POST['pa_term_thumbnail_id'] ) ) {
			$thumbnail_id = $_POST['pa_term_thumbnail_id'];

			if ( wp_attachment_is_image( $thumbnail_id ) ) {
				update_term_meta( $term_id, 'wcbvp-thumbnail', esc_attr( $thumbnail_id ) );
				return;
			}
		}
		delete_term_meta( $term_id, 'wcbvp-thumbnail' );
		// phpcs:enable
	}

	public function add_term_field( $term ) {
		$thumbnail_url = wc_placeholder_img_src();
		$thumbnail_id  = get_term_meta( $term->term_id, 'wcbvp-thumbnail', true );

		if ( wp_attachment_is_image( $thumbnail_id ) ) {
			$thumbnail_url = wp_get_attachment_url( $thumbnail_id );
		}

		?>
			<div class="form-field term-description-wrap">
				<label><?php esc_html_e( 'Thumbnail', 'woocommerce-bulk-variations' ); ?></label>
				<div id="pa_term_thumbnail" style="float: left; margin-right: 10px;">
					<img src="<?php echo esc_url( $thumbnail_url ); ?>" width="60px" height="60px" />
				</div>
				<div style="line-height: 60px;">
					<input type="hidden" id="pa_term_thumbnail_id" name="pa_term_thumbnail_id" value="<?php echo esc_attr( $thumbnail_id ); ?>" />
					<button type="button" class="upload_image_button button"><?php esc_html_e( 'Upload/Add image', 'woocommerce-bulk-variations' ); ?></button>
					<button type="button" class="remove_image_button button"><?php esc_html_e( 'Remove image', 'woocommerce-bulk-variations' ); ?></button>
				</div>
				<script type="text/javascript">

					// Only show the "remove image" button when needed
					if ( ! jQuery( '#pa_term_thumbnail_id' ).val() ) {
						jQuery( '.remove_image_button' ).hide();
					}

					// Uploading files
					var file_frame;

					jQuery( document ).on( 'click', '.upload_image_button', function( event ) {

						event.preventDefault();

						// If the media frame already exists, reopen it.
						if ( file_frame ) {
							file_frame.open();
							return;
						}

						// Create the media frame.
						file_frame = wp.media.frames.downloadable_file = wp.media({
							title: '<?php esc_html_e( 'Choose an image', 'woocommerce-bulk-variations' ); ?>',
							button: {
								text: '<?php esc_html_e( 'Use image', 'woocommerce-bulk-variations' ); ?>'
							},
							multiple: false
						});

						// When an image is selected, run a callback.
						file_frame.on( 'select', function() {
							var attachment           = file_frame.state().get( 'selection' ).first().toJSON();
							var attachment_thumbnail = attachment.sizes.thumbnail || attachment.sizes.full;

							jQuery( '#pa_term_thumbnail_id' ).val( attachment.id );
							jQuery( '#pa_term_thumbnail' ).find( 'img' ).attr( 'src', attachment_thumbnail.url );
							jQuery( '.remove_image_button' ).show();
						});

						// Finally, open the modal.
						file_frame.open();
					});

					jQuery( document ).on( 'click', '.remove_image_button', function() {
						jQuery( '#pa_term_thumbnail' ).find( 'img' ).attr( 'src', '<?php echo esc_js( wc_placeholder_img_src() ); ?>' );
						jQuery( '#pa_term_thumbnail_id' ).val( '' );
						jQuery( '.remove_image_button' ).hide();
						return false;
					});

					jQuery( document ).ajaxComplete( function( event, request, options ) {
						if ( request && 4 === request.readyState && 200 === request.status
							&& options.data && 0 <= options.data.indexOf( 'action=add-tag' ) ) {

							var res = wpAjax.parseAjaxResponse( request.responseXML, 'ajax-response' );
							if ( ! res || res.errors ) {
								return;
							}
							// Clear Thumbnail fields on submit
							jQuery( '#pa_term_thumbnail' ).find( 'img' ).attr( 'src', '<?php echo esc_js( wc_placeholder_img_src() ); ?>' );
							jQuery( '#pa_term_thumbnail_id' ).val( '' );
							jQuery( '.remove_image_button' ).hide();
							return;
						}
					} );

				</script>
			</div>
		<?php
	}

	public function edit_term_field( $term ) {
		$thumbnail_url = wc_placeholder_img_src();
		$thumbnail_id  = get_term_meta( $term->term_id, 'wcbvp-thumbnail', true );

		if ( wp_attachment_is_image( $thumbnail_id ) ) {
			$thumbnail_url = wp_get_attachment_url( $thumbnail_id );
		}

		?>
			<tr class="form-field term-thumbnail-wrap">
				<th scope="row">
					<label><?php _e( 'Thumbnail', 'woocommerce-bulk-variations' ); ?></label>
				</th>
				<td>
					<label><?php esc_html_e( 'Thumbnail', 'woocommerce-bulk-variations' ); ?></label>
					<div id="pa_term_thumbnail" style="float: left; margin-right: 10px;">
						<img src="<?php echo esc_url( $thumbnail_url ); ?>" width="60px" height="60px" />
					</div>
					<div style="line-height: 60px;">
						<input type="hidden" id="pa_term_thumbnail_id" name="pa_term_thumbnail_id" value="<?php echo esc_attr( $thumbnail_id ); ?>" />
						<button type="button" class="upload_image_button button"><?php esc_html_e( 'Upload/Add image', 'woocommerce-bulk-variations' ); ?></button>
						<button type="button" class="remove_image_button button"><?php esc_html_e( 'Remove image', 'woocommerce-bulk-variations' ); ?></button>
					</div>
					<script type="text/javascript">

						// Only show the "remove image" button when needed
						if ( ! jQuery( '#pa_term_thumbnail_id' ).val() ) {
							jQuery( '.remove_image_button' ).hide();
						}

						// Uploading files
						var file_frame;

						jQuery( document ).on( 'click', '.upload_image_button', function( event ) {

							event.preventDefault();

							// If the media frame already exists, reopen it.
							if ( file_frame ) {
								file_frame.open();
								return;
							}

							// Create the media frame.
							file_frame = wp.media.frames.downloadable_file = wp.media({
								title: '<?php esc_html_e( 'Choose an image', 'woocommerce-bulk-variations' ); ?>',
								button: {
									text: '<?php esc_html_e( 'Use image', 'woocommerce-bulk-variations' ); ?>'
								},
								multiple: false
							});

							// When an image is selected, run a callback.
							file_frame.on( 'select', function() {
								var attachment           = file_frame.state().get( 'selection' ).first().toJSON();
								var attachment_thumbnail = attachment.sizes.thumbnail || attachment.sizes.full;

								jQuery( '#pa_term_thumbnail_id' ).val( attachment.id );
								jQuery( '#pa_term_thumbnail' ).find( 'img' ).attr( 'src', attachment_thumbnail.url );
								jQuery( '.remove_image_button' ).show();
							});

							// Finally, open the modal.
							file_frame.open();
						});

						jQuery( document ).on( 'click', '.remove_image_button', function() {
							jQuery( '#pa_term_thumbnail' ).find( 'img' ).attr( 'src', '<?php echo esc_js( wc_placeholder_img_src() ); ?>' );
							jQuery( '#pa_term_thumbnail_id' ).val( '' );
							jQuery( '.remove_image_button' ).hide();
							return false;
						});

						jQuery( document ).ajaxComplete( function( event, request, options ) {
							if ( request && 4 === request.readyState && 200 === request.status
								&& options.data && 0 <= options.data.indexOf( 'action=add-tag' ) ) {

								var res = wpAjax.parseAjaxResponse( request.responseXML, 'ajax-response' );
								if ( ! res || res.errors ) {
									return;
								}
								// Clear Thumbnail fields on submit
								jQuery( '#pa_term_thumbnail' ).find( 'img' ).attr( 'src', '<?php echo esc_js( wc_placeholder_img_src() ); ?>' );
								jQuery( '#pa_term_thumbnail_id' ).val( '' );
								jQuery( '.remove_image_button' ).hide();
								return;
							}
						} );

					</script>
				</td>
			</tr>
		<?php
	}

}
