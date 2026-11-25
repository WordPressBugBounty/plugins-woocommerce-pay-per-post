<?php
/**
 * Access diagnostic — answers "why can (or can't) this customer see this page?"
 *
 * Support for this plugin routinely means reading thousands of lines of debug
 * log to establish one sentence: they never bought it, or their window closed,
 * or their subscription lapsed. Everything needed to answer that is already
 * computed during a normal request; this class runs the real evaluation as the
 * customer and reports what it decided, and why.
 *
 * THE RULE: this class never reimplements a protection check. It evaluates by
 * calling the same engine a front-end request calls. A second implementation
 * would drift and start producing confident wrong answers, which is worse than
 * no tool.
 *
 * @package Woocommerce_pay_per_post
 */

class Woocommerce_Pay_Per_Post_Access_Diagnostic {

	/**
	 * Run the diagnostic.
	 *
	 * @param string $email Customer email address.
	 * @param string $url   Full URL of the page they are trying to view.
	 *
	 * @return array Structured result: ['error' => string] on failure, otherwise
	 *               ['user','post','verdict','reason','evidence','history','focus_order',
	 *               'page_config','products','environment','warnings','checks'].
	 */
	public static function run( string $email, string $url ): array {
		$email = trim( $email );
		$url   = trim( $url );

		if ( '' === $email || '' === $url ) {
			return [ 'error' => __( 'Enter both a customer email address and a page URL.', 'wc_pay_per_post' ) ];
		}

		$user = get_user_by( 'email', $email );
		if ( ! $user ) {
			return [
				'error' => sprintf(
					/* translators: %s: email address */
					__( 'No WordPress user found with the email address %s. Note that access is tied to a user account — a customer who checked out as a guest has no account, and therefore no access.', 'wc_pay_per_post' ),
					esc_html( $email )
				),
			];
		}

		$post_id = self::resolve_post( $url );
		if ( ! $post_id ) {
			return [
				'error' => __( 'That URL could not be resolved to a post or page on this site. Paste the numeric post ID instead, or check the URL is from this site. This is NOT a statement about the customer\'s access.', 'wc_pay_per_post' ),
			];
		}

		$post = get_post( $post_id );

		return self::evaluate( $user, $post );
	}

	/**
	 * Resolve a URL, or a bare numeric ID, to a post id.
	 *
	 * url_to_postid() handles posts, pages and custom post types, but returns 0
	 * for some permalink shapes and for the front page — hence the fallbacks.
	 *
	 * @param string $url
	 *
	 * @return int 0 when unresolved.
	 */
	public static function resolve_post( string $url ): int {
		if ( ctype_digit( $url ) ) {
			return get_post( (int) $url ) ? (int) $url : 0;
		}

		$post_id = (int) url_to_postid( $url );

		if ( ! $post_id ) {
			// url_to_postid() ignores query strings like ?page_id=12 / ?p=12.
			$query = wp_parse_url( $url, PHP_URL_QUERY );
			if ( $query ) {
				parse_str( $query, $params );
				foreach ( [ 'page_id', 'p' ] as $key ) {
					if ( ! empty( $params[ $key ] ) && get_post( (int) $params[ $key ] ) ) {
						return (int) $params[ $key ];
					}
				}
			}
		}

		return $post_id && get_post( $post_id ) ? $post_id : 0;
	}

	/**
	 * Evaluate access for a user against a post, as a front-end request would.
	 *
	 * @param WP_User $user
	 * @param WP_Post $post
	 *
	 * @return array
	 */
	private static function evaluate( WP_User $user, WP_Post $post ): array {
		$original_user = get_current_user_id();

		// Force the engine to behave as a front-end request. Without this the
		// admin-call shortcut fires (we are in wp-admin) and everything reports
		// as accessible. See Restrict_Content::check_if_admin_call().
		$simulate_front_end = static function () {
			return false;
		};
		add_filter( 'wc_pay_per_post_is_admin_call', $simulate_front_end, 99 );

		// Expiry and page-view protection measure access from one order's paid
		// date. The engine applies this filter to the order it picked, so
		// recording it names that order without a second copy of the query.
		$date_order_id     = 0;
		$record_date_order = static function ( $sql, $order_id, $post_id ) use ( &$date_order_id, $post ) {
			if ( (int) $post_id === $post->ID ) {
				$date_order_id = (int) $order_id;
			}

			return $sql;
		};
		add_filter( 'wc_pay_per_post_override_purchase_date_sql', $record_date_order, 10, 3 );

		// The engine caches resolved product ids for the request in a global.
		$saved_product_ids = $GLOBALS['product_ids'] ?? null;

		try {
			wp_set_current_user( $user->ID );
			self::flush_request_caches();

			$GLOBALS['product_ids'] = Woocommerce_Pay_Per_Post_Helper::get_product_ids_by_post_id( $post->ID );
			$protection             = $GLOBALS['product_ids'];

			// track_page_view = false: diagnosing a page-view customer must never
			// consume one of their allowed views.
			$restrict = new \PRAMADILLO\Woocommerce_Pay_Per_Post_Restrict_Content( $post->ID, false );
			$verdict  = $restrict->can_user_view_content();
			$checks   = self::capture_checks( $restrict );
			$info     = $restrict->user_post_info ?? [];
		} finally {
			remove_filter( 'wc_pay_per_post_is_admin_call', $simulate_front_end, 99 );
			remove_filter( 'wc_pay_per_post_override_purchase_date_sql', $record_date_order, 10 );
			$GLOBALS['product_ids'] = $saved_product_ids;
			wp_set_current_user( $original_user );
			self::flush_request_caches();
		}

		$history = self::purchase_history(
			$user,
			(array) ( $protection['product_ids'] ?? [] ),
			(string) ( $protection['protection_type'] ?? 'standard' ),
			$date_order_id,
			$info['last_purchase_date'] ?? null
		);

		return [
			'user'        => $user,
			'post'        => $post,
			'verdict'     => (bool) $verdict,
			'protection'  => $protection,
			'reason'      => self::explain( (bool) $verdict, $checks, $protection, $info, $post->ID, $user, $history ),
			'evidence'    => self::evidence( $user, $protection, $info, $checks, $post->ID ),
			'history'     => $history,
			'focus_order' => self::focus_order( (bool) $verdict, $checks, $protection, $history, $date_order_id ),
			'page_config' => self::page_configuration( $post->ID ),
			'products'    => self::product_facts( (array) ( $protection['product_ids'] ?? [] ) ),
			'versions'    => self::versions(),
			'environment' => self::environment(),
			'warnings'    => self::warnings( (array) ( $protection['product_ids'] ?? [] ) ),
			'checks'      => $checks,
		];
	}

