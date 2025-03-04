<?php
if (!class_exists("nc_core")) {
    die;
}

global $ADMIN_PATH;


$system_info = nc_get_system_info();
?>

<h2><?= SUPPORT_UPDATES_LABEL ?></h2>
<div class="widgets">
    <?php
    // Виджет техподдержки для облачного сайта
    if (defined("NC_SAAS_SITE") && defined("NC_GET_SITE_INFO_URL")): ?>
        <?php
        $http = new nc_http();

        try {
            $response = $http->make_get_request(NC_GET_SITE_INFO_URL, array("client_id" => nc_core::get_object()->get_copy_id()));
            $saas_site_info = $response["data"] ?: array();
        } catch (nc_http_exception $e) {
            $saas_site_info = array();
        }

        $site_is_blocked = $saas_site_info["blocked"] < date("Y-m-d");
        $site_is_demo = $saas_site_info["demo"];
        $is_less_week = isset($saas_site_info["payment"]["days_left_before_block"]) &&
            $saas_site_info["payment"]["days_left_before_block"] <= 7;
        $widget_classname = !$saas_site_info || $site_is_demo || $site_is_blocked || $is_less_week ? "inactive" : "active";
        $support_url = defined("NC_SUPPORT_URL") ? NC_SUPPORT_URL : "https://netcat.ru/support/";
        ?>

        <div class="widget saas-site-info <?= $widget_classname ?>">
            <div class="widget-header"><?= SAAS_SUPPORT_WIDGET_CAPTION ?></div>
            <div class="widget-body">
                <?php if (empty($saas_site_info)): ?>
                    <div><?= SAAS_SUPPORT_WIDGET_CANNOT_GET_SITE_INFO ?></div>
                    <a href="<?= $support_url ?>" target="_blank"><?= SUPPORT_LABEL ?></a>

                <?php else: ?>
                    <div class="state-wrapper">
                        <?php
                        $site_state_text = "";

                        if ($site_is_demo && $site_is_blocked) {
                            $site_state_text = SAAS_SUPPORT_WIDGET_TRIAL_MODE_ENDS;
                        } elseif (!$site_is_demo && $site_is_blocked) {
                            $site_state_text = SAAS_SUPPORT_WIDGET_SITE_IS_DISABLED_NOTICE;
                        } else {
                            $site_state_text = CONTROL_CONTENT_CATALOUGE_ONESITE . " ";
                            if ($site_is_demo) {
                                $site_state_text .= SAAS_SUPPORT_WIDGET_IN_TRIAL_MODE;
                            } elseif (!$site_is_blocked) {
                                $site_state_text .= SUPPORT_PAYED_CAPTION;
                            }
                        }
                        ?>

                        <div class="state-col1 status<?= $site_is_demo || $site_is_blocked ? " ends-soon" :
                            "" ?>"><?= $site_state_text ?></div>

                        <?php if (!$site_is_blocked): ?>
                            <div class="state-col2">
                                <?php
                                $before_block_date = NETCAT_MODERATION_ADD_BLOCK_BEFORE . " " .
                                    date("d.m.y", strtotime($saas_site_info["blocked"]));
                                $before_block_text = $before_block_date;

                                if (!$site_is_demo && $is_less_week) {
                                    $nc_core = nc_core::get_object();
                                    $before_block_text = $nc_core->lang->get_numerical_inclination(
                                        $saas_site_info["payment"]["days_left_before_block"],
                                        explode("/", SUPPORT_REMAINED_CAPTION)
                                    );

                                    $before_block_text = sprintf(
                                        $before_block_text,
                                        $saas_site_info["payment"]["days_left_before_block"]

                                    );
                                }
                                echo $before_block_text;
                                ?>
                            </div>
                        <?php endif ?>
                    </div>

                    <?php if (isset($saas_site_info["payment"]["url"])): ?>
                        <?php
                        $period_text = isset($saas_site_info["payment"]["period_value"]) ?
                            NETCAT_MODERATION_PREPOSITION_AT . " {$saas_site_info["payment"]["period_value"]}" : "";

                        $price = $saas_site_info["payment"]["discount_price"] ?: $saas_site_info["payment"]["price"];
                        $payment_text = $period_text . " " . NETCAT_MODERATION_PREPOSITION_BEHIND . " $price " .
                            ($saas_site_info["payment"]["currency"] ?: "₽");
                        ?>

                        <a class="nc-btn" href="//<?= $saas_site_info["payment"]["url"] ?>"
                                target="_blank"><?= SAAS_SUPPORT_WIDGET_PAYMENT_RENEWAL_CAPTION . " " . $payment_text ?></a>
                    <?php endif ?>

                    <div class="notice">
                        <?php
                        $notice_text = "";

                        if ($site_is_blocked || $site_is_demo || $is_less_week) {
                            $notice_text = sprintf(
                                SAAS_SUPPORT_WIDGET_PAYMENT_NOTICE_BEFORE_SITE_DELETE,
                                date("d.m.y", strtotime($saas_site_info["deleted"]))
                            );
                        } else {
                            // Текст о наличии скидки, если имеется
                            if (isset($saas_site_info["payment"]["discount_price"]) && $saas_site_info["payment"]["discount_price"] &&
                                $saas_site_info["payment"]["discount_price"] < $saas_site_info["payment"]["price"]
                            ) {
                                $discount_percent = ($saas_site_info["payment"]["discount_price"] - $saas_site_info["payment"]["price"]) /
                                    $saas_site_info["payment"]["price"] * 100;
                                $discount_percent = abs(round($discount_percent));

                                $notice_text = sprintf("Получите скидку %s%% при оплате", $discount_percent);
                            }
                        }

                        echo $notice_text;
                        ?>

                    </div>

                <?php endif ?>
            </div>
        </div>

    <?php
    // Виджет техподдержки автономного сайта
    else: ?>
        <?php
        $support_info = nc_get_support_info();
        $license_link = "https://netcat.ru/support/moya-litsenziya/?search_word={$system_info['product_number']}";
        $license_status = $support_info ? $support_info["is_active"] ? SUPPORT_STATUS_ACTIVE : SUPPORT_STATUS_INACTIVE :
            SUPPORT_STATUS_INCORRECT_LICENSE;
        $timestamp = time();
        $is_less_week = $support_info && $support_info["discount_days_left"] != 0 && $support_info["discount_days_left"] <= 7;
        $widget_classname = !$support_info || !$support_info["is_active"] ? "inactive" : "active";

        if ($is_less_week && $support_info && $support_info["is_active"]) {
            $license_status = sprintf(
                SUPPORT_STATUS_ENDS_CLOSE,
                $support_info["discount_days_left"],
                nc_core::get_object()->lang->get_numerical_inclination(
                    $support_info["discount_days_left"],
                    array(SUPPORT_DAY_DECL_1, SUPPORT_DAY_DECL_2, SUPPORT_DAY_DECL_3)
                )
            );
        }
        ?>

        <div class="widget support-info <?= $widget_classname ?>">
            <div class="widget-header"><?= SUPPORT_LABEL ?></div>
            <div class="widget-body">
                <div class="state-wrapper">
                    <div class="state-col1 status<?= $support_info && $support_info["is_active"] && $is_less_week ? " ends-soon" :
                        "" ?>"><?= $license_status ?></div>
                    <?php if ($support_info): ?>
                        <div class="state-col2"><?= sprintf(
                                "%s %s",
                                $support_info["is_active"] ? SUPPORT_PAID_UNDER : SUPPORT_STATUS_OVER,
                                $support_info["support_ends_date_formatted"]
                            ) ?></div>
                    <?php endif ?>
                </div>

                <?php if ($support_info): ?>
                    <a class="nc-btn" target="_blank"
                            href=" <?= $license_link ?>"><?= sprintf(
                            SUPPORT_PROLONGATION,
                            $support_info["formatted_full_price"]
                        ) ?></a>
                <?php else: ?>
                    <div class="notice"><?= SUPPORT_INCORRECT_NOTICE ?></div>
                    <a class="nc-btn" href="mailto:info@netcat.ru"><?= SUPPORT_SEND_MESSAGE ?></a>
                <?php endif ?>
            </div>

            <?php if ($support_info && $is_less_week): ?>
                <div class="widget-footer">
                    <?= sprintf(
                        SUPPORT_PROLONGATION_PRICE_AFTER_DISCOUNTS,
                        $support_info["discount_date_formatted"],
                        number_format(
                            $support_info["prolongation_price"] +
                            $support_info["discount"],
                            0,
                            " ",
                            " "
                        ) . " ₽"
                    ) ?>.
                    <a target="_blank" href="<?= $license_link ?>"><?= SUPPORT_MORE_DETAILS ?></a>
                </div>
            <?php endif ?>
        </div>
    <?php endif ?>

    <?php $has_patch = $system_info["actual_version"] ?>

    <div class="widget license-info <?= $system_info["actual_version"] ? "inactive" : "active" ?>">
        <div class="widget-header"><?= SUPPORT_CURRENT_NETCAT_VERSION ?></div>
        <div class="widget-body">
            <div class="state-wrapper">
                <div class="state-col1"><?= sprintf(
                        "%s %s",
                        $system_info["system_name"],
                        $system_info["system_version"]
                    ) ?></div>

                <?php if ($has_patch): ?>
                    <div class="state-col2"><?= SUPPORT_OUTDATED_NETCAT_VERSION ?></div>
                <?php endif ?>
            </div>

            <?php if ($has_patch): ?>
                <a class="nc-btn" href="<?= $ADMIN_PATH ?>patch/">
                    <?php if ($support_info && $support_info["is_active"]) {
                        echo SUPPORT_UPDATE_SYSTEM_FREE;
                    } elseif ($support_info && $is_less_week) {
                        echo sprintf(SUPPORT_UPDATE_SYSTEM_FOR_MONEY, $support_info["formatted_discount_price"]);
                    } elseif ($support_info && !$is_less_week) {
                        echo sprintf(SUPPORT_UPDATE_SYSTEM_FOR_MONEY, $support_info["formatted_full_price"]);
                    } else {
                        echo SUPPORT_UPDATE_SYSTEM;
                    }
                    ?>
                </a>
            <?php endif ?>
        </div>

        <div class="widget-footer">
            <?php if (!$has_patch): ?>
                <div class="notice"><?= SUPPORT_CURRENT_NETCAT_VERSION_ACTUAL ?></div>
            <?php endif ?>
        </div>
    </div>
</div>
