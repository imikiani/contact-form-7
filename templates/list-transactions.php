<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( "manage_options" ) ) {
	wp_die( __( 'You do not have sufficient permissions to access this page.', 'contact-form-7-idpay' ) );
}

global $wpdb;
$pagenum    = isset( $_GET['pagenum'] ) ? absint( $_GET['pagenum'] ) : 1;
$limit      = 6;
$offset     = ( $pagenum - 1 ) * $limit;
$table_name = $wpdb->prefix . "cf7_transactions";

$transactions = $wpdb->get_results( "SELECT * FROM $table_name  ORDER BY $table_name.id DESC LIMIT $offset, $limit", ARRAY_A );
$total        = $wpdb->get_var( "SELECT COUNT($table_name.id) FROM $table_name" );
$num_of_pages = ceil( $total / $limit );
$cntx         = 0;
?>
<div class="wrap">
    <h2><?php _e( 'Forms Transactions', 'contact-form-7-idpay' ) ?></h2>
    <table class="widefat post fixed" cellspacing="0">
        <thead>
        <tr>
            <th><?php _e( 'Form Name', 'contact-form-7-idpay' ) ?></th>
            <th> <?php _e( 'Date', 'contact-form-7-idpay' ) ?></th>
            <th><?php _e( 'Email', 'contact-form-7-idpay' ) ?></th>
            <th><?php _e( 'Amount', 'contact-form-7-idpay' ) ?></th>
            <th><?php _e( 'Transaction ID', 'contact-form-7-idpay' ) ?></th>
            <th><?php _e( 'Tracking Code', 'contact-form-7-idpay' ) ?></th>
            <th><?php _e( 'Payment Status', 'contact-form-7-idpay' ) ?></th>
        </tr>
        </thead>
        <tfoot>
        <tr>
            <th><?php _e( 'Form Name', 'contact-form-7-idpay' ) ?></th>
            <th> <?php _e( 'Date', 'contact-form-7-idpay' ) ?></th>
            <th><?php _e( 'Email', 'contact-form-7-idpay' ) ?></th>
            <th><?php _e( 'Amount', 'contact-form-7-idpay' ) ?></th>
            <th><?php _e( 'Transaction ID', 'contact-form-7-idpay' ) ?></th>
            <th><?php _e( 'Tracking Code', 'contact-form-7-idpay' ) ?></th>
            <th><?php _e( 'Payment Status', 'contact-form-7-idpay' ) ?></th>
        </tr>
        </tfoot>
        <tbody>
		<?php
		if ( count( $transactions ) == 0 ) :
			?>
            <tr class="alternate author-self status-publish iedit" valign="top">
                <td colspan="6"><?php _e( 'There are not any transactions.', 'contact-form-7-idpay' ) ?></td>
            </tr>
		<?php
		else:
			foreach ( $transactions as $transaction ):
				?>
                <tr class="alternate author-self status-publish iedit"
                    valign="top">
                    <td> <?php echo get_the_title( $transaction['form_id'] ) ?> </td>
                    <td style="direction: ltr; text-align: right;">
						<?php echo date( "Y-m-d H:i:s", $transaction['created_at'] ); ?>
                    </td>

                    <td> <?php echo $transaction['email'] ?></td>
                    <td> <?php echo $transaction['amount'] . " " ?><?php _e( 'Rial', 'contact-form-7-idpay' ) ?></td>
                    <td> <?php echo $transaction['trans_id'] ?></td>
                    <td> <?php echo $transaction['track_id'] ?></td>
                    <td>
						<?php if ( $transaction['status'] == "completed" ): ?>
                            <b style="color: #388e3c"><?php _e( 'completed', 'contact-form-7-idpay' ) ?></b>
						<?php elseif ( $transaction['status'] == "failed" ): ?>
                            <b style="color: #f00"><?php _e( 'failed', 'contact-form-7-idpay' ) ?></b>
						<?php elseif ( $transaction['status'] == "pending" ): ?>
                            <b style="color: #ff8f00"><?php _e( 'pending payment', 'contact-form-7-idpay' ) ?></b>
						<?php endif; ?>
                    </td>
                </tr>
			<?php
			endforeach;
		endif;
		?>
        </tbody>
    </table>
    <br>
	<?php
	$page_links = paginate_links( array(
		'base'      => add_query_arg( 'pagenum', '%#%' ),
		'format'    => '',
		'prev_text' => __( '&laquo;', 'contact-form-7-idpay' ),
		'next_text' => __( '&raquo;', 'contact-form-7-idpay' ),
		'total'     => $num_of_pages,
		'current'   => $pagenum,
	) );

	if ( $page_links ):
		?>
        <center>
            <div class="tablenav">
                <div class="tablenav-pages" style="float:none; margin: 1em 0">
					<?php echo $page_links ?>
                </div>
            </div>
        </center>
	<?php endif; ?>
    <br>
    <hr>
</div>