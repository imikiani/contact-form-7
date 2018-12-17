<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$admin_table_output = "";
$admin_table_output .= "<form>";
$admin_table_output .= "<div id='additional_settings-sortables' class='meta-box-sortables ui-sortable'><div id='additionalsettingsdiv' class='postbox'>";
$admin_table_output .= "<div class='handlediv' title='Click to toggle'><br></div><h3 class='hndle ui-sortable-handle'> <span>اطلاعات پرداخت برای فرم</span></h3>";
$admin_table_output .= "<div class='inside'>";
$admin_table_output .= "<div class='mail-field'>";
$admin_table_output .= "<input name='enable' id='idpay_active' value='1' type='checkbox' $checked>";
$admin_table_output .= "<label for='idpay_active'>فعال سازی امکان پرداخت آنلاین</label>";
$admin_table_output .= "</div>";
$admin_table_output .= "<table>";
$admin_table_output .= "<tr><td>مبلغ: </td><td><input type='text' name='amount' style='text-align:left;direction:ltr;' value='$amount'></td><td>(ریال)</td></tr>";
$admin_table_output .= "</table>";
$admin_table_output .= "<br> برای اتصال به درگاه پرداخت میتوانید از فیلدهای زیر استفاده نمایید ";
$admin_table_output .= "<br>
        <span style='color:#F00;'>
         idpay_description فیلد توضیحات.
        <br>
         idpay_phone فیلد شماره تلفن کاربر.
        <br>
        idpay_amount فیلد مبلغ دلخواه کاربر (در صورتی که کادر مبلغ خالی باشد قال استفاده است)
        </span>	";
$admin_table_output .= "<input type='hidden' name='email' value='2'>";
$admin_table_output .= "<input type='hidden' name='post' value='$post_id'>";
$admin_table_output .= "</td></tr></table></form>";
$admin_table_output .= "</div>";
$admin_table_output .= "</div>";
$admin_table_output .= "</div>";
echo $admin_table_output;