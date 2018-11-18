<?php
/**
 * Exporter for Payment personal data.
 *
 * @package BDP\Admin
 */

// phpcs:disable Squiz.Commenting.VariableComment.EmptyVar
// phpcs:disable Squiz.Commenting.FunctionComment.MissingParamTag
// phpcs:disable Squiz.Commenting.FunctionComment.MissingParamName

/**
 * Class WPBDP_PaymentPersonalDataExporter
 */
class WPBDP_PaymentPersonalDataExporter implements WPBDP_PersonalDataExporterInterface {
    /**
     * @var
     */
    private $data_formatter;

    /**
     * WPBDP_PaymentPersonalDataExporter constructor.
     *
     * @param $data_formatter
     * @since 5.4
     */
    public function __construct( $data_formatter ) {
        $this->data_formatter = $data_formatter;
    }

    /**
     * @return int
     *
     * @since 5.4
     */
    public function get_page_size() {
        return 10;
    }

    /**
     * @param $user
     * @param $email_address
     * @param $page
     * @return array|mixed
     *
     * @since 5.4
     * @SuppressWarnings(PHPMD)
     */
    public function get_objects( $user, $email_address, $page ) {
        return WPBDP_Payment::objects()->filter( array( 'payer_email' => $email_address ) )->to_array();
    }

    /**
     * @param $payment_transactions
     * @return array|mixed
     *
     * @since 5.4
     */
    public function export_objects( $payment_transactions ) {
        $items        = array(
            'ID'          => __( 'Payment Transaction', 'business-directory-plugin-plugin' ),
            'payer_email' => __( 'Payer Email', 'business-directory-plugin-plugin' ),
        );
        $export_items = array();

        foreach ( $payment_transactions as $payment_transaction ) {

            $data           = $this->data_formatter->format_data( $items, $this->get_payment_transaction_properties( $payment_transaction ) );
            $export_items[] = array(
                'group_id'    => 'wpbdp-payments',
                'group_label' => __( 'Listing Payments Information', 'business-directory-plugin-plugin' ),
                'item_id'     => "wpbdp -payment-transaction-{$payment_transaction->id}",
                'data'        => $data,
            );
        }
        return $export_items;
    }

    /**
     * @param $payment_transaction
     * @return array
     *
     * @since 5.4
     */
    private function get_payment_transaction_properties( $payment_transaction ) {
        return array(
            'ID'          => $payment_transaction->id,
            'payer_email' => $payment_transaction->payer_email,
        );
    }
}
