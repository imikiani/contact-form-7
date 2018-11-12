<?php
/*
Plugin Name: IDPay - Contact Form 7
Plugin URI: https://idpay.ir/
Description: درگاه IDPay برای Contact Form 7
Author: Developer: JMDMahdi, Publisher: IDPay
Author URI: https://idpay.ir/
Version: 1.0
*/

function idpay_cf7_callback_handler($atts)
{
    global $wpdb;
    $options = get_option('idpay_cf7_options');
    foreach ($options as $k => $v) {
        $value[$k] = $v;
    }

    if (!empty($_POST['id']) && !empty($_POST['order_id'])) {
        $pid = $_POST['id'];
        $porder_id = $_POST['order_id'];

        $cf_Form = $wpdb->get_row("SELECT * FROM " . $wpdb->prefix . "cfZ7_transaction WHERE transid='$pid'");
        if ($cf_Form !== null) {
            $price = $cf_Form->cost;
        }

        $api_key = $value['api_key'];
        $sandbox = $value['sandbox'] == 1 ? 'true' : 'false';

        $data = array(
            'id' => $pid,
            'order_id' => $porder_id,
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.idpay.ir/v1/payment/inquiry');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'X-API-KEY:' . $api_key,
            'X-SANDBOX:' . $sandbox,
        ));

        $result = curl_exec($ch);
        $result = json_decode($result);
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_status != 200) {
            $wpdb->update($wpdb->prefix . 'cfZ7_transaction', array('status' => 'error'), array('transid' => $pid), array('%s'), array('%d'));
            return '<b style="color:#f44336;">' . sprintf('خطا هنگام بررسی وضعیت تراکنش. کد خطا: %s', $http_status) . '<b/>';;
        }

        $inquiry_status = empty($result->status) ? NULL : $result->status;
        $inquiry_track_id = empty($result->track_id) ? NULL : $result->track_id;
        $inquiry_order_id = empty($result->order_id) ? NULL : $result->order_id;
        $inquiry_amount = empty($result->amount) ? NULL : $result->amount;

        if (empty($inquiry_status) || empty($inquiry_track_id) || empty($inquiry_amount) || $inquiry_amount != $price || $inquiry_status != 100) {
            $wpdb->update($wpdb->prefix . 'cfZ7_transaction', array('status' => 'error'), array('transid' => $pid), array('%s'), array('%d'));
            return '<b style="color:#f44336;">' . idpay_cf7_get_failed_message($value['failed_massage'], $inquiry_track_id, $inquiry_order_id) . '<b/>';
        } else {
            $wpdb->update($wpdb->prefix . 'cfZ7_transaction', array('status' => 'success', 'transid' => $inquiry_track_id), array('transid' => $pid), array('%s', '%s'), array('%d'));
            return '<b style="color:#8BC34A;">' . idpay_cf7_get_success_message($value['success_massage'], $inquiry_track_id, $inquiry_order_id) . '<b/>';
        }
    } else {
        return $body = '<b style="color:#f44336;">تراکنش یافت نشد<b/>';
    }
}

add_shortcode('idpay_cf7_result', 'idpay_cf7_callback_handler');

function idpay_cf7_get_failed_message($failed_massage, $track_id, $order_id)
{
    return str_replace(["{track_id}", "{order_id}"], [$track_id, $order_id], $failed_massage);
}

function idpay_cf7_get_success_message($success_massage, $track_id, $order_id)
{
    return str_replace(["{track_id}", "{order_id}"], [$track_id, $order_id], $success_massage);
}

$dir = plugin_dir_path(__FILE__);
register_activation_hook(__FILE__, "idpay_cf7_activate");
register_deactivation_hook(__FILE__, "idpay_cf7_deactivate");
register_uninstall_hook(__FILE__, "idpay_cf7_uninstall");

