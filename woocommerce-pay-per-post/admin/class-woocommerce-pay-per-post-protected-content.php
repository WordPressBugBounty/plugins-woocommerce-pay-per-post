<?php
/**
 * The Protected Content screen's data (Premium).
 *
 * The screen lists protected posts a page at a time, and lists one post's
 * buyers only when an admin asks. Up to 3.3.0 it loaded every protected post
 * and ran a buyer query for every product of every post on each visit, which
 * timed out on stores with thousands of posts and orders, and read only the
 * legacy order tables, so stores using High-Performance Order Storage saw no
 * buyers.
 *
 * THE RULE for this screen: nothing on it may grow with the number of posts,
 * products, orders or customers, apart from the one paged query for the posts
 * on the page and its count. Per-row work is bounded by the page size, and
 * caches are primed once per page. The buyer lookup is work for one post, on
 * an explicit action, and returns at most a fixed number of buyers.
 * tests/integration/ProtectedContentScreenTest.php counts the queries.
 *
 * @package Woocommerce_pay_per_post
 */

use Automattic\WooCommerce\Utilities\OrderUtil;

class Woocommerce_Pay_Per_Post_Protected_Content {

	/** Posts per page, before the wc_pay_per_post_protected_content_per_page filter. */
	const PER_PAGE = 20;

	/** The most a filter may raise the page size to. */
	const MAX_PER_PAGE = 100;

	/** Buyers listed for one post; the total is always counted. */
	const BUYERS_LIMIT = 50;

	/** Nonce action for the buyers endpoint. */
	const BUYERS_NONCE = 'wc_ppp_protected_content_buyers';

	/** @var Woocommerce_Pay_Per_Post_Admin */
	private $admin;

	/** @var array|null Membership plans and levels, as [ 'plans' => [ id => name ], 'levels' => [ id => name ] ]. */
	private $memberships;

	/** @var array<int, true> IDs already looked up that are not posts. */
	private $not_posts = [];

	public function __construct( Woocommerce_Pay_Per_Post_Admin $admin ) {
		$this->admin = $admin;
	}

	/**
	 * Posts per page. Filterable, between 1 and MAX_PER_PAGE.
	 *
	 * @return int
	 */
	public static function per_page(): int {
		/**
		 * Number of protected posts per page on the Protected Content screen.
		 *
		 * @param int $per_page Default 20. Clamped to 1-100.
		 */
		$per_page = (int) apply_filters( 'wc_pay_per_post_protected_content_per_page', self::PER_PAGE );

		return max( 1, min( self::MAX_PER_PAGE, $per_page ) );
	}

	/**
	 * The post types protected content can be, from Settings.
	 *
	 * @return string[]
	 */
	public static function post_types(): array {
		$types = Woocommerce_Pay_Per_Post_Helper::get_protected_post_types();

		if ( ! is_array( $types ) ) {
			$types = explode( ',', (string) $types );
		}

		return array_values( array_filter( array_map( 'trim', array_map( 'strval', $types ) ) ) );
	}

	/**
	 * The screen's request, read from query arguments.
	 *
	 * @param array $get Usually $_GET.
	 *
	 * @return array{s: string, post_type: string, paged: int, post: int}
	 *               post is the old product_id argument: a post whose buyers to list.
	 */
	public static function request( array $get ): array {
		$post_type = isset( $get['post_type'] ) ? sanitize_key( wp_unslash( $get['post_type'] ) ) : '';

		if ( ! in_array( $post_type, self::post_types(), true ) ) {
			$post_type = '';
		}

		return [
			's'         => isset( $get['s'] ) ? trim( sanitize_text_field( wp_unslash( $get['s'] ) ) ) : '',
			'post_type' => $post_type,
			'paged'     => isset( $get['paged'] ) ? max( 1, absint( $get['paged'] ) ) : 1,
			// The buyers link has always been ?product_id=, but it carries a post ID.
			'post'      => isset( $get['product_id'] ) ? absint( $get['product_id'] ) : 0,
		];
	}

