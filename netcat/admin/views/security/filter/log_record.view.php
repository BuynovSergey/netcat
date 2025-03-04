<?php

if (!class_exists('nc_core')) {
    die;
}
$nc_core = nc_core::get_object();

/** @var nc_ui $ui */

// COMMON
/** @var int $site_id */
/** @var string $default_settings_link */
/** @var array $data */

if (!empty($data)) {
    $row = $data[0];
    ?>
    <h2><b><?= NETCAT_SECURITY_INCIDENT_NUMBER ?> <?= $row['Security_FilterLog_ID'] ?></b></h2>

    <h4><b><?= NETCAT_SECURITY_INCIDENT_DATA_TIME ?>:</b></h4>
    <p><?= date("d-m-Y, H:i:s", strtotime($row['Timestamp'])) ?></p>

    <h4><b><?= NETCAT_SECURITY_INCIDENT_TYPE ?>:</b></h4>
    <p><?= $row['FilterType'] ?></p>

    <h4><b><?= NETCAT_SECURITY_INCIDENT_CHECK_TYPE ?>:</b></h4>
    <p>
        <?php
        switch ($row['CheckType']) {
            case 'variable':
                echo NETCAT_SECURITY_INCIDENT_VARILABLE;
                break;

            case 'variable_function':
                echo NETCAT_SECURITY_INCIDENT_VARILABLE_FUNCTION;
                break;

            case 'quote':
                echo NETCAT_SECURITY_INCIDENT_QUOTE;
                break;

            case 'statement':
                echo NETCAT_SECURITY_INCIDENT_STATEMENT;
                break;

            case 'comment':
                echo NETCAT_SECURITY_INCIDENT_COMMENT;
                break;

            case 'dangerous_tag':
                echo NETCAT_SECURITY_INCIDENT_DANGEROUS_TAG;
                break;

            case 'attribute':
                echo NETCAT_SECURITY_INCIDENT_ATTRIBUTE;
                break;

            case 'javascript':
                echo NETCAT_SECURITY_INCIDENT_JAVASCRIPT;
                break;
        }
        ?>
    </p>

    <h4><b><?= NETCAT_SECURITY_INCIDENT_SITE ?>:</b></h4>
    <p><a href='<?= ($nc_core->catalogue->get_by_id((int)$row['Catalogue_ID'], "ncHTTPS") ? "https://" : "http://")
        . $nc_core->catalogue->get_by_id((int)$row['Catalogue_ID'], "Domain") ?>'
                target='_blank'>
            <?= $nc_core->catalogue->get_by_id((int)$row['Catalogue_ID'], "Catalogue_Name") ?>
        </a></p>

    <h4><b><?= NETCAT_SECURITY_FILTER_URL ?>:</b></h4>
    <p><?= $row['URL'] ?></p>

    <h4><b><?= NETCAT_SECURITY_FILTER_REFERER ?>:</b></h4>
    <p><?= $row['Referer'] ?></p>

    <h4><b><?= NETCAT_SECURITY_INCIDENT_POST ?>:</b></h4>
    <?php
        $data = unserialize($row['PostData']);
        if ($data) {
            echo '<table class="nc-security-log-record-post">';
            foreach ($data as $k => $v) {
                echo '<tr><td>' . htmlspecialchars($k) . '</td><td>=</td><td>';
                if (!is_scalar($v)) {
                    $v = var_export($v, true);
                }
                echo htmlspecialchars($v);
                echo '</td></tr>';
            }
            echo '</table>';
        } else {
            echo '<p>—</p>';
        }
    ?>

    <h4><b><?= NETCAT_SECURITY_INCIDENT_BACKTRACE ?>:</b></h4>
    <p>
        <?php
        $backtrace = unserialize($row['Backtrace']);

        $doc_root = realpath($nc_core->DOCUMENT_ROOT);
        $short_path = function($full_path) use ($doc_root) {
            $full_path = realpath($full_path);
            if (strpos($full_path, $doc_root) === 0) {
                return substr($full_path, strlen($doc_root));
            }
            return $full_path;
        };

        echo '<table class="nc-table">';
        echo '<tr>
                <th>#</th>
                <th>File</th>
                <th>Line</th>
                <th>Function</th>
                <th>Arguments</th>
              </tr>';
        foreach ($backtrace as $i => $stack) {
            echo "<tr>";
            echo "<td>$i</td>";
            echo "<td>" . $short_path($stack['file']) . "</td>";
            echo "<td>$stack[line]</td>";
            $fn = nc_array_value($stack, 'class', '') . nc_array_value($stack, 'type', '') . nc_array_value($stack, 'function');
            echo "<td>$fn()</td>";
            $args = nc_array_value($stack, 'args');
            echo "<td>";
            if (is_array($args)) {
                foreach ($args as $k => $v) {
                    echo "$k => " . htmlspecialchars(is_scalar($v) ? $v : var_export($v, true)) . "<br>";
                }
            } else {
                echo '&nbsp;';
            }
            echo "</td>";
            echo "</tr>";
        }
        echo '</table>';
        ?>
    </p>

    <h4><b><?= NETCAT_SECURITY_INCIDENT_IP ?>:</b></h4>
    <p><?= $row['IP'] ?><?php if ($row['ForwardedForIP']): ?>, <?= $row['ForwardedForIP'] ?><?php endif; ?></p>

    <h4><b><?= NETCAT_SECURITY_INCIDENT_SOURCE ?>:</b></h4>
    <p><?= htmlspecialchars($row['ValueSource']) ?></p>

    <h4><b><?= NETCAT_SECURITY_INCIDENT_VALUE ?>:</b></h4>
    <p><?= htmlspecialchars($row['Value']) ?></p>

    <h4><b><?= NETCAT_SECURITY_INCIDENT_CHECKEDSTRING ?>:</b></h4>
    <p>
        <?php
            $value = htmlspecialchars($row['Value']);

            $source = $row['CheckedString'];
            $source = htmlspecialchars($source);
            $source = str_replace($value, "<span class='nc-security-log-record-match'>$value</span>", $source);
        ?>
        <pre class="nc-security-log-record-source"><?= $source ?></pre>
    </p>

    <h4><b><?= NETCAT_SECURITY_INCIDENT_SEND_ADMIN ?>:</b></h4>
    <p>
        <?php
        if ($row['EmailAlertSent']) {
            echo NETCAT_SECURITY_INCIDENT_SEND_ADMIN_YES;
        } elseif (!$nc_core->get_settings('SecurityFilterEmailAlertEnabled')) {
            echo NETCAT_SECURITY_INCIDENT_SEND_ADMIN_NO;
        } else {
            echo NETCAT_SECURITY_INCIDENT_SEND_ADMIN_NO_ALREADY_SENT;
        }
        ?>
    </p>

    <?php
    $query = "SELECT COUNT(*)
                   FROM `Security_FilterLog` 
                   WHERE `Hash` = '" . $row['Hash'] . "'";
    $count_analogue = (int)$nc_core->db->get_var($query) - 1;

    ?>
    <h4><b><?= NETCAT_SECURITY_INCIDENT_COUNT_ANALOGUE ?>:</b></h4>
    <p>
        <a href="<?= $nc_core->ADMIN_PATH . "#security.filter.log.group($row[Hash])" ?>" target="_top"><?= $count_analogue ?></a>
    </p>

    <?php
} else {
    echo NETCAT_SECURITY_LOG_NO_DATA;
}
?>