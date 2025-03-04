<?php

if (!class_exists("nc_core")) {
    die;
}

/** @var string $current_url */
/** @var array $sites */
/** @var string $initial_test_name */
/** @var int $tests_count */
?>

<div class="description"><?= TOOLS_SELFTEST_DESCRIPTION ?></div>

<?php
// Генерирует строку вида {1:0, 2:0} (сайт: счетчик), которая необходима для задания изначального состояния свойства problemsCount в testManager
$sites_js_hashmap_stroke = "{" . implode(
        ",",
        array_map(
            function ($site) {
                return sprintf("%s:%s", $site["Catalogue_ID"], 0);
            },
            $sites
        )
    ) . "}";
?>

<h2><?= TOOLS_SELFTEST_SUBHEADING ?></h2>

<?php if (count($sites) > 1): ?>
    <label>
        <div><?= CONTROL_USER_SELECTSITE ?>:</div>
        <select id="site-select" name="site_id">
            <?php foreach ($sites as $site): ?>
                <option value="<?= $site["Catalogue_ID"] ?>"><?= $site["Catalogue_Name"] ?></option>
            <?php endforeach; ?>
        </select>
    </label>
<?php endif ?>

<div class="status-bar">
    <div class="current-test-wrapper"><span id="current-test-label"><?= $initial_test_name ?></span></div>
    <div id="start-test" class="nc-btn nc--white">
        <svg class="" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 14 14" fill="none">
            <path d="M12.8334 7.00002C12.8334 10.22 10.2201 12.8334 7.00008 12.8334C3.78008 12.8334 1.81425 9.59002 1.81425 9.59002M1.81425 9.59002H4.45092M1.81425 9.59002V12.5067M1.16675 7.00002C1.16675 3.78002 3.75675 1.16669 7.00008 1.16669C10.8909 1.16669 12.8334 4.41002 12.8334 4.41002M12.8334 4.41002V1.49335M12.8334 4.41002H10.2434"
                    stroke="#333333" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <span class="caption"><?= TOOLS_SELFTEST_RUN ?></span></div>
    <div class="problems-summary"></div>
</div>

<div class="test-results">
    <?php if ($sites): ?>
        <?php foreach ($sites as $site): ?>
            <div id="tests-site-<?= $site["Catalogue_ID"] ?>">
                <div class="result-wrapper"></div>
            </div>
        <?php endforeach; ?>
    <?php endif ?>

    <div id="tests-shared">
        <div class="result-wrapper"></div>
    </div>
</div>

<div class="widgets-wrapper">
    <?= $this->view("support_widget") ?>
</div>

<script type="text/javascript" src="<?= nc_core::get_object()->ADMIN_PATH . "js/selftest_manager.js" ?>"></script>

<script>
    (function () {
        <?php $initial_site_id = empty($sites) ? -1 : (int)$sites[0]["Catalogue_ID"] ?>

        const selftestManager = new SelftestManager("<?=$initial_test_name?>", <?= $sites_js_hashmap_stroke?>, <?= $tests_count?>, <?= $initial_site_id ?>);
        const elements = {
            currentTest: $nc("#current-test-label"),
            problemsSummary: $nc(".problems-summary"),
            resultWrapper: $nc("#tests-shared .result-wrapper"),
            testResult: $nc(".test-results"),
        }

        document.addEventListener("DOMContentLoaded", async () => {
            $nc(".widgets-wrapper").fadeIn();
            await runTests();

            $nc("#start-test").on("click", async () => {
                if (selftestManager.isRunning) {
                    return;
                }

                await runTests();
            })

            $nc("#site-select").on("change", function () {
                $nc(`#tests-site-${selftestManager.currentSiteId}`).hide();
                selftestManager.currentSiteId = $(this).find(":selected").val();

                if (!selftestManager.getCompletedTests()) {
                    return;
                }

                updateProblemsCount();
                $nc(`#tests-site-${selftestManager.currentSiteId}`).show();
            })
        })

        async function runTests() {
            beforeTestsRun();

            while (selftestManager.hasTests) {
                try {
                    const { result } = await nc_http_post("<?= $current_url . "start_test"?>", {
                        step: selftestManager.currentStep
                    })

                    if ("error" in result) {
                        selftestManager.skipTest();
                        continue;
                    }

                    selftestManager.processResult(result);
                    updateTemplate();
                } catch (e) {
                    console.error(e)
                    break;
                }
            }

            afterTestsRun();
        }

        function beforeTestsRun() {
            selftestManager.init();
            $nc(".result-wrapper").empty();
            $nc(".current-test-wrapper").fadeIn();
            $nc("#start-test svg").attr("class", "rotating");
            $nc("#start-test .caption").text("<?= TOOLS_SELFTEST_IN_PROCESS ?>");
        }

        function afterTestsRun() {
            selftestManager.shutdown();
            $nc(".status-bar .caption").text("<?= NETCAT_TAB_REFRESH ?>");
            $nc("#start-test svg").attr("class", "");
            $nc("#current-test-label").text("<?= TOOLS_SELFTEST_DONE ?>");
            $nc(".widgets-wrapper").fadeIn();
        }

        function updateTemplate() {
            elements.currentTest.text(selftestManager.currentTestName);
            updateProblemsCount()
            updateResultItems();
        }

        function updateProblemsCount() {
            const problemsCount = selftestManager.getProblemsCount();

            elements.problemsSummary.html(nc_plural_form(problemsCount,
                "<?=TOOLS_SELFTEST_PROBLEM_DETECTED_1 ?>".replace("%s", problemsCount),
                "<?=TOOLS_SELFTEST_PROBLEM_DETECTED_2 ?>".replace("%s", problemsCount),
                "<?=TOOLS_SELFTEST_PROBLEM_DETECTED_3 ?>".replace("%s", problemsCount),
            ))

            elements.problemsSummary.show();
        }

        function updateResultItems() {
            if (selftestManager.isTestShared()) {
                if (selftestManager.currentTestData.error) {
                    return
                }

                elements.resultWrapper.append(getResultItemTemplate(
                    selftestManager.currentTestData.label,
                    selftestManager.currentTestData.description,
                    selftestManager.currentTestData.status,
                    selftestManager.currentTestData.status_i18n
                ));
            } else {
                for (const siteId in selftestManager.currentTestData) {
                    if (selftestManager.currentTestData[siteId].error) {
                        return
                    }

                    $nc(`#tests-site-${siteId} .result-wrapper`).append(getResultItemTemplate(
                        selftestManager.currentTestData[siteId].label,
                        selftestManager.currentTestData[siteId].description,
                        selftestManager.currentTestData[siteId].status,
                        selftestManager.currentTestData[siteId].status_i18n
                    ));
                }
            }

            elements.testResult.show();
            $nc(`#tests-site-${selftestManager.currentSiteId}`).show();
        }

        function getResultItemTemplate(label, description, status, statusi18n) {
            return $nc(`<div class='result-item'>
                            <div class="result-item-header">
                                <div class="result-item-label">${label}</div>
                                <div class="result-item-status status--${status}">${statusi18n}</div>
                            </div>
                            <div class="result-item-body">
                                <div class="result-item-description">${description}</div>
                            </div>
                        </div>`);
        }
    })()
</script>