	/**
	 * One page of protected posts: the screen's only query whose cost depends
	 * on how many posts there are. It selects the same posts as
	 * Woocommerce_Pay_Per_Post_Helper::get_protected_posts().
	 *
	 * @param array $request From request().
	 *
	 * @return WP_Query
	 */
	public function query( array $request ): WP_Query {
		$types = '' !== $request['post_type'] ? [ $request['post_type'] ] : self::post_types();

		$args = [
			'post_type'           => $types ? $types : [ '__wc_ppp_none__' ],
			'post_status'         => 'publish',
			'orderby'             => 'date',
			'order'               => 'DESC',
			'posts_per_page'      => self::per_page(),
			'paged'               => $request['paged'],
			'meta_query'          => Woocommerce_Pay_Per_Post_Helper::get_protected_posts_meta_query(), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- the one paged query.
			'ignore_sticky_posts' => true,
			'suppress_filters'    => false,
		];

		if ( '' !== $request['s'] ) {
			$args['s']              = $request['s'];
			$args['search_columns'] = [ 'post_title' ];
		}

		return new WP_Query( $args );
	}

	/**
	 * Whether a post is one this screen lists, for the buyers view and endpoint.
	 *
	 * @param int $post_id
	 *
	 * @return WP_Post|null
	 */
	public function protected_post( int $post_id ) {
		$post = $post_id ? get_post( $post_id ) : null;

		if ( ! $post || 'publish' !== $post->post_status || ! in_array( $post->post_type, self::post_types(), true ) ) {
			return null;
		}

		$protection = $this->admin->is_protected( $post->ID );

		return empty( $protection['is_protected'] ) ? null : $post;
	}

	/**
	 * The table rows for a page of posts.
	 *
	 * @param WP_Post[] $posts
	 *
	 * @return array[] Each: post, protection_type, origin_type, ids (from classify()).
	 */
	public function rows( array $posts ): array {
		$protections = [];
		$all_ids     = [];

		foreach ( $posts as $post ) {
			$protection               = $this->admin->is_protected( $post->ID );
			$protections[ $post->ID ] = $protection;
			$all_ids                  = array_merge( $all_ids, $this->protection_ids( $protection['product_ids'] ?? [] ) );
		}

		// One query for every product named on the page.
		$this->prime_posts( $all_ids );

		// A child page's address is built from its parents, one get_post()
		// each: load them a generation at a time instead.
		$ancestors = $posts;
		for ( $depth = 0; $depth < 10 && $ancestors; $depth ++ ) {
			$parents = array_filter( array_map( 'intval', wp_list_pluck( $ancestors, 'post_parent' ) ) );
			$parents = array_values( array_diff( array_unique( $parents ), array_keys( $this->not_posts ) ) );

			if ( ! $parents ) {
				break;
			}

			$this->prime_posts( $parents );
			$ancestors = array_filter( array_map( 'get_post', $parents ) );
		}

		$rows = [];

		foreach ( $posts as $post ) {
			$protection = $protections[ $post->ID ];

			$rows[] = [
				'post'            => $post,
				'protection_type' => (string) ( $protection['protection_type'] ?? '' ),
				'origin_type'     => (string) ( $protection['origin_type'] ?? '' ),
				'ids'             => $this->classify( $protection['product_ids'] ?? [] ),
			];
		}

		return $rows;
	}

	/**
	 * Protection IDs as unique positive integers.
	 *
	 * @param mixed $ids
	 *
	 * @return int[]
	 */
	private function protection_ids( $ids ): array {
		$ids = array_filter(
			(array) $ids,
			static function ( $id ) {
				return is_scalar( $id ) && ctype_digit( trim( (string) $id ) ) && (int) $id > 0;
			}
		);

		return array_values( array_unique( array_map( 'intval', $ids ) ) );
	}

	/**
	 * What a post's protection IDs are. They mix WooCommerce products with
	 * WooCommerce Memberships plan IDs and Paid Memberships Pro level IDs. A
	 * level ID can equal a product's ID; it is then both, as on the paywall
	 * (Woocommerce_Pay_Per_Post_Helper::get_paywall_offer()).
	 *
	 * @param mixed $ids
	 *
	 * @return array{products: array<int, string>, plans: array<int, string>, levels: array<int, string>, missing: int[]}
	 *               products, plans and levels map ID => name; missing are IDs that are none of them.
	 */
	public function classify( $ids ): array {
		$memberships = $this->memberships();
		$result      = [
			'products' => [],
			'plans'    => [],
			'levels'   => [],
			'missing'  => [],
		];

		$ids = $this->protection_ids( $ids );
		$this->prime_posts( $ids );

		foreach ( $ids as $id ) {
			if ( isset( $memberships['plans'][ $id ] ) ) {
				$result['plans'][ $id ] = $memberships['plans'][ $id ];
				continue;
			}

			$known = false;

			if ( isset( $memberships['levels'][ $id ] ) ) {
				$result['levels'][ $id ] = $memberships['levels'][ $id ];
				$known                   = true;
			}

			$post = isset( $this->not_posts[ $id ] ) ? null : get_post( $id );

			if ( $post && in_array( $post->post_type, [ 'product', 'product_variation' ], true ) ) {
				$result['products'][ $id ] = get_the_title( $post );
				$known                     = true;
			}

			if ( ! $known ) {
				$result['missing'][] = $id;
			}
		}

		return $result;
	}

