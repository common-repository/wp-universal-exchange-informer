<?php

/*
Plugin Name: WP Universal Exchange Informer
Plugin URI: http://cyber-notes.net
Description: Exchange rate informer for Wordpress
Version: 0.5.3
Author: Santiaga
Author URI: http://cyber-notes.net
License: GPLv2 or later
*/

/*

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

/* Global vars */
global $wpdb;
global $uci_db_version;
global $uci_nbm_table;
global $uci_cbr_table;
global $uci_nbu_table;
global $uci_cbu_table;
global $uci_nbk_table;
global $uci_cba_table;
global $uci_nbg_table;
global $uci_nbb_table;
global $uci_widgets_table;

$uci_nbm_table=$wpdb->prefix."uci_nbm_rates";
$uci_cbr_table=$wpdb->prefix."uci_cbr_rates";
$uci_nbu_table=$wpdb->prefix."uci_nbu_rates";
$uci_cbu_table=$wpdb->prefix."uci_cbu_rates";
$uci_nbk_table=$wpdb->prefix."uci_nbk_rates";
$uci_cba_table=$wpdb->prefix."uci_cba_rates";
$uci_nbg_table=$wpdb->prefix."uci_nbg_rates";
$uci_nbb_table=$wpdb->prefix."uci_nbb_rates";
$uci_widgets_table=$wpdb->prefix."uci_widgets";
$uci_db_version="0.5";

/* Plugin activation */
register_activation_hook(__FILE__, 'uci_plugin_activate');

function uci_plugin_activate()
{
    global $wpdb;
    global $uci_nbm_table;
    global $uci_cbr_table;
    global $uci_nbu_table;
    global $uci_cbu_table;
    global $uci_nbk_table;
    global $uci_cba_table;
    global $uci_nbg_table;
    global $uci_nbb_table;
    global $uci_widgets_table;
    global $uci_db_version;
    $installed_ver=get_option("uci_db_version");
    $charset_collate=$wpdb->get_charset_collate();

    require_once(ABSPATH.'wp-admin/includes/upgrade.php');

    /* Bank tables variables array */
    $bank_tables=array($uci_nbm_table,$uci_cbr_table,$uci_nbu_table,$uci_cbu_table,$uci_nbk_table,$uci_cba_table,$uci_nbg_table,$uci_nbb_table);
    /* Create bank tables */
    foreach ($bank_tables as $bank_table) {
        if ($wpdb->get_var("SHOW TABLES LIKE `".$bank_table."`")!=$bank_table || $installed_ver!=$uci_db_version) {
            $sql="CREATE TABLE `".$bank_table."` (
				`id` mediumint(3) NOT NULL AUTO_INCREMENT,
				`num` VARCHAR(3) DEFAULT '' NOT NULL,
				`char` VARCHAR(3) DEFAULT '' NOT NULL,
				`nominal` VARCHAR(6) DEFAULT '' NOT NULL,
				`value` VARCHAR(12) DEFAULT '' NOT NULL,
				`dif` VARCHAR(12) DEFAULT '' NOT NULL,
				UNIQUE KEY `id` (`id`)
			) ".$charset_collate.";";
            dbDelta($sql);
        }
    }

    /* Create widgets table */
    if ($wpdb->get_var("SHOW TABLES LIKE `".$uci_widgets_table."`")!=$uci_widgets_table || $installed_ver!=$uci_db_version) {
        $sql4="CREATE TABLE `".$uci_widgets_table."` (
			`id` mediumint(3) NOT NULL AUTO_INCREMENT,
			`name` VARCHAR(255) DEFAULT '' NOT NULL,
			`bank` VARCHAR(3) DEFAULT '' NOT NULL,
			`currency` VARCHAR(255) DEFAULT '' NOT NULL,
			UNIQUE KEY `id` (`id`)
		) ".$charset_collate.";";
        dbDelta($sql4);
    }

    /* Update plugin options */
    update_option('wp_uci_plurl', plugins_url().'/'.basename(dirname(__FILE__)));
    update_option("uci_db_version", $uci_db_version);
    update_option('wp_uci_nbm_date', '');
    update_option('wp_uci_cbr_date', '');
    update_option('wp_uci_nbu_date', '');
    update_option('wp_uci_cbu_date', '');
    update_option('wp_uci_nbk_date', '');
    update_option('wp_uci_cba_date', '');
    update_option('wp_uci_nbg_date', '');
    update_option('wp_uci_nbb_date', '');

    /* Check for allow_url_fopen */
    if (ini_get('allow_url_fopen')) {
        update_option('wp_uci_allow_urls', 'on');
    } else {
        update_option('wp_uci_allow_urls', 'off');
    }
    /* Check for CURL */
    if (function_exists("curl_version")) {
        update_option('wp_uci_curl', 'on');
    } else {
        update_option('wp_uci_curl', 'off');
    }
    /* Check for SOAP */
    if (class_exists("SOAPClient")) {
        update_option('wp_uci_soap', 'on');
    } else {
        update_option('wp_uci_soap', 'off');
    }

    /* Get rates on activation */
    uci_get_rates();
}

