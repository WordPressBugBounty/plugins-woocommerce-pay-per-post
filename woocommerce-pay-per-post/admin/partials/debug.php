<?php  /** @noinspection PhpUndefinedVariableInspection */ ?>
<div class="wrap about-wrap debug wc-ppp-debug full-width-layout">
    <h1><?php use Pramadillo\PayForPost\Carbon\Carbon;

	    _e( 'Pay For Post with WooCommerce Debug' ); ?></h1>
    <div class="pramadillo-badge"><img alt="Logo" src="<?php echo plugin_dir_url( __DIR__ ) . 'img/icon.png'; ?>"/></div>
    <p class="about-text"><?php _e( 'This page can show you every detail about the configuration of your WordPress website. This information is incredibly helpful when debugging any issues related to the Pay For Post with WooCommerce plugin.' ); ?></p>

    <div class="wc-ppp-settings-wrap">

        <div>

            <div id="post-body" class="metabox-holder columns-2">
                <div id="post-body-content">

                    <div class="postbox">
                        <h2 class="hndle"><?php esc_attr_e( 'Common Issues', 'wc_pay_per_post' ); ?></h2>

                        <div class="inside">
                            <h4><?php _e( 'Enabling Guest Checkout' ); ?></h4>
                            <p><?php _e( 'You need to make sure that you do <strong>NOT</strong> have guest checkout enabled on your WooCommerce store.  User accounts are necessary to keep track of who purchased what and when. <br><br>If you are not sure, check your WooCommerce settings <a href="/wp-admin/admin.php?page=wc-settings&tab=account">here</a>' ); ?></p>
                            <h4><?php _e( 'Content Still Displaying to Public' ); ?></h4>
                            <p><?php _e( 'Out of the box this plugin works with the standard POSTS and PAGES using the_content filter from Wordpress.  Your theme may not be utilizing the_content filter or may be a different custom post type.  No worries though, you can still utilize this plugin, you just need to look at the <a href="https://pramadillo.com/plugins/woocommerce-pay-per-post/#template-functions" target="_blank">Template Functions</a>.' ); ?></p>

                        </div>

                    </div>

                    <br><br>

                    <div class="postbox wc-ppp-access-diagnostic">
                        <h2 class="hndle"><?php esc_html_e( 'Access Diagnostic', 'wc_pay_per_post' ); ?></h2>

                        <div class="inside">
                            <p><?php esc_html_e( 'Answers one question: can this customer see this page, and why? The check runs the real protection engine as that customer, exactly as a visit to the page would — it does not re-implement the rules, and it does not consume one of their page views.', 'wc_pay_per_post' ); ?></p>

                            <form method="post" action="">
			                    <?php wp_nonce_field( 'wc_ppp_access_diagnostic', 'wc_ppp_access_diagnostic_nonce' ); ?>
                                <table class="form-table" role="presentation">
                                    <tr>
                                        <th scope="row">
                                            <label for="wc_ppp_diagnostic_email"><?php esc_html_e( 'Customer email', 'wc_pay_per_post' ); ?></label>
                                        </th>
                                        <td>
                                            <input type="email" class="regular-text" id="wc_ppp_diagnostic_email"
                                                   name="wc_ppp_diagnostic_email" required
                                                   value="<?php echo esc_attr( $diagnostic_email ); ?>">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label for="wc_ppp_diagnostic_url"><?php esc_html_e( 'Page URL', 'wc_pay_per_post' ); ?></label>
                                        </th>
                                        <td>
                                            <input type="text" class="large-text code" id="wc_ppp_diagnostic_url"
                                                   name="wc_ppp_diagnostic_url" required
                                                   value="<?php echo esc_attr( $diagnostic_url ); ?>">
                                            <p class="description"><?php esc_html_e( 'The full URL the customer is trying to open. A numeric post ID also works if the URL will not resolve.', 'wc_pay_per_post' ); ?></p>
                                        </td>
                                    </tr>
                                </table>
                                <p class="submit">
                                    <input type="submit" class="button button-primary"
                                           value="<?php esc_attr_e( 'Check Access', 'wc_pay_per_post' ); ?>">
                                </p>
                            </form>

			                <?php if ( is_array( $diagnostic_result ) ) : ?>
				                <?php if ( isset( $diagnostic_result['error'] ) ) : ?>
                                    <div class="notice notice-error inline">
                                        <p><?php echo esc_html( $diagnostic_result['error'] ); ?></p>
                                    </div>
				                <?php else : ?>
                                    <hr>
                                    <h3<?php echo $diagnostic_result['verdict'] ? ' class="success"' : ' class="error-message"'; ?>>
					                    <?php
					                    echo $diagnostic_result['verdict']
						                    ? esc_html__( 'HAS ACCESS', 'wc_pay_per_post' )
						                    : esc_html__( 'NO ACCESS', 'wc_pay_per_post' );
					                    ?>
                                        —
					                    <?php echo esc_html( $diagnostic_result['user']->user_email ); ?>
                                        →
					                    <?php echo esc_html( get_the_title( $diagnostic_result['post'] ) ); ?>
                                        <?php /* translators: %d: post ID */ ?>
                                        <small>(<?php echo esc_html( sprintf( __( 'post %d', 'wc_pay_per_post' ), $diagnostic_result['post']->ID ) ); ?>)</small>
                                    </h3>

                                    <p><strong><?php echo esc_html( $diagnostic_result['reason'] ); ?></strong></p>

					                <?php if ( ! empty( $diagnostic_result['warnings'] ) ) : ?>
						                <?php foreach ( $diagnostic_result['warnings'] as $warning ) : ?>
                                            <div class="notice notice-warning inline">
                                                <p><?php echo esc_html( $warning ); ?></p>
                                            </div>
						                <?php endforeach; ?>
					                <?php endif; ?>

                                    <h4><?php esc_html_e( 'Evidence', 'wc_pay_per_post' ); ?></h4>
                                    <table class="wp-list-table widefat fixed striped table-view-list">
                                        <tbody>
										<?php foreach ( $diagnostic_result['evidence'] as $label => $value ) : ?>
                                            <tr>
                                                <td style="width:30%;"><strong><?php echo esc_html( $label ); ?></strong></td>
                                                <td><?php echo nl2br( esc_html( (string) $value ) ); ?></td>
                                            </tr>
										<?php endforeach; ?>
                                        </tbody>
                                    </table>

			                        <?php if ( ! empty( $diagnostic_result['page_config'] ) ) : ?>
                                        <h4><?php esc_html_e( 'Page configuration', 'wc_pay_per_post' ); ?></h4>
                                        <table class="wp-list-table widefat fixed striped table-view-list">
                                            <tbody>
										    <?php foreach ( $diagnostic_result['page_config'] as $label => $value ) : ?>
                                                <tr>
                                                    <td style="width:30%;"><strong><?php echo esc_html( $label ); ?></strong></td>
                                                    <td><?php echo esc_html( (string) $value ); ?></td>
                                                </tr>
										    <?php endforeach; ?>
                                            </tbody>
                                        </table>
			                        <?php endif; ?>

			                        <?php if ( ! empty( $diagnostic_result['products'] ) ) : ?>
                                        <h4><?php esc_html_e( 'Products that unlock this page', 'wc_pay_per_post' ); ?></h4>
                                        <table class="wp-list-table widefat fixed striped table-view-list">
                                            <thead>
                                            <tr>
                                                <th><?php esc_html_e( 'Product', 'wc_pay_per_post' ); ?></th>
                                                <th><?php esc_html_e( 'Name', 'wc_pay_per_post' ); ?></th>
                                                <th><?php esc_html_e( 'Type', 'wc_pay_per_post' ); ?></th>
                                                <th><?php esc_html_e( 'Subscription?', 'wc_pay_per_post' ); ?></th>
                                                <th><?php esc_html_e( 'Membership?', 'wc_pay_per_post' ); ?></th>
                                            </tr>
                                            </thead>
                                            <tbody>
										    <?php foreach ( $diagnostic_result['products'] as $p ) : ?>
                                                <tr>
                                                    <td><a href="<?php echo esc_url( admin_url( 'post.php?post=' . (int) $p['id'] . '&action=edit' ) ); ?>">#<?php echo esc_html( $p['id'] ); ?></a></td>
                                                    <td><?php echo esc_html( $p['name'] ); ?></td>
                                                    <td><?php echo esc_html( $p['type'] ); ?></td>
                                                    <td><?php echo esc_html( $p['subscription'] ); ?></td>
                                                    <td><?php echo esc_html( $p['membership'] ); ?></td>
                                                </tr>
										    <?php endforeach; ?>
                                            </tbody>
                                        </table>
			                        <?php endif; ?>

			                        <?php if ( ! empty( $diagnostic_result['history']['orders'] ) ) : ?>
                                        <h4>
					                        <?php
					                        printf(
						                        /* translators: 1: total orders, 2: how many count */
						                        esc_html__( 'Purchase history — %1$d order(s), %2$d counting toward access', 'wc_pay_per_post' ),
						                        (int) $diagnostic_result['history']['total_found'],
						                        (int) $diagnostic_result['history']['qualifying_count']
					                        );
					                        ?>
                                        </h4>
                                        <p class="description"><?php esc_html_e( 'Nothing is filtered out. Found by customer account AND by billing email, so guest orders and orders on a second account appear too. A star marks a product that unlocks this page.', 'wc_pay_per_post' ); ?></p>
					                    <?php if ( ! empty( $diagnostic_result['history']['truncated'] ) ) : ?>
                                            <div class="notice notice-warning inline"><p><?php esc_html_e( 'The order list was capped, so older orders are not shown.', 'wc_pay_per_post' ); ?></p></div>
					                    <?php endif; ?>
                                        <table class="wp-list-table widefat fixed striped table-view-list">
                                            <thead>
                                            <tr>
                                                <th><?php esc_html_e( 'Order', 'wc_pay_per_post' ); ?></th>
                                                <th><?php esc_html_e( 'Placed', 'wc_pay_per_post' ); ?></th>
                                                <th><?php esc_html_e( 'Paid', 'wc_pay_per_post' ); ?></th>
                                                <th><?php esc_html_e( 'Status', 'wc_pay_per_post' ); ?></th>
                                                <th><?php esc_html_e( 'Attached to', 'wc_pay_per_post' ); ?></th>
                                                <th><?php esc_html_e( 'Items', 'wc_pay_per_post' ); ?></th>
                                                <th><?php esc_html_e( 'Counts?', 'wc_pay_per_post' ); ?></th>
                                            </tr>
                                            </thead>
                                            <tbody>
										    <?php foreach ( $diagnostic_result['history']['orders'] as $row ) : ?>
                                                <tr>
                                                    <td><a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-orders&action=edit&id=' . (int) $row['id'] ) ); ?>">#<?php echo esc_html( $row['id'] ); ?></a></td>
                                                    <td><?php echo esc_html( $row['date'] ); ?></td>
                                                    <td><?php echo esc_html( $row['paid'] ); ?></td>
                                                    <td><?php echo esc_html( $row['status'] ); ?></td>
                                                    <td><?php echo esc_html( $row['attachment'] ); ?></td>
                                                    <td>
													    <?php foreach ( $row['items'] as $item ) : ?>
                                                            <div>
															    <?php echo $item['required'] ? '<strong>&#9733;</strong> ' : ''; ?>
                                                                <a href="<?php echo esc_url( admin_url( 'post.php?post=' . (int) $item['id'] . '&action=edit' ) ); ?>">#<?php echo esc_html( $item['id'] ); ?></a>
														        <?php echo esc_html( $item['name'] ); ?>
                                                            </div>
													    <?php endforeach; ?>
                                                    </td>
                                                    <td class="<?php echo $row['qualifies'] ? 'success' : 'error'; ?>">
													    <?php
													    echo $row['qualifies']
														    ? esc_html__( 'yes', 'wc_pay_per_post' )
														    : esc_html__( 'no — ', 'wc_pay_per_post' ) . esc_html( $row['why_not'] );
													    ?>
                                                    </td>
                                                </tr>
										    <?php endforeach; ?>
                                            </tbody>
                                        </table>
			                        <?php endif; ?>

			                        <?php if ( ! empty( $diagnostic_result['history']['other_products'] ) ) : ?>
                                        <h4><?php esc_html_e( 'Products this customer bought that do NOT unlock this page', 'wc_pay_per_post' ); ?></h4>
                                        <p class="description"><?php esc_html_e( 'If the customer believes they paid for this page, the answer is usually here. These products are not listed on the page, so the plugin never counts them.', 'wc_pay_per_post' ); ?></p>
                                        <table class="wp-list-table widefat fixed striped table-view-list">
                                            <thead>
                                            <tr>
                                                <th><?php esc_html_e( 'Product', 'wc_pay_per_post' ); ?></th>
                                                <th><?php esc_html_e( 'Name', 'wc_pay_per_post' ); ?></th>
                                                <th><?php esc_html_e( 'Times bought', 'wc_pay_per_post' ); ?></th>
                                            </tr>
                                            </thead>
                                            <tbody>
										    <?php foreach ( $diagnostic_result['history']['other_products'] as $p ) : ?>
                                                <tr>
                                                    <td><a href="<?php echo esc_url( admin_url( 'post.php?post=' . (int) $p['id'] . '&action=edit' ) ); ?>">#<?php echo esc_html( $p['id'] ); ?></a></td>
                                                    <td><?php echo esc_html( $p['name'] ); ?></td>
                                                    <td><?php echo esc_html( $p['count'] ); ?></td>
                                                </tr>
										    <?php endforeach; ?>
                                            </tbody>
                                        </table>
			                        <?php endif; ?>

                                    <h4><?php esc_html_e( 'Environment', 'wc_pay_per_post' ); ?></h4>
                                    <table class="wp-list-table widefat fixed striped table-view-list">
                                        <tbody>
										<?php foreach ( $diagnostic_result['environment'] as $label => $value ) : ?>
                                            <tr>
                                                <td style="width:30%;"><strong><?php echo esc_html( $label ); ?></strong></td>
                                                <td><?php echo esc_html( (string) $value ); ?></td>
                                            </tr>
										<?php endforeach; ?>
                                        </tbody>
                                    </table>

                                    <h4><?php esc_html_e( 'Individual checks', 'wc_pay_per_post' ); ?></h4>
                                    <table class="wp-list-table widefat fixed striped table-view-list">
                                        <tbody>
										<?php foreach ( $diagnostic_result['checks'] as $check => $passed ) : ?>
                                            <tr>
                                                <td style="width:60%;"><code><?php echo esc_html( $check ); ?></code></td>
                                                <td class="<?php echo $passed ? 'success' : 'error'; ?>">
													<?php echo $passed ? 'true' : 'false'; ?>
                                                </td>
                                            </tr>
										<?php endforeach; ?>
                                        </tbody>
                                    </table>

                                    <h4><?php esc_html_e( 'Copy for a support reply', 'wc_pay_per_post' ); ?></h4>
                                    <textarea id="wc-ppp-diagnostic-text" readonly
                                              style="width:100%; height:260px; font-family:monospace;"><?php
										echo esc_textarea( Woocommerce_Pay_Per_Post_Access_Diagnostic::as_text( $diagnostic_result ) );
										?></textarea>
                                    <p>
                                        <button type="button" class="button" onclick="
                                            var el = document.getElementById('wc-ppp-diagnostic-text');
                                            el.select();
                                            document.execCommand('copy');
                                            this.textContent = '<?php echo esc_js( __( 'Copied', 'wc_pay_per_post' ) ); ?>';
                                        "><?php esc_html_e( 'Copy to clipboard', 'wc_pay_per_post' ); ?></button>
                                    </p>
				                <?php endif; ?>
			                <?php endif; ?>
                        </div>

                    </div>

	                <?php if ( $enable_debugging ): ?>
                    <br><br>

                        <div class="postbox">
                            <h2 class="hndle"><?php esc_attr_e( 'Debug Log', 'wc_pay_per_post' ); ?></h2>

                            <div class="inside">
                                <label><strong><?php esc_attr_e( 'Log File Location', 'wc_pay_per_post' ); ?></strong>: <a href="<?php echo Woocommerce_Pay_Per_Post_Helper::logger_url(); ?>" target="_blank"><?php echo Woocommerce_Pay_Per_Post_Helper::logger_uri(); ?></a> | <a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=' . WC_PPP_SLUG . '-settings' ), 'delete_log', 'delete_log_nonce' ); ?>" title="Delete Log"><span class="dashicons dashicons-dismiss error-message"></span></a>
                                <?php if(file_exists(Woocommerce_Pay_Per_Post_Helper::logger_uri())): ?>
                                <textarea style="width:100%; height:500px;"><?php include(Woocommerce_Pay_Per_Post_Helper::logger_uri()); ?></textarea>
                                <?php endif; ?>
                                </label>
                            </div>

                        </div>
	                <?php endif; ?>
                    <br><br>

                    <div class="postbox">
                        <h2 class="hndle"><?php esc_attr_e( 'Transients', 'wc_pay_per_post' ); ?></h2>

                        <div class="inside">
                            <?php _e('<strong>The default setting for when transients expire is 1 hour.</strong><br>  You can change that buy using the filter <code>wc_pay_per_post_posts_transient_time</code>  You need to specify how many SECONDS before the transient expires. <br><br> Example:<br> <code>add_filter(&#x27;wc_pay_per_post_posts_transient_time&#x27;, function(){ return 10800; });</code>', 'wc_pay_per_post'); ?>
                            <form id="wc-ppp-create-table" action="" method="post" style="margin:20px 0 ;">
                                <?php wp_nonce_field("wc_ppp_delete_transients", "wc_ppp_delete_transients_nonce"); ?>
                                <input type="submit" name="wc-ppp-delete-btn" class="wc-ppp-delete-transients-btn button action" value="Clear/Refresh Transients">
                            </form>
                            <table class="wp-list-table widefat fixed striped table-view-list">
                                <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Value</th>
                                    <th>Expires</th>
                                </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($transients as $transient):
                                        ?>
                                    <tr>
                                        <td><?= $transient->option_id; ?></td>
                                        <td><?= $transient->option_name; ?></td>
                                        <td><?= wp_trim_words($transient->option_value); ?></td>
                                        <td><?= Carbon::createFromTimestamp(Woocommerce_Pay_Per_Post_Debug::get_transient_timeout(str_replace('_transient_', '', $transient->option_name)))->diffForHumans();?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>

                        </div>

                    </div>
                </div>
				<?php require_once plugin_dir_path(__FILE__ ).'settings-sidebar.php'; ?>
            </div>

        </div>
    </div>
</div>