	/**
	 * Load posts into the cache in one query, and remember which IDs are not
	 * posts so that nothing looks them up again one at a time.
	 *
	 * @param int[] $ids
	 */
	private function prime_posts( array $ids ): void {
		$ids = array_values( array_diff( array_unique( $ids ), array_keys( $this->not_posts ) ) );

		if ( ! $ids ) {
			return;
		}

		_prime_post_caches( $ids, false, false );

		$parents = [];

		foreach ( $ids as $id ) {
			$post = wp_cache_get( $id, 'posts' );

			if ( false === $post ) {
				$this->not_posts[ $id ] = true;
			} elseif ( 'product_variation' === $post->post_type && $post->post_parent ) {
				// A variation links to its parent product's edit screen.
				$parents[] = (int) $post->post_parent;
			}
		}

		$parents = array_diff( array_unique( $parents ), $ids );

		if ( $parents ) {
			_prime_post_caches( array_values( $parents ), false, false );
		}
	}

	/**
	 * Membership plans and levels, read once per request through the
	 * integrations, as the meta box lists them.
	 *
	 * @return array{plans: array<int, string>, levels: array<int, string>}
	 */
	private function memberships(): array {
		if ( null !== $this->memberships ) {
			return $this->memberships;
		}

		$this->memberships = [
			'plans'  => [],
			'levels' => [],
		];

		$integrations = (array) $this->admin->integrations;
		$sources      = [
			'woocommerce-memberships' => 'plans',
			'paid-memberships-pro'    => 'levels',
		];

		foreach ( $sources as $integration => $key ) {
			if ( empty( $integrations[ $integration ] ) || ! method_exists( $integrations[ $integration ], 'get_membership_levels' ) ) {
				continue;
			}

			$levels = $integrations[ $integration ]->get_membership_levels();

			if ( ! is_array( $levels ) ) {
				continue;
			}

			foreach ( $levels as $level ) {
				if ( isset( $level->id ) ) {
					$this->memberships[ $key ][ (int) $level->id ] = (string) ( $level->name ?? '' );
				}
			}
		}

		return $this->memberships;
	}

	/**
	 * The buyers of one post's products, for the screen.
	 *
	 * @param WP_Post $post  A post protected_post() accepted.
	 * @param int     $limit Most buyers to list.
	 *
	 * @return array{post: WP_Post, ids: array, total: int, buyers: array[], limit: int}
	 */
	public function buyers( WP_Post $post, int $limit = self::BUYERS_LIMIT ): array {
		$protection = $this->admin->is_protected( $post->ID );
		$ids        = $this->classify( $protection['product_ids'] ?? [] );
		$found      = self::find_buyers( array_keys( $ids['products'] ), $limit );

		return [
			'post'   => $post,
			'ids'    => $ids,
			'total'  => $found['total'],
			'buyers' => $found['buyers'],
			'limit'  => $limit,
		];
	}

