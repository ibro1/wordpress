<?php
/**
 * Cobalt override of Ultimate Multisite's "clean" template-selection field
 * template (choose a starter site template). Every v-on/v-show/:class
 * binding preserved verbatim from the stock template
 * (views/checkout/templates/template-selection/clean.php).
 */

defined( 'ABSPATH' ) || exit;

if ( isset( $should_display ) && ! $should_display ) {
	?>
	<div id="wu-site-template-container"></div>
	<?php
	return;
}

$sites = wu_normalize_sites_list( $sites ?? array() );

$categories ??= array();

$customer_sites_category = __( 'Your Sites', 'davebukar-cobalt' );

$customer_sites = isset( $customer_sites ) ? array_map( 'intval', $customer_sites ) : array();

?>

<div id="wu-site-template-container">

	<?php if ( ! empty( $customer_sites ) || ! empty( $categories ) ) : ?>
		<ul class="dbt-template-filter">

			<li>
				<a
					href="#"
					data-category=""
					:class="$parent.template_category === '' ? 'is-active' : ''"
					v-on:click.prevent="$parent.template_category = ''"
				><?php esc_html_e( 'All', 'davebukar-cobalt' ); ?></a>
			</li>

			<?php if ( ! empty( $customer_sites ) ) : ?>
				<li>
					<a
						href="#"
						data-category="<?php echo esc_attr( $customer_sites_category ); ?>"
						:class="$parent.template_category === '<?php echo esc_attr( $customer_sites_category ); ?>' ? 'is-active' : ''"
						v-on:click.prevent="$parent.template_category = '<?php echo esc_attr( $customer_sites_category ); ?>'"
					><?php echo esc_html( $customer_sites_category ); ?></a>
				</li>
			<?php endif; ?>

			<?php foreach ( $categories as $category ) : ?>
				<li>
					<a
						href="#"
						data-category="<?php echo esc_attr( $category ); ?>"
						:class="$parent.template_category === '<?php echo esc_attr( $category ); ?>' ? 'is-active' : ''"
						v-on:click.prevent="$parent.template_category = '<?php echo esc_attr( $category ); ?>'"
					><?php echo esc_html( $category ); ?></a>
				</li>
			<?php endforeach; ?>

		</ul>
	<?php endif; ?>

	<div class="dbt-templates" style="--dbt-template-cols: <?php echo esc_attr( $cols ?? '3' ); ?>;">

		<?php foreach ( $sites as $site_template ) : ?>
			<?php /** @var WP_Ultimo\Models\Site $site_template */ ?>
			<?php
			if ( $site_template->get_type() !== 'site_template' && ! in_array( $site_template->get_id(), $customer_sites, true ) ) {
				continue;
			}
			?>

			<?php $is_template = $site_template->get_type() === 'site_template'; ?>
			<?php $template_categories = array_merge( $site_template->get_categories(), ! $is_template ? array( $customer_sites_category ) : array() ); ?>

			<div
				id="wu-site-template-<?php echo esc_attr( $site_template->get_id() ); ?>"
				class="cell dbt-template"
				:class="$parent.template_id == <?php echo esc_attr( $site_template->get_id() ); ?> ? 'dbt-template--selected' : ''"
				v-show="!$parent.template_category || <?php echo esc_attr( wp_json_encode( $template_categories ) ); ?>.join(',').indexOf($parent.template_category) > -1"
				v-cloak
			>

				<a
					title="<?php esc_attr_e( 'View template preview', 'davebukar-cobalt' ); ?>"
					class="dbt-template__preview"
					<?php echo $is_template ? $site_template->get_preview_url_attrs() : sprintf( 'href="%s" target="_blank"', esc_attr( $site_template->get_active_site_url() ) ); ?>
				>
					<img class="dbt-template__image" src="<?php echo esc_attr( $site_template->get_featured_image( 'wu-thumb-large' ) ); ?>" alt="<?php echo esc_attr( $site_template->get_title() ); ?>">
				</a>

				<div class="dbt-template__name"><?php echo esc_html( $site_template->get_title() ); ?></div>
				<div class="dbt-template__desc"><?php echo esc_html( $site_template->get_description() ); ?></div>

				<button
					type="button"
					class="btn btn--outline dbt-template__select"
					:class="$parent.template_id == <?php echo esc_attr( $site_template->get_id() ); ?> ? 'dbt-template__select--selected' : ''"
					v-on:click.prevent="$parent.template_id = <?php echo esc_attr( $site_template->get_id() ); ?>"
				>
					<span v-if="$parent.template_id == <?php echo esc_attr( $site_template->get_id() ); ?>"><?php esc_html_e( 'Selected', 'davebukar-cobalt' ); ?></span>
					<span v-else><?php esc_html_e( 'Select', 'davebukar-cobalt' ); ?></span>
				</button>

			</div>

		<?php endforeach; ?>

	</div>

</div>