/* Plugin deactivation */
register_deactivation_hook(__FILE__, 'uci_plugin_deactivation');

function uci_plugin_deactivation()
{
    update_option('wp_uci_nbm_date', '');
    update_option('wp_uci_cbr_date', '');
    update_option('wp_uci_nbu_date', '');
    update_option('wp_uci_cbu_date', '');
    update_option('wp_uci_nbk_date', '');
    update_option('wp_uci_cba_date', '');
    update_option('wp_uci_nbg_date', '');
    update_option('wp_uci_nbb_date', '');
}

/* Localization */
add_action('plugins_loaded', 'wpuci_text_domain', 1);

function wpuci_text_domain()
{
    load_plugin_textdomain('wp-universal-exchange-informer', false, dirname(plugin_basename(__FILE__)).'/lang/');
}

/* Admin Interface */
if (is_admin() && (!defined('DOING_AJAX') || !DOING_AJAX)) {
    /* Add menu to options sidebar */
    add_action('admin_menu', 'wp_uci_menu');

    function wp_uci_menu()
    {
        /* Add new submenu */
        add_options_page('WP Universal Exchange Informer', 'WP Universal Exchange Informer', 'manage_options', 'wp_uci', 'uci_options_page');
    }

    /* Options page */
    function uci_options_page()
    {
        global $wpdb;
        global $uci_widgets_table;

        /* Check for modules and settings if were changes */
        /* Check for allow_url_fopen */
        if (ini_get('allow_url_fopen')) {
            update_option('wp_uci_allow_urls', 'on');
        } else {
            update_option('wp_uci_allow_urls', 'off');
        }
        /* Check for CURL */
        if (function_exists("curl_version")) {
            update_option('wp_uci_curl', 'on');
        } else {
            update_option('wp_uci_curl', 'off');
        }
        /* Check for SOAP */
        if (class_exists("SOAPClient")) {
            update_option('wp_uci_soap', 'on');
        } else {
            update_option('wp_uci_soap', 'off');
        }

        $banks=array(
            "cbr"=>"The Central Bank of the Russian Federation",
            "nbu"=>"National bank of Ukraine",
            "nbm"=>"National Bank of Moldova",
            "cbu"=>"The Central Bank of the Republic of Uzbekistan",
            "nbk"=>"National Bank of Kazakhstan",
            "cba"=>"The Central Bank of Armenia",
            "nbg"=>"National Bank Of Georgia",
            "nbb"=>"National Bank of the Republic of Belarus"
        );

        $currencies=array(
            "EUR"=>"978","USD"=>"840","RUB"=>"643","RON"=>"946","UAH"=>"980","MDL"=>"498","AED"=>"784","ALL"=>"008","AMD"=>"051","AUD"=>"036",
            "AZN"=>"944","BGN"=>"975","BYN"=>"933","CAD"=>"124","CHF"=>"756","CNY"=>"156","CZK"=>"203","DKK"=>"208","GBP"=>"826",
            "GEL"=>"981","HKD"=>"344","HRK"=>"191","HUF"=>"348","ILS"=>"376","INR"=>"356","ISK"=>"352","JPY"=>"392","KGS"=>"417",
            "KRW"=>"410","KWD"=>"414","KZT"=>"398","LTL"=>"440","LVL"=>"428","MKD"=>"807","MYR"=>"458","NOK"=>"578","NZD"=>"554",
            "PLN"=>"985","RSD"=>"941","SEK"=>"752","TJS"=>"972","TMT"=>"934","TRY"=>"949","UZS"=>"860","XDR"=>"960"
        );

        echo "<div class=\"card\">";
        echo "<h3><b>Allow URLs open: ".strtoupper(get_option('wp_uci_allow_urls'))." | CURL: ".strtoupper(get_option('wp_uci_curl'))." | SOAP: ".strtoupper(get_option('wp_uci_soap'))."</b></h3>";
        if (get_option('wp_uci_allow_urls')=="off") {
            echo "<span style=\"color:#FF0000;\"><b>".__('If allow_urls_open are off the plugin will not work (enable allow_urls_open options on yor hosting to use this plugin).', 'wp-universal-exchange-informer')."</b></span><br /><br />";
        }
        if (get_option('wp_uci_soap')=="off") {
            echo "<span  style=\"color:#FF0000;\"><b>".__('If SOAP is off plugin will not work with The Central Bank of Armenia and National Bank Of Georgia, if you need rates from this banks please install PHP-SOAP module on your hosting.', 'wp-universal-exchange-informer')."</b></span>";
        }
        echo "</div>";

        /* Start Options Form */
        echo "
			<span id=\"info_message_plug\"></span>
			<h3>".__('WP Universal Exchange Informer Settings', 'wp-universal-exchange-informer').":</h3>\n
		";
        /* Modal window */
        add_thickbox();
        echo "
			<div id=\"uci_add_box\" style=\"display:none;\">\n
			<h3>".__('New Informer Parameters', 'wp-universal-exchange-informer').":</h3>\n
			<span id=\"info_message\"></span>
		";
        /* Set title */
        echo "<h4>".__('Informer title', 'wp-universal-exchange-informer').":</h4><input type=\"text\" size=\"30\" id=\"uci_informer_title\" name=\"uci_informer_title\" value=\"\" placeholder=\"".__('Set title for informer', 'wp-universal-exchange-informer')."...\" /><br>\n";
        /* Select Bank */
        echo "
			<h4>".__('Select bank to get exchange rates', 'wp-universal-exchange-informer').":</h4>\n
			<select id=\"wp_uci_bank\" name=\"wp_uci_bank\">\n
			<option>".__('Bank not selected', 'wp-universal-exchange-informer')."...</option>\n
		";
        foreach ($banks as $k=>$v) {
            echo "<option value=\"".$k."\">".$v."</option>\n";
        }
        echo "
			</select>\n
			<br>\n
		";
        /* Select Currency */
        echo "
			<h4>".__('Select currency', 'wp-universal-exchange-informer').":</h4>\n
			<div id=\"currency_selectors\">\n
		";
        for ($i=1;$i<=5;$i++) {
            echo "
			<label>Currency ".$i.": </label><select id=\"wp_uci_currency_".$i."\" name=\"wp_uci_currency_".$i."\">\n
			<option value=\"\">".__('Currency not selected', 'wp-universal-exchange-informer')."...</option>\n
		";
            foreach ($currencies as $k=>$v) {
                echo "<option value=\"".$v."\">".$k."</option>\n";
            }
            echo "
			</select>\n
			<br>\n
		";
        }
        echo "
			</div>\n
			<a id=\"add_currency_selector_btn\" href=\"#\">+".__('Add currency selector', 'wp-universal-exchange-informer')."</a>\n
			<input type=\"hidden\" id=\"uci-plurl\" name=\"uci-plurl\" value=\"".get_option('wp_uci_plurl')."\">
			<p class=\"submit\"><input id=\"uci_informer_save\" class=\"button button-primary\" type=\"button\" value=\"".__('Save informer', 'wp-universal-exchange-informer')."\" name=\"save\"></p>\n
			</div>\n
			<p class=\"submit\">\n
			<a href=\"#TB_inline?width=600&height=550&inlineId=uci_add_box\" class=\"thickbox\"><input id=\"create\" class=\"button button-primary\" type=\"button\" value=\"".__('Create informer', 'wp-universal-exchange-informer')."\" name=\"create\"></a>\n
			</p>\n
		";

        /* Informers List */
        $informers=$wpdb->get_results("SELECT * FROM `".$uci_widgets_table."`");
        echo "
			<table id=\"uci_widgets\" class=\"wp-list-table widefat fixed striped posts\">\n
			<thead>\n
			<tr>\n
				<th id=\"title\" class=\"manage-column\" style=\"\" scope=\"col\">".__('Title', 'wp-universal-exchange-informer')."</th>\n
				<th id=\"bank\" class=\"manage-column\" style=\"\" scope=\"col\">".__('Bank name', 'wp-universal-exchange-informer')."</th>\n
				<th id=\"currency\" class=\"manage-column\" style=\"\" scope=\"col\">".__('Currency', 'wp-universal-exchange-informer')."</th>\n
				<th id=\"shortcode\" class=\"manage-column\" style=\"\" scope=\"col\">".__('Shortcode', 'wp-universal-exchange-informer')."</th>\n
				<th id=\"buttons\" class=\"manage-column\" style=\"\" scope=\"col\">".__('Options', 'wp-universal-exchange-informer')."</th>\n
			</tr>\n
			</thead>\n
			<tbody id=\"the-list\">\n
		";
        if ($informers==null) {
            echo "
				<tr id=\"uci_noinf\" class=\"type-post format-standard\">\n
					<td colspan=\"5\" class=\"column-title\">".__('Informers not found', 'wp-universal-exchange-informer')."...</td>\n
				</tr>\n
			";
        } else {
            foreach ($informers as $informer) {
                $bank_name=$banks[$informer->bank];
                $cur_flipped=array_flip($currencies);
                $cur_name=array();
                $cur_codes=explode(",", $informer->currency);
                foreach ($cur_codes as $cur_code) {
                    $cur_name[]=$cur_flipped[$cur_code];
                }
                $cur_name=implode(",", $cur_name);
                echo "
					<tr id=\"uci_informer_".$informer->id."\" class=\"type-post format-standard\">\n
						<td class=\"column-title\">".$informer->name."</td>\n
						<td class=\"column-title\">".$bank_name."</td>\n
						<td class=\"column-title\">".$cur_name."</td>\n
						<td class=\"column-title\">[excange-informer informer=\"".$informer->id."\"]</td>\n
						<td class=\"column-title\">\n
							<input id=\"uci_delete_".$informer->id."\" class=\"button button-primary\" type=\"button\" value=\"[X]\" name=\"delete\">\n
						</td>\n
					</tr>\n
				";
            }
        }
        echo "
			</tbody>\n
			<tfoot>\n
			<tr>\n
				<th id=\"title\" class=\"manage-column\" style=\"\" scope=\"col\">".__('Title', 'wp-universal-exchange-informer')."</th>\n
				<th id=\"bank\" class=\"manage-column\" style=\"\" scope=\"col\">".__('Bank name', 'wp-universal-exchange-informer')."</th>\n
				<th id=\"currency\" class=\"manage-column\" style=\"\" scope=\"col\">".__('Currency', 'wp-universal-exchange-informer')."</th>\n
				<th id=\"shortcode\" class=\"manage-column\" style=\"\" scope=\"col\">".__('Shortcode', 'wp-universal-exchange-informer')."</th>\n
				<th id=\"buttons\" class=\"manage-column\" style=\"\" scope=\"col\">".__('Options', 'wp-universal-exchange-informer')."</th>\n
			</tr>\n
			</tfoot>\n
			</table>\n
			";
    }
}

