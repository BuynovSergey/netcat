<?php

/**
 * Входящие параметры:
 *  - start
 *
 * @global $nc_core
 * @global $catalogue
 * @global $db
 */
header("Content-type: text/xml");

//$NETCAT_FOLDER = realpath("../../../");
$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
require_once("$NETCAT_FOLDER/vars.inc.php");
//require ($INCLUDE_FOLDER."index.php");
require_once ($ROOT_FOLDER."connect_io.php");
$nc_core->modules->load_env();

nc_set_http_response_code(200);

ob_clean(); // до "<?xml" ничего не должно быть
print '<?xml version="1.0" encoding="UTF-8"?>';

$url_prefix = $nc_core->catalogue->get_scheme_by_host_name($_SERVER['HTTP_HOST']) . "://$_SERVER[HTTP_HOST]";
$site = $nc_core->catalogue->get_by_host_name($_SERVER['HTTP_HOST']); // never trust a cat
$site_id = $site['Catalogue_ID'];

$start = $nc_core->input->fetch_get("start");
$max_num_urls = nc_search::get_setting('NumberOfEntriesPerSitemap');

if (!strlen($start)) { // если результатов слишком много, выдать sitemapindex
    $num_urls = $db->get_var("SELECT COUNT(*)
                              FROM `Search_Document` 
                             WHERE `Catalogue_ID` = $site_id AND `IncludeInSitemap`=1");
    if ($num_urls > $max_num_urls) {
        $url = "$url_prefix$_SERVER[REQUEST_URI]?start=";
        print '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        for ($i = 0, $last = ceil($num_urls / $max_num_urls); $i < $last; $i++) {
            print "<sitemap><loc>".$url.($i * $max_num_urls)."</loc></sitemap>\n";
        }
        print "</sitemapindex>\n";
        die;
    }
}

$start = (int) $start;

$entries = $db->get_results("SELECT `Path`, 
                                    `SitemapChangefreq`, 
                                    `SitemapPriority`,
                                    DATE_FORMAT(`LastModified`, '%Y-%m-%dT%T') AS `LastModified`
                               FROM `Search_Document`
                              WHERE `Catalogue_ID` = $site_id AND `IncludeInSitemap`=1
                              LIMIT $max_num_urls OFFSET $start",
                ARRAY_A);

$tz = date("P");
if ($tz == "P") {
    $tz = "";
}

// url encode non-ascii paths
$urlencode = function ($matches) {
    return urlencode($matches[0]);
};

// output the sitemap
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', "\n";

if ($entries) {
    foreach ($entries as $url) {
        $path = $url["Path"];
        if (!$nc_core->NC_UNICODE) {
            $path = preg_replace_callback('![^a-zA-Z0-9\./?&=~_-]+!', $urlencode, $url["Path"]);
        }
        $path = htmlspecialchars($url["Path"]);

        echo "<url>",
             "<loc>", $url_prefix, $path, "</loc>",
             "<lastmod>", $url["LastModified"], $tz, "</lastmod>", //2012-12-23T18:00:15+03:00
             "<changefreq>", $url["SitemapChangefreq"], "</changefreq>",
             // 0.5 is the default value for <priority>
             ($url["SitemapPriority"] != "0.5" ? "<priority>$url[SitemapPriority]</priority>" : ""),
             "</url>\n";
    }
}

print "</urlset>\n";