<?php
    $add_to_calendar   = apply_filters('dy_add_to_calendar', null);
    $discount          = 0;
    $pax_discount      = 0;
    $free              = 0;
    $pax_free          = 0;
    $start_free        = 0;
    $start_discount    = 0;
    $price_regular     = dy_utilities::get_price_regular();
    $price_discount    = dy_utilities::get_price_discount();
    $payment           = 0;
    $deposit           = 25;
    $total             = dy_utilities::total();
    $payment_amount    = $total;
    $pax_regular       = intval(sanitize_text_field($_GET['pax_regular']));
    $participants      = $pax_regular;
    $deposit_label     = '';

    if (package_field('package_free') > 0 && isset($_GET['pax_free'])) {
        $pax_free = intval(sanitize_text_field($_GET['pax_free']));
        $free     = package_field('package_free');
    }

    if (package_field('package_discount') > 0 && isset($_GET['pax_discount'])) {
        $pax_discount   = intval(sanitize_text_field($_GET['pax_discount']));
        $discount       = package_field('package_discount');
        if (package_field('package_free') > 0) {
            $start_discount = $free + 1;
        }
    }

    if ($pax_free > 0) {
        $participants += $pax_free;
    }

    if ($pax_discount > 0) {
        $participants += $pax_discount;
    }

    if (package_field('package_payment') == 1) {
        $payment            = 1;
        $deposit            = dy_utilities::get_deposit();
        $payment_amount     = dy_utilities::total() * ($deposit * 0.01);
        $outstanding_amount = floatval($total) - $payment_amount;

        $outstanding_label = sprintf(
            '%s %s<span class="dy_calc dy_calc_outstanding">%s</span> %s',
            esc_html__('Outstanding Balance', 'dynamicpackages'),
            currency_symbol(),
            money($outstanding_amount),
            currency_name()
        );

        $deposit_label = sprintf(
            '%s $<span class="dy_calc dy_calc_total">%s</span> %s (%s%%)',
            esc_html__('Deposit', 'dynamicpackages'),
            money($payment_amount),
            currency_name(),
            esc_html($deposit)
        );
    }

?>
<hr/>
<div class="clearfix relative small text-right">
    <a class="pure-button rounded pure-button-bordered bottom-20" href="<?php the_permalink(); ?>">
        <span class="dashicons dashicons-arrow-left"></span>
        <?php echo esc_html__('Go back', 'dynamicpackages'); ?>
    </a>
    <?php do_action('dy_copy_payment_link'); ?>
</div>
<hr/>
<?php do_action('dy_coupon_confirmation'); ?>
<?php do_action('dy_invalid_min_duration'); ?>

