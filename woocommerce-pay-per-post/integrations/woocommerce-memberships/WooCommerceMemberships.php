<?php

namespace PRAMADILLO\INTEGRATIONS;

use Woocommerce_Pay_Per_Post_Helper;

class WooCommerceMemberships {

	public function get_membership_levels() {
		if ( ! function_exists( 'wc_memberships_get_membership_plans' ) ) {
			return [];
		}
		/** @noinspection PhpUndefinedFunctionInspection */
		return wc_memberships_get_membership_plans();

	}

	/** @noinspection PhpUnused */
	public function generate_membership_levels_dropdown( $selected = [] ): string {
		if ( ! function_exists( 'wc_memberships_get_membership_plans' ) ) {
			return '';
		}

		$selected  = empty( $selected ) ? [] : $selected;
		$drop_down = '';
		/** @noinspection PhpUndefinedFunctionInspection */
		$membership_levels = wc_memberships_get_membership_plans();

		if ( empty( $membership_levels ) || ! is_array( $membership_levels ) ) {
			return '';
		}

		$drop_down         .= '<optgroup label="Membership Levels">';

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
	 */
	public function is_member( $post_id = null, $product_ids = null ): bool {
		if ( ! function_exists( 'wc_memberships_is_user_active_member' ) ) {
			return false;
		}

		if ( is_null( $post_id ) ) {
			$post_id = get_the_ID();
		}
		if(is_null($product_ids)){
			$product_ids  = (array) get_post_meta( $post_id, WC_PPP_SLUG . '_product_ids', true );
		}

		$current_user = wp_get_current_user();

		foreach ( $product_ids as $id ) {
			Woocommerce_Pay_Per_Post_Helper::logger( 'Looking to see if user is an active member of member level - ' . trim( $id ) );

			/** @noinspection PhpUndefinedFunctionInspection */
			$membership = wc_memberships_is_user_active_member( $current_user->ID, $id, false );

			if ( $membership ) {
				Woocommerce_Pay_Per_Post_Helper::logger( 'IS A MEMBER OF - ' . trim( $id ) );
				add_filter( 'wc_pay_per_post_enable_javascript_expiration_refresh', function () {
					return false;
				} );

				return true;
			}
			Woocommerce_Pay_Per_Post_Helper::logger( 'IS NOT A MEMBER OF - ' . trim( $id ) );

		}

		return false;
	}


	/**
	 * Whether an ID stored in a post's protection is a membership plan.
	 *
	 * @param int|string $id
	 *
	 * @return bool
	 */
	public function is_plan( $id ): bool {
		$membership_levels = $this->get_membership_levels();

		return is_array( $membership_levels ) && array_key_exists( (int) $id, $membership_levels );
	}

	/**
	 * The products whose purchase grants a plan, so the paywall can sell the
	 * plan. Memberships grants a plan from any order containing one of these
	 * products, whatever the plan's access method says. Products that no
	 * longer exist or are not published are left out, because the paywall
	 * could not sell them.
	 *
	 * @param int|string $plan_id
	 *
	 * @return int[] Empty for a plan granted only manually or on sign-up.
	 */
	public function get_plan_product_ids( $plan_id ): array {
		$membership_levels = $this->get_membership_levels();

		if ( ! is_array( $membership_levels ) || ! isset( $membership_levels[ (int) $plan_id ] ) ) {
			return [];
		}

		$product_ids = [];

		foreach ( $membership_levels[ (int) $plan_id ]->get_product_ids() as $product_id ) {
			$product = wc_get_product( $product_id );

			if ( $product && 'publish' === $product->get_status() ) {
				$product_ids[] = (int) $product_id;
			}
		}

		return $product_ids;
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
			if ( ( $product_id ) ) {
				if ( array_key_exists( (int) $product_id, $membership_levels ) ) {
					return true;
				}
			}
		}

		return false;

	}

}