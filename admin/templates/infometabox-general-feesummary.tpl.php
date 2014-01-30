<div>
    <p><strong><?php _ex('Listing Categories', 'admin infometabox', 'WPBDM'); ?></strong></p>

    <dl>
        <?php foreach ($post_categories as $term): ?>
        <?php $fee = wpbdp_listings_api()->get_listing_fee_for_category( $post_id, $term->term_id ); ?>
        <?php $expired = $fee && $fee->expires_on && ( strtotime( $fee->expires_on ) < time() ) ? true : false; ?>

        <dt class="category-name">
            <?php if ( $expired ): ?><s><?php endif; ?><?php echo $term->name; ?><?php if ( $expired ): ?></s><?php endif; ?> 
        </dt>
        <dd>
            <?php if ( $fee ) : ?>
                <?php if ( $expired ): ?> (<?php _ex( 'Expired', 'admin infometabox', 'WPBDM' ); ?>)<?php endif; ?>
                <dl class="feeinfo">
                    <dt>
                        <?php if ( $fee->expires_on && $expired ): ?>
                            <?php _ex('Expired on', 'admin infometabox', 'WPBDM'); ?>
                        <?php else: ?>
                            <?php _ex('Expires on', 'admin infometabox', 'WPBDM'); ?>
                        <?php endif; ?> 
                    </dt>
                    <dd>
                        <?php if (current_user_can('administrator')): ?>
                            <a href="<?php echo add_query_arg( array( 'wpbdmaction' => 'change_expiration', 'listing_fee_id' => $fee->renewal_id ) ); ?>"
                               class="listing-fee-expiration-change-link"
                               title="<?php _ex( 'Click to manually change expiration date.', 'admin infometabox', 'WPBDM' ); ?>"
                               data-date="<?php echo date('Y-m-d', strtotime( $fee->expires_on ) ); ?>">
                        <?php endif; ?>
                        <?php if ($fee->expires_on): ?>
                            <?php echo date_i18n(get_option('date_format'), strtotime($fee->expires_on)); ?>
                        <?php else: ?>
                            <?php _ex('never', 'admin infometabox', 'WPBDM'); ?>
                        <?php endif; ?>
                        <?php if (current_user_can('administrator')): ?>
                            </a>

                            <div class="listing-fee-expiration-datepicker"></div>
                        <?php endif; ?>
                    </dd>
                </dl>

            <?php else: ?>
                <?php _ex('No fee assigned.', 'admin infometabox', 'WPBDM'); ?>
            <?php endif; ?>
                <?php if (current_user_can('administrator')): ?>
                <?php if ( $fee ): ?>
                - <a href="#" onclick="window.prompt('<?php _ex( 'Renewal URL (copy & paste)', 'admin infometabox', 'WPBDM' ); ?>', '<?php echo wpbdp_listings_api()->get_renewal_url( $fee->renewal_id ); ?>'); return false;"><?php _ex( 'Show renewal link', 'admin infometabox', 'WPBDM' ); ?></a><br />
                - <a href="<?php echo add_query_arg( array( 'wpbdmaction' => 'send-renewal-email',
                                                            'renewal_id' => $fee->renewal_id ) ); ?>"><?php _ex( 'Send renewal e-mail to user', 'admin infometabox', 'WPBDM' ); ?></a><br /><?php endif; ?>
                </a>
                <?php endif; ?>

        </dd>
        <?php endforeach; ?>
    </dl>

    <?php if ( $expired_categories ): ?>
    <a href="<?php echo add_query_arg( 'wpbdmaction', 'renewlisting' ); ?>" class="button-primary button"><?php _ex( 'Renew listing in all expired categories', 'admin infometabox', 'WPBDM'); ?></a>
    <?php endif; ?>

</div>