	/**
	 * Re-run each protection check individually so the reasoning can be shown.
	 *
	 * can_user_view_content() computes these internally but does not expose
	 * them; running them again against the same instance is cheap and keeps
	 * this class from duplicating any logic.
	 *
	 * @param object $restrict
	 *
	 * @return array<string, bool>
	 */
	private static function capture_checks( $restrict ): array {
		$results = [];

		foreach ( (array) $restrict->protection_checks as $check ) {
			if ( is_callable( [ $restrict, $check ] ) ) {
				$results[ $check ] = (bool) $restrict->$check();
			}
		}

		return $results;
	}

	private static function flush_request_caches(): void {
		if ( class_exists( '\PRAMADILLO\INTEGRATIONS\WooCommerceSubscriptions' ) ) {
			\PRAMADILLO\INTEGRATIONS\WooCommerceSubscriptions::flush_subscription_cache();
		}
	}

	/**
	 * Turn the check results into the single sentence a support reply needs.
	 */
	private static function explain( bool $verdict, array $checks, array $protection, array $info, int $post_id, ?WP_User $user = null, array $history = [] ): string {
		if ( empty( $protection['is_protected'] ) ) {
			return __( 'This page is not protected by Pay For Post at all, so everyone can see it. If the customer cannot, the cause is elsewhere — a membership plugin, the theme, or caching.', 'wc_pay_per_post' );
		}

		if ( $verdict ) {
			// Page-level, and therefore the most important thing to say: the
			// paywall is not active on this page yet, so EVERYONE can read it,
			// purchase or no purchase. Only delay protection produces this.
			if ( array_key_exists( 'check_if_should_show_paywall', $checks ) && ! $checks['check_if_should_show_paywall'] ) {
				return sprintf(
					/* translators: %s: a sentence about whether the customer purchased */
					__( 'Access granted because the paywall is not active on this page yet — delay protection is set, and the delay window has not elapsed. Note this applies to EVERYONE, not just this customer: the page is currently public. %s', 'wc_pay_per_post' ),
					empty( $checks['check_if_purchased'] )
						? __( 'This customer has not purchased, and will lose access when the delay elapses.', 'wc_pay_per_post' )
						: __( 'This customer has also purchased, so they keep access once the delay elapses.', 'wc_pay_per_post' )
				);
			}

			if ( ! empty( $checks['check_if_admin_user_have_access'] ) ) {
				return __( 'Access granted because this user is an administrator and "allow admins access to protected posts" is enabled in settings — not because of anything they bought.', 'wc_pay_per_post' );
			}
			if ( ! empty( $checks['check_if_user_role_has_access'] ) ) {
				return __( 'Access granted because this user\'s role skips the paywall (Settings > Access, "Other roles that skip the paywall", or the wc_pay_for_post_allowed_roles filter) — not because of anything they bought.', 'wc_pay_per_post' );
			}
			if ( ! empty( $checks['check_if_is_subscriber'] ) ) {
				return __( 'Access granted by an active subscription to one of the products protecting this page.', 'wc_pay_per_post' );
			}
			if ( ! empty( $checks['check_if_is_member'] ) ) {
				return __( 'Access granted by an active membership.', 'wc_pay_per_post' );
			}
			if ( ! empty( $checks['check_if_purchased'] ) ) {
				return __( 'Access granted by a qualifying purchase, within any configured limits.', 'wc_pay_per_post' );
			}

			return __( 'Access granted.', 'wc_pay_per_post' );
		}

		if ( empty( $checks['check_if_logged_in'] ) ) {
			return __( 'No access: the visitor is not logged in. Access is tied to a user account.', 'wc_pay_per_post' );
		}

		if ( empty( $checks['check_if_purchased'] ) ) {
			// They DID pay — on a plan whose subscription has since lapsed.
			// "Has not purchased" would send support looking for a missing order.
			$lapsed = self::lapsed_plan_products( $user, (array) ( $protection['product_ids'] ?? [] ) );
			if ( $lapsed ) {
				return sprintf(
					/* translators: %s: product name(s) */
					__( 'No access: this customer bought %s on a subscription plan, and holds no active subscription to it. A purchase made on a plan unlocks the page only while its subscription is active or pending cancellation; buying the product one-time unlocks it permanently.', 'wc_pay_per_post' ),
					implode( ', ', $lapsed )
				);
			}

			// An order for the right product that has not been paid, or no
			// longer is, is the usual answer to "but I bought it". Name it.
			$unpaid = self::unpaid_order( $history );
			if ( $unpaid ) {
				return sprintf(
					/* translators: 1: order number, 2: product name(s), 3: order status, 4: what that status means */
					__( 'No access: none of this customer\'s paid orders holds a product that protects this page. Their order #%1$d for %2$s is %3$s, so it %4$s.', 'wc_pay_per_post' ),
					$unpaid['id'],
					$unpaid['products'],
					$unpaid['status_label'],
					in_array( $unpaid['status'], [ 'pending', 'on-hold', 'checkout-draft' ], true )
						? __( 'does not count until it is paid', 'wc_pay_per_post' )
						: __( 'does not count', 'wc_pay_per_post' )
				);
			}

			return __( 'No access: this customer has not purchased any of the products that protect this page. If they believe they paid, check the order exists, is Completed or Processing, and is attached to THIS user account rather than a guest checkout.', 'wc_pay_per_post' );
		}

		// Purchased, but a protection type closed the door.
		switch ( $protection['protection_type'] ?? '' ) {
			case 'expire':
				$expires = $info['expiration_date'] ?? null;

				return sprintf(
					/* translators: %s: expiry date */
					__( 'No access: the customer DID purchase, but the access window has closed. Access ended %s.', 'wc_pay_per_post' ),
					$expires ? $expires->format( Woocommerce_Pay_Per_Post_Helper::date_time_format() ) : __( 'on the configured expiry date', 'wc_pay_per_post' )
				);
			case 'page-view':
				return sprintf(
					/* translators: 1: views used, 2: views allowed */
					__( 'No access: the customer DID purchase, but has used their page-view allowance (%1$s of %2$s).', 'wc_pay_per_post' ),
					$info['total_page_views'] ?? '?',
					get_post_meta( $post_id, WC_PPP_SLUG . '_page_view_restriction', true ) ?: '?'
				);
			case 'delay':
				return __( 'No access: the customer DID purchase, but the paywall applies to this page now that the delay window has elapsed.', 'wc_pay_per_post' );
		}

		if ( ! empty( $checks['check_if_post_contains_subscription_products'] ) && empty( $checks['check_if_is_subscriber'] ) ) {
			return __( 'No access: this page is protected by a subscription product, and the customer does not hold an active subscription to it. A past purchase alone is not enough for a subscription product.', 'wc_pay_per_post' );
		}

		return __( 'No access. See the individual checks below for which condition failed.', 'wc_pay_per_post' );
	}