/* Register Widget */
function uci_register_widgets()
{
    register_widget('uci_exchange_widget');
}

/* Include Widget */
add_action('widgets_init', 'uci_register_widgets');
require_once('widget/exchange-widget.php');

/* CSS */
if (!function_exists('uci_add_stylesheets')) {
    function uci_add_stylesheets()
    {
        wp_enqueue_style("uci_css", plugins_url()."/".basename(dirname(__FILE__))."/css/uci.css", array(), null);
    }
}
add_action('wp_enqueue_scripts', 'uci_add_stylesheets');

/* Get and update exchange rates */
require_once('uci-getrates.php');
uci_get_rates();

/* Generate informer */
function uci_generate_informer($id)
{
    global $wpdb;
    global $uci_widgets_table;
    $nominals=array(
        "978"=>"1","840"=>"1","643"=>"1","946"=>"1","980"=>"1","784"=>"1","008"=>"10","051"=>"10","036"=>"1","944"=>"1","975"=>"1","933"=>"1","124"=>"1","756"=>"1","156"=>"1",
        "203"=>"1","208"=>"1","826"=>"1","981"=>"1","344"=>"1","191"=>"1","348"=>"100","376"=>"1","356"=>"10","352"=>"10","392"=>"100","417"=>"10","410"=>"100","414"=>"1","398"=>"10",
        "440"=>"1","428"=>"1","807"=>"10","458"=>"1","578"=>"1","554"=>"1","985"=>"1","941"=>"100","752"=>"1","972"=>"1","934"=>"1","949"=>"1","860"=>"100","960"=>"1","498"=>"10",
    );
    $informer=$wpdb->get_row("SELECT * FROM `".$uci_widgets_table."` WHERE `id`='".$id."'");
    if ($informer!=null) {
        $curr_name="";

        $banks=array(
            "cbr"=>"The Central Bank of the Russian Federation",
            "nbu"=>"National bank of Ukraine",
            "nbm"=>"National Bank of Moldova",
            "cbu"=>"The Central Bank of the Republic of Uzbekistan",
            "nbk"=>"National Bank of Kazakhstan",
            "cba"=>"The Central Bank of Armenia",
            "nbg"=>"National Bank Of Georgia",
            "nbb"=>"National Bank of the Republic of Belarus"
        );

        if ($informer->bank=="nbm") {
            $curr_name=__('Lei', 'wp-universal-exchange-informer');
        } elseif ($informer->bank=="nbu") {
            $curr_name=__('Grn', 'wp-universal-exchange-informer');
        } elseif ($informer->bank=="cbr") {
            $curr_name=__('Rub', 'wp-universal-exchange-informer');
        } elseif ($informer->bank=="cbu") {
            $curr_name=__('Sum', 'wp-universal-exchange-informer');
        } elseif ($informer->bank=="nbk") {
            $curr_name=__('Ten', 'wp-universal-exchange-informer');
        } elseif ($informer->bank=="cba") {
            $curr_name=__('Dra', 'wp-universal-exchange-informer');
        } elseif ($informer->bank=="nbg") {
            $curr_name=__('Lar', 'wp-universal-exchange-informer');
        } elseif ($informer->bank=="nbb") {
            $curr_name=__('Rub', 'wp-universal-exchange-informer');
        }

        $date_str="wp_uci_".$informer->bank."_date";
        $table_rates=$wpdb->prefix."uci_".$informer->bank."_rates";
        $date=get_option($date_str);
        $rate_codes=explode(",", $informer->currency);
        $informer_code="
			<table id=\"uci_table\">\n
			<tr><td id=\"uci_curr_title\" colspan=\"7\">".$date."</td></tr>\n
		";
        foreach ($rate_codes as $rate_code) {
            $cur=$wpdb->get_row("SELECT * FROM `".$table_rates."` WHERE `num`='".$rate_code."'");
            if ($cur!=null) {
                if ($nominals[$rate_code]!=$cur->nominal) {
                    $rate=round($cur->value/$cur->nominal*$nominals[$rate_code], 4);
                    $dif=round($cur->dif/$cur->nominal*$nominals[$rate_code], 4);
                } else {
                    $rate=$cur->value;
                    $dif=$cur->dif;
                }
                $informer_code.=
                    "<tr id=\"uci_row\">\n
					<td id=\"uci_curr_text\"><img src=\"".plugins_url()."/".basename(dirname(__FILE__))."/img/".$cur->char.".gif\" /></td>\n
					<td id=\"uci_curr_text\">".$nominals[$rate_code]."</td>\n
					<td id=\"uci_curr_text\">".$cur->char."</td>\n
					<td id=\"uci_curr_text\">".$rate."</td>\n
					<td id=\"uci_curr_text\">".$curr_name."</td>\n
				";
                if ($dif>0) {
                    $informer_code.="<td id=\"uci_curr_text\"><img src=\"".plugins_url()."/".basename(dirname(__FILE__))."/img/arrow_up.gif\" /></td>\n";
                } elseif ($dif<0) {
                    $informer_code.="<td id=\"uci_curr_text\"><img src=\"".plugins_url()."/".basename(dirname(__FILE__))."/img/arrow_down.gif\" /></td>\n";
                } else {
                    $informer_code.="<td id=\"uci_curr_text\"><img src=\"".plugins_url()."/".basename(dirname(__FILE__))."/img/point.gif\" /></td>\n";
                }
                if ($dif>0) {
                    $informer_code.= "<td id=\"uci_curr_text_green\">+".$dif."</td>\n";
                } elseif ($dif<0) {
                    $informer_code.="<td id=\"uci_curr_text_red\">".$dif."</td>\n";
                } else {
                    $informer_code.="<td id=\"uci_curr_text\">0.0000</td>\n";
                }
                $informer_code.= "</tr>\n";
            }
        }
        $informer_code.="</table>";
        return $informer_code;
    } else {
        return "<p>".__('Informer not selected!', 'wp-universal-exchange-informer')."</p>";
    }
}

/* Add shortcode */
function uci_informer_shortcode($atts)
{
    global $wpdb;
    extract(shortcode_atts(array("informer"=>''), $atts));
    return uci_generate_informer($informer);
}
add_shortcode("excange-informer", "uci_informer_shortcode");
/* Shortcodes in text widgets */
add_filter('widget_text', 'do_shortcode');

/* Ajax for admin(options) panel */
require_once('uci-handler.php');