	/**
	 * Customers with a paid order containing any of the products, newest order
	 * first. An order line matches on its product or its variation, so a
	 * variable product matches orders for any of its variations.
	 *
	 * A buyer is a customer account, or for a guest order the billing email. A
	 * guest order placed with an account's email belongs to that account, as
	 * wc_customer_bought_product() counts it, in both the total and the list.
	 * Those emails are matched to accounts first and passed back in as values:
	 * joining the users table on the email column fails with "Illegal mix of
	 * collations" on a site whose tables were created years apart.
	 *
	 * These are buyers, not customers with access: access also depends on
	 * expiry, page views, subscriptions and memberships. The access diagnostic
	 * answers that for one customer.
	 *
	 * Reads WooCommerce's order tables (High-Performance Order Storage) or the
	 * posts table, whichever the store uses.
	 *
	 * @param int[] $product_ids
	 * @param int   $limit Most buyers to return.
	 *
	 * @return array{total: int, buyers: array[]} Each buyer: user (WP_User|null),
	 *                                         email, orders, last_order_id, last_order_date (GMT).
	 */
	public static function find_buyers( array $product_ids, int $limit = self::BUYERS_LIMIT ): array {
		global $wpdb;

		$product_ids = array_values( array_unique( array_filter( array_map( 'absint', $product_ids ) ) ) );
		$statuses    = array_map(
			static function ( $status ) {
				return 'wc-' . $status;
			},
			(array) wc_get_is_paid_statuses()
		);

		if ( ! $product_ids || ! $statuses ) {
			return [
				'total'  => 0,
				'buyers' => [],
			];
		}

		$items    = $wpdb->prefix . 'woocommerce_order_items';
		$itemmeta = $wpdb->prefix . 'woocommerce_order_itemmeta';
		$id_in    = implode( ',', array_fill( 0, count( $product_ids ), '%s' ) );
		$st_in    = implode( ',', array_fill( 0, count( $statuses ), '%s' ) );
		$args     = array_merge( array_map( 'strval', $product_ids ), $statuses );

		if ( Woocommerce_Pay_Per_Post_Helper::hops_enabled() ) {
			$orders   = OrderUtil::get_table_for_orders();
			$from     = "FROM {$items} AS i
				INNER JOIN {$itemmeta} AS im ON im.order_item_id = i.order_item_id
				INNER JOIN {$orders} AS o ON o.id = i.order_id
				WHERE i.order_item_type = 'line_item'
				AND im.meta_key IN ( '_product_id', '_variation_id' )
				AND im.meta_value IN ( {$id_in} )
				AND o.type = 'shop_order'
				AND o.status IN ( {$st_in} )";
			$customer = 'o.customer_id';
			$email    = 'o.billing_email';
			$order_id = 'o.id';
			$date     = 'o.date_created_gmt';
		} else {
			$from     = "FROM {$items} AS i
				INNER JOIN {$itemmeta} AS im ON im.order_item_id = i.order_item_id
				INNER JOIN {$wpdb->posts} AS o ON o.ID = i.order_id
				LEFT JOIN {$wpdb->postmeta} AS cu ON cu.post_id = o.ID AND cu.meta_key = '_customer_user'
				LEFT JOIN {$wpdb->postmeta} AS em ON em.post_id = o.ID AND em.meta_key = '_billing_email'
				WHERE i.order_item_type = 'line_item'
				AND im.meta_key IN ( '_product_id', '_variation_id' )
				AND im.meta_value IN ( {$id_in} )
				AND o.post_type = 'shop_order'
				AND o.post_status IN ( {$st_in} )";
			$customer = 'CAST( COALESCE( cu.meta_value, 0 ) AS UNSIGNED )';
			$email    = 'em.meta_value';
			$order_id = 'o.ID';
			$date     = 'o.post_date_gmt';
		}

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table and column names only; values are placeholders.
		$guest_emails = (array) $wpdb->get_col(
			$wpdb->prepare( "SELECT DISTINCT LOWER( {$email} ) {$from} AND {$customer} = 0 AND {$email} <> ''", $args )
		);

		// The account each guest email belongs to.
		$account      = $customer;
		$account_args = [];
		foreach ( self::accounts_by_email( $guest_emails ) as $guest_email => $user_id ) {
			$account_args[] = $guest_email;
			$account_args[] = $user_id;
		}
		if ( $account_args ) {
			$account = "CASE WHEN {$customer} > 0 THEN {$customer}"
				. str_repeat( " WHEN LOWER( {$email} ) = %s THEN %d", count( $account_args ) / 2 )
				. ' ELSE 0 END';
		}

		$buyer = "CASE WHEN {$account} > 0 THEN CONCAT( 'u', {$account} ) ELSE CONCAT( 'e', LOWER( COALESCE( {$email}, '' ) ) ) END";

		// Placeholders in the order they appear: the account expression twice
		// in $buyer, then the query's own.
		$buyer_args = array_merge( $account_args, $account_args );

		$total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT( DISTINCT {$buyer} ) {$from}", array_merge( $buyer_args, $args ) ) );

		$rows = $total ? (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT {$buyer} AS buyer, MAX( {$account} ) AS user_id, MAX( {$email} ) AS email,
					COUNT( DISTINCT {$order_id} ) AS orders, MAX( {$order_id} ) AS last_order_id, MAX( {$date} ) AS last_order_date
				{$from}
				GROUP BY buyer
				ORDER BY last_order_date DESC, last_order_id DESC
				LIMIT %d",
				array_merge( $buyer_args, $account_args, $args, [ max( 1, $limit ) ] )
			),
			ARRAY_A
		) : [];
		// phpcs:enable

