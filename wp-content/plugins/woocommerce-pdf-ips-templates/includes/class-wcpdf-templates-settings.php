<?php

namespace WPO\WC\PDF_Invoices_Templates;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\\WPO\\WC\\PDF_Invoices_Templates\\Settings' ) ) :

class Settings {		
	
	public $settings;
	public $option   = 'wpo_wcpdf_editor_settings';
	
	protected static $_instance = null;
	
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	
	public function __construct() {
		$this->settings = get_option( $this->option, array() );
		
		// Hook into main pdf plugin settings
		add_filter( 'wpo_wcpdf_settings_tabs', array( $this, 'settings_tab' ) );
		add_action( 'admin_init', array( $this, 'init_settings' ) );
		add_action( 'wpo_wcpdf_before_settings', array( $this, 'column_editor' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'load_scripts_styles' ), 99 );

		// Fix compatibility issues with YIT themes and other plugins loading jquery-ui styles everywhere
		add_action( 'admin_enqueue_scripts', array( $this, 'dequeue_jquery_ui_styles' ), 999 );

		// Footer height settings (also initiated in the template functions but registered here too for backwards compatibility)
		add_filter( 'wpo_wcpdf_settings_fields_general', array( $this, 'add_footer_height_setting' ), 10, 4 );

		// Replace extra fields description based on selected template
		add_filter( 'wpo_wcpdf_settings_fields_general', array( $this, 'extra_fields_description_replacement' ), 10, 4 );

		// Add field to columns or totals
		add_action( 'wp_ajax_wcpdf_templates_add_totals_columns_field', array( $this, 'add_totals_columns_field' ) );

		// Add custom block
		add_action( 'wp_ajax_wcpdf_templates_add_custom_block', array( $this, 'add_custom_block' ) );

		// remove single use query arg for restoring defaults
		add_action( 'updated_option', array( $this, 'remove_load_defaults_after_updating_option' ), 999, 3 );
		
		// update editor columns/totals positions
		add_action( "update_option_{$this->option}", array( $this, 'add_or_update_editor_totals_columns' ), 10, 2 );
		add_action( "add_option_{$this->option}", array( $this, 'add_or_update_editor_totals_columns' ), 10, 2 );
		
		// output settings
		add_action( 'wpo_wcpdf_settings_output_editor', array( $this, 'output' ), 10, 1 );

		// show notice when the Simple template is active
		add_action( 'wpo_wcpdf_before_settings_page', array( $this, 'simple_template_notice' ), 1 );
		
		// import/export customizer settings
		add_filter( 'wpo_wcpdf_setting_types', array( $this, 'customizer_setting_types' ), 10, 1 );
		add_filter( 'wpo_wcpdf_export_settings', array( $this, 'customizer_settings_export' ), 10, 2 );
		add_filter( 'wpo_wcpdf_import_settings_option', array( $this, 'customizer_settings_option_import' ), 10, 3 );
		add_filter( 'wpo_wcpdf_reset_settings_option', array( $this, 'customizer_settings_option_reset' ), 10, 2 );
	}
	
	public function output( $section ) {
		settings_fields( $this->option );
		do_settings_sections( $this->option );
		submit_button();
	}

	/**
	 * Display notification when the Simple theme is selected
	 */
	public function simple_template_notice ( $active_tab ) {
		if ( $active_tab == 'editor' ) {
			$settings = get_option( 'wpo_wcpdf_settings_general', array() ); 

			if ( is_array( $settings ) && isset( $settings['template_path'] ) ) {
				if ( $settings['template_path'] == 'default/Simple' ) {					
					?>
					<div class="notice notice-warning inline">
						<p>
							<?php
							echo wp_kses_post( __( 'The <strong>Simple</strong> template has limited compatibilty with the Customizer. You will need to switch your template to a premium one, e.g. <strong>Simple Premium</strong>, if you want to take full advantage of the Customizer.', 'wpo_wcpdf_templates' ) );
							?>
						</p>
					</div>
					<?php
				}
			}
		}
	}

	/**
	 * Styles for settings page
	 */
	public function load_scripts_styles ( $hook ) {
		// only load on our own settings page
		// maybe find a way to refer directly to WPO\WC\PDF_Invoices\Settings::$options_page_hook ?
		if ( !( $hook == 'woocommerce_page_wpo_wcpdf_options_page' || $hook == 'settings_page_wpo_wcpdf_options_page' || ( isset($_GET['page']) && $_GET['page'] == 'wpo_wcpdf_options_page' ) ) ) {
			return;
		}

		wp_enqueue_script(
			'wcpdf-editor',
			WPO_WCPDF_Templates()->plugin_url() . '/assets/js/editor.js',
			array( 'jquery-ui-accordion', 'jquery-ui-sortable', 'jquery-ui-draggable', 'jquery-ui-tabs', 'wc-enhanced-select', 'jquery-tiptip' ),
			WPO_WCPDF_TEMPLATES_VERSION
		);

		wp_enqueue_style(
			'wcpdf-editor',
			WPO_WCPDF_Templates()->plugin_url() . '/assets/css/editor.css',
			array(),
			WPO_WCPDF_TEMPLATES_VERSION
		);

		wp_enqueue_style(
			'woocommerce-pdf-ips-templates-jquery-ui-style',
			'https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/themes/smoothness/jquery-ui.css'
		);

		wp_localize_script(
			'wcpdf-editor',
			'wpo_wcpdf_templates',
			array(  
				'ajaxurl'        => admin_url( 'admin-ajax.php' ), // URL to WordPress ajax handling page
				'nonce'          => wp_create_nonce('wpo_wcpdf_templates'),
			)
		);
	}

	/**
	 * Dequeue YIT styles (they're all over the place man!)
	 */
	public function dequeue_jquery_ui_styles ( $hook ) {
		// only load on our own settings page
		// maybe find a way to refer directly to WPO\WC\PDF_Invoices\Settings::$options_page_hook ?
		if ( !( $hook == 'woocommerce_page_wpo_wcpdf_options_page' || $hook == 'settings_page_wpo_wcpdf_options_page' ) ) {
			return;
		}

		$offending_styles = array (
			'jquery-ui-overcast',
			'yit-plugin-metaboxes',
			'jquery-ui-style',
			'jquery-ui',
			'jquery-style',
			'yit-jquery-ui-style',
			'jquery-ui-style-css',
			'yith-wcaf',
			'yith_ywdpd_admin',
			'ig-pb-jquery-ui',
			'jquery_smoothness_ui',
			'fblb_jquery-ui',
			'wp-review-admin-ui-css',
			'tribe-jquery-ui-theme',
			'jquery-style-css',
		);
		$offending_scripts = array();

		if ( class_exists( '\Zhours\Setup' ) ) { // Order Store Hours Scheduler for WooCommerce
			$offending_styles[] = 'custom_wp_admin_css';
			$offending_scripts[] = 'wc-jquery-ui';
			$offending_scripts[] = 'wc-multidatespicker';
		}

		foreach ($offending_styles as $handle) {
			wp_dequeue_style( $handle );
			wp_deregister_style( $handle );
		}

		foreach ($offending_scripts as $handle) {
			wp_dequeue_script( $handle );
			wp_deregister_script( $handle );
		}
	}

