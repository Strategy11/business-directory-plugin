<?php
/**
 * Privacy Policy
 *
 * @package BDP/Templates/Admin/Privacy Policy
 * @since 5.5
 */

?>
<div class="wp-suggested-text">
    <h3><?php echo _x( 'Business Directory Plugin', 'privacy policy', 'wpbdp' ); // XSS Ok. ?></h3>
    <p><strong class="privacy-policy-tutorial"><?php echo __( 'Suggested text:' ); // XSS Ok. ?> </strong><?php echo _x( 'When you submit a directory listing, the content of the listing and its metadata are retained indefinitely. All users can see, edit or delete the personal information included on their listings at any time. Website administrators can also see and edit that information.', 'privacy policy', 'wpbdp' ); // XSS Ok. ?></p>
    <p><?php echo _x( 'Website visitors can see the contact name, website URL, phone number, address and other information included in your submission to describe the directory listing.', 'privacy policy', 'wpbdp' ); // XSS Ok. ?></p>
    <h4><?php echo _x( 'Payment Information', 'privacy policy', 'wpbdp' ); // XSS Ok. ?></h4>
    <p><?php echo str_replace( '{home_url}', home_url(), _x( 'If you pay to post a directory listing entering your credit card and billing information directly on <a href="{home_url}">{home_url}</a>, the credit card information won\'t be stored but it will be shared through a secure connection with the following payment gateways to process the payment:', 'privacy policy', 'wpbdp' ) ); // XSS Ok. ?></p>
    <ul>
        <li><?php echo _x( 'PayPal &mdash; <a href="https://www.paypal.com/webapps/mpp/ua/privacy-full">https://www.paypal.com/webapps/mpp/ua/privacy-full</a>', 'privacy policy', 'wpbdp' ); // XSS Ok. ?></li>
        <li><?php echo _x( 'Authorize.Net &mdash; <a href="https://www.authorize.net/company/privacy/">https://www.authorize.net/company/privacy/</a>', 'privacy policy', 'wpbdp' ); // XSS Ok. ?></li>
        <li><?php echo _x( 'Stripe &mdash; <a href="https://stripe.com/us/privacy/">https://stripe.com/us/privacy/</a>', 'privacy policy', 'wpbdp' ); // XSS Ok. ?></li>
        <li><?php echo _x( '2Checkout &mdash; <a href="https://www.2checkout.com/policies/privacy-policy">https://www.2checkout.com/policies/privacy-policy</a>', 'privacy policy', 'wpbdp' ); // XSS Ok. ?></li>
        <li><?php echo _x( 'Payfast &mdash; <a href="https://www.payfast.co.za/privacy-policy/">https://www.payfast.co.za/privacy-policy/</a>', 'privacy policy', 'wpbdp' ); // XSS Ok. ?></li>
    </ul>
    <?php do_action( 'wpbdp_privacy_policy_content' ); ?>
</div>
