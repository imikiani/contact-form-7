<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( "manage_options" ) ) {
	wp_die( __( "You do not have sufficient permissions to access this page." ) );
}

global $wpdb;
$pagenum    = isset( $_GET['pagenum'] ) ? absint( $_GET['pagenum'] ) : 1;
$limit      = 6;
$offset     = ( $pagenum - 1 ) * $limit;
$table_name = $wpdb->prefix . "cf7_transactions";

$transactions = $wpdb->get_results( "SELECT * FROM $table_name  ORDER BY $table_name.id DESC LIMIT $offset, $limit", ARRAY_A );
$total        = $wpdb->get_var( "SELECT COUNT($table_name.id) FROM $table_name where (status NOT like 'none') " );
$num_of_pages = ceil( $total / $limit );
$cntx         = 0;

echo '<div class="wrap">
		<h2>تراکنش فرم ها</h2>
		<table class="widefat post fixed" cellspacing="0">
			<thead>
				<tr>
					<th scope="col" id="name" width="15%" class="manage-column" style="">نام فرم</th>
					<th scope="col" id="name" width="" class="manage-column" style="">تاريخ</th>
                    <th scope="col" id="name" width="" class="manage-column" style="">ایمیل</th>
                    <th scope="col" id="name" width="15%" class="manage-column" style="">مبلغ</th>
					<th scope="col" id="name" width="15%" class="manage-column" style="">شناسه پرداخت</th>
					<th scope="col" id="name" width="15%" class="manage-column" style="">کد رهگیری</th>
					<th scope="col" id="name" width="13%" class="manage-column" style="">وضعیت</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<th scope="col" id="name" width="15%" class="manage-column" style="">نام فرم</th>
					<th scope="col" id="name" width="" class="manage-column" style="">تاريخ</th>
                    <th scope="col" id="name" width="" class="manage-column" style="">ایمیل</th>
                    <th scope="col" id="name" width="" class="manage-column" style="">مبلغ</th>
                    <th scope="col" id="name" width="15%" class="manage-column" style="">شناسه پرداخت</th>
					<th scope="col" id="name" width="15%" class="manage-column" style="">کد رهگیری</th>
					<th scope="col" id="name" width="13%" class="manage-column" style="">وضعیت</th>
				</tr>
			</tfoot>
			<tbody>';

if ( count( $transactions ) == 0 ) {

	echo '<tr class="alternate author-self status-publish iedit" valign="top">
					<td colspan="6">هيج تراکنش وجود ندارد.</td>
				</tr>';

} else {
	foreach ( $transactions as $transaction ) {

		echo '<tr class="alternate author-self status-publish iedit" valign="top">
					<td>' . get_the_title( $transaction['form_id'] ) . '</td>';
		echo '<td style="direction: ltr; text-align: right;" >' . date( "Y-m-d H:i:s", $transaction['created_at'] );
		echo '<br>(';
		date_default_timezone_set( "Asia/Tehran" );
		$ttime = time() - $transaction["created_at"];
		if ( $ttime < 1 ) {
			echo '0 ثانیه';
		}
		$a = array(
			12 * 30 * 24 * 60 * 60 => 'سال',
			30 * 24 * 60 * 60      => 'ماه',
			24 * 60 * 60           => 'روز',
			60 * 60                => 'ساعت',
			60                     => 'دقیقه',
			1                      => 'ثانیه',
		);
		foreach ( $a as $secs => $str ) {
			$d = $ttime / $secs;
			if ( $d >= 1 ) {
				$r = round( $d );
				echo $r . ' ' . $str . ( $r > 1 ? ' ' : '' );
			}
		}
		echo ' قبل)</td>';

		echo '<td>' . $transaction['email'] . '</td>';
		echo '<td>' . $transaction['amount'] . ' ریال</td>';
		echo '<td>' . $transaction['trans_id'] . '</td>';
		echo '<td>' . $transaction['track_id'] . '</td>';
		echo '<td>';

		if ( $transaction['status'] == "completed" ) {
			echo '<b style="color:#0C9F55">موفقیت آمیز</b>';
		} elseif ( $transaction['status'] == "failed" ) {
			echo '<b style="color:#f00">ناموفق</b>';
		} elseif ( $transaction['status'] == "pending" ) {
			echo '<b style="color:#f00">در حال پرداخت</b>';
		}
		echo '</td></tr>';

	}
}
echo '</tbody>
		</table>
        <br>';

$page_links = paginate_links( array(
	'base'      => add_query_arg( 'pagenum', '%#%' ),
	'format'    => '',
	'prev_text' => __( '&laquo;', 'aag' ),
	'next_text' => __( '&raquo;', 'aag' ),
	'total'     => $num_of_pages,
	'current'   => $pagenum,
) );

if ( $page_links ) {
	echo '<center><div class="tablenav"><div class="tablenav-pages"  style="float:none; margin: 1em 0">' . $page_links . '</div></div>
		</center>';
}
echo '<br>
		<hr>
	</div>';