	public function get_settings ( $template_type, $settings_name, $document = null ) {
		$editor_settings = get_option( $this->option );

		$settings_key = 'fields_'.$template_type.'_'.$settings_name;
		if (isset($editor_settings[$settings_key])) {
			$settings = $editor_settings[$settings_key];
		} else {
			$settings = array();
		}

		// use defaults if settings not defined
		if ( empty($settings) && !isset($editor_settings['settings_saved'])) {
			// only packing slip is different
			if ( $template_type == 'packing-slip' ) {
				switch ($settings_name) {
					case 'columns':
						$settings = array (
							1 => array (
								'type'			=> 'sku',
							),
							2 => array (
								'type'			=> 'description',
								'show_meta'		=> 1,
							),
							3 => array (
								'type'			=> 'quantity',
							),
						);
						break;
					case 'totals':
						$settings = array();
						break;
				}
			} else {
				switch ($settings_name) {
					case 'columns':
						$settings = array (
							1 => array (
								'type'			=> 'sku',
							),
							2 => array (
								'type'			=> 'description',
								'show_meta'		=> 1,
							),
							3 => array (
								'type'			=> 'quantity',
							),
							4 => array (
								'type'			=> 'price',
								'price_type'	=> 'single',
								'tax'			=> 'excl',
								'discount'		=> 'before',
							),
							5 => array (
								'type'			=> 'tax_rate',
							),
							6 => array (
								'type'			=> 'price',
								'price_type'	=> 'total',
								'tax'			=> 'excl',
								'discount'		=> 'before',
							),
						);
						break;
					case 'totals':
						$settings = array(
							1 => array (
								'type'			=> 'subtotal',
								'tax'			=> 'excl',
								'discount'		=> 'before',
							),
							2 => array (
								'type'			=> 'discount',
								'tax'			=> 'excl',
							),
							3 => array (
								'type'			=> 'shipping',
								'tax'			=> 'excl',
							),
							4 => array (
								'type'			=> 'fees',
								'tax'			=> 'excl',
							),
							5 => array (
								'type'			=> 'vat',
							),
							6 => array (
								'type'			=> 'total',
								'tax'			=> 'incl',
							),
						);
						break;
				}
			}
		}

		return apply_filters( 'wpo_wcpdf_template_editor_settings', $settings, $template_type, $settings_name, $document );
	}

	/**
	 * add Editor settings tab to the PDF Invoice settings page
	 * @param  array $tabs slug => Title
	 * @return array $tabs with Editor
	 */
	public function settings_tab( $tabs ) {
		$tabs['editor'] = array(
			'title'          => __( 'Customizer', 'wpo_wcpdf_templates' ),
			'preview_states' => 2,
		);
		return $tabs;
	}