function idpay_cf7_activate()
{
    global $wpdb;
    $table_name = $wpdb->prefix . "cfZ7_transaction";
    if ($wpdb->get_var("show tables like '$table_name'") != $table_name) {
        $sql = "CREATE TABLE " . $table_name . " (
			id mediumint(11) NOT NULL AUTO_INCREMENT,
			idform bigint(11) DEFAULT '0' NOT NULL,
			transid VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_persian_ci NOT NULL,
			gateway VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_persian_ci NOT NULL,
			cost bigint(11) DEFAULT '0' NOT NULL,
			created_at bigint(11) DEFAULT '0' NOT NULL,
			email VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_persian_ci  NULL,
			description VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_persian_ci NOT NULL,
			user_mobile VARCHAR(11) CHARACTER SET utf8 COLLATE utf8_persian_ci  NULL,
			status VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_persian_ci NOT NULL,
			PRIMARY KEY id (id)
		);";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    function wp_config_put($slash = '')
    {
        $config = file_get_contents(ABSPATH . "wp-config.php");
        $config = preg_replace("/^([\r\n\t ]*)(\<\?)(php)?/i", "<?php define('WPCF7_LOAD_JS', false);", $config);
        file_put_contents(ABSPATH . $slash . "wp-config.php", $config);
    }

    if (file_exists(ABSPATH . "wp-config.php") && is_writable(ABSPATH . "wp-config.php")) {
        wp_config_put();
    } else if (file_exists(dirname(ABSPATH) . "/wp-config.php") && is_writable(dirname(ABSPATH) . "/wp-config.php")) {
        wp_config_put('/');
    } else {
        ?>
        <div class="error">
            <p><?php _e('wp-config.php is not writable, please make wp-config.php writable - set it to 0777 temporarily, then set back to its original setting after this plugin has been activated.', 'cf7pp'); ?></p>
        </div>
        <?php
        exit;
    }

    $idpay_cf7_options = array(
        'api_key' => '',
        'return' => '',
        'sandbox' => '1',
    );

    add_option("idpay_cf7_options", $idpay_cf7_options);
}


function idpay_cf7_deactivate()
{

    function wp_config_delete($slash = '')
    {
        $config = file_get_contents(ABSPATH . "wp-config.php");
        $config = preg_replace("/( ?)(define)( ?)(\()( ?)(['\"])WPCF7_LOAD_JS(['\"])( ?)(,)( ?)(0|1|true|false)( ?)(\))( ?);/i", "", $config);
        file_put_contents(ABSPATH . $slash . "wp-config.php", $config);
    }

    if (file_exists(ABSPATH . "wp-config.php") && is_writable(ABSPATH . "wp-config.php")) {
        wp_config_delete();
    } else if (file_exists(dirname(ABSPATH) . "/wp-config.php") && is_writable(dirname(ABSPATH) . "/wp-config.php")) {
        wp_config_delete('/');
    } else if (file_exists(ABSPATH . "wp-config.php") && !is_writable(ABSPATH . "wp-config.php")) {
        ?>
        <div class="error">
            <p><?php _e('wp-config.php is not writable, please make wp-config.php writable - set it to 0777 temporarily, then set back to its original setting after this plugin has been deactivated.', 'cf7pp'); ?></p>
        </div>
        <button onclick="goBack()">Go Back and try again</button>
        <script>
            function goBack() {
                window.history.back();
            }
        </script>
        <?php
        exit;
    } else if (file_exists(dirname(ABSPATH) . "/wp-config.php") && !is_writable(dirname(ABSPATH) . "/wp-config.php")) {
        ?>
        <div class="error">
            <p><?php _e('wp-config.php is not writable, please make wp-config.php writable - set it to 0777 temporarily, then set back to its original setting after this plugin has been deactivated.', 'cf7pp'); ?></p>
        </div>
        <button onclick="goBack()">Go Back and try again</button>
        <script>
            function goBack() {
                window.history.back();
            }
        </script>
        <?php
        exit;
    } else {
        ?>
        <div class="error">
            <p><?php _e('wp-config.php is not writable, please make wp-config.php writable - set it to 0777 temporarily, then set back to its original setting after this plugin has been deactivated.', 'cf7pp'); ?></p>
        </div>
        <button onclick="goBack()">Go Back and try again</button>
        <script>
            function goBack() {
                window.history.back();
            }
        </script>
        <?php
        exit;
    }

    delete_option("idpay_cf7_options");
    delete_option("idpay_cf7_my_plugin_notice_shown");
}

add_action('admin_notices', 'idpay_cf7_my_plugin_admin_notices');

