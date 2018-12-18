<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<form>
    <div>
        <input name='idpay_enable' id='idpay_active' value='1'
               type='checkbox' <?php echo $checked ?>>
        <label for='idpay_active'><?php _e( 'Enable Payment through IDPay gateway', 'contact-form-7-idpay' ) ?></label>
    </div>
    <table>
        <tr>
            <td><?php _e( 'Predefined amount', 'contact-form-7-idpay' ) ?></td>
            <td><input type='text' name='idpay_amount'
                       value='<?php echo $amount ?>'>
            </td>
            <td><?php _e( 'Rial', 'contact-form-7-idpay' ) ?></td>
        </tr>
    </table>

    <div>
        <p>
			<?php _e( 'You can choose fields below in your form. If the predefined amount is not empty, field <code>idpay_amount</code> will be ignored. On the other hand, if you want your customer to enter an arbitrary amount, choose <code>idpay_amount</code> in your form and clear the predefined amount.', 'contact-form-7-idpay' ) ?>
        </p>
        <p>
			<?php _e( "Also check your wp-config.php file and look for this line of code: <code>define('WPCF7_LOAD_JS', false)</code>. If there is not such a line, please put it into your wp-config.file.", 'contact-form-7-idpay' ) ?>
        </p>
    </div>

    <table class="widefat">
        <thead>
        <tr>
            <th><?php _e( 'Field', 'contact-form-7-idpay' ) ?></th>
            <th><?php _e( 'Description', 'contact-form-7-idpay' ) ?></th>
            <th><?php _e( 'Example', 'contact-form-7-idpay' ) ?></th>
        </tr>
        </thead>

        <tbody>
        <tr>
            <td>idpay_amount</td>
            <td><?php _e( 'An arbitrary amount', 'contact-form-7-idpay' ) ?></td>
            <td><code>[text idpay_amount]</code>
            </td>
        </tr>
        <tr>
            <td>idpay_description</td>
            <td><?php _e( 'Payment description', 'contact-form-7-idpay' ) ?></td>
            <td><code>[text idpay_description]</code></td>
        </tr>
        <tr>
            <td>idpay_phone</td>
            <td><?php _e( 'Phone number field', 'contact-form-7-idpay' ) ?></td>
            <td><code>[text idpay_phone]</code>
            </td>
        </tr>

        </tbody>

    </table>
    <input type='hidden' name='post' value='<?php echo $post_id ?>'>

</form>
