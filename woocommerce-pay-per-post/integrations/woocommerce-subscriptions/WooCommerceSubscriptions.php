<?php

	namespace PRAMADILLO\INTEGRATIONS;

	use WC_Order;
	use WC_Order_Item_Product;
	use WC_Subscriptions_Product;
	use WP_User;
	use Woocommerce_Pay_Per_Post_Helper;

	class WooCommerceSubscriptions {

		/** @noinspection PhpUnused */
		public function generate_subscriptions_dropdown( $subscription_products, $selected = [] ): string {
			$drop_down = '<optgroup label="Subscription Products">';
			foreach ( $subscription_products as $product ) {
				$drop_down .= '<option value="' . $product['ID'] . '"';

				if ( in_array( (string) $product['ID'], (array) $selected, true ) ) {
					$drop_down .= ' selected="selected"';
				}

				$drop_down .= '>' . $product['post_title'] . ' - [#' . $product['ID'] . ']</option>';
			}
			$drop_down .= '</optgroup>';

			return $drop_down;

		}

		/**
		 * @param $product_id
		 *
		 * @return bool
		 */
		public function is_subscription_product( $product_id ): bool {
			if ( ! class_exists( 'WC_Subscriptions_Product' ) ) {
				return false;
			}

			// A product sold on plans is never a subscription type, though
			// is_subscription() can claim it is (see is_plan_product()). Unfiltered,
			// so a plan product the filter opts out is ordinary; filtered, so a
			// product kept on the plan rule after its plans were removed stays out.
			if ( self::has_plans( $product_id ) || $this->is_plan_product( $product_id ) ) {
				return false;
			}

			if ( WC_Subscriptions_Product::is_subscription( $product_id ) ) {
				return true;
			}

			return false;
		}


		public function post_contains_subscription_products( $post_id, $product_ids = null ): bool {
			if(is_null( $product_ids ) ) {
				$product_ids = (array) get_post_meta( $post_id, WC_PPP_SLUG . '_product_ids', true );
			}

			foreach ( $product_ids as $product_id ) {
				if ( $this->is_subscription_product( $product_id ) ) {
					return true;
				}
			}

			return false;

		}

		/** @noinspection PhpUnused */
		public function filter_subscription_products( $products ): array {
			if ( ! class_exists( 'WC_Subscriptions_Product' ) ) {
				return $products;
			}

			$return = [];
			foreach ( $products as $product ) {
				if ( ! $this->is_subscription_product( $product['ID'] ) ) {
					$return[] = $product;
				}
			}

			return $return;
		}

		/**
		 * @param null $post_id
		 *
		 * @return bool
		 */
		/**
		 * Per-request cache of subscription lookups, keyed "userId:productId".
		 *
		 * can_user_view_content() runs every registered check on every
		 * evaluation, so is_subscriber() is consulted even for posts that
		 * contain no subscription products at all. Each miss calls
		 * wcs_user_has_subscription(), and WooCommerce Subscriptions answers it
		 * by querying the orders table — plus whatever else hooks in; on a store
		 * with Subscriptions Gifting, WCSG_Recipient_Management adds a second
		 * query per call.
		 *
		 * Measured on a product page rendering 60 unlocked posts, all protected
		 * by the same product: 64 identical subscription queries per render, and
		 * they dominated the page. The (user, product) pair repeats constantly,
		 * so caching it collapses them to one.
		 *
		 * Request-scoped only. flush_subscription_cache() clears it when a
		 * subscription changes state mid-request.
		 *
		 * @var array<string, bool>
		 */
		private static array $subscription_cache = [];

		/**
		 * Per-request: product ID => whether the product is sold on plans. Asked
		 * several times per access check, for every product, plans or not.
		 *
		 * @var array<int, bool>
		 */
		private static array $plan_product_cache = [];

		/**
		 * Per-request: user ID => that user's paid orders.
		 *
		 * @var array<int, WC_Order[]>
		 */
		private static array $paid_orders_cache = [];

		/**
		 * Drop the per-request caches.
		 *
		 * @return void
		 */
		public static function flush_subscription_cache() {
			self::$subscription_cache = [];
			self::$plan_product_cache = [];
			self::$paid_orders_cache  = [];
		}

		public function is_subscriber( $post_id = null, $product_ids = null ): bool {
			if ( ! function_exists( 'wcs_user_has_subscription' ) ) {
				return false;
			}

			Woocommerce_Pay_Per_Post_Helper::logger( 'Looking to see if user has any active subscriptions' );

			if ( is_null( $post_id ) ) {
				$post_id = get_the_ID();
			}

			if(is_null($product_ids)){
				$product_ids  = get_post_meta( $post_id, WC_PPP_SLUG . '_product_ids', true );
			}
			$current_user = wp_get_current_user();

			if ( ! empty( $product_ids ) ):
				foreach ( (array) $product_ids as $id ) {
					if ( $this->has_active_subscription_to( $current_user->ID, $id ) ) {
						Woocommerce_Pay_Per_Post_Helper::logger( 'IS AN ACTIVE SUBSCRIBER OF  - ' . trim( $id ) );

						return true;
					}
					Woocommerce_Pay_Per_Post_Helper::logger( 'Is NOT a subscriber of  - ' . trim( $id ) );

				}
			endif;

			Woocommerce_Pay_Per_Post_Helper::logger( 'Has no active subscriptions' );

			return false;
		}

		/**
		 * Whether the user holds an active or pending-cancel subscription that
		 * contains the product, whether bought on a plan or as a subscription
		 * product. Shares is_subscriber()'s per-request cache.
		 *
		 * @param int        $user_id
		 * @param int|string $product_id Product or variation ID.
		 */
		public function has_active_subscription_to( $user_id, $product_id ): bool {
			if ( ! function_exists( 'wcs_user_has_subscription' ) || ! $user_id ) {
				return false;
			}

			$cache_key = (int) $user_id . ':' . trim( (string) $product_id );

			if ( ! isset( self::$subscription_cache[ $cache_key ] ) ) {
				self::$subscription_cache[ $cache_key ] = wcs_user_has_subscription( (int) $user_id, trim( (string) $product_id ), [ 'active', 'pending-cancel' ] );
			}

			return (bool) self::$subscription_cache[ $cache_key ];
		}

		/**
		 * Whether the product is sold on WooCommerce Subscriptions plans (9.0+):
		 * an ordinary product that can be bought one-time, on a plan, or both.
		 *
		 * Asks the plans module whether the product has plans, its own or
		 * storewide, not WC_Subscriptions_Product::is_subscription(). The plans
		 * module marks a product object loaded from a plan order line as a
		 * subscription, keyed by spl_object_hash() and never cleared, so a later
		 * wc_get_product() that reuses the handle can report a subscription.
		 *
		 * Filterable: false treats the product as ordinary (any paid order
		 * unlocks, as before 3.3.0); true keeps the plan rule for a product whose
		 * plans were removed, which is otherwise ordinary again.
		 *
		 * @param int|string $product_id Product or variation ID.
		 */
		public function is_plan_product( $product_id ): bool {
			return (bool) apply_filters( 'wc_pay_per_post_is_subscription_plan_product', self::has_plans( $product_id ), (int) $product_id );
		}

		/**
		 * Unfiltered: whether the plans module offers this product on any plan.
		 *
		 * @param int|string $product_id
		 */
		private static function has_plans( $product_id ): bool {
			$product_id = absint( $product_id );

			if ( isset( self::$plan_product_cache[ $product_id ] ) ) {
				return self::$plan_product_cache[ $product_id ];
			}

			$has_plans = false;

			// WCS_ATT() exists only once the plans module has booted; its classes
			// autoload whenever WooCommerce Subscriptions is active.
			if ( $product_id && function_exists( 'WCS_ATT' ) && class_exists( 'WCS_ATT_Product_Schemes' ) && function_exists( 'wc_get_product' ) ) {
				$product = wc_get_product( $product_id );

				// Subscription-type products are never sold on plans.
				if ( $product && ! $product->is_type( [ 'subscription', 'subscription_variation', 'variable-subscription' ] ) ) {
					$has_plans = \WCS_ATT_Product_Schemes::has_subscription_schemes( $product );
				}
			}

			return self::$plan_product_cache[ $product_id ] = $has_plans;
		}

		/**
		 * Access through a product sold on plans: an active or pending-cancel
		 * subscription containing it, or a paid one-time purchase. A purchase
		 * made on a plan (the parent order and every renewal) counts only while
		 * that subscription is active.
		 *
		 * @param WP_User    $user
		 * @param int|string $product_id
		 */
		public function has_plan_product_access( WP_User $user, $product_id ): bool {
			if ( ! $user->exists() ) {
				return false;
			}

			if ( $this->has_active_subscription_to( $user->ID, $product_id ) ) {
				Woocommerce_Pay_Per_Post_Helper::logger( 'Subscription plan product #' . trim( (string) $product_id ) . ' - user ' . $user->ID . ' holds an active subscription to it' );

				return true;
			}

			if ( $this->has_one_time_purchase( $user, $product_id ) ) {
				Woocommerce_Pay_Per_Post_Helper::logger( 'Subscription plan product #' . trim( (string) $product_id ) . ' - user ' . $user->ID . ' bought it one-time' );

				return true;
			}

			Woocommerce_Pay_Per_Post_Helper::logger( 'Subscription plan product #' . trim( (string) $product_id ) . ' - user ' . $user->ID . ' has no active subscription to it and no one-time purchase of it' );

			return false;
		}

		/**
		 * Whether the user has a paid one-time purchase of the product.
		 *
		 * @param WP_User    $user
		 * @param int|string $product_id
		 */
		public function has_one_time_purchase( WP_User $user, $product_id ): bool {
			foreach ( $this->get_purchase_lines( $user, $product_id ) as $line ) {
				if ( ! $line['on_plan'] ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Whether the user ever paid for the product on a plan. Used to explain a
		 * refusal: they DID pay, but the subscription has lapsed.
		 *
		 * @param WP_User    $user
		 * @param int|string $product_id
		 */
		public function has_plan_purchase( WP_User $user, $product_id ): bool {
			foreach ( $this->get_purchase_lines( $user, $product_id ) as $line ) {
				if ( $line['on_plan'] ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Every paid order line for the product, and whether it was bought on a
		 * plan. Orders and statuses are matched as for an ordinary product's
		 * purchase: the account or its billing email, processing or completed.
		 *
		 * @param WP_User    $user
		 * @param int|string $product_id Product or variation ID.
		 *
		 * @return array<int, array{order_id: int, on_plan: bool}>
		 */
		public function get_purchase_lines( WP_User $user, $product_id ): array {
			$product_id = absint( $product_id );
			$lines      = [];

			// Most visitors never bought the product. The ordinary purchase check
			// says so in one query, without loading every order they have.
			if ( ! $product_id || ! Woocommerce_Pay_Per_Post_Helper::customer_has_purchased_product( $user->user_email, $user->ID, $product_id ) ) {
				return $lines;
			}

			foreach ( $this->get_paid_orders( $user ) as $order ) {
				foreach ( $order->get_items() as $item ) {
					if ( ! $item instanceof WC_Order_Item_Product ) {
						continue;
					}

					// As WC_Subscription::has_product(): a parent ID matches any of
					// its variations, a variation ID only itself.
					if ( (int) $item->get_product_id() !== $product_id && (int) $item->get_variation_id() !== $product_id ) {
						continue;
					}

					$lines[] = [
						'order_id' => $order->get_id(),
						'on_plan'  => $this->is_subscription_purchase( $order, $item, $product_id ),
					];
				}
			}

			return $lines;
		}

		/**
		 * Whether an order line bought the product on a plan rather than one-time.
		 *
		 * The plan recorded on the line decides when there is one: it judges each
		 * line separately, and outlives the store trashing or deleting the
		 * subscription. A line that records nothing, such as one under a
		 * subscription set up by hand, falls back to whether the order belongs to
		 * a subscription containing the product.
		 *
		 * @param WC_Order              $order
		 * @param WC_Order_Item_Product $item
		 * @param int|string            $product_id Product or variation ID.
		 */
		public function is_subscription_purchase( WC_Order $order, WC_Order_Item_Product $item, $product_id ): bool {
			if ( is_callable( [ 'WCS_ATT_Order', 'get_subscription_scheme' ] ) ) {
				// The plan's key, false for one-time, null when the line records neither.
				$plan = \WCS_ATT_Order::get_subscription_scheme( $item );

				if ( null !== $plan ) {
					return false !== $plan;
				}
			}

			return $this->order_belongs_to_subscription_for( $order, $product_id );
		}

		/**
		 * Whether the order created, renewed, resubscribed or switched a
		 * subscription that contains the product.
		 *
		 * @param WC_Order   $order
		 * @param int|string $product_id Product or variation ID.
		 */
		private function order_belongs_to_subscription_for( WC_Order $order, $product_id ): bool {
			if ( ! function_exists( 'wcs_get_subscriptions_for_order' ) ) {
				return false;
			}

			foreach ( wcs_get_subscriptions_for_order( $order, [ 'order_type' => 'any' ] ) as $subscription ) {
				if ( $subscription->has_product( absint( $product_id ) ) ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * The user's paid orders, cached for the request.
		 *
		 * @param WP_User $user
		 *
		 * @return WC_Order[]
		 */
		private function get_paid_orders( WP_User $user ): array {
			if ( isset( self::$paid_orders_cache[ $user->ID ] ) ) {
				return self::$paid_orders_cache[ $user->ID ];
			}

			$orders = [];

			if ( function_exists( 'wc_get_orders' ) && $user->exists() ) {
				$orders = wc_get_orders( [
					'customer' => array_values( array_filter( [ $user->ID, $user->user_email ] ) ),
					'status'   => wc_get_is_paid_statuses(),
					'type'     => 'shop_order',
					'limit'    => -1,
				] );
			}

			return self::$paid_orders_cache[ $user->ID ] = array_filter( (array) $orders, static function ( $order ) {
				return $order instanceof WC_Order;
			} );
		}

	}