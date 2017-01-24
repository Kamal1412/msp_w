<?php
/**
 * Template for displaying Credit card payment form
 *
 * @author ThimPress
 */
defined( 'ABSPATH' ) || exit();
?>
<?php echo $this->get_description(); ?>
<div id="learn-press-stripe-form">
    <p class="learn-press-form-row">
        <label><?php _e( 'Card Number <span class="required">*</span>', 'learnpress-stripe' ); ?></label>
        <input type="text" name="learn-press-stripe[card_number]" id="learn-press-stripe-payment-card-number" maxlength="19" value="" autocomplete="cc-number" placeholder="•••• •••• •••• ••••"/>
    </p>
    <p class="learn-press-form-row">
        <label><?php _e( 'Expiry (MM/YY) <span class="required">*</span>', 'learnpress-stripe' ); ?></label>
        <select class="learn-press-stripe-expiry">
            <option value=1>01</option>
            <option value=2>02</option>
            <option value=3>03</option>
            <option value=4>04</option>
            <option value=5>05</option>
            <option value=6>06</option>
            <option value=7>07</option>
            <option value=8>08</option>
            <option value=9>09</option>
            <option value=10>10</option>
            <option value=11>11</option>
            <option value=12>12</option>
        </select>
        <select class="learn-press-stripe-expiry">
            <?php for ( $a = (int) date( 'Y', time() ), $b = $a + 10, $i = $a; $i < $b; $i ++ ) { ?>
                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
            <?php } ?>
        </select>
        <input type="hidden" name="learn-press-stripe[card_expiry]" autocomplete="cc-exp" id="learn-press-stripe-payment-card-expiry" value="1/<?php echo esc_attr( date( 'Y', time() ) ) ?>" placeholder="•• / ••••"/>
    </p>
    <p class="learn-press-form-row">
        <label><?php _e( 'Card Code <span class="required">*</span>', 'learnpress-stripe' ); ?></label>
        <input type="text" name="learn-press-stripe[card_code]" id="learn-press-stripe-payment-card-code" value="" placeholder="••••" />
    </p>
</div>
<?php if ( $this->settings['test_mode'] == 'yes' ): ?>
    <?php learn_press_display_message( esc_html( 'Test mode is enabled. You can use the card number 4242424242424242 with any CVC and a valid expiration date for testing purpose.', 'learnpress-stripe' ), 'error' ); ?></p>
<?php endif; ?>