	/**
	 * The most recent order that holds a product protecting the page but does
	 * not count because of its status: unpaid, failed, cancelled or refunded.
	 *
	 * @param array $history From purchase_history().
	 *
	 * @return array{id: int, status: string, status_label: string, products: string}|null
	 */
	private static function unpaid_order( array $history ): ?array {
		foreach ( (array) ( $history['orders'] ?? [] ) as $order ) {
			if ( ! empty( $order['qualifies'] ) || empty( $order['status_unpaid'] ) ) {
				continue;
			}

			$names = [];
			foreach ( (array) $order['items'] as $item ) {
				if ( ! empty( $item['required'] ) ) {
					$names[] = $item['name'];
				}
			}

			if ( $names ) {
				return [
					'id'           => (int) $order['id'],
					'status'       => (string) $order['status'],
					'status_label' => (string) $order['status_label'],
					'products'     => implode( ', ', array_unique( $names ) ),
				];
			}
		}

		return null;
	}

	/**
	 * The one order that explains the answer, for the "Open order" button:
	 * the unpaid order the reason names, the order an expiry window or
	 * page-view allowance is measured from, or the order that grants access.
	 * Null when no single order explains it.
	 *
	 * @param bool  $verdict       The engine's answer.
	 * @param array $checks        Check name => answer.
	 * @param array $protection    The page's protection.
	 * @param array $history       From purchase_history().
	 * @param int   $date_order_id The order the engine measured from, or 0.
	 *
	 * @return array{id: int, url: string}|null
	 */
	private static function focus_order( bool $verdict, array $checks, array $protection, array $history, int $date_order_id ): ?array {
		$orders = (array) ( $history['orders'] ?? [] );
		$id     = 0;

		if ( empty( $protection['is_protected'] ) ) {
			return null;
		}

		if ( ! $verdict && empty( $checks['check_if_purchased'] ) ) {
			$unpaid = self::unpaid_order( $history );
			$id     = $unpaid ? $unpaid['id'] : 0;
		} elseif ( $date_order_id && in_array( $protection['protection_type'] ?? '', [ 'expire', 'page-view' ], true ) ) {
			$id = $date_order_id;
		} elseif ( $verdict && ! empty( $checks['check_if_purchased'] ) && empty( $checks['check_if_admin_user_have_access'] ) && empty( $checks['check_if_user_role_has_access'] ) ) {
			foreach ( $orders as $order ) {
				if ( ! empty( $order['qualifies'] ) ) {
					$id = (int) $order['id'];
					break;
				}
			}
		}

		if ( ! $id ) {
			return null;
		}

		foreach ( $orders as $order ) {
			if ( (int) $order['id'] === $id ) {
				return [
					'id'  => $id,
					'url' => (string) $order['edit_url'],
				];
			}
		}

		return [
			'id'  => $id,
			'url' => Woocommerce_Pay_Per_Post_Protected_Content::order_url( $id ),
		];
	}

	/**
	 * The WooCommerce Subscriptions integration, or null when it is not in use.
	 */
	private static function subscriptions_integration(): ?\PRAMADILLO\INTEGRATIONS\WooCommerceSubscriptions {
		if ( ! Woocommerce_Pay_Per_Post_Helper::can_use_woocommerce_subscriptions() || ! class_exists( '\PRAMADILLO\INTEGRATIONS\WooCommerceSubscriptions' ) ) {
			return null;
		}

		return new \PRAMADILLO\INTEGRATIONS\WooCommerceSubscriptions();
	}

	/**
	 * Products sold on plans that this customer bought on a plan but no longer
	 * has access through.
	 *
	 * @return string[] "#id name" labels.
	 */
	private static function lapsed_plan_products( ?WP_User $user, array $product_ids ): array {
		$subscriptions = self::subscriptions_integration();

		if ( ! $user || ! $subscriptions ) {
			return [];
		}

		$lapsed = [];
		foreach ( $product_ids as $id ) {
			$id = trim( (string) $id );

			if ( $subscriptions->is_plan_product( $id ) && $subscriptions->has_plan_purchase( $user, $id ) && ! $subscriptions->has_plan_product_access( $user, $id ) ) {
				$product  = wc_get_product( $id );
				$lapsed[] = '#' . $id . ( $product ? ' ' . $product->get_name() : '' );
			}
		}

		return $lapsed;
	}

	/**
	 * How a customer stands with a product sold on subscription plans — the
	 * product's line in "Required products".
	 */
	private static function plan_purchase_status( \PRAMADILLO\INTEGRATIONS\WooCommerceSubscriptions $subscriptions, WP_User $user, string $product_id ): string {
		if ( $subscriptions->has_active_subscription_to( $user->ID, $product_id ) ) {
			return __( 'sold on subscription plans; ACTIVE subscription', 'wc_pay_per_post' );
		}

		if ( $subscriptions->has_one_time_purchase( $user, $product_id ) ) {
			return __( 'sold on subscription plans; bought one-time', 'wc_pay_per_post' );
		}

		if ( $subscriptions->has_plan_purchase( $user, $product_id ) ) {
			return __( 'sold on subscription plans; bought on a plan, but NO active subscription', 'wc_pay_per_post' );
		}

		return __( 'sold on subscription plans; NOT purchased', 'wc_pay_per_post' );
	}

