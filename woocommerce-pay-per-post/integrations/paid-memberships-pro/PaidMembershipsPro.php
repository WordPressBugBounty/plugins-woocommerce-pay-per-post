<?php

	namespace PRAMADILLO\INTEGRATIONS;

	use Woocommerce_Pay_Per_Post_Helper;

	class PaidMembershipsPro {

		/** @noinspection PhpUndefinedFunctionInspection */
		public function get_membership_levels() {
			if ( function_exists( 'pmpro_getAllLevels' ) ) {
				return pmpro_getAllLevels();
			}

			return false;
		}

		/** @noinspection PhpUnused */
		public function generate_membership_levels_dropdown( $selected = array() ): string {
			$selected          = empty( $selected ) ? array() : $selected;
			$drop_down         = '';
			$membership_levels = $this->get_membership_levels();

			if ( empty( $membership_levels ) || ! is_array( $membership_levels ) ) {
				return '';
			}

			$drop_down         .= '<optgroup label="PaidMembershipPro Membership Levels">';

			foreach ( $membership_levels as $level ) {

				$drop_down .= '<option value="' . $level->id . '"';

				if ( in_array( (string) $level->id, $selected, true ) ) {
					$drop_down .= ' selected="selected"';
				}

				$drop_down .= '>' . $level->name . ' - [#' . $level->id . ']</option>';
			}
			$drop_down .= '</optgroup>';

			return $drop_down;

		}

		/**
		 * @param null $post_id
		 *
		 * @return bool
		 * @noinspection PhpUndefinedFunctionInspection
		 */
		public function is_member( $post_id = null, $product_ids = null ): bool {
			if ( is_null( $post_id ) ) {
				$post_id = get_the_ID();
			}

			if(is_null( $product_ids ) ) {
				$product_ids  = (array) get_post_meta( $post_id, WC_PPP_SLUG . '_product_ids', true );
			}

			$current_user = wp_get_current_user();

			foreach ( $product_ids as $id ) {
				Woocommerce_Pay_Per_Post_Helper::logger( 'Looking to see if user is an active member of Paid Membership Pro member level - ' . trim( $id ) );
				if ( function_exists( 'pmpro_hasMembershipLevel' ) ) {
					$membership = pmpro_hasMembershipLevel( $id, $current_user->ID );
				} else {
					$membership = false;
				}

				if ( $membership ) {
					Woocommerce_Pay_Per_Post_Helper::logger( 'IS A PAID MEMBERSHIPS PRO MEMBER OF - ' . trim( $id ) );

					return true;
				}
				Woocommerce_Pay_Per_Post_Helper::logger( 'IS NOT A MEMBER OF - ' . trim( $id ) );
			}

			return false;
		}


		/**
		 * The level with this ID, or null. Level IDs are Paid Memberships Pro's
		 * own and can equal a WooCommerce product's ID. Unlike
		 * get_membership_levels(), this finds levels that do not allow new
		 * sign-ups too.
		 *
		 * @param int|string $id
		 *
		 * @return object|null
		 * @noinspection PhpUndefinedFunctionInspection
		 */
		public function get_level( $id ) {
			if ( ! function_exists( 'pmpro_getLevel' ) || (int) $id <= 0 ) {
				return null;
			}

			$level = pmpro_getLevel( (int) $id );

			return empty( $level->id ) ? null : $level;
		}

		/**
		 * Whether a visitor can buy this level at checkout.
		 *
		 * @param object $level From get_level().
		 *
		 * @return bool
		 */
		public function allows_signups( $level ): bool {
			return ! empty( $level->allow_signups );
		}

		/**
		 * The Paid Memberships Pro checkout page for a level. 3.0 renamed the
		 * query argument to pmpro_level; earlier versions read only level.
		 *
		 * @param int|string  $level_id
		 * @param string|null $version Paid Memberships Pro's version; the
		 *                             installed one when null.
		 *
		 * @return string
		 * @noinspection PhpUndefinedFunctionInspection
		 */
		public function get_checkout_url( $level_id, ?string $version = null ): string {
			if ( ! function_exists( 'pmpro_url' ) ) {
				return '';
			}

			if ( null === $version ) {
				$version = defined( 'PMPRO_VERSION' ) ? (string) PMPRO_VERSION : '0';
			}

			$argument = version_compare( $version, '3.0', '>=' ) ? 'pmpro_level' : 'level';

			return (string) pmpro_url( 'checkout', '?' . $argument . '=' . (int) $level_id );
		}

		public function post_contains_membership_products( $post_id, $product_ids = null ): bool {
			if(is_null($product_ids)){
				$product_ids = (array) get_post_meta( $post_id, WC_PPP_SLUG . '_product_ids', true );
			}

			$membership_levels = $this->get_membership_levels();

			if ( empty( $membership_levels ) || ! is_array( $membership_levels ) ) {
				return false;
			}

			foreach ( $product_ids as $product_id ) {

				foreach ( $membership_levels as $membership_level ) {

					if ( $product_id == $membership_level->id ) {
						return true;
					}
				}
			}

			return false;

		}

	}