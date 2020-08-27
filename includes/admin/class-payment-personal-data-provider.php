<?php
/**
 * Exporter for Payment personal data.
 *
 * @package BDP/Admin
 * @since 5.5
 */

// phpcs:disable Squiz.Commenting.VariableComment.EmptyVar
// phpcs:disable Squiz.Commenting.FunctionComment.MissingParamTag
// phpcs:disable Squiz.Commenting.FunctionComment.MissingParamName

/**
 * Class WPBDP_PaymentPersonalDataExporter
 */
class WPBDP_PaymentPersonalDataProvider implements WPBDP_PersonalDataProviderInterface {
    /**
     * @var
     */
    private $data_formatter;

    /**
     * WPBDP_PaymentPersonalDataProvider constructor.
     *
     * @param $data_formatter
     */
    public function __construct( $data_formatter ) {
        $this->data_formatter = $data_formatter;
    }

    /**
     * @return int
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
     * @SuppressWarnings(PHPMD)
     */
    public function get_objects( $user, $email_address, $page ) {
        return WPBDP_Payment::objects()->filter( array( 'payer_email' => $email_address ) )->to_array();
    }

    /**
     * @param $payment_transactions
     * @return array|mixed
     */
    public function export_objects( $payment_transactions ) {
        $items        = array(
            'ID'          => __( 'Payment Transaction', 'business-directory-plugin' ),
            'payer_email' => __( 'Payer Email', 'business-directory-plugin' ),
        );
        $export_items = array();

        foreach ( $payment_transactions as $payment_transaction ) {

            $data           = $this->data_formatter->format_data( $items, $this->get_payment_transaction_properties( $payment_transaction ) );
            $export_items[] = array(
                'group_id'    => 'wpbdp-payments',
                'group_label' => __( 'Listing Payments Information', 'business-directory-plugin' ),
                'item_id'     => "wpbdp -payment-transaction-{$payment_transaction->id}",
                'data'        => $data,
            );
        }
        return $export_items;
    }

    /**
     * @param $payment_transaction
     * @return array
     */
    private function get_payment_transaction_properties( $payment_transaction ) {
        return array(
            'ID'          => $payment_transaction->id,
            'payer_email' => $payment_transaction->payer_email,
        );
    }

    /**
     * @param $payment_transactions
     * @return array|mixed
     */
    public function erase_objects( $payment_transactions ) {
        $items_removed  = false;
        $items_retained = false;
        $messages       = array();
        foreach ( $payment_transactions as $payment_transaction ) {
            if ( $payment_transaction->delete() ) {
                $items_removed = true;
                continue;
            }
            $items_retained = true;
            $message        = __( 'An unknown error occurred while trying to delete listing payment information for transaction {transaction_id}.', 'business-directory-plugin' );
            $message        = str_replace( '{transaction_id}', $payment_transaction->id, $message );
            $messages[]     = $message;
        }
        return compact( 'items_removed', 'items_retained', 'messages' );
    }
}