	/**
	 * The supporting facts — what a support reply needs to quote.
	 */
	private static function evidence( WP_User $user, array $protection, array $info, array $checks, int $post_id ): array {
		$rows = [];

		$rows[ __( 'Protected', 'wc_pay_per_post' ) ] = ! empty( $protection['is_protected'] )
			? __( 'yes', 'wc_pay_per_post' )
			: __( 'no', 'wc_pay_per_post' );

		if ( empty( $protection['is_protected'] ) ) {
			return $rows;
		}

		$rows[ __( 'Protection type', 'wc_pay_per_post' ) ] = Woocommerce_Pay_Per_Post_Helper::protection_type_label( (string) ( $protection['protection_type'] ?? 'standard' ) );

		$product_ids   = (array) ( $protection['product_ids'] ?? [] );
		$products      = [];
		$subscriptions = self::subscriptions_integration();
		foreach ( $product_ids as $id ) {
			$product    = wc_get_product( trim( (string) $id ) );
			$label      = $product ? $product->get_name() : __( 'missing product', 'wc_pay_per_post' );
			$status     = $subscriptions && $subscriptions->is_plan_product( trim( (string) $id ) )
				? self::plan_purchase_status( $subscriptions, $user, trim( (string) $id ) )
				: ( Woocommerce_Pay_Per_Post_Helper::customer_has_purchased_product( $user->user_email, $user->ID, trim( (string) $id ) )
					? __( 'purchased', 'wc_pay_per_post' )
					: __( 'NOT purchased', 'wc_pay_per_post' ) );
			$products[] = sprintf( '#%s %s — %s', trim( (string) $id ), $label, $status );
		}
		$rows[ __( 'Required products', 'wc_pay_per_post' ) ] = $products ? implode( "\n", $products ) : __( 'none', 'wc_pay_per_post' );

		// Delay protection is driven by the POST's date, not the customer's — a
		// distinction that causes real confusion, so state both.
		if ( 'delay' === ( $protection['protection_type'] ?? '' ) ) {
			$amount    = (int) get_post_meta( $post_id, WC_PPP_SLUG . '_delay_restriction', true );
			$frequency = (string) get_post_meta( $post_id, WC_PPP_SLUG . '_delay_restriction_frequency', true );

			$rows[ __( 'Published', 'wc_pay_per_post' ) ]    = get_the_time( Woocommerce_Pay_Per_Post_Helper::date_time_format(), $post_id );
			$rows[ __( 'Delay window', 'wc_pay_per_post' ) ] = sprintf( '%d %s after publication', $amount, $frequency );

			$starts = strtotime( '+' . $amount . ' ' . $frequency, (int) get_post_time( 'U', true, $post_id ) );
			if ( $starts ) {
				$rows[ __( 'Paywall starts', 'wc_pay_per_post' ) ] = sprintf(
					'%s (%s)',
					wp_date( Woocommerce_Pay_Per_Post_Helper::date_time_format(), $starts ),
					$starts > time() ? __( 'not yet — the page is public right now', 'wc_pay_per_post' ) : __( 'already in effect', 'wc_pay_per_post' )
				);
			}
		}

		if ( ! empty( $info['last_purchase_date'] ) ) {
			$rows[ __( 'Last qualifying purchase', 'wc_pay_per_post' ) ] = $info['last_purchase_date']->format( Woocommerce_Pay_Per_Post_Helper::date_time_format() );
		}
		if ( ! empty( $info['expiration_date'] ) ) {
			$expires = $info['expiration_date'];
			$rows[ __( 'Access expires', 'wc_pay_per_post' ) ] = sprintf(
				'%s (%s)',
				$expires->format( Woocommerce_Pay_Per_Post_Helper::date_time_format() ),
				$expires->isPast() ? __( 'in the past', 'wc_pay_per_post' ) : __( 'still in the future', 'wc_pay_per_post' )
			);
		}
		if ( isset( $info['total_page_views'] ) ) {
			$rows[ __( 'Page views used', 'wc_pay_per_post' ) ] = (string) $info['total_page_views'];
		}

		if ( array_key_exists( 'check_if_is_subscriber', $checks ) ) {
			$rows[ __( 'Active subscription', 'wc_pay_per_post' ) ] = $checks['check_if_is_subscriber']
				? __( 'yes', 'wc_pay_per_post' )
				: __( 'no', 'wc_pay_per_post' );
		}
		if ( array_key_exists( 'check_if_is_member', $checks ) ) {
			$rows[ __( 'Active membership', 'wc_pay_per_post' ) ] = $checks['check_if_is_member']
				? __( 'yes', 'wc_pay_per_post' )
				: __( 'no', 'wc_pay_per_post' );
		}

		return $rows;
	}

	/**
	 * Settings and integrations that change the answer but never appear in a
	 * debug log.
	 */
	private static function environment(): array {
		return [
			__( 'Plugin active (wcppp_is_active)', 'wc_pay_per_post' ) => apply_filters( 'wcppp_is_active', true ) ? __( 'yes', 'wc_pay_per_post' ) : __( 'NO — protection is disabled site-wide', 'wc_pay_per_post' ),
			__( 'Admins bypass protection', 'wc_pay_per_post' )        => get_option( WC_PPP_SLUG . '_allow_admins_access_to_protected_posts', false ) ? __( 'yes', 'wc_pay_per_post' ) : __( 'no', 'wc_pay_per_post' ),
			__( 'Roles bypassing paywall', 'wc_pay_per_post' )         => implode( ', ', Woocommerce_Pay_Per_Post_Helper::paywall_bypass_roles() ) ?: __( 'none', 'wc_pay_per_post' ),
			__( 'Subscriptions integration', 'wc_pay_per_post' )       => self::describe_subscriptions_integration(),
			__( 'Memberships integration', 'wc_pay_per_post' )         => Woocommerce_Pay_Per_Post_Helper::can_use_woocommerce_memberships() ? __( 'active', 'wc_pay_per_post' ) : __( 'inactive', 'wc_pay_per_post' ),
			// Order storage decides which purchase-history query runs at all, so a
			// report about purchases is incomplete without it.
			__( 'Order storage', 'wc_pay_per_post' )                   => Woocommerce_Pay_Per_Post_Helper::hops_enabled()
				? __( 'HPOS (High-Performance Order Storage)', 'wc_pay_per_post' )
				: __( 'legacy post tables', 'wc_pay_per_post' ),
		];
	}