<div class="pure-g gutters">
    <div class="pure-u-1 pure-u-md-1-3">
        <div class="bottom-20">
            <?php echo apply_filters('dy_details', false); ?>
            <?php if (isset($add_to_calendar)) : ?>
                <div class="text-center bottom-10"><?php echo $add_to_calendar; ?></div>
            <?php endif; ?>
            <div class="text-center"><?php do_action('dy_whatsapp_button'); ?></div>
        </div>
    </div>

    <div class="pure-u-1 pure-u-md-2-3">
        <?php if (intval($total) > 0) : ?>
            <?php
                // Determine whether to show the "Prices Per Person" column
                $show_price_col = ! wp_is_mobile();
                // Total columns including subtotal and description (2) plus optional price column
                $colspan        = $show_price_col ? 3 : 2;
                // colspan for the Add-ons header: spans description + (price col if shown)
                $addon_colspan  = $show_price_col ? 2 : 1;
            ?>
            <table id="dynamic_table" class="text-center pure-table pure-table-bordered width-100">
                <thead>
                    <tr>
                        <th><?php echo esc_html__('Description', 'dynamicpackages'); ?></th>
                        <?php if ($show_price_col) : ?>
                            <th><?php echo esc_html__('Prices Per Person', 'dynamicpackages'); ?></th>
                        <?php endif; ?>
                        <th><?php echo esc_html__('Subtotal', 'dynamicpackages'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <?php if ($price_discount == 0) : ?>
                            <td>
                                <?php echo esc_html__('Participants', 'dynamicpackages'); ?>:
                                <strong><?php echo esc_html($pax_regular); ?></strong>
                            </td>
                        <?php else : ?>
                            <td>
                                <?php echo esc_html__('Adults', 'dynamicpackages'); ?>:
                                <strong><?php echo esc_html($pax_regular); ?></strong>
                            </td>
                        <?php endif; ?>
                        <?php if ($show_price_col) : ?>
                            <td>$<?php echo money($price_regular); ?></td>
                        <?php endif; ?>
                        <td>$<?php echo money($price_regular * $pax_regular); ?></td>
                    </tr>

                    <?php if ($free > 0 && $pax_free > 0) : ?>
                        <tr>
                            <td>
                                <?php
                                    echo esc_html__('Children', 'dynamicpackages')
                                        . ' ' . esc_html($start_free . ' - ' . $free)
                                        . ' ' . esc_html__('years old', 'dynamicpackages');
                                ?>:
                                <strong><?php echo esc_html($pax_free); ?></strong>
                            </td>
                            <?php if ($show_price_col) : ?>
                                <td>0.00</td>
                            <?php endif; ?>
                            <td>0.00</td>
                        </tr>
                    <?php endif; ?>

                    <?php if ($discount > 0 && $pax_discount > 0) : ?>
                        <tr>
                            <td>
                                <?php
                                    echo esc_html__('Children', 'dynamicpackages')
                                        . ' ' . esc_html($start_discount . ' - ' . $discount)
                                        . ' ' . esc_html__('years old', 'dynamicpackages');
                                ?>:
                                <strong><?php echo esc_html($pax_discount); ?></strong>
                            </td>
                            <?php if ($show_price_col) : ?>
                                <td>$<?php echo money($price_discount); ?></td>
                            <?php endif; ?>
                            <td>$<?php echo money($price_discount * $pax_discount); ?></td>
                        </tr>
                    <?php endif; ?>

                    <tr>
                        <td colspan="<?php echo $colspan; ?>">
                            <p class="small text-left">
                                <strong><?php echo esc_html__('Included', 'dynamicpackages'); ?>:</strong>
                                <?php echo esc_html(dy_utilities::implode_taxo_names('package_included', __('and', 'dynamicpackages'), '✅')); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="<?php echo $colspan; ?>">
                            <p class="small text-left">
                                <strong><?php echo esc_html__('Not Included', 'dynamicpackages'); ?>:</strong>
                                <?php echo esc_html(dy_utilities::implode_taxo_names('package_not_included', __('or', 'dynamicpackages'), '❌')); ?>
                            </p>
                        </td>
                    </tr>
                </tbody>

                <?php if (apply_filters('dy_has_add_ons', null)) : ?>
                    <thead>
                        <tr>
                            <th colspan="<?php echo $addon_colspan; ?>"><?php echo esc_html__('Add-ons', 'dynamicpackages'); ?></th>
                            <th><?php echo esc_html__('Include?', 'dynamicpackages'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php do_action('dy_checkout_items'); ?>
                    </tbody>
                <?php endif; ?>

                <tfoot class="text-center strong">
                    <tr>
                        <td colspan="<?php echo $colspan; ?>">
                            <?php if (dy_validators::validate_coupon()) : ?>
                                <s class="small light text-muted">
                                    <?php
                                        echo sprintf(
                                            esc_html(__('Regular Price %s', 'dynamicpackages')),
                                            esc_html(currency_symbol())
                                        );
                                    ?>
                                    <span class="dy_calc dy_calc_regular">
                                        <?php echo esc_html(money(dy_utilities::total('regular'))); ?>
                                    </span> <?php echo esc_html(currency_name()); ?>
                                </s><br/>
                            <?php endif; ?>

                            <?php echo esc_html__('Total', 'dynamicpackages'); ?>
                            <?php echo esc_html(currency_symbol()); ?><span class="dy_calc dy_calc_amount"><?php echo money(dy_utilities::total()); ?></span> <?php echo esc_html(currency_name()); ?>
                        </td>
                    </tr>
                    <?php if (dy_validators::has_deposit()) : ?>
                        <tr>
                            <td colspan="<?php echo $colspan; ?>"><?php echo $deposit_label; ?></td>
                        </tr>
                    <?php endif; ?>
                </tfoot>
            </table>

            <?php if ($payment == 1 && intval(package_field('package_auto_booking')) == 1) : ?>
                <div class="text-muted large strong text-center bottom-20">
                    <?php echo $outstanding_label; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <hr/>

        <?php do_action('dy_checkout_area'); ?>
    </div>
</div>
