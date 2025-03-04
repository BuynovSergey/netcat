<?php

global $ADMIN_FOLDER;
$NETCAT_FOLDER = realpath(dirname(__FILE__) . "/../../../../") . DIRECTORY_SEPARATOR;

require_once $NETCAT_FOLDER . "vars.inc.php";
require_once $ADMIN_FOLDER . "function.inc.php";

$selftest = new nc_selftest();
$sites = array_values(nc_core::get_object()->catalogue->get_enabled_sites());
$initial_test_name = $selftest->get_current_test();
$tests_count = $selftest->get_total_tests_count();

// Генерирует строку вида {1:0, 2:0} (сайт: счетчик), которая необходима для задания изначального состояния свойства problemsCount в testManager
$sites_js_hashmap_stroke = "{" . implode(",", array_map(function ($site) {
        return sprintf("%s:%s", $site["Catalogue_ID"], 0);
    }, $sites)) . "}";
?>

<div class="nc-selftest-widget nc-bg-lighten">
    <div class="nc-widget-header nc-bg-dark">
        <i class="nc-icon nc--monitor"></i>
        <div class="label"><?= TOOLS_SELFTEST ?></div>
    </div>

    <div class="nc-widget-body">
        <div class="problems-summary"></div>
        <div class="nc-widget-content"></div>
        <a href="<?= nc_core::get_object()->ADMIN_PATH ?>/#selftest" target="_top" style="display:none;" class="nc-btn">Все проблемы</a>
    </div>
</div>

<script type="text/javascript" src="<?= nc_core::get_object()->ADMIN_PATH . "js/selftest_manager.js" ?>"></script>
<script>
    <?php $initial_site_id = empty($sites) ? -1 : (int)$sites[0]["Catalogue_ID"] ?>

    (function () {
        const selftestManager = new SelftestManager("<?=$initial_test_name?>", <?= $sites_js_hashmap_stroke?>, <?= $tests_count?>, <?= $initial_site_id ?>);
        const RENDER_RESULTS_LIMIT = 7;
        const elements = {
            content: $nc(".nc-widget-body"),
            problemsSummary: $nc(".problems-summary"),
            resultWrapper: $nc(".nc-widget-content")
        }

        let i = 0;

        document.addEventListener("DOMContentLoaded", async () => {
            while (selftestManager.hasTests) {
                try {
                    const { result } = await nc_http_post("/netcat/action.php?ctrl=selftest&action=start_test", {
                        step: selftestManager.currentStep
                    })

                    if ("error" in result) {
                        selftestManager.skipTest();
                        continue;
                    }
                    selftestManager.processResult(result);

                    if (i < RENDER_RESULTS_LIMIT) {
                        updateTemplate();
                    }

                    updateProblemsCount();
                } catch (e) {
                    console.error(e)
                    break;
                } finally {
                    i++;
                }
            }

            afterTestsRun();
        })

        function afterTestsRun() {
            if (selftestManager.getProblemsCount(true) === 0) {
                elements.problemsSummary.text("<?= mb_strtoupper(TOOLS_SELFTEST_HAS_NO_PROBLEMS) ?>");
                elements.resultWrapper.text("<?=TOOLS_SELF_TEST_ALL_WORKS_CLEAN?>");
            } else {
                if (selftestManager.getProblemsCount(true) > 4) {
                    elements.resultWrapper.append(getResultItemTemplate("..."));
                }

                $nc(".nc-btn").fadeIn();
            }
        }

        function updateTemplate() {
            if (selftestManager.isTestShared()) {
                if (selftestManager.currentTestData.status === "recommended"
                    || selftestManager.currentTestData.passed === true
                    || "error" in selftestManager.currentTestData) {
                    return
                }

                elements.resultWrapper.append(getResultItemTemplate(selftestManager.currentTestData.short_description));
            } else {
                if (selftestManager.currentTestData[selftestManager.currentSiteId].status === "recommended"
                    || selftestManager.currentTestData[selftestManager.currentSiteId].passed === true
                    || "error" in selftestManager.currentTestData[selftestManager.currentSiteId]) {
                    return
                }

                elements.resultWrapper.append(getResultItemTemplate(selftestManager.currentTestData[selftestManager.currentSiteId].short_description));
            }
        }

        function getResultItemTemplate(description) {
            return $nc(`<div class='result-item'>
                            <div class="result-item-body">${description}</div>
                        </div>`);
        }

        function updateProblemsCount() {
            const problemsCount = selftestManager.getProblemsCount(true);

            if (problemsCount === 0) {
                return;
            }

            elements.problemsSummary.html(nc_plural_form(problemsCount,
                "<?=TOOLS_SELFTEST_PROBLEM_DETECTED_4 ?>".replace("%s", problemsCount),
                "<?=TOOLS_SELFTEST_PROBLEM_DETECTED_5 ?>".replace("%s", problemsCount),
                "<?=TOOLS_SELFTEST_PROBLEM_DETECTED_6 ?>".replace("%s", problemsCount),
            ))

            elements.problemsSummary.show();
        }
    })()

</script>