	/**
	 * The customer's complete purchase history, with nothing filtered out.
	 *
	 * An earlier version of this listed only orders containing a product that
	 * protects the page. That hid the answer to the very case it was built for:
	 * a customer with twelve monthly payments, of which only the first two used
	 * the product listed on the page. The report showed two orders, the site
	 * showed twelve, and the gap — the actual finding — was invisible.
	 *
	 * So: every order, every line item, and an explicit list of the products this
	 * customer bought that do NOT unlock this page. That last list is usually
	 * where the answer is.
	 *
	 * An order counts toward access by the rule of the engine's purchase check,
	 * `wc_customer_bought_product()`: it carries a product that unlocks the page
	 * and its status is one WooCommerce treats as paid. Not whether it has a paid
	 * date: WooCommerce leaves a Cash on Delivery order processing, with no paid
	 * date, until it is completed, and the purchase check counts it all along.
	 * A line bought on a subscription plan counts only while that subscription
	 * is active, as in `WooCommerceSubscriptions::has_plan_product_access()`.
	 *
	 * Orders are gathered by account AND by billing email, because the two differ
	 * in ways that matter. The engine's purchase CHECK matches on billing email
	 * or account, while the purchase DATE that expiry and page-view protection
	 * measure from comes from an order on the account only — so an order can
	 * count as a purchase yet never move the expiry clock. Both are shown, per
	 * order.
	 *
	 * Contains no personal data: no name, address, phone or billing details. Just
	 * order ids, dates, statuses, totals and product ids.
	 *
	 * @param WP_User                $user
	 * @param array                  $required_product_ids Products that protect the page.
	 * @param string                 $protection_type      The page's protection type, as the engine applies it.
	 * @param int                    $date_order_id        The order the engine measured the purchase date from; 0 when it measured none.
	 * @param DateTimeInterface|null $measured_from        The date the engine measured from.
	 *
	 * @return array{orders: array, other_products: array, total_found: int, truncated: bool, qualifying_count: int}
	 */
	public static function purchase_history( WP_User $user, array $required_product_ids, string $protection_type = 'standard', int $date_order_id = 0, ?DateTimeInterface $measured_from = null ): array {
		$empty = [
			'orders'           => [],
			'other_products'   => [],
			'total_found'      => 0,
			'truncated'        => false,
			'qualifying_count' => 0,
		];

		if ( ! function_exists( 'wc_get_orders' ) ) {
			return $empty;
		}

		$required = array_filter( array_map( 'absint', $required_product_ids ) );

		$limit  = (int) apply_filters( 'wc_pay_per_post_diagnostic_order_limit', 50 );
		$common = [
			'limit'   => $limit,
			'orderby' => 'date',
			'order'   => 'DESC',
			'status'  => array_keys( wc_get_order_statuses() ),
			'return'  => 'objects',
		];

		$found = [];
		foreach ( [ [ 'customer_id' => $user->ID ], [ 'billing_email' => $user->user_email ] ] as $args ) {
			foreach ( (array) wc_get_orders( array_merge( $common, $args ) ) as $order ) {
				if ( $order instanceof WC_Order ) {
					$found[ $order->get_id() ] = $order;
				}
			}
		}

		$rows           = [];
		$other_products = [];
		$qualifying     = 0;
		$subscriptions  = self::subscriptions_integration();

		foreach ( $found as $order ) {
			$items        = [];
			$has_required = false;
			// A plan line counts only while its subscription is active. Track it
			// apart, so a lapsed plan is not reported as "no product that unlocks".
			$counting_line    = false;
			$lapsed_plan_line = false;

			foreach ( $order->get_items() as $item ) {
				$product_id   = (int) $item->get_product_id();
				$variation_id = (int) $item->get_variation_id();
				$id           = $variation_id ?: $product_id;
				$is_required  = in_array( $product_id, $required, true ) || in_array( $variation_id, $required, true );

				if ( $is_required ) {
					$has_required = true;
					$matched      = in_array( $product_id, $required, true ) ? $product_id : $variation_id;

					if ( $subscriptions && $order instanceof WC_Order && $item instanceof WC_Order_Item_Product
						&& $subscriptions->is_plan_product( $matched )
						&& $subscriptions->is_subscription_purchase( $order, $item, $matched )
						&& ! $subscriptions->has_active_subscription_to( $user->ID, $matched ) ) {
						$lapsed_plan_line = true;
					} else {
						$counting_line = true;
					}
				} else {
					if ( ! isset( $other_products[ $id ] ) ) {
						$other_products[ $id ] = [
							'id'    => $id,
							'name'  => $item->get_name(),
							'count' => 0,
						];
					}
					++$other_products[ $id ]['count'];
				}

				$items[] = [
					'id'       => $id,
					'name'     => $item->get_name(),
					'required' => $is_required,
				];
			}

			$status    = $order->get_status();
			$attached  = (int) $order->get_customer_id() === (int) $user->ID;
			$status_ok = in_array( $status, wc_get_is_paid_statuses(), true );
			$paid      = $order->get_date_paid();
			$qualifies = $counting_line && $status_ok;

			if ( $qualifies ) {
				++$qualifying;
			}

			$reasons = [];
			if ( ! $has_required ) {
				$reasons[] = __( 'contains no product that unlocks this page', 'wc_pay_per_post' );
			}
			if ( $has_required && ! $counting_line && $lapsed_plan_line ) {
				$reasons[] = __( 'bought on a subscription plan, and no subscription containing the product is active — a plan purchase counts only while its subscription is active', 'wc_pay_per_post' );
			}
			if ( ! $status_ok ) {
				$reasons[] = sprintf(
					/* translators: 1: order status, 2: qualifying statuses */
					__( 'status is "%1$s"; only %2$s count', 'wc_pay_per_post' ),
					$status,
					implode( ' / ', wc_get_is_paid_statuses() )
				);
			}

			// What counting does not show: how the order matched, and whether the
			// purchase date comes from it.
			$note = '';
			if ( $qualifies && ! $attached ) {
				// Deliberately precise: such an order DOES satisfy the purchase
				// check, which matches on billing email, but cannot supply the
				// purchase date, which comes from an order on the account only.
				$owner = 0 === (int) $order->get_customer_id()
					? __( 'placed as a GUEST', 'wc_pay_per_post' )
					: sprintf(
						/* translators: %d: user ID the order belongs to */
						__( 'belongs to a DIFFERENT account (user %d)', 'wc_pay_per_post' ),
						(int) $order->get_customer_id()
					);

				if ( 'expire' === $protection_type ) {
					$note = sprintf(
						/* translators: %s: whose order it is, e.g. "placed as a GUEST" */
						__( '%s — counts as a purchase (matched on billing email), but cannot supply the purchase date the expiry window is measured from; only an order on the customer\'s account can', 'wc_pay_per_post' ),
						$owner
					);
				} elseif ( 'page-view' === $protection_type ) {
					$note = sprintf(
						/* translators: %s: whose order it is, e.g. "placed as a GUEST" */
						__( '%s — counts as a purchase (matched on billing email), but cannot supply the purchase date page views are counted from; only an order on the customer\'s account can', 'wc_pay_per_post' ),
						$owner
					);
				} else {
					$note = sprintf(
						/* translators: %s: whose order it is, e.g. "placed as a GUEST" */
						__( '%s — counts as a purchase (matched on billing email)', 'wc_pay_per_post' ),
						$owner
					);
				}
			}

			// The order the engine measured expiry or page views from: the one place
			// a paid date matters. A Cash on Delivery order has none until it is
			// completed, and the engine then measures from a stand-in date.
			if ( $date_order_id && $date_order_id === $order->get_id() ) {
				$fallback = $measured_from ? $measured_from->format( Woocommerce_Pay_Per_Post_Helper::date_time_format() ) : '?';

				if ( 'expire' === $protection_type ) {
					$note = $paid
						? __( 'the expiry window is measured from this order\'s paid date', 'wc_pay_per_post' )
						: sprintf(
							/* translators: %s: the date the plugin measured the expiry window from instead */
							__( 'the expiry window is measured from this order, but it has no paid date, so the plugin fell back to %s', 'wc_pay_per_post' ),
							$fallback
						);
				} elseif ( 'page-view' === $protection_type ) {
					$note = $paid
						? __( 'page views are counted from this order\'s paid date', 'wc_pay_per_post' )
						: sprintf(
							/* translators: %s: the date the plugin counted page views from instead */
							__( 'page views are counted from this order, but it has no paid date, so the plugin fell back to %s', 'wc_pay_per_post' ),
							$fallback
						);
				}
			}

			$rows[] = [
				'id'            => $order->get_id(),
				'edit_url'      => Woocommerce_Pay_Per_Post_Protected_Content::order_url( (int) $order->get_id() ),
				'date'          => $order->get_date_created() ? $order->get_date_created()->format( 'Y-m-d H:i' ) : '—',
				'paid'          => $paid ? $paid->format( 'Y-m-d H:i' ) : '—',
				'status'        => $status,
				'status_label'  => function_exists( 'wc_get_order_status_name' ) ? wc_get_order_status_name( $status ) : $status,
				// Holds a product that unlocks the page, and its status alone keeps it from counting.
				'status_unpaid' => $has_required && $counting_line && ! $status_ok,
				'attachment' => $attached ? 'account' : ( 0 === (int) $order->get_customer_id() ? 'GUEST' : 'user ' . (int) $order->get_customer_id() ),
				'total'      => $order->get_total(),
				'currency'   => $order->get_currency(),
				'items'      => $items,
				'products'   => implode( ', ', array_map( static function ( $i ) {
					return '#' . $i['id'] . ( $i['required'] ? ' *' : '' );
				}, $items ) ),
				'qualifies'  => (bool) $qualifies,
				'why_not'    => implode( '; ', $reasons ),
				'note'       => $note,
			];
		}

		usort( $rows, static function ( $a, $b ) {
			return strcmp( (string) $b['date'], (string) $a['date'] );
		} );

		usort( $other_products, static function ( $a, $b ) {
			return $b['count'] <=> $a['count'];
		} );

		return [
			'orders'           => $rows,
			'other_products'   => array_values( $other_products ),
			'total_found'      => count( $rows ),
			// Never let a cap look like completeness.
			'truncated'        => count( $found ) >= $limit,
			'qualifying_count' => $qualifying,
		];
	}

