<?php
/**
 * Class used for querying domains' meta data.
 *
 * @package WP_Ultimo
 * @subpackage Database\Domains
 * @since 2.4.0
 */

namespace WP_Ultimo\Database\Domains;

use WP_Ultimo\Database\Engine\Table;

// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * Setup the "wu_domainmeta" database table
 *
 * @since 2.4.0
 */
final class Domains_Meta_Table extends Table {

	/**
	 * Table name
	 *
	 * @since 2.4.0
	 * @var string
	 */
	protected $name = 'domainmeta';

	/**
	 * Is this table global?
	 *
	 * @since 2.4.0
	 * @var boolean
	 */
	protected $global = true;

	/**
	 * Table current version
	 *
	 * @since 2.4.0
	 * @var string
	 */
	protected $version = '2.0.0';

	/**
	 * Setup the database schema
	 *
	 * @access protected
	 * @since  2.4.0
	 * @return void
	 */
	protected function set_schema(): void {

		$max_index_length = 191;

		$this->schema = "meta_id bigint(20) unsigned NOT NULL auto_increment,
		wu_domain_id bigint(20) unsigned NOT NULL default '0',
		meta_key varchar(255) DEFAULT NULL,
		meta_value longtext DEFAULT NULL,
		PRIMARY KEY (meta_id),
		KEY wu_domain_id (wu_domain_id),
		KEY meta_key (meta_key({$max_index_length}))";
	}
}