		return [
			'total'  => $total,
			'buyers' => self::attach_users( $rows ),
		];
	}

	/**
	 * Accounts whose email is one of these, keyed by lowercased email.
	 *
	 * @param string[] $emails Lowercased.
	 *
	 * @return array<string, int>
	 */
	private static function accounts_by_email( array $emails ): array {
		global $wpdb;

		$accounts = [];
		foreach ( array_chunk( array_values( array_filter( $emails ) ), 500 ) as $chunk ) {
			$in = implode( ',', array_fill( 0, count( $chunk ), '%s' ) );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			foreach ( (array) $wpdb->get_results( $wpdb->prepare( "SELECT ID, user_email FROM {$wpdb->users} WHERE user_email IN ( {$in} ) ORDER BY ID", $chunk ) ) as $user ) {
				$accounts[ strtolower( $user->user_email ) ] = $accounts[ strtolower( $user->user_email ) ] ?? (int) $user->ID;
			}
		}

		return $accounts;
	}

	/**
	 * Attach accounts to buyer rows in one query.
	 *
	 * @param array[] $rows
	 *
	 * @return array[]
	 */
	private static function attach_users( array $rows ): array {
		if ( ! $rows ) {
			return [];
		}

		$buyers = [];
		foreach ( $rows as $row ) {
			$buyers[] = [
				'user_id'         => (int) $row['user_id'],
				'user'            => null,
				'email'           => (string) $row['email'],
				'orders'          => (int) $row['orders'],
				'last_order_id'   => (int) $row['last_order_id'],
				'last_order_date' => (string) $row['last_order_date'],
			];
		}

		$user_ids = array_values( array_filter( wp_list_pluck( $buyers, 'user_id' ) ) );
		$users    = [];
		if ( $user_ids ) {
			$found = get_users(
				[
					'include'     => $user_ids,
					'fields'      => [ 'ID', 'display_name', 'user_email' ],
					'count_total' => false,
				]
			);

			foreach ( $found as $user ) {
				$users[ (int) $user->ID ] = $user;
			}
		}

		foreach ( $buyers as $key => $buyer ) {
			$buyers[ $key ]['user'] = $users[ $buyer['user_id'] ] ?? null;
		}

		return $buyers;
	}

	/**
	 * WooCommerce's orders screen, with query arguments.
	 *
	 * @param array $args
	 *
	 * @return string
	 */
	public static function orders_url( array $args = [] ): string {
		$base = Woocommerce_Pay_Per_Post_Helper::hops_enabled()
			? admin_url( 'admin.php?page=wc-orders' )
			: admin_url( 'edit.php?post_type=shop_order' );

		return add_query_arg( array_map( 'rawurlencode', $args ), $base );
	}

	/**
	 * The orders screen searched for a product's name. WooCommerce's order
	 * search matches line item names; on High-Performance Order Storage the
	 * search is narrowed to products.
	 *
	 * @param string $product_name
	 *
	 * @return string
	 */
	public static function product_orders_url( string $product_name ): string {
		$args = [ 's' => $product_name ];

		if ( Woocommerce_Pay_Per_Post_Helper::hops_enabled() ) {
			$args['search-filter'] = 'products';
		}

		return self::orders_url( $args );
	}

	/**
	 * The edit screen of one order.
	 *
	 * @param int $order_id
	 *
	 * @return string
	 */
	public static function order_url( int $order_id ): string {
		if ( Woocommerce_Pay_Per_Post_Helper::hops_enabled() ) {
			return admin_url( 'admin.php?page=wc-orders&action=edit&id=' . $order_id );
		}

		return admin_url( 'post.php?post=' . $order_id . '&action=edit' );
	}
}