	/**
	 * The page's raw Pay For Post settings.
	 *
	 * The report already says "protection type: expire" and shows a computed
	 * expiry date, but never the configured window that produced it. Reading the
	 * settings back is what separates "the plugin is wrong" from "the plugin is
	 * doing exactly what this page says".
	 */
	public static function page_configuration( int $post_id ): array {
		$meta = static function ( $key ) use ( $post_id ) {
			return get_post_meta( $post_id, WC_PPP_SLUG . '_' . $key, true );
		};

		$product_ids = (array) $meta( 'product_ids' );
		$product_ids = array_filter( array_map( 'absint', $product_ids ) );

		$config = [
			__( 'Post', 'wc_pay_per_post' )               => sprintf( '%d (%s, %s)', $post_id, get_post_type( $post_id ), get_post_status( $post_id ) ),
			__( 'Protected by products', 'wc_pay_per_post' ) => $product_ids ? implode( ', ', array_map( static function ( $id ) {
				return '#' . $id;
			}, $product_ids ) ) : __( 'none', 'wc_pay_per_post' ),
			__( 'Delay enabled', 'wc_pay_per_post' )      => $meta( 'delay_restriction_enable' ) ? 'yes' : 'no',
			__( 'Page view enabled', 'wc_pay_per_post' )  => $meta( 'page_view_restriction_enable' ) ? 'yes' : 'no',
			__( 'Expire enabled', 'wc_pay_per_post' )     => $meta( 'expire_restriction_enable' ) ? 'yes' : 'no',
		];

		if ( $meta( 'expire_restriction_enable' ) ) {
			$config[ __( 'Expire window', 'wc_pay_per_post' ) ] = trim( $meta( 'expire_restriction' ) . ' ' . $meta( 'expire_restriction_frequency' ) )
				. ' ' . __( '(measured from the customer\'s MOST RECENT qualifying purchase, not their first)', 'wc_pay_per_post' );
		}

		if ( $meta( 'delay_restriction_enable' ) ) {
			$config[ __( 'Delay window', 'wc_pay_per_post' ) ] = trim( $meta( 'delay_restriction' ) . ' ' . $meta( 'delay_restriction_frequency' ) )
				. ' ' . __( '(measured from the post\'s publication date)', 'wc_pay_per_post' );
		}

		if ( $meta( 'page_view_restriction_enable' ) ) {
			$config[ __( 'Page views allowed', 'wc_pay_per_post' ) ] = (string) $meta( 'page_view_restriction' );
			if ( $meta( 'page_view_restriction_enable_time_frame' ) ) {
				$config[ __( 'Page view time frame', 'wc_pay_per_post' ) ] = trim( $meta( 'page_view_restriction_time_frame' ) . ' ' . $meta( 'page_view_restriction_frequency' ) );
			}
		}

		return $config;
	}

	/**
	 * What each protecting product actually IS.
	 *
	 * A product being a subscription or membership product changes which rules
	 * apply, and that is not visible from the product name.
	 */
	public static function product_facts( array $product_ids ): array {
		$rows = [];

		foreach ( array_filter( array_map( 'absint', $product_ids ) ) as $id ) {
			$product = function_exists( 'wc_get_product' ) ? wc_get_product( $id ) : null;

			if ( ! $product ) {
				$rows[] = [
					'id'      => $id,
					'name'    => __( 'PRODUCT NOT FOUND — it may have been deleted', 'wc_pay_per_post' ),
					'type'    => '—',
					'subscription' => '—',
					'membership'   => '—',
				];
				continue;
			}

			$rows[] = [
				'id'           => $id,
				'name'         => $product->get_name(),
				'type'         => $product->get_type(),
				// "plan": sold on subscription plans, a different rule from "yes".
				'subscription' => Woocommerce_Pay_Per_Post_Protection_Checks::check_if_product_is_a_subscription_product( $id )
					? 'yes'
					: ( Woocommerce_Pay_Per_Post_Protection_Checks::check_if_product_is_a_subscription_plan_product( $id ) ? 'plan' : 'no' ),
				'membership'   => Woocommerce_Pay_Per_Post_Protection_Checks::check_if_product_is_a_membership_product( $id ) ? 'yes' : 'no',
			];
		}

		return $rows;
	}

	/**
	 * Versions. Half of any support reply starts by asking for these.
	 */
	public static function versions(): array {
		global $wp_version;

		return [
			'Pay For Post' => defined( 'WC_PPP_VERSION' ) ? WC_PPP_VERSION : '?',
			'Licence'      => ( function_exists( 'wcppp_freemius' ) && wcppp_freemius()->can_use_premium_code() ) ? 'premium' : 'free',
			'WordPress'    => $wp_version ?? '?',
			'WooCommerce'  => defined( 'WC_VERSION' ) ? WC_VERSION : __( 'not active', 'wc_pay_per_post' ),
			'PHP'          => PHP_VERSION,
		];
	}

