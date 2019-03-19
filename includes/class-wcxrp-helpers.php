<?php


class WCXRP_Helpers
{
    /**
     * Ugly helper to print pretty statuses.
     * @param $status
     * @return string
     */
    function wc_pretty_status($status)
    {
        switch ($status) {
            case 'wc-pending':
                return 'Pending payment';
            case 'wc-processing':
                return 'Processing (Paid)';
            case 'wc-on-hold':
                return 'On hold';
            case 'wc-completed':
                return 'Completed';
            case 'wc-cancelled':
                return 'Cancelled';
            case 'wc-refunded':
                return 'Refunded';
            case 'wc-failed':
                return 'Failed';
            default:
                return 'Unknown';
        }
    }

    /**
     * Generate a QR-code for the XRP payment.
     * @param $account
     * @param $tag
     * @param $amount
     * @return string
     */
    function xrp_qr( $account, $tag, $amount ) {
        $data = sprintf(
            'https://ripple.com/send?to=%s&dt=%s&amount=%s',
            $account,
            $tag,
            $amount
        );
        return sprintf(
            'https://chart.googleapis.com/chart?chs=256x256&cht=qr&chld=M|0&chl=%s&choe=UTF-8',
            urlencode( $data )
        );
    }

}