	public function column_editor ( $settings_tab ) {
		if ( $settings_tab != 'editor') {
			return;
		}

		// hidden option to check if user has saved/modified the settings (to know whether to load defaults or not!)
		printf( '<input type="hidden" data-key="type" name="%s[settings_saved]" value="1">', $this->option );

		// show drag & drop editor
		$editor_args = array(
			'menu'        => $this->option,
			'id'          => 'fields',
			'documents'   => array(),
			'description' => __( 'Drag & drop any of these fields to the documents below', 'wpo_wcpdf_templates' ),
		);

		$documents = WPO_WCPDF()->documents->get_documents( 'all' );
		foreach ( $documents as $document ) {
			$document_type = $document->get_type();
			$editor_args['documents'][$document_type] = $document->get_title();
		}

		$this->columns_editor_callback( $editor_args );

		?>
		<style>
		#wpo-wcpdf-settings .form-table td,
		#wpo-wcpdf-settings .form-table th {
			display: block !important;
			padding: 0 !important;
		}
		</style>
		<?php
	}

	/**
	 * User settings.
	 */
	public function init_settings() {
		$page = $option_group = $option_name = $this->option;

		$settings_fields = array(
			array(
				'type'		=> 'section',
				'id'		=> 'custom_styles',
				'title'		=> '',
				'callback'	=> 'section',
			),
			array(
				'type'			=> 'setting',
				'id'			=> 'custom_styles',
				'title'			=> sprintf('<h3>%s</h3>', __( 'Custom Styles', 'wpo_wcpdf_templates' )),
				'callback'		=> 'textarea',
				'section'		=> 'custom_styles',
				'args'			=> array(
					'option_name'	=> $option_name,
					'id'			=> 'custom_styles',
					'description'	=> __( 'Enter any custom styles here to modify/override the template styles', 'wpo_wcpdf_templates' ),
					'width'			=> '72',
					'height'		=> '8',
				)
			),
		);

		// allow plugins to alter settings fields
		$settings_fields = apply_filters( 'wpo_wcpdf_settings_fields_customizer', $settings_fields, $page, $option_group, $option_name );
		WPO_WCPDF()->settings->add_settings_fields( $settings_fields, $page, $option_group, $option_name );
		
		return;	
	}

	/**
	 * Section null callback.
	 *
	 * @return void.
	 */
	public function section_options_callback() {
	}

	public function get_sorting_options() {
		return array (
			'title'		=> __( 'Sort items by', 'wpo_wcpdf_templates' ),
			'options'	=> array (
				'default'	=> __( 'Default', 'wpo_wcpdf_templates' ),
				'product'	=> __( 'Product name', 'wpo_wcpdf_templates' ),
				'sku'		=> __( 'SKU', 'wpo_wcpdf_templates' ),
				'category'	=> __( 'Category', 'wpo_wcpdf_templates' ),
			),
		);
	}

	public function get_columns_field_options() {
		return apply_filters( 'wpo_wcpdf_templates_customizer_column_blocks', array (
			'position'		=> array (
				'title'		=> __( 'Position', 'wpo_wcpdf_templates' ),
				'options'	=> array (
					'label'		=> array(
						'type'			=> 'text',
						'description'	=> __( 'Label', 'wpo_wcpdf_templates' ),
					),
				),
			),
			'sku'			=> array (
				'title'		=> __( 'SKU', 'wpo_wcpdf_templates' ),
				'options'	=> array (
					'label'		=> array(
						'type'			=> 'text',
						'description'	=> __( 'Label', 'wpo_wcpdf_templates' ),
						'placeholder'	=> __( 'Use default', 'wpo_wcpdf_templates' ),
					),
				),
			),
			'thumbnail'		=> array (
				'title'		=> __( 'Thumbnail', 'wpo_wcpdf_templates' ),
				'options'	=> array (
					'label'		=> array(
						'type'			=> 'text',
						'description'	=> __( 'Label', 'wpo_wcpdf_templates' ),
						'placeholder'	=> __( 'Use default', 'wpo_wcpdf_templates' ),
					),
				),
			),
			'description'	=> array (
				'title'		=> __( 'Product', 'wpo_wcpdf_templates' ),
				'options'	=> array (
					'label'			=> array(
						'type'			=> 'text',
						'description'	=> __( 'Label', 'wpo_wcpdf_templates' ),
						'placeholder'	=> __( 'Use default', 'wpo_wcpdf_templates' ),
					),
					'show_sku'		=> array(
						'type'			=> 'checkbox',
						'description'	=> __( 'Show SKU', 'wpo_wcpdf_templates' ),
					),
					'show_weight'	=> array(
						'type'			=> 'checkbox',
						'description'	=> __( 'Show weight', 'wpo_wcpdf_templates' ),
					),
					'show_meta'		=> array(
						'type'			=> 'checkbox',
						'description'	=> __( 'Show meta data', 'wpo_wcpdf_templates' ),
					),
					'show_external_plugin_meta'	=> array(
						'type'			=> 'checkbox',
						'description'	=> __( 'Show third party plugin data', 'wpo_wcpdf_templates' ),
					),
					'custom_text'	=> array(
						'type'			=> 'textarea',
						'rows'			=> 4,
						'description'	=> __( 'Text', 'wpo_wcpdf_templates' ),
					),
				),
			),
			'quantity'		=> array (
				'title'		=> __( 'Quantity', 'wpo_wcpdf_templates' ),
				'options'	=> array (
					'label'		=> array(
						'type'			=> 'text',
						'description'	=> __( 'Label', 'wpo_wcpdf_templates' ),
						'placeholder'	=> __( 'Use default', 'wpo_wcpdf_templates' ),
					),
				),
			),
			'all_meta'		=> array (
				'title'		=> __( 'Variation / item meta', 'wpo_wcpdf_templates' ),
				'options'	=> array (
					'label'		=> array(
						'type'			=> 'text',
						'description'	=> __( 'Label', 'wpo_wcpdf_templates' ),
						'placeholder'	=> __( 'Use default', 'wpo_wcpdf_templates' ),
					),
					'product_fallback'	=> array(
						'type'			=> 'checkbox',
						'description'	=> __( 'Fallback to product variation data', 'wpo_wcpdf_templates' ),
					),
				),
			),
			'item_meta'	=> array (
				'title'		=> __( 'Item meta (single)', 'wpo_wcpdf_templates' ),
				'options'	=> array (
					'field_name' => array(
						'type'			=> 'text',
						'description'	=> __( 'Meta key / name', 'wpo_wcpdf_templates' ),
					),
					'label'		=> array(
						'type'			=> 'text',
						'description'	=> __( 'Label', 'wpo_wcpdf_templates' ),
					),
				),
			),
			'price'	=> array (
				'title'		=> __( 'Price', 'wpo_wcpdf_templates' ),
				'options'	=> array (
					'label'		=> array(
						'type'			=> 'text',
						'description'	=> __( 'Label', 'wpo_wcpdf_templates' ),
						'placeholder'	=> __( 'Use default', 'wpo_wcpdf_templates' ),
					),
					'price_type'	=> array(
						'type'			=> 'select',
						'options'		=> array(
							'single'	=> __( 'Single price', 'wpo_wcpdf_templates' ),
							'total'		=> __( 'Total price', 'wpo_wcpdf_templates' ),
						),
					),
					'tax'	=> array(
						'type'			=> 'select',
						'options'		=> array(
							'incl'		=> __( 'Including tax', 'wpo_wcpdf_templates' ),
							'excl'		=> __( 'Excluding tax', 'wpo_wcpdf_templates' ),
						),
					),
					'discount'	=> array(
						'type'			=> 'select',
						'options'		=> array(
							'before'	=> __( 'Before discount', 'wpo_wcpdf_templates' ),
							'after'		=> __( 'After discount', 'wpo_wcpdf_templates' ),
						),
					),
					'only_discounted'	=> array(
						'type'			=> 'checkbox',
						'description'	=> __( 'Show column only for discounted orders', 'wpo_wcpdf_templates' ),
					),
				),
			),
			'regular_price'	=> array (
				'title'		=> __( 'Regular price', 'wpo_wcpdf_templates' ),
				'options'	=> array (
					'label'		=> array(
						'type'			=> 'text',
						'description'	=> __( 'Label', 'wpo_wcpdf_templates' ),
						'placeholder'	=> __( 'Use default', 'wpo_wcpdf_templates' ),
					),
					'price_type'	=> array(
						'type'			=> 'select',
						'options'		=> array(
							'single'	=> __( 'Single price', 'wpo_wcpdf_templates' ),
							'total'		=> __( 'Total price', 'wpo_wcpdf_templates' ),
						),
					),
					'tax'	=> array(
						'type'			=> 'select',
						'options'		=> array(
							'incl'		=> __( 'Including tax', 'wpo_wcpdf_templates' ),
							'excl'		=> __( 'Excluding tax', 'wpo_wcpdf_templates' ),
						),
					),
					'only_sale'	=> array(
						'type'			=> 'checkbox',
						'description'	=> __( 'Only show for items that sold for a sale price', 'wpo_wcpdf_templates' ),
					),
				),
			),
			'discount'	=> array (
				'title'		=> __( 'Discount', 'wpo_wcpdf_templates' ),
				'options'	=> array (
					'label'		=> array(
						'type'			=> 'text',
						'description'	=> __( 'Label', 'wpo_wcpdf_templates' ),
						'placeholder'	=> __( 'Use default', 'wpo_wcpdf_templates' ),
					),
					'price_type'	=> array(
						'type'			=> 'select',
						'options'		=> array(
							'single'	=> __( 'Single price', 'wpo_wcpdf_templates' ),
							'total'		=> __( 'Total price', 'wpo_wcpdf_templates' ),
							'percent'	=> __( 'Percent', 'wpo_wcpdf_templates' ),
						),
					),
					'tax'	=> array(
						'type'			=> 'select',
						'options'		=> array(
							'incl'	=> __( 'Including tax', 'wpo_wcpdf_templates' ),
							'excl'		=> __( 'Excluding tax', 'wpo_wcpdf_templates' ),
						),
					),
					'only_discounted'	=> array(
						'type'			=> 'checkbox',
						'description'	=> __( 'Show column only for discounted orders', 'wpo_wcpdf_templates' ),
					),
				),
			),
			'vat'	=> array (
				'title'		=> __( 'VAT', 'wpo_wcpdf_templates' ),
				'options'	=> array (
					'label'		=> array(
						'type'			=> 'text',
						'description'	=> __( 'Label', 'wpo_wcpdf_templates' ),
						'placeholder'	=> __( 'Use default', 'wpo_wcpdf_templates' ),
					),
					'price_type'	=> array(
						'type'			=> 'select',
						'options'		=> array(
							'single'	=> __( 'Single price', 'wpo_wcpdf_templates' ),
							'total'		=> __( 'Total price', 'wpo_wcpdf_templates' ),
						),
					),
					'discount'	=> array(
						'type'			=> 'select',
						'options'		=> array(
							'before'	=> __( 'Before discount', 'wpo_wcpdf_templates' ),
							'after'		=> __( 'After discount', 'wpo_wcpdf_templates' ),
						),
					),
					'split'	=> array(
						'type'          => 'checkbox',
						'description'   => __( 'Split', 'wpo_wcpdf_templates' ) . wc_help_tip( __( 'Split 2 or more taxes in separated columns.', 'wpo_wcpdf_templates' ) ),
					),
					'only_discounted'   => array(
						'type'          => 'checkbox',
						'description'   => __( 'Show column only for discounted orders', 'wpo_wcpdf_templates' ),
					),
					'dash_for_zero'	=> array(
						'type'          => 'checkbox',
						'description'   => sprintf(
							/* translators: (—) */
							__( 'Use a dash %s for zero taxes', 'wpo_wcpdf_templates' ),
							'(—)'
						),
					),
				),
			),
			'tax_rate'	=> array (
				'title'		=> __( 'Tax rate', 'wpo_wcpdf_templates' ),
				'options'	=> array (
					'label'		=> array(
						'type'			=> 'text',
						'description'	=> __( 'Label', 'wpo_wcpdf_templates' ),
						'placeholder'	=> __( 'Use default', 'wpo_wcpdf_templates' ),
					),
					'show_tax_name'		=> array(
						'type'			=> 'checkbox',
						'description'	=> __( 'Show tax rate name', 'wpo_wcpdf_templates' ),
					),
				),
			),
			'weight'			=> array (
				'title'		=> __( 'Weight', 'wpo_wcpdf_templates' ),
				'options'	=> array (
					'label'		=> array(
						'type'			=> 'text',
						'description'	=> __( 'Label', 'wpo_wcpdf_templates' ),
						'placeholder'	=> __( 'Use default', 'wpo_wcpdf_templates' ),
					),
					'qty'	=> array(
						'type'			=> 'select',
						'options'		=> array(
							'single'	=> __( 'Single weight', 'wpo_wcpdf_templates' ),
							'total'		=> __( 'Total weight', 'wpo_wcpdf_templates' ),
						),
					),
					'show_unit'		=> array(
						'type'			=> 'checkbox',
						'description'	=> __( 'Append weight unit', 'wpo_wcpdf_templates' ),
					),
				),
			),
			'dimensions'	=> array (
				'title'		=> __( 'Product dimensions', 'wpo_wcpdf_templates' ),
				'options'	=> array (
					'label'		=> array(
						'type'			=> 'text',
						'description'	=> __( 'Label', 'wpo_wcpdf_templates' ),
						'placeholder'	=> __( 'Use default', 'wpo_wcpdf_templates' ),
					),
				),
			),
			'product_attribute'	=> array (
				'title'		=> __( 'Attribute', 'wpo_wcpdf_templates' ),
				'options'	=> array (
					'attribute_name' => array(
						'type'			=> 'text',
						'description'	=> __( 'Attribute name', 'wpo_wcpdf_templates' ),
					),
					'label'		=> array(
						'type'			=> 'text',
						'description'	=> __( 'Label', 'wpo_wcpdf_templates' ),
					),
				),
			),
			'product_custom'	=> array (
				'title'		=> __( 'Custom field (Product)', 'wpo_wcpdf_templates' ),
				'options'	=> array (
					'field_name' => array(
						'type'			=> 'text',
						'description'	=> __( 'Field name', 'wpo_wcpdf_templates' ),
					),
					'label'		=> array(
						'type'			=> 'text',
						'description'	=> __( 'Label', 'wpo_wcpdf_templates' ),
					),
				),
			),
			'product_description'	=> array (
				'title'		=> __( 'Product description', 'wpo_wcpdf_templates' ),
				'options'	=> array (
					'label'		=> array(
						'type'			=> 'text',
						'description'	=> __( 'Label', 'wpo_wcpdf_templates' ),
						'placeholder'	=> __( 'Use default', 'wpo_wcpdf_templates' ),
					),
					'description_type'	=> array(
						'type'			=> 'select',
						'options'		=> array(
							'short'		=> __( 'Short description', 'wpo_wcpdf_templates' ),
							'long'		=> __( 'Long description', 'wpo_wcpdf_templates' ),
						),
					),
					'use_variation_description' => array(
						'type'			=> 'checkbox',
						'description'	=> __( 'Use variation description when available', 'wpo_wcpdf_templates' ),
					),
				),
			),
			'product_categories'	=> array (
				'title'		=> __( 'Product categories', 'wpo_wcpdf_templates' ),
				'options'	=> array (
					'label'		=> array(
						'type'			=> 'text',
						'description'	=> __( 'Label', 'wpo_wcpdf_templates' ),
						'placeholder'	=> __( 'Use default', 'wpo_wcpdf_templates' ),
					),
				),
			),
			'cb'	=> array (
				'title'		=> __( 'Checkbox', 'wpo_wcpdf_templates' ),
				'options'		=> array (
					'label'		=> array(
						'type'			=> 'text',
						'description'	=> __( 'Label', 'wpo_wcpdf_templates' ),
					),
				),
			),
			'static_text'	=> array (
				'title'			=> __( 'Text', 'wpo_wcpdf_templates' ),
				'options'		=> array (
					'label'		=> array(
						'type'			=> 'text',
						'description'	=> __( 'Label', 'wpo_wcpdf_templates' ),
					),
					'text'		=> array(
						'type'			=> 'textarea',
						'rows'			=> 4,
						'description'	=> __( 'Text', 'wpo_wcpdf_templates' ),
					),
				),
			),
			'custom_function'		=> array (
				'title'		=> __( 'Custom function', 'wpo_wcpdf_templates' ),
				'options'	=> array (
					'label'		=> array(
						'type'			=> 'text',
						'description'	=> __( 'Label', 'wpo_wcpdf_templates' ),
					),
					'function'		=> array(
						'type'			=> 'text',
						'description'	=> __( 'Function name', 'wpo_wcpdf_templates' ),
					),
				),
			),
		) );
	}

	public function get_totals_field_options() {
		return apply_filters( 'wpo_wcpdf_templates_customizer_total_blocks', array (
			'subtotal'	=> array (
				'title'		=> __( 'Subtotal', 'wpo_wcpdf_templates' ),
				'options'	=> array (
					'label'		=> array(
						'type'			=> 'text',
						'description'	=> __( 'Label', 'wpo_wcpdf_templates' ),
						'placeholder'	=> __( 'Use default', 'wpo_wcpdf_templates' ),
					),
					'tax'	=> array(
						'type'			=> 'select',
						'options'		=> array(
							'incl'	=> __( 'Including tax', 'wpo_wcpdf_templates' ),
							'excl'		=> __( 'Excluding tax', 'wpo_wcpdf_templates' ),
						),
					),
					'discount'	=> array(
						'type'			=> 'select',
						'options'		=> array(
							'before'	=> __( 'Before discount', 'wpo_wcpdf_templates' ),
							'after'		=> __( 'After discount', 'wpo_wcpdf_templates' ),
						),
					),
					'only_discounted'	=> array(
						'type'			=> 'checkbox',
						'description'	=> __( 'Show only for discounted orders', 'wpo_wcpdf_templates' ),
					),
				),
			),
			'discount'	=> array (
				'title'		=> __( 'Discount', 'wpo_wcpdf_templates' ),
				'options'	=> array (
					'label'		=> array(
						'type'			=> 'text',
						'description'	=> __( 'Label', 'wpo_wcpdf_templates' ),
						'placeholder'	=> __( 'Use default', 'wpo_wcpdf_templates' ),
					),
					'tax'	=> array(
						'type'			=> 'select',
						'options'		=> array(
							'incl'	=> __( 'Including tax', 'wpo_wcpdf_templates' ),
							'excl'		=> __( 'Excluding tax', 'wpo_wcpdf_templates' ),
						),
					),
					'show_percentage'	=> array(
						'type'			=> 'checkbox',
						'description'	=> __( 'Add discount percentage to label', 'wpo_wcpdf_templates' ),
					),
					'show_codes'	=> array(
						'type'			=> 'checkbox',
						'description'	=> __( 'Add coupon codes to label', 'wpo_wcpdf_templates' ),
					),
					'breakdown_coupons'	=> array(
						'type'			=> 'checkbox',
						'description'	=> __( 'Print each coupon discount separately', 'wpo_wcpdf_templates' ),
					),
				),
			),
			'shipping'	=> array (
				'title'		=> __( 'Shipping', 'wpo_wcpdf_templates' ),
				'options'	=> array (
					'label'		=> array(
						'type'			=> 'text',
						'description'	=> __( 'Label', 'wpo_wcpdf_templates' ),
						'placeholder'	=> __( 'Use default', 'wpo_wcpdf_templates' ),
					),
					'hide_free'		=> array(
						'type'			=> 'checkbox',
						'description'	=> __( 'Hide when free', 'wpo_wcpdf_templates' ),
					),
					'method'		=> array(
						'type'			=> 'checkbox',
						'description'	=> _x( 'Show method instead of cost', 'shipping method', 'wpo_wcpdf_templates' ),
					),
					'tax'	=> array(
						'type'			=> 'select',
						'options'		=> array(
							'incl'	=> __( 'Including tax', 'wpo_wcpdf_templates' ),
							'excl'	=> __( 'Excluding tax', 'wpo_wcpdf_templates' ),
						),
					),
				),
			),
			'fees'	=> array (
				'title'		=> __( 'Fees', 'wpo_wcpdf_templates' ),
				'options'	=> array (
					'tax'	=> array(
						'type'			=> 'select',
						'options'		=> array(
							'incl'	=> __( 'Including tax', 'wpo_wcpdf_templates' ),
							'excl'		=> __( 'Excluding tax', 'wpo_wcpdf_templates' ),
						),
					),
				),
			),
			'vat'	=> array (
				'title'		=> __( 'VAT', 'wpo_wcpdf_templates' ),
				'options'	=> array (
					'label'		=> array(
						'type'			=> 'text',
						'description'	=> __( 'Label', 'wpo_wcpdf_templates' ),
						'placeholder'	=> __( 'Use default', 'wpo_wcpdf_templates' ),
					),
					'percent'		=> array(
						'type'			=> 'checkbox',
						'description'	=> __( 'Include %', 'wpo_wcpdf_templates' ),
					),
					'base'		=> array(
						'type'			=> 'checkbox',
						'description'	=> __( 'Include tax base/subtotal', 'wpo_wcpdf_templates' ),
					),
					'single_total'		=> array(
						'type'			=> 'checkbox',
						'description'	=> __( 'Single total', 'wpo_wcpdf_templates' ),
					),
					'tax_type'	=> array(
						'type'			=> 'select',
						'options'		=> array(
							'combined'	=> __( 'Combined tax', 'wpo_wcpdf_templates' ),
							'shipping'	=> __( 'Shipping tax', 'wpo_wcpdf_templates' ),
							'product'	=> __( 'Product tax', 'wpo_wcpdf_templates' ),
						),
					),
				),
			),
			'vat_base'	=> array (
				'title'		=> __( 'VAT base/subtotal', 'wpo_wcpdf_templates' ),
				'options'	=> array (
					'label'		=> array(
						'type'			=> 'text',
						'description'	=> __( 'Label', 'wpo_wcpdf_templates' ),
						'placeholder'	=> __( 'Use default', 'wpo_wcpdf_templates' ),
					),
					'percent'		=> array(
						'type'			=> 'checkbox',
						'description'	=> __( 'Include %', 'wpo_wcpdf_templates' ),
					),
				),
			),
			'total'	=> array (
				'title'		=> __( 'Grand total', 'wpo_wcpdf_templates' ),
				'options'	=> array (
					'label'		=> array(
						'type'			=> 'text',
						'description'	=> __( 'Label', 'wpo_wcpdf_templates' ),
						'placeholder'	=> __( 'Use default', 'wpo_wcpdf_templates' ),
					),
					'tax'	=> array(
						'type'			=> 'select',
						'options'		=> array(
							'incl'	=> __( 'Including tax', 'wpo_wcpdf_templates' ),
							'excl'	=> __( 'Excluding tax', 'wpo_wcpdf_templates' ),
						),
					),
				),
			),
			'order_weight'	=> array (
				'title'		=> __( 'Total weight', 'wpo_wcpdf_templates' ),
				'options'	=> array (
					'label'		=> array(
						'type'			=> 'text',
						'description'	=> __( 'Label', 'wpo_wcpdf_templates' ),
						'placeholder'	=> __( 'Use default', 'wpo_wcpdf_templates' ),
					),
					'show_unit'		=> array(
						'type'			=> 'checkbox',
						'description'	=> __( 'Append weight unit', 'wpo_wcpdf_templates' ),
					),
				),
			),
			'total_qty'		=> array (
				'title'		=> __( 'Total quantity', 'wpo_wcpdf_templates' ),
				'options'	=> array (
					'label'		=> array(
						'type'			=> 'text',
						'description'	=> __( 'Label', 'wpo_wcpdf_templates' ),
						'placeholder'	=> __( 'Use default', 'wpo_wcpdf_templates' ),
					),
				),
			),
			'custom_function'		=> array (
				'title'		=> __( 'Custom function', 'wpo_wcpdf_templates' ),
				'options'	=> array (
					'function'		=> array(
						'type'			=> 'text',
						'description'	=> __( 'Function name', 'wpo_wcpdf_templates' ),
					),
				),
			),
			'text'	=> array (
				'title'			=> __( 'Text', 'wpo_wcpdf_templates' ),
				'options'		=> array (
					'label'		=> array(
						'type'			=> 'text',
						'description'	=> __( 'Label', 'wpo_wcpdf_templates' ),
					),
					'text'		=> array(
						'type'			=> 'textarea',
						'rows'			=> 4,
						'description'	=> __( 'Text', 'wpo_wcpdf_templates' ),
					),
				),
			),
		) );	
	}

	/**
	 * Editor callback.
	 */
	public function columns_editor_callback( $args ) {
		$menu = $args['menu'];
		$id = $args['id'];
	
		$options = get_option( $menu, array() );

		$available_sorting = $this->get_sorting_options();
		$available_columns = $this->get_columns_field_options();
		$available_totals = $this->get_totals_field_options();
	
		?>
		<div id="documents" style="display:none;">
			<ul class="document-tabs">
				<?php foreach ($args['documents'] as $document => $title) {
					$document_id = $id.'_'.$document;
					printf( '<li><a href="#%1$s" data-document_type="%2$s">%3$s</a></li>', $document_id, $document, $title );
				}
				?>
			</ul>

			<?php foreach ($args['documents'] as $document => $title): ?>
				<?php
				$document_id = $id.'_'.$document;
				$sections = array(
					'columns'	=> __( 'Item Columns', 'wpo_wcpdf_templates'),
					'totals'	=> __( 'Total Rows', 'wpo_wcpdf_templates'),
				);
				printf('<div id="%1$s" class="document-content fields %2$s" data-document-type="%2$s">', $document_id, $document);
					if ( has_filter('wpo_wcpdf_template_editor_defaults') ) {
						printf( '<a class="button load-defaults" href="%s">%s</a>', esc_url( add_query_arg( 'load-defaults', 'true' ) ), __( 'Load defaults (all documents!)', 'wpo_wcpdf_templates') );
					}
					foreach ($sections as $section_key => $section_title) {
						$document_section = $document_id.'_'.$section_key
						?>
						<h4 class="columns-header">
							<?php echo $section_title; 
							if ( $section_key == 'columns' ) { ?>
								<span><?php _e( 'Need help?', 'wpo_wcpdf_templates'); ?> <a href="https://docs.wpovernight.com/woocommerce-pdf-invoices-packing-slips/using-the-customizer/" target="_blank"><?php _e( 'Using the Customizer', 'wpo_wcpdf_templates'); ?></a></span>
							<?php } ?>
						</h4>
						<?php
						if ( $section_key == 'columns' ) {
							?>
							<div class="sort-items">
								<span><?php echo $available_sorting['title']; ?></span>
								<select name="<?php printf( '%s[sort_items][%s]', $menu, $document ); ?>">
									<?php
									foreach ( $available_sorting['options'] as $sort_key => $sort_description ) {
										$selected = '';
										if ( array_key_exists( 'sort_items', $options ) ) {
											$selected = ( isset( $options['sort_items'][$document] ) && $options['sort_items'][$document] == $sort_key ) ? 'selected="selected"' : '';
										}
										printf( '<option value="%s" %s>%s</option>', $sort_key, $selected, $sort_description );
									}
									?>
								</select>
							</div>
							<?php
						}

						printf( '<div class="document field-list %1$s" data-option="%2$s[%3$s]" data-section_key="%1$s">', $section_key, $menu, $document_section );
						$current = isset( $options[$document_section] ) ? $options[$document_section] : '';
						if ( ! isset( $options['settings_saved'] ) || isset( $_GET['load-defaults'] ) ) {
							$current = apply_filters( 'wpo_wcpdf_template_editor_defaults', $current, $document, $section_key );
						}

						if ( ! empty( $current ) ) {
							foreach ( $current as $key => $field ) {
								$available = 'available_'.$section_key;
								if ( isset( $field['type'] ) && in_array( $field['type'], array_keys( ${$available} ) ) ) {
									$name = sprintf( '%s[%s][%s]', $menu, $document_section, $key);
									$this->display_table_field( $field['type'], $key, ${$available}[$field['type']], $args, $name, $field ); 
								}
							}
						} ?>

						<div class="document field add-field">
							<span class="dashicons dashicons-plus add-field-plus"></span>
							<select class='dropdown-add-field'>
								<?php
								if ($section_key == 'columns') {
									printf( '<option value="default">%s</option>', __( 'Add a column', 'wpo_wcpdf_templates' ) );
									foreach ($available_columns as $column_key => $column) {
										printf( '<option value="%1$s">%2$s</option>', $column_key, $column['title'] );
									}
								} elseif ($section_key == 'totals') {
									printf( '<option value="default">%s</option>', __( 'Add a row', 'wpo_wcpdf_templates' ) );
									foreach ($available_totals as $total_key => $total) {
										printf( '<option value="%1$s">%2$s</option>', $total_key, $total['title'] );
									}
								}
								?>
							</select>
						</div>

						<?php
						echo '</div>'; // document field-list
					}
					?>
					<!-- Custom Blocks -->
					<h4 class="columns-header"><?php echo __( 'Custom blocks', 'wpo_wcpdf_templates') ?><span><?php _e( 'Need help?', 'wpo_wcpdf_templates'); ?> <a href="https://docs.wpovernight.com/woocommerce-pdf-invoices-packing-slips/using-custom-blocks/" target="_blank"><?php _e( 'Using Custom Blocks', 'wpo_wcpdf_templates'); ?></a></span></h4>
					<?php
					$section_key = 'custom';
					$document_section = $document_id.'_'.$section_key;
					printf( '<div class="document field-list custom-blocks" data-option="%1$s[%2$s]" data-section="%2$s">', $menu, $document_section );

					$current = isset( $options[$document_section] ) ? $options[$document_section] : '';
					if (!empty($current)) {
						foreach ($current as $key => $field) {
							$name = sprintf( '%s[%s][%s]', $menu, $document_section, $key);
							$this->display_custom_block( $key, $args, $name, $field );
						}
					}
					?>
					</div>
					<br/><div class="button add-custom-block"><?php echo __( 'Add a block', 'wpo_wcpdf_templates') ?></div>
				</div> <!-- document-content -->
			<?php endforeach ?>
		</div>
		<?php
	}

	public function add_totals_columns_field() {
		if ( ! isset( $_REQUEST['section'] ) || ! isset( $_REQUEST['field_value'] ) || ! isset( $_REQUEST['document_type'] ) ) {
			die();
		}
		
		$options       = array(
			'columns' => $this->get_columns_field_options(),
			'totals'  => $this->get_totals_field_options(),
		);
		$section       = sanitize_text_field( $_REQUEST['section'] );
		$field_value   = sanitize_text_field( $_REQUEST['field_value'] );
		$document_type = sanitize_text_field( $_REQUEST['document_type'] );

		if ( 'default' == $field_value ) {
			die();
		}
		
		// we should provide a bigger number to not clash with the default keys (1,2,3,...).
		// when the settings are saved it will reassign the default keys again.
		$field_key = time();
		$field     = $options[ $section ][ $field_value ];
		$args      = array(
			'menu' => $this->option,
			'id'   => 'fields',
		);
		$name      = sprintf( '%s[fields_%s_%s][%s]', $args['menu'], $document_type, $section, $field_key );
		
		$this->display_table_field( $field_value, $field_key, $field, $args, $name );
		die();
	}
	
	public function update_totals_columns_positions() {
		$this->settings = get_option( $this->option, array() ); // get latest settings
		$documents      = WPO_WCPDF()->documents->get_documents( 'all' );
		$update         = false;
		
		foreach ( $documents as $document ) {
			$keys = array(
				"fields_{$document->get_type()}_columns",
				"fields_{$document->get_type()}_totals",
			);
			
			foreach ( $keys as $key ) {
				if ( isset( $this->settings[ $key ] ) ) {
					$this->settings[ $key ] = array_combine( range( 1, count( $this->settings[ $key ] ) ), array_values( $this->settings[ $key ] ) );
					$update                 = true;
				}
			}
		}
		
		if ( $update ) {
			update_option( $this->option, $this->settings );	
		}
	}
	
	public function add_or_update_editor_totals_columns( $param_one, $param_two ) {
		$this->update_totals_columns_positions();
	}

	public function display_table_field( $field_value, $field_key, $field, $args, $name = '', $current = '' ) {
		$menu = $args['menu'];
		$id   = $args['id'];

		$options_class = isset( $field['options'] ) ? 'options' : '';
		printf( '<div class="field %1$s %2$s" data-name="%2$s" data-option="%3$s[%4$s]" data-key="%5$s">', $options_class, $field_value, $menu, $id, $field_key );
		?>
		<span class="dashicons dashicons-dismiss delete-field"></span>
		<div class="field-title"><?php echo $field['title']; ?></div>
		<?php
			if ( isset( $field['options'] ) ) {
				echo '<div class="field-options">';
				foreach ( $field['options'] as $option_key => $field_option ) {
					$this->display_table_field_options( $option_key, $field_option, $current, $name ); 
				}
				echo '</div>';
			}
			printf( '<input type="hidden" data-key="type" name="%s[type]" value="%s">', $name, $field_value );
		?>
		</div>
		<?php
	}

	public function display_table_field_options ($option_key, $field_option, $current, $name = '' ) {
		$name = sprintf('%s[%s]', $name, $option_key);
		$current = !empty($current[$option_key]) ? $current[$option_key] : '';
		echo '<div class="field-option">';
		switch ($field_option['type']) {
			case 'checkbox':
				printf( '<input type="checkbox" data-key="%s" name="%s" value="1" %s>', $option_key, $name, checked( 1, $current, false ) );
				printf( '<span class="option-description">%s</span>', $field_option['description'] );
				break;
			case 'select':
				printf( '<select data-key="%s" name="%s">', $option_key, $name );
				foreach ($field_option['options'] as $select_option_value => $select_option_title) {
					printf( '<option value="%s" %s>%s</option>', $select_option_value, selected( $current, $select_option_value, false ), $select_option_title );
				}
				echo '</select>';
				break;

			case 'text':
				printf( '<span class="option-description">%s: </span>', $field_option['description'] );
				$placeholder = isset($field_option['placeholder']) ? $field_option['placeholder'] : '';
				printf( '<input type="text" data-key="%s" name="%s" value="%s" placeholder="%s">', $option_key, $name, $current, $placeholder );
				break;
			case 'textarea':
				printf( '<div class="option-description">%s: </div>', $field_option['description'] );
				$placeholder = isset($field_option['placeholder']) ? $field_option['placeholder'] : '';
				$cols = isset($field_option['cols']) ? $field_option['cols'] : '';
				$rows = isset($field_option['rows']) ? $field_option['rows'] : '';
				printf( '<textarea data-key="%s" name="%s" placeholder="%s" cols="%s" rows="%s">%s</textarea>', $option_key, $name, $placeholder, $cols, $rows, $current );
				break;
		}
		echo '</div>';
	}

	public function add_custom_block() {
		check_ajax_referer( 'wpo_wcpdf_templates', 'security' );

		$id = 'fields';
		$args = array(
			'menu' 	=> $this->option,
			'id'	=> $id
		);
		$key = uniqid();
		$document = $_POST['document_type'];
		$document_section = "{$id}_{$document}_custom";

		$name = sprintf( '%s[%s][%s]', $this->option, $document_section, $key);
		$this->display_custom_block( $key , $args, $name );
		die();
	}

	public function display_custom_block ( $field_key, $args, $name = '', $current = array() ) {
		$menu = $args['menu'];
		$id = $args['id'];

		printf( '<div class="custom-block field" data-name="%s" data-option="%s[%s]">', $field_key, $menu, $id);

		?>
		<span class="dashicons dashicons-dismiss delete-field"></span>
		<table class="custom-block-settings">
			<tr>
				<td><?php _e('Type', 'wpo_wcpdf_templates'); ?></td>
				<td>
					<?php 
					$types = array(
						'text'			=> __('Text', 'wpo_wcpdf_templates'),
						'custom_field'	=> __('Custom Field', 'wpo_wcpdf_templates'),
						'user_meta'	=> __('User Meta', 'wpo_wcpdf_templates'),
					);
					$option_key = 'type';
					$this->select_element(array(
						'option_name'     => "{$name}[{$option_key}]",
						'options'         => $types,
						'current'         => !empty($current[$option_key]) ? $current[$option_key] : '',
						'class'           => "custom-block-type",
					));
					?>
				</td>
			</tr>
			<tr>
				<td><?php _e('Position', 'wpo_wcpdf_templates'); ?></td>
				<td>
					<?php 
					$positions = array(
						'wpo_wcpdf_before_document'         => __('Before document', 'wpo_wcpdf_templates'),
						'wpo_wcpdf_before_shop_name'        => __('Before the shop name', 'wpo_wcpdf_templates'),
						'wpo_wcpdf_after_shop_name'         => __('After the shop name', 'wpo_wcpdf_templates'),
						'wpo_wcpdf_before_shop_address'     => __('Before the shop address', 'wpo_wcpdf_templates'),
						'wpo_wcpdf_after_shop_address'      => __('After the shop address', 'wpo_wcpdf_templates'),
						'wpo_wcpdf_before_document_label'   => __('Before the document label', 'wpo_wcpdf_templates'),
						'wpo_wcpdf_after_document_label'    => __('After the document label', 'wpo_wcpdf_templates'),
						'wpo_wcpdf_before_billing_address'  => __('Before the billing address', 'wpo_wcpdf_templates'),
						'wpo_wcpdf_after_billing_address'   => __('After the billing address', 'wpo_wcpdf_templates'),
						'wpo_wcpdf_before_shipping_address' => __('Before the shipping address', 'wpo_wcpdf_templates'),
						'wpo_wcpdf_after_shipping_address'  => __('After the shipping address', 'wpo_wcpdf_templates'),
						'wpo_wcpdf_before_order_data'       => __('Before the order data (invoice number, order date, etc.)', 'wpo_wcpdf_templates'),
						'wpo_wcpdf_after_order_data'        => __('After the order data', 'wpo_wcpdf_templates'),
						'wpo_wcpdf_before_customer_notes'   => __('Before the customer notes', 'wpo_wcpdf_templates'),
						'wpo_wcpdf_after_customer_notes'    => __('After the customer notes', 'wpo_wcpdf_templates'),
						'wpo_wcpdf_before_order_details'    => __('Before the order details table with all items', 'wpo_wcpdf_templates'),
						'wpo_wcpdf_after_order_details'     => __('After the order details table', 'wpo_wcpdf_templates'),
						'wpo_wcpdf_before_footer'           => __('Before the footer', 'wpo_wcpdf_templates'),
						'wpo_wcpdf_after_footer'            => __('After the footer', 'wpo_wcpdf_templates'),
						'wpo_wcpdf_after_document'          => __('After document', 'wpo_wcpdf_templates'),
					);
					$option_key = 'position';
					$this->select_element(array(
						'option_name'     => "{$name}[{$option_key}]",
						'options'         => $positions,
						'current'         => !empty($current[$option_key]) ? $current[$option_key] : '',
					));
					?>
				</td>
			</tr>
			<tr>
				<td><?php _e('Label / header', 'wpo_wcpdf_templates'); ?></td>
				<td>
					<?php 
					$option_key = 'label';
					$this->input_element(array(
						'option_name'     => "{$name}[{$option_key}]",
						'current'         => !empty($current[$option_key]) ? $current[$option_key] : '',
					));
					?>
				</td>
			</tr>
			<tr class="meta_key" data-types="custom_field user_meta">
				<td><?php _e('Field name / meta key', 'wpo_wcpdf_templates'); ?></td>
				<td data-tip="<?php _e( "Only blocks of type “Text” support the use of {{placeholders}}", 'wpo_wcpdf_templates' ); ?>">
					<?php 
					$option_key = 'meta_key';
					$this->input_element(array(
						'option_name'     => "{$name}[{$option_key}]",
						'current'         => !empty($current[$option_key]) ? $current[$option_key] : '',
						// 'class'           => 'meta_key',
					));
					?>
				</td>
			</tr>
			<tr class="custom_text" data-types="text">
				<td colspan="2">
					<?php _e('Text', 'wpo_wcpdf_templates'); ?><br>
					<?php 
					$option_key = 'text';
					$this->textarea_element(array(
						'option_name'     => "{$name}[{$option_key}]",
						'current'         => !empty($current[$option_key]) ? $current[$option_key] : '',
						// 'class'           => 'custom_text',
						'rows'            => 8,
					));
					?>
				</td>
			</tr>
		</table>

		<hr>

		<h5 class="custom-block-advanced-header"><?php _e('advanced', 'wpo_wcpdf_templates'); ?></h5>
		<div class="custom-block-advanced">
			<table class="custom-block-requirements">
				<tr class="select-requirements">
					<td>
					<?php 
					$this->select_element(array(
						'class'		=> 'select-requirements',
						'options'	=> array( ''	=> __('Select additional requirements for displaying this custom block', 'wpo_wcpdf_templates') . '&hellip;' ),
						'current'	=> 'requirement',
						'css'		=> 'width:100%;',
					));
					?>
					</td>
				</tr>

				<?php $option_key = 'order_statuses'; ?>
				<tr class="requirement"	data-requirement_id="<?php echo $option_key; ?>">
					<td>
						<label><?php _e('Order status', 'wpo_wcpdf_templates'); ?></label>
						<?php
						$this->select_element(array(
							'option_name'     => "{$name}[{$option_key}]",
							'options'         => wc_get_order_statuses(),
							'current'         => !empty($current[$option_key]) ? $current[$option_key] : array(),
							'enhanced_select' => true,
							'multiple'        => true,
							'placeholder'     => __( 'Select one or more statuses', 'wpo_wcpdf_templates' ),
							'css'             => 'width:90%',
						));
						?>
						<span class="dashicons dashicons-trash remove-requirement"></span>
					</td>
				</tr>
			
				<?php if (WC()->payment_gateways()): ?>
					<?php $option_key = 'payment_methods'; ?>
					<tr class="requirement" data-requirement_id="<?php echo $option_key; ?>">
						<td>
							<label><?php _e('Payment method', 'wpo_wcpdf_templates'); ?></label>
							<?php 
							$payment_gateways = array();
							foreach (WC()->payment_gateways->payment_gateways() as $gateway) {
								$payment_gateways[$gateway->id] = $gateway->get_title();
							}
							$this->select_element(array(
								'option_name'     => "{$name}[{$option_key}]",
								'options'         => $payment_gateways,
								'current'         => !empty($current[$option_key]) ? $current[$option_key] : array(),
								'enhanced_select' => true,
								'multiple'        => true,
								'placeholder'     => __( 'Select one or more payment methods', 'wpo_wcpdf_templates' ),
								'class'           => 'wc-enhanced-select wpo-wcpdf-enhanced-select',
								'css'             => 'width:90%',
							));
							?>
							<span class="dashicons dashicons-trash remove-requirement"></span>
						</td>
					</tr>
				<?php endif // gateways found ?>

				<?php if ($countries = WC()->countries->countries): ?>
					<?php $option_key = 'billing_country'; ?>
					<tr class="requirement" data-requirement_id="<?php echo $option_key; ?>">
						<td>
							<label><?php _e('Billing country', 'wpo_wcpdf_templates'); ?></label>
							<?php 
							$this->select_element(array(
								'option_name'     => "{$name}[{$option_key}]",
								'options'         => $countries,
								'current'         => !empty($current[$option_key]) ? $current[$option_key] : array(),
								'enhanced_select' => true,
								'multiple'        => true,
								'placeholder'     => __( 'Select one or more countries', 'wpo_wcpdf_templates' ),
								'class'           => 'wc-enhanced-select wpo-wcpdf-enhanced-select',
								'css'             => 'width:90%',
							));
							?>
							<span class="dashicons dashicons-trash remove-requirement"></span>
						</td>
					</tr>

					<?php $option_key = 'shipping_country'; ?>
					<tr class="requirement" data-requirement_id="<?php echo $option_key; ?>">
						<td>
							<label><?php _e('Shipping country', 'wpo_wcpdf_templates'); ?></label>
							<?php
							$this->select_element(array(
								'option_name'     => "{$name}[{$option_key}]",
								'options'         => $countries,
								'current'         => !empty($current[$option_key]) ? $current[$option_key] : array(),
								'enhanced_select' => true,
								'multiple'        => true,
								'placeholder'     => __( 'Select one or more countries', 'wpo_wcpdf_templates' ),
								'class'           => 'wc-enhanced-select wpo-wcpdf-enhanced-select',
								'css'             => 'width:90%',
							));
							?>
							<span class="dashicons dashicons-trash remove-requirement"></span>
						</td>
					</tr>
				<?php endif // countries found ?>

				<?php $option_key = 'vat_reverse_charge'; ?>
				<tr class="requirement" data-requirement_id="<?php echo $option_key; ?>">
					<td>
						<?php 
						$current_vat_reverse_charge = !empty($current[$option_key]) ? $current[$option_key] : '';
						$option_name = "{$name}[{$option_key}]";
						printf( '<input type="checkbox" data-key="%1$s" name="%2$s" id="%2$s" value="1" %3$s>', $option_key, $option_name, checked( 1, $current_vat_reverse_charge, false ) );
						?>
						<label for="<?= $option_name; ?>"><?php _e("VAT reverse charge", 'wpo_wcpdf_templates'); ?></label>
					</td>
				</tr>

				<?php do_action( 'wpo_wcpdf_after_custom_block_requirements', $name, $current ); ?>

				<?php $option_key = 'hide_if_empty'; ?>
				<tr class="<?php echo $option_key; ?>">
					<td>
						<?php 
						$current_hide_if_empty = !empty($current[$option_key]) ? $current[$option_key] : '';
						$option_name = "{$name}[{$option_key}]";
						printf( '<input type="checkbox" data-key="%1$s" name="%2$s" id="%2$s" value="1" %3$s>', $option_key, $option_name, checked( 1, $current_hide_if_empty, false ) );
						?>
						<label for="<?= $option_name; ?>"><?php _e("Don't show if empty", 'wpo_wcpdf_templates'); ?></label>
					</td>
				</tr>

				<?php $option_key = 'html_mode'; ?>
				<tr class="<?php echo $option_key; ?>">
					<td data-types="text">
						<?php 
						$current_html_mode = !empty($current[$option_key]) ? $current[$option_key] : '';
						$option_name = "{$name}[{$option_key}]";
						printf( '<input type="checkbox" data-key="%1$s" name="%2$s" id="%2$s" value="1" %3$s>', $option_key, $option_name, checked( 1, $current_html_mode, false ) );
						?>
						<label for="<?= $option_name; ?>"><?php _e("Raw HTML mode (don't convert line breaks)", 'wpo_wcpdf_templates'); ?></label>
					</td>
				</tr>

			</table>
		</div>

		</div>
		<?php
	}

	public function get_footer_height() {
		$footer_height = isset( WPO_WCPDF()->settings->general_settings['footer_height'] ) ? WPO_WCPDF()->settings->general_settings['footer_height'] : '';
		return $footer_height;
	}

	/**
	 * Add extra setting for the footer height to the template settings
	 */
	public function add_footer_height_setting( $settings_fields, $page, $option_group, $option_name ) {

		$footer_height_setting = array(
			'type'		=> 'setting',
			'id'		=> 'footer_height',
			'title'		=> __( 'Footer height', 'wpo_wcpdf_templates' ),
			'callback'	=> 'text_input',
			'section'	=> 'general_settings',
			'args'		=> array(
				'option_name'	=> $option_name,
				'id'			=> 'footer_height',
				'size'			=> '5',
				'description'	=> __( 'Enter the total height of the footer in mm, cm or in and use a dot for decimals.<br/>For example: 1.25in or 82mm', 'wpo_wcpdf_templates' )
			)
		);

		$settings_fields = $this->insert_after_setting( $settings_fields, $footer_height_setting, 'footer');
		return $settings_fields;
	}

	/**
	 * Replace extra fields description based on selected template
	 */
	public function extra_fields_description_replacement( $settings_fields, $page, $option_group, $option_name ) {
		$settings = get_option('wpo_wcpdf_settings_general');      // wcpdf 2.0+
		if( empty( $settings ) ) {
			$settings = get_option('wpo_wcpdf_template_settings'); // wcpdf 1.6.5 or older
		}

		if ( is_array( $settings ) && isset( $settings['template_path'] ) ) {
			$normalize_path       = wp_normalize_path( $settings['template_path'] );
			$template_path_arr    = explode( '/', $normalize_path );
			$template_name        = end( $template_path_arr );

			if( in_array( $template_name, array( 'Business', 'Simple Premium' ) ) ) {
				foreach( $settings_fields as $key => &$settings ) {
					foreach( $settings as $setting ) {							
						if( ! empty( $settings['id'] ) ) {
							$not_used_description = sprintf(
								/* translators: 1. template name, 2. placeholder */
								__( 'Not used for <i>%1$s</i> template by default. You can use the <code>{{wpo_wcpdf_%2$s}}</code> placeholder within the customizer to display the content from this field.', 'wpo_wcpdf_templates' ),
								$template_name,
								$settings['id']
							);
						}
						if( ! empty( $setting['id'] ) && $setting['id'] == 'extra_1' ) {
							if( $template_name == 'Business' ) {
								$settings['args']['description'] = sprintf(
									/* translators: template name */
									__( 'This shows in the <i>%s (Premium)</i> template header', 'wpo_wcpdf_templates' ),
									$template_name
								);
							} else {
								$settings['args']['description'] = $not_used_description;
							}
						} elseif( ! empty( $setting['id'] ) && in_array( $setting['id'], array( 'extra_2', 'extra_3' ) ) ) {
							$settings['args']['description'] = $not_used_description;
						}
					}
				}
			}
		}

		return $settings_fields;
	}

	public function insert_after_setting( $settings, $new_setting, $insert_after_id ) {
		// search setting with $insert_after_id
		foreach ($settings as $key => $setting) {
			if ($setting['type'] == 'setting' && $setting['id'] == $insert_after_id) {
				$insert_pos = array_search($key, array_keys($settings)) + 1;
			}
		}

		// simply append if position not found
		if (empty($insert_pos)) {
			return array_merge( $settings, array( $new_setting ) );
		}

		// splicemup!
		array_splice( $settings, $insert_pos, 0, array( $new_setting ) );

		return $settings;
	}

	/**
	 * Validate options.
	 *
	 * @param  array $input options to valid.
	 *
	 * @return array		validated options.
	 */
	public function validate_options( $input ) {
		// no validation required at this point!
		$output = $input;
				
		// Return the array processing any additional functions filtered by this action.
		return apply_filters( 'wpo_wcpdf_templates_validate_settings', $output, $input );
	}

	/**
	 * Remove load-defaults query variable after option is updated (to prevent loading the defaults again)
	 */
	public function remove_load_defaults_after_updating_option( $option, $old_value, $value ) {
		if ( $option == $this->option ) {
			add_filter( 'wp_redirect', function( $location, $status ) {
				return esc_url_raw( remove_query_arg( 'load-defaults', $location ) );
			}, 10, 2 );
		}
	}

	public function select_element( $args ) {
		$defaults = array(
			'option_name'     => '',
			'options'         => array(),
			'current'         => null,
			'enhanced_select' => false,
			'multiple'        => false,
			'placeholder'     => '',
			'title'           => '',
			'id'              => '',
			'class'           => '',
			'css'             => '',
		);
		$args = wp_parse_args( $args, $defaults );
		extract($args);

		if ( $enhanced_select ) {
			if ( $multiple ) {
				$option_name = "{$option_name}[]";
				$multiple = 'multiple=multiple';
			} else {
				$multiple = '';
			}

			$placeholder = isset($placeholder) ? esc_attr( $placeholder ) : '';
			$title = isset($title) ? esc_attr( $title ) : '';
			$class .= ' wc-enhanced-select wpo-wcpdf-enhanced-select';
			// $css = 'width:400px';
			printf( '<select id="%1$s" name="%2$s" data-placeholder="%3$s" title="%4$s" class="%5$s" style="%6$s" %7$s>', $id, $option_name, $placeholder, $title, $class, $css, $multiple );
		} else {
			printf( '<select id="%1$s" name="%2$s" class="%3$s" style="%4$s">', $id, $option_name, $class, $css );
		}

		foreach ( $options as $key => $label ) {
			if ( isset( $multiple ) && is_array( $current ) ) {
				$selected = in_array($key, $current) ? ' selected="selected"' : '';
				printf( '<option value="%s"%s>%s</option>', $key, $selected, $label );
			} else {
				printf( '<option value="%s"%s>%s</option>', $key, selected( $current, $key, false ), $label );
			}
		}

		echo '</select>';
	}

	public function input_element( $args ) {
		$defaults = array(
			'type'            => 'text',
			'option_name'     => '',
			'current'         => '',
			'id'              => '',
			'class'           => '',
			'css'             => '',
		);
		$args = wp_parse_args( $args, $defaults );
		extract($args);
		printf( '<input type="%1$s" name="%2$s" value="%3$s" class="%4$s" id="%5$s" style="%6$s">', $type, $option_name, $current, $class, $id, $css );
	}

	public function textarea_element( $args ) {
		$defaults = array(
			'option_name'     => '',
			'current'         => '',
			'id'              => '',
			'class'           => '',
			'css'             => '',
			'rows'            => 4,
		);
		$args = wp_parse_args( $args, $defaults );
		extract($args);
		printf( '<textarea name="%1$s" class="%2$s" id="%3$s" style="%4$s" rows="%5$s">%6$s</textarea>', $option_name, $class, $id, $css, $rows, $current );
	}
	
	public function customizer_setting_types( $setting_types ) {
		$setting_types['customizer'] = __( 'Customizer', 'wpo_wcpdf_templates' );
		return $setting_types;
	}
	
	public function customizer_settings_export( $settings, $type ) {
		if ( 'customizer' === $type ) {
			$settings = $this->settings;
		}
		return $settings;
	}
	
	public function customizer_settings_option( $settings_option, $type ) {
		if ( 'customizer' === $type ) {
			$settings_option = $this->option;
		}
		return $settings_option;
	}
	
	public function customizer_settings_option_import( $settings_option, $type, $new_settings ) {
		return $this->customizer_settings_option( $settings_option, $type );
	}
	
	public function customizer_settings_option_reset( $settings_option, $type ) {
		return $this->customizer_settings_option( $settings_option, $type );
	}

} // end class

endif;