	/**
	 * Describe the Subscriptions integration, including WHY it is on or off.
	 *
	 * A bare "active" / "inactive" is ambiguous in exactly the situation where
	 * the answer matters most. On a store using a compatibility layer, the
	 * integration is normally switched off with the
	 * `wc_pay_per_post_can_use_woocommerce_subscriptions` filter — and "inactive"
	 * then reads identically whether that filter is still in place or whether the
	 * subscriptions plugin itself was removed. Those two states call for opposite
	 * responses, so the tool has to distinguish them.
	 *
	 * Reported from a live support thread where the only way to tell the two
	 * apart was to look at the site's plugin sidebar in a screenshot.
	 */
	public static function describe_subscriptions_integration(): string {
		$enabled        = Woocommerce_Pay_Per_Post_Helper::can_use_woocommerce_subscriptions();
		$classes_exist  = class_exists( 'WC_Subscriptions_Product' );
		$really_wcs     = self::woocommerce_subscriptions_is_really_installed();

		if ( $enabled ) {
			return $really_wcs
				? __( 'active (WooCommerce Subscriptions)', 'wc_pay_per_post' )
				: __( 'active — but via a compatibility layer, NOT WooCommerce Subscriptions itself', 'wc_pay_per_post' );
		}

		// The integration needs Premium before the setting or the filter count.
		if ( $classes_exist && ! Woocommerce_Pay_Per_Post_Helper::can_use_premium_protection() ) {
			return __( 'inactive — subscription classes ARE present, but the integration needs an active Premium licence', 'wc_pay_per_post' );
		}

		if ( $classes_exist && ! get_option( WC_PPP_SLUG . '_use_woocommerce_subscriptions', true ) ) {
			return __( 'inactive — subscription classes ARE present, and "Use WooCommerce Subscriptions" is off under Settings > Integrations', 'wc_pay_per_post' );
		}

		if ( $classes_exist ) {
			return __( 'inactive — subscription classes ARE present, so it is switched off by the wc_pay_per_post_can_use_woocommerce_subscriptions filter (that filter is doing its job)', 'wc_pay_per_post' );
		}

		return __( 'inactive — no subscription classes are present on this site at all', 'wc_pay_per_post' );
	}

	/**
	 * Configurations that are legal but are known to produce exactly the
	 * support tickets this tool exists to answer.
	 *
	 * @return string[] Guest checkout is under the key 'guest_checkout'.
	 */
	public static function warnings( array $product_ids = [] ): array {
		$warnings = [];

		// A recurring product whose recurrence the plugin is not honouring. The
		// page then applies its own expiry window to what the store treats as a
		// subscription, and renewals billed through separate products do not
		// extend access at all — the shape of a real support case where a
		// customer paid twelve times and only the first two counted.
		if ( ! Woocommerce_Pay_Per_Post_Helper::can_use_woocommerce_subscriptions() && function_exists( 'wc_get_product' ) ) {
			foreach ( array_filter( array_map( 'absint', $product_ids ) ) as $id ) {
				$product = wc_get_product( $id );
				if ( ! $product || false === strpos( $product->get_type(), 'subscription' ) ) {
					continue;
				}

				$warnings[] = sprintf(
					/* translators: 1: product ID, 2: product type */
					__( 'Product #%1$d unlocks this page and is a recurring product (type "%2$s"), but the Subscriptions integration is switched off — so Pay For Post treats it as an ordinary one-time purchase and applies this page\'s expiry window instead of following the subscription. If renewals are billed through separate products, those products must ALSO be listed on this page, or the customer\'s renewal payments will not extend their access.', 'wc_pay_per_post' ),
					$id,
					$product->get_type()
				);
			}
		}

		// The July 2026 ticket in one check: a compatibility layer can declare
		// WC_Subscriptions_Product without WooCommerce Subscriptions being
		// installed, which silently switches every subscription product onto a
		// stricter path that also needs a subscription RECORD to exist.
		if ( Woocommerce_Pay_Per_Post_Helper::can_use_woocommerce_subscriptions()
		     && ! self::woocommerce_subscriptions_is_really_installed()
		) {
			$warnings[] = __( 'The WooCommerce Subscriptions integration is ACTIVE, but WooCommerce Subscriptions itself does not appear to be installed. Another plugin is probably providing a compatibility layer (Flexible Subscriptions by WP Desk does this). That makes Pay For Post treat those products as subscription products, which require an active subscription RECORD and not merely a completed order — a common cause of paying customers being refused. Untick "Use WooCommerce Subscriptions" under Settings > Integrations, or use the wc_pay_per_post_can_use_woocommerce_subscriptions filter, to turn this off.', 'wc_pay_per_post' );
		}

		// Legitimate for content kept for members granted by hand, but a
		// customer asking how to buy access is asking for something that
		// does not exist.
		$offer = Woocommerce_Pay_Per_Post_Helper::get_paywall_offer( $product_ids );
		if ( $offer['has_membership'] && empty( $offer['product_ids'] ) && empty( $offer['pmpro_levels'] ) ) {
			$warnings[] = __( 'This page cannot be bought. It is protected only by membership plans or levels that no product sells and that are closed to sign-ups, so its paywall shows a members-only notice instead of something to buy. A customer gets in only once they are given the membership.', 'wc_pay_per_post' );
		}

		if ( ! apply_filters( 'wcppp_is_active', true ) ) {
			$warnings[] = __( 'Protection is switched off site-wide by the wcppp_is_active filter. Nothing is protected while that is in place.', 'wc_pay_per_post' );
		}

		// Keyed so the screen can show it as an error: with guest checkout on
		// the plugin does not work for anyone who checks out as a guest.
		if ( 'yes' === get_option( 'woocommerce_enable_guest_checkout' ) ) {
			$warnings['guest_checkout'] = __( 'Guest checkout is ENABLED in WooCommerce. Pay For Post ties access to a user account, so any customer who checks out as a guest will have no account and therefore no access. This is the most common cause of "I paid but I cannot get in".', 'wc_pay_per_post' );
		}

		return $warnings;
	}

