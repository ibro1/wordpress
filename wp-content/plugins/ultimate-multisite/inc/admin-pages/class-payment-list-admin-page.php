<?php
/**
 * Ultimate Multisite Payment Admin Page.
 *
 * @package WP_Ultimo
 * @subpackage Admin_Pages
 * @since 2.0.0
 */

namespace WP_Ultimo\Admin_Pages;

// Exit if accessed directly
defined('ABSPATH') || exit;

use WP_Ultimo\Database\Payments\Payment_Status;

/**
 * Ultimate Multisite Payment Admin Page.
 */
class Payment_List_Admin_Page extends List_Admin_Page {

	/**
	 * Holds the ID for this page, this is also used as the page slug.
	 *
	 * @var string
	 */
	protected $id = 'wp-ultimo-payments';

	/**
	 * Is this a top-level menu or a submenu?
	 *
	 * @since 1.8.2
	 * @var string
	 */
	protected $type = 'submenu';

	/**
	 * If this number is greater than 0, a badge with the number will be displayed alongside the menu title
	 *
	 * @since 1.8.2
	 * @var integer
	 */
	protected $badge_count = 0;

	/**
	 * Holds the admin panels where this page should be displayed, as well as which capability to require.
	 *
	 * To add a page to the regular admin (wp-admin/), use: 'admin_menu' => 'capability_here'
	 * To add a page to the network admin (wp-admin/network), use: 'network_admin_menu' => 'capability_here'
	 * To add a page to the user (wp-admin/user) admin, use: 'user_admin_menu' => 'capability_here'
	 *
	 * @since 2.0.0
	 * @var array
	 */
	protected $supported_panels = [
		'network_admin_menu' => 'wu_read_payments',
	];

	/**
	 * Register ajax forms that we use for payments.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function register_forms(): void {
		/*
		 * Edit/Add Line Item
		 */
		wu_register_form(
			'add_new_payment',
			[
				'render'     => [$this, 'render_add_new_payment_modal'],
				'handler'    => [$this, 'handle_add_new_payment_modal'],
				'capability' => 'wu_edit_payments',
			]
		);