function idpay_cf7_my_plugin_admin_notices()
{
    if (!get_option('idpay_cf7_my_plugin_notice_shown')) {
        echo "<div class='updated'><p><a href='admin.php?page=idpay_cf7_admin_table'>برای تنظیم اطلاعات درگاه  کلیک کنید</a>.</p></div>";
        update_option("idpay_cf7_my_plugin_notice_shown", "true");
    }
}

include_once(ABSPATH . 'wp-admin/includes/plugin.php');
if (is_plugin_active('contact-form-7/wp-contact-form-7.php')) {

    add_action('admin_menu', 'idpay_cf7_admin_menu', 20);
    function idpay_cf7_admin_menu()
    {
        add_submenu_page('wpcf7',
            __('تنظیمات IDPay', 'IDPay'),
            __('تنظیمات IDPay', 'IDPay'),
            'wpcf7_edit_contact_forms', 'idpay_cf7_admin_table',
            'idpay_cf7_admin_table');

        add_submenu_page('wpcf7',
            __('لیست تراکنش ها', 'IDPay'),
            __('لیست تراکنش ها', 'IDPay'),
            'wpcf7_edit_contact_forms', 'idpay_cf7_admin_list_trans',
            'idpay_cf7_admin_list_trans');

    }

    add_action('wpcf7_mail_sent', 'idpay_cf7_after_send_mail');
    function idpay_cf7_after_send_mail($cf7)
    {
        global $postid;
        $postid = $cf7->id();

        $enable = get_post_meta($postid, "_idpay_cf7_enable", true);
        $email = get_post_meta($postid, "_idpay_cf7_email", true);

        if ($enable == "1") {
            if ($email == "2") {
                global $wpdb;
                global $postid;

                $wpcf7 = WPCF7_ContactForm::get_current();
                $submission = WPCF7_Submission::get_instance();
                $user_email = '';
                $user_mobile = '';
                $description = '';
                $user_price = '';

                if ($submission) {
                    $data = $submission->get_posted_data();
                    $user_email = isset($data['user_email']) ? $data['user_email'] : "";
                    $user_mobile = isset($data['user_mobile']) ? $data['user_mobile'] : "";
                    $description = isset($data['description']) ? $data['description'] : "";
                    $user_price = isset($data['user_price']) ? $data['user_price'] : "";
                }
                $price = get_post_meta($postid, "_idpay_cf7_price", true);
                if ($price == "") {
                    $price = $user_price;
                }
                $options = get_option('idpay_cf7_options');
                foreach ($options as $k => $v) {
                    $value[$k] = $v;
                }
                $active_gateway = 'IDPay';
                $url_return = $value['return'];
                $table_name = $wpdb->prefix . "cfZ7_transaction";
                $_x = array();
                $_x['idform'] = $postid;
                $_x['transid'] = '';
                $_x['gateway'] = $active_gateway;
                $_x['cost'] = $price;
                $_x['created_at'] = time();
                $_x['email'] = $user_email;
                $_x['user_mobile'] = $user_mobile;
                $_x['description'] = $description;
                $_x['status'] = 'none';
                $_y = array('%d', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s');

                if ($active_gateway == 'IDPay') {

                    $api_key = $value['api_key'];
                    $sandbox = $value['sandbox'] == 1 ? 'true' : 'false';
                    $amount = intval($price);

                    $desc = $description;

                    if (empty($amount)) {
                        $tmp = 'واحد پول انتخاب شده پشتیبانی نمی شود.' . ' لطفا به مدیر اطلاع دهید <br>';
                        $tmp .= '<a href="' . get_option('siteurl') . '" class="mrbtn_red" > بازگشت به سایت </a>';
                        echo '<html>
                            <head>
                            <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
                            <title>خطا در عملیات پرداخت</title>
                            </head>
                            <link rel="stylesheet"  media="all" type="text/css" href="' . plugins_url('style.css', __FILE__) . '">
                            <body>	
                            <div> 
                            <h3><span>خطا در عملیات پرداخت</span></h3>
                            ' . $tmp . '	
                            </div>
                            </body>
                            </html>';
                    }

                    $data = array(
                        'order_id' => time(),
                        'amount' => $amount,
                        'phone' => $user_mobile,
                        'desc' => $desc,
                        'callback' => $url_return,
                    );

                    $ch = curl_init('https://api.idpay.ir/v1/payment');
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                        'Content-Type: application/json',
                        'X-API-KEY:' . $api_key,
                        'X-SANDBOX:' . $sandbox,
                    ));

                    $result = curl_exec($ch);
                    $result = json_decode($result);
                    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);

                    if ($http_status != 201 || empty($result) || empty($result->id) || empty($result->link)) {
                        $tmp = sprintf('خطا هنگام ایجاد تراکنش. کد خطا: %s', $http_status) . '<br> لطفا به مدیر اطلاع دهید <br><br>';
                        $tmp .= '<a href="' . get_option('siteurl') . '" class="mrbtn_red" > بازگشت به سایت </a>';
                        echo '<html>
                            <head>
                            <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
                            <title>خطا در عملیات پرداخت</title>
                            </head>
                            <link rel="stylesheet"  media="all" type="text/css" href="' . plugins_url('style.css', __FILE__) . '">
                            <body>	
                            <div> 
                            <h3><span>خطا در عملیات پرداخت</span></h3>
                                                ' . $tmp . '	
                            </div>
                            </body>
                            </html>';
                    } else {
                        $_x['transid'] = $result->id;
                        $wpdb->insert($table_name, $_x, $_y);
                        Header('Location: ' . $result->link);
                    }
                    exit();
                }
            }
        }
    }

    add_action('wpcf7_admin_after_additional_settings', 'idpay_cf7_admin_after_additional_settings');
    function idpay_cf7_editor_panels($panels)
    {
        $new_page = array(
            'PricePay' => array(
                'title' => __('اطلاعات پرداخت', 'IDPay'),
                'callback' => 'idpay_cf7_admin_after_additional_settings'
            )
        );
        $panels = array_merge($panels, $new_page);
        return $panels;
    }

    add_filter('wpcf7_editor_panels', 'idpay_cf7_editor_panels');


    function idpay_cf7_admin_after_additional_settings($cf7)
    {
        $post_id = sanitize_text_field($_GET['post']);
        $enable = get_post_meta($post_id, "_idpay_cf7_enable", true);
        $price = get_post_meta($post_id, "_idpay_cf7_price", true);
        $email = get_post_meta($post_id, "_idpay_cf7_email", true);
        if ($enable == "1") {
            $checked = "CHECKED";
        } else {
            $checked = "";
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
        $admin_table_output .= "<tr><td>مبلغ: </td><td><input type='text' name='price' style='text-align:left;direction:ltr;' value='$price'></td><td>(ریال)</td></tr>";
        $admin_table_output .= "</table>";
        $admin_table_output .= "<br> برای اتصال به درگاه پرداخت میتوانید از فیلدهای زیر استفاده نمایید ";
        $admin_table_output .= "<br>
        <span style='color:#F00;'>
        user_email فیلد یمیل کاربر.
        <br>
         description فیلد توضیحات.
        <br>
         user_mobile فیلد شماره تلفن کاربر.
        <br>
        user_price فیلد مبلغ دلخواه کاربر (در صورتی که کادر مبلغ خالی باشد قال استفاده است)
        </span>	";
        $admin_table_output .= "<input type='hidden' name='email' value='2'>";
        $admin_table_output .= "<input type='hidden' name='post' value='$post_id'>";
        $admin_table_output .= "</td></tr></table></form>";
        $admin_table_output .= "</div>";
        $admin_table_output .= "</div>";
        $admin_table_output .= "</div>";
        echo $admin_table_output;
    }

    add_action('wpcf7_save_contact_form', 'idpay_cf7_save_contact_form');
    function idpay_cf7_save_contact_form($cf7)
    {
        $post_id = sanitize_text_field($_POST['post']);
        if (!empty($_POST['enable'])) {
            $enable = sanitize_text_field($_POST['enable']);
            update_post_meta($post_id, "_idpay_cf7_enable", $enable);
        } else {
            update_post_meta($post_id, "_idpay_cf7_enable", 0);
        }
        $price = sanitize_text_field($_POST['price']);
        update_post_meta($post_id, "_idpay_cf7_price", $price);
        $email = sanitize_text_field($_POST['email']);
        update_post_meta($post_id, "_idpay_cf7_email", $email);
    }

    function idpay_cf7_admin_list_trans()
    {
        if (!current_user_can("manage_options")) {
            wp_die(__("You do not have sufficient permissions to access this page."));
        }

        global $wpdb;
        $pagenum = isset($_GET['pagenum']) ? absint($_GET['pagenum']) : 1;
        $limit = 6;
        $offset = ($pagenum - 1) * $limit;
        $table_name = $wpdb->prefix . "cfZ7_transaction";

        $transactions = $wpdb->get_results("SELECT * FROM $table_name where (status NOT like 'none') ORDER BY $table_name.id DESC LIMIT $offset, $limit", ARRAY_A);
        $total = $wpdb->get_var("SELECT COUNT($table_name.id) FROM $table_name where (status NOT like 'none') ");
        $num_of_pages = ceil($total / $limit);
        $cntx = 0;

        echo '<div class="wrap">
		<h2>تراکنش فرم ها</h2>
		<table class="widefat post fixed" cellspacing="0">
			<thead>
				<tr>
					<th scope="col" id="name" width="15%" class="manage-column" style="">نام فرم</th>
					<th scope="col" id="name" width="" class="manage-column" style="">تاريخ</th>
                    <th scope="col" id="name" width="" class="manage-column" style="">ایمیل</th>
                    <th scope="col" id="name" width="15%" class="manage-column" style="">مبلغ</th>
					<th scope="col" id="name" width="15%" class="manage-column" style=""> کد پیگیری/آی دی پرداخت</th>
					<th scope="col" id="name" width="13%" class="manage-column" style="">وضعیت</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<th scope="col" id="name" width="15%" class="manage-column" style="">نام فرم</th>
					<th scope="col" id="name" width="" class="manage-column" style="">تاريخ</th>
                    <th scope="col" id="name" width="" class="manage-column" style="">ایمیل</th>
                    <th scope="col" id="name" width="15%" class="manage-column" style="">مبلغ</th>
					<th scope="col" id="name" width="15%" class="manage-column" style="">کد پیگیری</th>
					<th scope="col" id="name" width="13%" class="manage-column" style="">وضعیت</th>
				</tr>
			</tfoot>
			<tbody>';

        if (count($transactions) == 0) {

            echo '<tr class="alternate author-self status-publish iedit" valign="top">
					<td colspan="6">هيج تراکنش وجود ندارد.</td>
				</tr>';

        } else {
            foreach ($transactions as $transaction) {

                echo '<tr class="alternate author-self status-publish iedit" valign="top">
					<td>' . get_the_title($transaction['idform']) . '</td>';
                echo '<td style="direction: ltr; text-align: right;" >' . date("Y-m-d H:i:s", $transaction['created_at']);
                echo '<br>(';
                date_default_timezone_set("Asia/Tehran");
                $ttime = time() - $transaction["created_at"];
                if ($ttime < 1) {
                    echo '0 ثانیه';
                }
                $a = array(12 * 30 * 24 * 60 * 60 => 'سال',
                    30 * 24 * 60 * 60 => 'ماه',
                    24 * 60 * 60 => 'روز',
                    60 * 60 => 'ساعت',
                    60 => 'دقیقه',
                    1 => 'ثانیه'
                );
                foreach ($a as $secs => $str) {
                    $d = $ttime / $secs;
                    if ($d >= 1) {
                        $r = round($d);
                        echo $r . ' ' . $str . ($r > 1 ? ' ' : '');
                    }
                }
                echo ' قبل)</td>';

                echo '<td>' . $transaction['email'] . '</td>';
                echo '<td>' . $transaction['cost'] . ' ریال</td>';
                echo '<td>' . $transaction['transid'] . '</td>';
                echo '<td>';

                if ($transaction['status'] == "success") {
                    echo '<b style="color:#0C9F55">موفقیت آمیز</b>';
                } else {
                    echo '<b style="color:#f00">انجام نشده</b>';
                }
                echo '</td></tr>';

            }
        }
        echo '</tbody>
		</table>
        <br>';

        $page_links = paginate_links(array(
            'base' => add_query_arg('pagenum', '%#%'),
            'format' => '',
            'prev_text' => __('&laquo;', 'aag'),
            'next_text' => __('&raquo;', 'aag'),
            'total' => $num_of_pages,
            'current' => $pagenum
        ));

        if ($page_links) {
            echo '<center><div class="tablenav"><div class="tablenav-pages"  style="float:none; margin: 1em 0">' . $page_links . '</div></div>
		</center>';
        }
        echo '<br>
		<hr>
	</div>';
    }


    function idpay_cf7_admin_table()
    {
        if (!current_user_can("manage_options")) {
            wp_die(__("You do not have sufficient permissions to access this page."));
        }

        if (isset($_POST['update'])) {

            $options['api_key'] = sanitize_text_field($_POST['api_key']);
            $options['return-page-id'] = (intval($_POST['return-page-id']));
            $options['return'] = get_page_link(intval($_POST['return-page-id']));
            $options['success_massage'] = wp_filter_post_kses($_POST['success_massage']);
            $options['failed_massage'] = wp_filter_post_kses($_POST['failed_massage']);

            if (!empty($_POST['sandbox']))
                $options['sandbox'] = 1;
            else
                $options['sandbox'] = 0;

            update_option("idpay_cf7_options", $options);

            echo "<br><div class='updated'><p><strong>";
            _e("Settings Updated.");
            echo "</strong></p></div>";
        }

        $options = get_option('idpay_cf7_options');
        foreach ($options as $k => $v) {
            $value[$k] = $v;
        }

        $success_massage = (!empty($value['success_massage']) ? $value['success_massage'] : 'پرداخت شما با موفقیت انجام شد. کد رهگیری: {track_id}');
        $failed_massage = (!empty($value['failed_massage']) ? $value['failed_massage'] : 'پرداخت شما ناموفق بوده است. لطفا مجددا تلاش نمایید یا در صورت بروز اشکال با مدیر سایت تماس بگیرید.');
        $return_page_id = (!empty($value['return-page-id']) ? $value['return-page-id'] : 0);
        $api_key = (!empty($value['api_key']) ? $value['api_key'] : "");
        if ($value['sandbox'] == 1) {
            $checked = 'checked';
        } else {
            $checked = '';
        }

        echo "<h2>Contact Form 7 - IDPay Gateway Settings</h2>";
        echo "<table width='100%'><tr><td>" . '<form method="post" enctype="multipart/form-data">';
        echo '<table id=idpay_main_setting_table""> 
<tr>
		  <td><b>API KEY</b></td>
            <td>
            <input type="input" name="api_key" value="' . $api_key . '">
            </td>
          </tr>

         <tr>
		  <td><b>آزمایشگاه</b></td>
            <td>
            <input type="checkbox" name="sandbox" value="1" ' . $checked . '>
            </td>
          </tr>

          <tr>
            <td><b>صفحه بازگشت از تراکنش :</b></td>
            <td>
            ' . wp_dropdown_pages([
                'depth' => 0,
                'child_of' => 0,
                'selected' => $return_page_id,
                'echo' => 0,
                'name' => 'return-page-id',
                'id' => null,
                'class' => null,
                'show_option_none' => null,
                'show_option_no_change' => null,
                'option_none_value' => null
            ]) . '<br>کد [idpay_cf7_result] را در صفحه انتخابی قرار دهید
            </td>
         </tr>
		  <tr>
            <td><b>پیام تراکنش موفق :</b></td>
            <td>
			<textarea name="success_massage" dir="auto">' . $success_massage . '</textarea>
			<br>
متن پیامی که می خواهید بعد از پرداخت موفق به کاربر نمایش دهید را وارد کنید. همچنین می توانید از شورت کدهای {order_id} برای نمایش شماره سفارش و {track_id} برای نمایش کد رهگیری آیدی پی استفاده نمایید.
            </td>
          </tr>
          <tr></tr>
           <tr>
            <td><b>پیام تراکنش ناموفق :</b></td>
            <td>
			<textarea name="failed_massage" dir="auto">' . $failed_massage . '</textarea>
			<br>
متن پیامی که می خواهید بعد از پرداخت ناموفق به کاربر نمایش دهید را وارد کنید. همچنین می توانید از شورت کدهای {order_id} برای نمایش شماره سفارش و {track_id} برای نمایش کد رهگیری آیدی پی استفاده نمایید.
            </tr>
		   <tr>
          <td>
          <input type="submit" name="btn2" class="button-primary" style="font-size: 17px;line-height: 28px;height: 32px;float: right;" value="ذخیره">
          </td>
          </tr>
        </table>
		<input type="hidden" name="update">
		</form>		
		</td></tr></table>';
    }
}
?>