	/**
	 * Is the real WooCommerce Subscriptions plugin installed, as opposed to a
	 * compatibility layer that merely declares its classes?
	 *
	 * The integration keys off class_exists( 'WC_Subscriptions_Product' ), which
	 * a compatibility layer satisfies. Telling the two apart needs a marker that
	 * only the genuine plugin sets:
	 *
	 *   - WC_Subscriptions_Core_Plugin and WCS_INIT_TIMESTAMP come from WCS core
	 *     and are absent from Flexible Subscriptions' compat layer.
	 *   - The plugin path is checked too, but only as a fallback: it is wrong for
	 *     a renamed folder or an mu-plugin, and a warning that cries wolf on a
	 *     correctly configured site is worse than no warning.
	 */
	public static function woocommerce_subscriptions_is_really_installed(): bool {
		if ( class_exists( 'WC_Subscriptions_Core_Plugin' ) || defined( 'WCS_INIT_TIMESTAMP' ) ) {
			return true;
		}

		if ( ! function_exists( 'is_plugin_active' ) && is_admin() ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return function_exists( 'is_plugin_active' )
		       && is_plugin_active( 'woocommerce-subscriptions/woocommerce-subscriptions.php' );
	}

	/**
	 * Shorten a product name for the plain-text columns WITHOUT losing the end of
	 * it.
	 *
	 * Trimming the tail is the obvious approach and it is wrong here. Real stores
	 * name instalment products things like "Miosfera – płatność miesięczna (333 zł
	 * przez 12 miesięcy) - #6", where every product shares a long prefix and
	 * differs only in the final few characters. Cutting the end renders ten
	 * distinct products identically — which is precisely the detail the report
	 * exists to surface. So the middle goes instead.
	 */
	private static function truncate( string $text, int $length ): string {
		$text = trim( wp_strip_all_tags( $text ) );

		if ( mb_strlen( $text ) <= $length ) {
			return $text;
		}

		// Enough tail to keep a distinguishing suffix; the head takes the rest.
		$tail = min( 14, (int) floor( $length / 3 ) );
		$head = $length - $tail - 1;

		return mb_substr( $text, 0, $head ) . '…' . mb_substr( $text, -$tail );
	}

	/**
	 * Plain-text version for pasting into a support reply.
	 *
	 * This is the export a site owner sends to the developer, so it has to be
	 * complete enough to answer without a follow-up round trip. It deliberately
	 * carries NO personal data — no name, address, phone or billing details —
	 * only the email used to look the customer up, order ids, dates, statuses,
	 * totals, product ids and the page's own settings.
	 */
	public static function as_text( array $result ): string {
		if ( isset( $result['error'] ) ) {
			return $result['error'];
		}

		$lines   = [];
		$lines[] = 'Pay For Post — access diagnostic';
		$lines[] = sprintf( 'Customer : %s (user %d)', $result['user']->user_email, $result['user']->ID );
		$lines[] = sprintf( 'Page     : %s (post %d, %s)', get_permalink( $result['post'] ), $result['post']->ID, $result['post']->post_type );
		$lines[] = sprintf( 'Verdict  : %s', $result['verdict'] ? 'HAS ACCESS' : 'NO ACCESS' );
		$lines[] = '';
		$lines[] = 'Reason: ' . wp_strip_all_tags( $result['reason'] );
		$lines[] = '';

		foreach ( $result['evidence'] as $label => $value ) {
			$lines[] = sprintf( '%-26s %s', $label . ':', str_replace( "\n", "\n" . str_repeat( ' ', 27 ), (string) $value ) );
		}

		// ---- Page configuration -------------------------------------------
		if ( ! empty( $result['page_config'] ) ) {
			$lines[] = '';
			$lines[] = 'PAGE CONFIGURATION (what this page is actually set to)';
			foreach ( $result['page_config'] as $label => $value ) {
				$lines[] = sprintf( '  %-24s %s', $label . ':', $value );
			}
		}

		// ---- Protecting products ------------------------------------------
		if ( ! empty( $result['products'] ) ) {
			$lines[] = '';
			$lines[] = 'PRODUCTS THAT UNLOCK THIS PAGE';
			foreach ( $result['products'] as $p ) {
				$lines[] = sprintf(
					'  #%-8s %-46s type:%-10s subscription:%-4s membership:%s',
					$p['id'],
					self::truncate( $p['name'], 46 ),
					$p['type'],
					$p['subscription'],
					$p['membership']
				);
			}
		}

		// ---- Purchase history ----------------------------------------------
		$history = $result['history'] ?? [];
		if ( ! empty( $history['orders'] ) ) {
			$lines[] = '';
			$lines[] = sprintf(
				'PURCHASE HISTORY — %d order(s) found for this customer, nothing filtered out.',
				(int) $history['total_found']
			);
			$lines[] = sprintf(
				'  %d of them count toward access. Found by customer account AND by billing email.',
				(int) $history['qualifying_count']
			);
			if ( ! empty( $history['truncated'] ) ) {
				$lines[] = '  NOTE: the order list was capped, so older orders are not shown.';
			}
			$lines[] = '  ( * marks a product that unlocks this page )';
			$lines[] = '';

			foreach ( $history['orders'] as $row ) {
				$lines[] = sprintf(
					'  #%-8s %-17s paid %-17s %-11s %-9s %8s %s',
					$row['id'],
					$row['date'],
					$row['paid'],
					$row['status'],
					$row['attachment'],
					$row['total'] . ' ' . $row['currency'],
					$row['qualifies'] ? 'COUNTS' : 'does not count'
				);
				foreach ( $row['items'] as $item ) {
					$lines[] = sprintf(
						'        %s #%-8s %s',
						$item['required'] ? '*' : ' ',
						$item['id'],
						self::truncate( $item['name'], 62 )
					);
				}
				if ( ! $row['qualifies'] ) {
					$lines[] = '        -> ' . $row['why_not'];
				}
				if ( '' !== $row['note'] ) {
					$lines[] = '        -> ' . $row['note'];
				}
			}
		}

		// ---- The list that usually holds the answer -------------------------
		if ( ! empty( $history['other_products'] ) ) {
			$lines[] = '';
			$lines[] = 'PRODUCTS THIS CUSTOMER BOUGHT THAT DO **NOT** UNLOCK THIS PAGE';
			$lines[] = '  (if the customer believes they paid for this page, the answer is usually here —';
			$lines[]  = '   these products are not listed on the page, so the plugin never sees them)';
			foreach ( $history['other_products'] as $p ) {
				$lines[] = sprintf( '  #%-8s x%-3d %s', $p['id'], $p['count'], self::truncate( $p['name'], 62 ) );
			}
		}

		$lines[] = '';
		foreach ( $result['environment'] as $label => $value ) {
			$lines[] = sprintf( '%-26s %s', $label . ':', $value );
		}

		if ( ! empty( $result['versions'] ) ) {
			$lines[] = '';
			$versions = [];
			foreach ( $result['versions'] as $label => $value ) {
				$versions[] = $label . ' ' . $value;
			}
			$lines[] = 'Versions: ' . implode( ' | ', $versions );
		}

		if ( ! empty( $result['warnings'] ) ) {
			$lines[] = '';
			$lines[] = 'Warnings:';
			foreach ( $result['warnings'] as $warning ) {
				$lines[] = ' - ' . wp_strip_all_tags( $warning );
			}
		}

		return implode( "\n", $lines );
	}
}