		/*
		 * Send Invoice
		 */
		wu_register_form(
			'send_invoice',
			[
				'render'     => [$this, 'render_send_invoice_modal'],
				'handler'    => [$this, 'handle_send_invoice_modal'],
				'capability' => 'wu_edit_payments',
			]
		);
	}

	/**
	 * Renders the add/edit line items form.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function render_add_new_payment_modal(): void {

		$fields = [
			'products'       => [
				'type'        => 'model',
				'title'       => __('Products', 'ultimate-multisite'),
				'placeholder' => __('Search Products...', 'ultimate-multisite'),
				'desc'        => __('Each product will be added as a line item.', 'ultimate-multisite'),
				'value'       => '',
				'tooltip'     => '',
				'html_attr'   => [
					'data-model'        => 'product',
					'data-value-field'  => 'id',
					'data-label-field'  => 'name',
					'data-search-field' => 'name',
					'data-max-items'    => 10,
				],
			],
			'status'         => [
				'type'        => 'select',
				'title'       => __('Status', 'ultimate-multisite'),
				'placeholder' => __('Status', 'ultimate-multisite'),
				'desc'        => __('The payment status to attach to the newly created payment.', 'ultimate-multisite'),
				'value'       => Payment_Status::COMPLETED,
				'options'     => Payment_Status::to_array(),
				'tooltip'     => '',
			],
			'membership_id'  => [
				'type'        => 'model',
				'title'       => __('Membership', 'ultimate-multisite'),
				'placeholder' => __('Search Membership...', 'ultimate-multisite'),
				'desc'        => __('The membership associated with this payment.', 'ultimate-multisite'),
				'value'       => '',
				'tooltip'     => '',
				'html_attr'   => [
					'data-model'       => 'membership',
					'data-value-field' => 'id',
					'data-label-field' => 'reference_code',
					'data-max-items'   => 1,
					'data-selected'    => '',
				],
			],
			'add_setup_fees' => [
				'type'  => 'toggle',
				'title' => __('Include Setup Fees', 'ultimate-multisite'),
				'desc'  => __('Checking this box will include setup fees attached to the selected products as well.', 'ultimate-multisite'),
				'value' => 1,
			],
			'submit_button'  => [
				'type'            => 'submit',
				'title'           => __('Add Payment', 'ultimate-multisite'),
				'value'           => 'save',
				'classes'         => 'wu-w-full button button-primary',
				'wrapper_classes' => 'wu-items-end',
			],
		];

		$form = new \WP_Ultimo\UI\Form(
			'add_payment',
			$fields,
			[
				'views'                 => 'admin-pages/fields',
				'classes'               => 'wu-modal-form wu-widget-list wu-striped wu-m-0 wu-mt-0',
				'field_wrapper_classes' => 'wu-w-full wu-box-border wu-items-center wu-flex wu-justify-between wu-p-4 wu-m-0 wu-border-t wu-border-l-0 wu-border-r-0 wu-border-b-0 wu-border-gray-300 wu-border-solid',
				'html_attr'             => [
					'data-wu-app' => 'add_payment',
					'data-state'  => wu_convert_to_state(
						[
							'taxable' => 0,
							'type'    => 'product',
						]
					),
				],
			]
		);

		$form->render();
	}

	/**
	 * Handles the add/edit of line items.
	 *
	 * @since 2.0.0
	 * @return mixed
	 */
	public function handle_add_new_payment_modal() {

		$membership = wu_get_membership(wu_request('membership_id'));

		if ( ! $membership) {
			$error = new \WP_Error('invalid-membership', __('Invalid membership.', 'ultimate-multisite'));

			return wp_send_json_error($error);
		}

		$cart = new \WP_Ultimo\Checkout\Cart(
			[
				'products'  => explode(',', (string) wu_request('products')),
				'cart_type' => wu_request('add_setup_fees') ? 'new' : 'renewal',
			]
		);

		$payment_data = array_merge(
			$cart->to_payment_data(),
			[
				'status'        => wu_request('status'),
				'membership_id' => $membership->get_id(),
				'customer_id'   => $membership->get_customer_id(),
			]
		);

		$payment = wu_create_payment($payment_data);

		if (is_wp_error($payment)) {
			return wp_send_json_error($payment);
		}

		wp_send_json_success(
			[
				'redirect_url' => wu_network_admin_url(
					'wp-ultimo-edit-payment',
					[
						'id' => $payment->get_id(),
					]
				),
			]
		);
	}

	/**
	 * Renders the Send Invoice modal form.
	 *
	 * @since 2.5.0
	 * @return void
	 */
	public function render_send_invoice_modal(): void {

		$fields = [
			'customer_id'       => [
				'type'        => 'model',
				'title'       => __('Customer', 'ultimate-multisite'),
				'placeholder' => __('Search Customers...', 'ultimate-multisite'),
				'desc'        => __('The customer to send the invoice to.', 'ultimate-multisite'),
				'value'       => '',
				'tooltip'     => '',
				'html_attr'   => [
					'data-model'        => 'customer',
					'data-value-field'  => 'id',
					'data-label-field'  => 'display_name',
					'data-search-field' => 'display_name',
					'data-max-items'    => 1,
				],
			],
			'products'          => [
				'type'        => 'model',
				'title'       => __('Products', 'ultimate-multisite'),
				'placeholder' => __('Search Products...', 'ultimate-multisite'),
				'desc'        => __('Select products to include as line items. Leave empty for custom-only invoices.', 'ultimate-multisite'),
				'value'       => '',
				'tooltip'     => '',
				'html_attr'   => [
					'data-model'        => 'product',
					'data-value-field'  => 'id',
					'data-label-field'  => 'name',
					'data-search-field' => 'name',
					'data-max-items'    => 10,
				],
			],
			'custom_line_items' => [
				'type'              => 'group',
				'title'             => __('Custom Line Items', 'ultimate-multisite'),
				'desc'              => __('Add custom charges (e.g. consulting hours). Use comma-separated entries in the format: description|amount|quantity.', 'ultimate-multisite'),
				'wrapper_html_attr' => [
					'v-show' => 'show_custom',
				],
				'fields'            => [
					'custom_items' => [
						'type'            => 'textarea',
						'placeholder'     => "Consulting - 3 hours|150|3\nSetup assistance|50|1",
						'value'           => '',
						'wrapper_classes' => 'wu-w-full',
						'html_attr'       => [
							'rows' => 3,
						],
					],
				],
			],
			'show_custom_btn'   => [
				'type'              => 'note',
				'desc'              => '<a href="#" v-on:click.prevent="show_custom = !show_custom" class="wu-no-underline">' . __('+ Add custom line items', 'ultimate-multisite') . '</a>',
				'wrapper_html_attr' => [
					'v-show' => '!show_custom',
				],
			],
			'membership_id'     => [
				'type'        => 'model',
				'title'       => __('Membership (optional)', 'ultimate-multisite'),
				'placeholder' => __('Search Membership...', 'ultimate-multisite'),
				'desc'        => __('Optionally link this invoice to an existing membership.', 'ultimate-multisite'),
				'value'       => '',
				'tooltip'     => '',
				'html_attr'   => [
					'data-model'       => 'membership',
					'data-value-field' => 'id',
					'data-label-field' => 'reference_code',
					'data-max-items'   => 1,
					'data-selected'    => '',
				],
			],
			'invoice_message'   => [
				'type'        => 'textarea',
				'title'       => __('Message (optional)', 'ultimate-multisite'),
				'placeholder' => __('Add a personal note to include in the invoice email...', 'ultimate-multisite'),
				'desc'        => __('This note will be included in the email sent to the customer.', 'ultimate-multisite'),
				'value'       => '',
				'html_attr'   => [
					'rows' => 3,
				],
			],
			'send_notification' => [
				'type'  => 'toggle',
				'title' => __('Send Email Notification', 'ultimate-multisite'),
				'desc'  => __('Send the customer an email with a link to pay this invoice.', 'ultimate-multisite'),
				'value' => 1,
			],
			'submit_button'     => [
				'type'            => 'submit',
				'title'           => __('Create Invoice', 'ultimate-multisite'),
				'value'           => 'save',
				'classes'         => 'wu-w-full button button-primary',
				'wrapper_classes' => 'wu-items-end',
			],
		];

		$form = new \WP_Ultimo\UI\Form(
			'send_invoice',
			$fields,
			[
				'views'                 => 'admin-pages/fields',
				'classes'               => 'wu-modal-form wu-widget-list wu-striped wu-m-0 wu-mt-0',
				'field_wrapper_classes' => 'wu-w-full wu-box-border wu-items-center wu-flex wu-justify-between wu-p-4 wu-m-0 wu-border-t wu-border-l-0 wu-border-r-0 wu-border-b-0 wu-border-gray-300 wu-border-solid',
				'html_attr'             => [
					'data-wu-app' => 'send_invoice',
					'data-state'  => wu_convert_to_state(
						[
							'show_custom' => false,
						]
					),
				],
			]
		);

		$form->render();
	}

	/**
	 * Handles the Send Invoice form submission.
	 *
	 * @since 2.5.0
	 * @return void
	 */
	public function handle_send_invoice_modal(): void {

		$customer_id = absint(wu_request('customer_id'));
		$customer    = wu_get_customer($customer_id);

		if ( ! $customer) {
			wp_send_json_error(new \WP_Error('invalid-customer', __('Please select a valid customer.', 'ultimate-multisite')));

			return;
		}

		$membership_id = absint(wu_request('membership_id', 0));
		$products_raw  = wu_request('products', '');
		$custom_items  = wu_request('custom_items', '');

		/*
		 * Build line items from products.
		 */
		$line_items = [];

		if ( ! empty($products_raw)) {
			$product_ids = array_filter(array_map('absint', explode(',', (string) $products_raw)));

			foreach ($product_ids as $product_id) {
				$product = wu_get_product($product_id);

				if ( ! $product) {
					continue;
				}

				$line_items[] = new \WP_Ultimo\Checkout\Line_Item(
					[
						'product'    => $product,
						'quantity'   => 1,
						'unit_price' => $product->get_amount(),
						'title'      => $product->get_name(),
					]
				);
			}
		}

		/*
		 * Build line items from custom entries.
		 */
		if ( ! empty($custom_items)) {
			$lines = array_filter(array_map('trim', explode("\n", (string) $custom_items)));

			foreach ($lines as $line) {
				$parts      = array_map('trim', explode('|', $line));
				$title      = $parts[0] ?? '';
				$unit_price = isset($parts[1]) ? wu_to_float($parts[1]) : 0;
				$quantity   = isset($parts[2]) ? absint($parts[2]) : 1;

				if (empty($title) || $unit_price <= 0) {
					continue;
				}

				$line_items[] = new \WP_Ultimo\Checkout\Line_Item(
					[
						'type'       => 'fee',
						'hash'       => uniqid(),
						'title'      => sanitize_text_field($title),
						'unit_price' => $unit_price,
						'quantity'   => max(1, $quantity),
					]
				);
			}
		}

		if (empty($line_items)) {
			wp_send_json_error(new \WP_Error('no-items', __('Please add at least one product or custom line item.', 'ultimate-multisite')));

			return;
		}

		/*
		 * Calculate totals from line items.
		 */
		$subtotal  = 0;
		$tax_total = 0;
		$total     = 0;

		foreach ($line_items as $line_item) {
			$line_item->recalculate_totals();

			$subtotal  += $line_item->get_subtotal();
			$tax_total += $line_item->get_tax_total();
			$total     += $line_item->get_total();
		}

		/*
		 * Create the pending payment.
		 */
		$payment_data = [
			'customer_id'   => $customer->get_id(),
			'membership_id' => $membership_id,
			'status'        => Payment_Status::PENDING,
			'subtotal'      => $subtotal,
			'tax_total'     => $tax_total,
			'total'         => $total,
			'line_items'    => $line_items,
		];

		$payment = wu_create_payment($payment_data);

		if (is_wp_error($payment)) {
			wp_send_json_error($payment);

			return;
		}

		/*
		 * Fire the invoice_sent event.
		 */
		$send_notification = wu_request('send_notification');

		if ($send_notification) {
			$payload = array_merge(
				wu_generate_event_payload('payment', $payment),
				wu_generate_event_payload('customer', $customer),
				[
					'payment_url'     => $payment->get_payment_url(),
					'invoice_message' => sanitize_textarea_field(wu_request('invoice_message', '')),
				]
			);

			wu_do_event('invoice_sent', $payload);
		}

		wp_send_json_success(
			[
				'redirect_url' => wu_network_admin_url(
					'wp-ultimo-edit-payment',
					[
						'id' => $payment->get_id(),
					]
				),
			]
		);
	}

	/**
	 * Allow child classes to register widgets, if they need them.
	 *
	 * @since 1.8.2
	 * @return void
	 */
	public function register_widgets() {}

	/**
	 * Returns an array with the labels for the edit page.
	 *
	 * @since 1.8.2
	 * @return array
	 */
	public function get_labels() {

		return [
			'deleted_message' => __('Payment removed successfully.', 'ultimate-multisite'),
			'search_label'    => __('Search Payment', 'ultimate-multisite'),
		];
	}

	/**
	 * Returns the title of the page.
	 *
	 * @since 2.0.0
	 * @return string Title of the page.
	 */
	public function get_title() {

		return __('Payments', 'ultimate-multisite');
	}

	/**
	 * Returns the title of menu for this page.
	 *
	 * @since 2.0.0
	 * @return string Menu label of the page.
	 */
	public function get_menu_title() {

		return __('Payments', 'ultimate-multisite');
	}

	/**
	 * Allows admins to rename the sub-menu (first item) for a top-level page.
	 *
	 * @since 2.0.0
	 * @return string False to use the title menu or string with sub-menu title.
	 */
	public function get_submenu_title() {

		return __('Payments', 'ultimate-multisite');
	}

	/**
	 * Returns the action links for that page.
	 *
	 * @since 1.8.2
	 * @return array
	 */
	public function action_links() {

		return [
			[
				'label'   => __('Add Payment', 'ultimate-multisite'),
				'icon'    => 'wu-circle-with-plus',
				'classes' => 'wubox',
				'url'     => wu_get_form_url('add_new_payment'),
			],
			[
				'label'   => __('Send Invoice', 'ultimate-multisite'),
				'icon'    => 'wu-mail',
				'classes' => 'wubox',
				'url'     => wu_get_form_url('send_invoice'),
			],
		];
	}

	/**
	 * Loads the list table for this particular page.
	 *
	 * @since 2.0.0
	 * @return \WP_Ultimo\List_Tables\Base_List_Table
	 */
	public function table() {

		return new \WP_Ultimo\List_Tables\Payment_List_Table();
	}
}
