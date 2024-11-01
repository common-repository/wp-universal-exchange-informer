<?php

/* Rates parser */

function uci_get_rates()
{
    global $wpdb;
    $current_date=date('d.m.Y', current_time('timestamp'));

    $currencies=array(
        "AFN"=>"971", "EUR"=>"978", "ALL"=>"8", "DZD"=>"12", "USD"=>"840", "AOA"=>"973", "XCD"=>"951", "ARS"=>"32", "AMD"=>"51", "AWG"=>"533", "AUD"=>"36", "AZN"=>"944",
        "BSD"=>"44", "BHD"=>"48", "BDT"=>"50", "BBD"=>"52", "BYN"=>"933", "BZD"=>"84", "XOF"=>"952", "BMD"=>"60", "INR"=>"356", "BTN"=>"64", "BOB"=>"68", "BOV"=>"984",
        "BAM"=>"977", "BWP"=>"72", "NOK"=>"578", "BRL"=>"986", "BND"=>"96", "BGN"=>"975", "BIF"=>"108", "CVE"=>"132", "KHR"=>"116", "XAF"=>"950", "CAD"=>"124",
        "KYD"=>"136", "CLP"=>"152", "CLF"=>"990", "CNY"=>"156", "COP"=>"170", "COU"=>"970", "KMF"=>"174", "CDF"=>"976", "NZD"=>"554", "CRC"=>"188", "HRK"=>"191",
        "CUP"=>"192", "CUC"=>"931", "ANG"=>"532", "CZK"=>"203", "DKK"=>"208", "DJF"=>"262", "DOP"=>"214", "EGP"=>"818", "SVC"=>"222", "ERN"=>"232", "ETB"=>"230",
        "FKP"=>"238", "FJD"=>"242", "XPF"=>"953", "GMD"=>"270", "GEL"=>"981", "GHS"=>"936", "GIP"=>"292", "GTQ"=>"320", "GBP"=>"826", "GNF"=>"324", "GYD"=>"328",
        "HTG"=>"332", "HNL"=>"340", "HKD"=>"344", "HUF"=>"348", "ISK"=>"352", "IDR"=>"360", "XDR"=>"960", "IRR"=>"364", "IQD"=>"368", "ILS"=>"376", "JMD"=>"388",
        "JPY"=>"392", "JOD"=>"400", "KZT"=>"398", "KES"=>"404", "KPW"=>"408", "KRW"=>"410", "KWD"=>"414", "KGS"=>"417", "LAK"=>"418", "LBP"=>"422", "LSL"=>"426",
        "ZAR"=>"710", "LRD"=>"430", "LYD"=>"434", "CHF"=>"756", "MOP"=>"446", "MKD"=>"807", "MGA"=>"969", "MWK"=>"454", "MYR"=>"458", "MVR"=>"462", "MRU"=>"929",
        "MUR"=>"480", "XUA"=>"965", "MXN"=>"484", "MXV"=>"979", "MDL"=>"498", "MNT"=>"496", "MAD"=>"504", "MZN"=>"943", "MMK"=>"104", "NAD"=>"516", "NPR"=>"524",
        "NIO"=>"558", "NGN"=>"566", "OMR"=>"512", "PKR"=>"586", "PAB"=>"590", "PGK"=>"598", "PYG"=>"600", "PEN"=>"604", "PHP"=>"608", "PLN"=>"985", "QAR"=>"634",
        "RON"=>"946", "RUB"=>"643", "RWF"=>"646", "SHP"=>"654", "WST"=>"882", "STN"=>"930", "SAR"=>"682", "RSD"=>"941", "SCR"=>"690", "SLL"=>"694", "SGD"=>"702",
        "XSU"=>"994", "SBD"=>"90", "SOS"=>"706", "SSP"=>"728", "LKR"=>"144", "SDG"=>"938", "SRD"=>"968", "SZL"=>"748", "SEK"=>"752", "CHE"=>"947", "CHW"=>"948",
        "SYP"=>"760", "TWD"=>"901", "TJS"=>"972", "TZS"=>"834", "THB"=>"764", "TOP"=>"776", "TTD"=>"780", "TND"=>"788", "TRY"=>"949", "TMT"=>"934", "UGX"=>"800",
        "UAH"=>"980", "AED"=>"784", "USN"=>"997", "UYU"=>"858", "UYI"=>"940", "UZS"=>"860", "VUV"=>"548", "VEF"=>"937", "VND"=>"704", "YER"=>"886", "ZMW"=>"967",
        "ZWL"=>"932", "XBA"=>"955", "XBB"=>"956", "XBC"=>"957", "XBD"=>"958", "XTS"=>"963", "XXX"=>"999", "XAU"=>"959", "XPD"=>"964", "XPT"=>"962", "XAG"=>"961"
    );

    /* If allow_urls_open continue */
    if (get_option('wp_uci_allow_urls')=="on") {
        /* National Bank of Moldova */
        if (get_option('wp_uci_nbm_date')!=$current_date) {
            $today=date('d.m.Y', current_time('timestamp'));
            $yesterday=date("d.m.Y", strtotime(date("d.m.Y", strtotime($today))."-1 day"));
            $get_xml_today=file_get_contents("http://www.bnm.md/ru/official_exchange_rates?get_xml=1&date=".$today, 0);
            $get_xml_yesterday=file_get_contents("http://www.bnm.md/ru/official_exchange_rates?get_xml=1&date=".$yesterday, 0);
            try {
                $xml_today=new SimplexmlElement($get_xml_today);
                $xml_yesterday=new SimplexmlElement($get_xml_yesterday);
            } catch (Exception $e) {
                $error1 = $e;
            }
            if (!isset($error1)) {
                $xml_date=(string)$xml_today->attributes()->{'Date'};
                $table_name=$wpdb->prefix."uci_nbm_rates";
                if ($xml_date==$today) {
                    foreach ($xml_today->Valute as $ind=>$item) {
                        $rates_char=(string)$item->CharCode;
                        $rates_num=(string)$item->NumCode;
                        $rates_value=(string)$item->Value;
                        $rates_nominal=(string)$item->Nominal;
                        $val_exists=$wpdb->get_var("SELECT `num` FROM `".$table_name."` WHERE `num`='".$rates_num."'");
                        if ($val_exists==null) {
                            $wpdb->insert($table_name, array(
                                'num'=>$rates_num,
                                'char'=>$rates_char,
                                'nominal'=>$rates_nominal,
                                'value'=>$rates_value
                            ));
                        } else {
                            $wpdb->update(
                                $table_name,
                                array(
                                'num'=>$rates_num,
                                'char'=>$rates_char,
                                'nominal'=>$rates_nominal,
                                'value'=>$rates_value
                            ),
                                array('num'=>$rates_num)
                            );
                        }
                    }
                    foreach ($xml_yesterday->Valute as $item) {
                        $today_value=$wpdb->get_var("SELECT `value` FROM `".$table_name."` WHERE `num`='".(string)$item->NumCode."'");
                        $yesterday_value=(string)$item->Value;
                        $difference=$today_value-$yesterday_value;
                        $wpdb->update(
                            $table_name,
                            array(
                            'dif'=>round($difference, 4)
                        ),
                            array('num'=>(string)$item->NumCode)
                        );
                    }
                    update_option("wp_uci_nbm_date", $current_date);
                }
            }
        }

        /* Central Bank of Russia */
        if (get_option('wp_uci_cbr_date')!=$current_date) {
            $today=date('d.m.Y', current_time('timestamp'));
            $yesterday=date("d.m.Y", strtotime(date("d.m.Y", strtotime($today))."-1 day"));
            $get_xml_today=file_get_contents("http://www.cbr.ru/scripts/XML_daily.asp?date_req=".$today, 0);
            $get_xml_yesterday=file_get_contents("http://www.cbr.ru/scripts/XML_daily.asp?date_req=".$yesterday, 0);
            try {
                $xml_today=new SimplexmlElement($get_xml_today);
                $xml_yesterday=new SimplexmlElement($get_xml_yesterday);
            } catch (Exception $e) {
                $error2 = $e;
            }
            if (!isset($error2)) {
                $xml_date=(string)$xml_today->attributes()->{'Date'};
                $table_name=$wpdb->prefix."uci_cbr_rates";
                foreach ($xml_today->Valute as $ind=>$item) {
                    $rates_char=(string)$item->CharCode;
                    $rates_num=(string)$item->NumCode;
                    $rates_value=(string)$item->Value;
                    $rates_nominal=(string)$item->Nominal;
                    $val_exists=$wpdb->get_var("SELECT `num` FROM `".$table_name."` WHERE `num`='".$rates_num."'");
                    if ($val_exists==null) {
                        $wpdb->insert($table_name, array(
                            'num'=>$rates_num,
                            'char'=>$rates_char,
                            'nominal'=>$rates_nominal,
                            'value'=>str_replace(",", ".", $rates_value)
                        ));
                    } else {
                        $wpdb->update(
                            $table_name,
                            array(
                            'num'=>$rates_num,
                            'char'=>$rates_char,
                            'nominal'=>$rates_nominal,
                            'value'=>str_replace(",", ".", $rates_value)
                        ),
                            array('num'=>$rates_num)
                        );
                    }
                }
                foreach ($xml_yesterday->Valute as $item) {
                    $today_nominal=$wpdb->get_var("SELECT `nominal` FROM `".$table_name."` WHERE `num`='".(string)$item->NumCode."'");
                    $today_value=$wpdb->get_var("SELECT `value` FROM `".$table_name."` WHERE `num`='".(string)$item->NumCode."'");
                    if ($today_nominal!=(string)$item->Nominal) {
                        $yesterday_value=str_replace(",", ".", (string)$item->Value)/(string)$item->Nominal*$today_nominal;
                    } else {
                        $yesterday_value=str_replace(",", ".", (string)$item->Value);
                    }
                    $difference=$today_value-$yesterday_value;
                    $wpdb->update(
                        $table_name,
                        array(
                        'dif'=>round($difference, 4)
                    ),
                        array('num'=>(string)$item->NumCode)
                    );
                }
                update_option("wp_uci_cbr_date", $current_date);
            }
        }

        /* National Bank of Ukraine */
        if (get_option('wp_uci_nbu_date')!=$current_date) {
            $today=date('dmY', current_time('timestamp'));
            $today2=date('d.m.Y', current_time('timestamp'));
            $yesterday=date("d.m.Y", strtotime(date("d.m.Y", strtotime($today2))."-1 day"));
            $yesterday=str_replace(".", "", $yesterday);
            $get_xml_today=file_get_contents("http://pf-soft.net/service/currency/?date=".$today, 0);
            $get_xml_yesterday=file_get_contents("http://pf-soft.net/service/currency/?date=".$yesterday, 0);
            try {
                $xml_today=new SimplexmlElement($get_xml_today);
                $xml_yesterday=new SimplexmlElement($get_xml_yesterday);
            } catch (Exception $e) {
                $error3 = $e;
            }
            if (!isset($error3)) {
                $xml_date=str_replace("/", ".", (string)$xml_today->attributes()->{'Date'});
                $table_name=$wpdb->prefix."uci_nbu_rates";
                if ($xml_date==$today2) {
                    foreach ($xml_today->Valute as $ind=>$item) {
                        $rates_char=(string)$item->CharCode;
                        $rates_num=(string)$item->NumCode;
                        $rates_value=(string)$item->Value;
                        $rates_nominal=(string)$item->Nominal;
                        $val_exists=$wpdb->get_var("SELECT `num` FROM `".$table_name."` WHERE `num`='".$rates_num."'");
                        if ($val_exists==null) {
                            $wpdb->insert($table_name, array(
                                'num'=>$rates_num,
                                'char'=>$rates_char,
                                'nominal'=>$rates_nominal,
                                'value'=>str_replace(",", ".", $rates_value)
                            ));
                        } else {
                            $wpdb->update(
                                $table_name,
                                array(
                                'num'=>$rates_num,
                                'char'=>$rates_char,
                                'nominal'=>$rates_nominal,
                                'value'=>str_replace(",", ".", $rates_value)
                            ),
                                array('num'=>$rates_num)
                            );
                        }
                    }
                    foreach ($xml_yesterday->Valute as $item) {
                        $today_nominal=$wpdb->get_var("SELECT `nominal` FROM `".$table_name."` WHERE `num`='".(string)$item->NumCode."'");
                        $today_value=$wpdb->get_var("SELECT `value` FROM `".$table_name."` WHERE `num`='".(string)$item->NumCode."'");
                        if ($today_nominal!=(string)$item->Nominal) {
                            $yesterday_value=str_replace(",", ".", (string)$item->Value)/(string)$item->Nominal*$today_nominal;
                        } else {
                            $yesterday_value=str_replace(",", ".", (string)$item->Value);
                        }
                        $difference=$today_value-$yesterday_value;
                        $wpdb->update(
                            $table_name,
                            array(
                            'dif'=>round($difference, 4)
                        ),
                            array('num'=>(string)$item->NumCode)
                        );
                    }
                    update_option("wp_uci_nbu_date", $current_date);
                }
            }
        }

        /* The Central Bank of the Republic of Uzbekistan */
        if (get_option('wp_uci_cbu_date')!=$current_date) {
            $today=date('Y.m.d', current_time('timestamp'));
            $yesterday=date("Y.m.d", strtotime(date("d.m.Y", strtotime(date('d.m.Y', current_time('timestamp'))))."-1 day"));
            $get_xml_today=file_get_contents("http://cbu.uz/ru/arkhiv-kursov-valyut/xml/all/".$today."/", 0);
            $get_xml_yesterday=file_get_contents("http://cbu.uz/ru/arkhiv-kursov-valyut/xml/all/".$yesterday."/", 0);
            try {
                $xml_today=new SimplexmlElement($get_xml_today);
                $xml_yesterday=new SimplexmlElement($get_xml_yesterday);
            } catch (Exception $e) {
                $error4 = $e;
            }
            if (!isset($error4)) {
                $table_name=$wpdb->prefix."uci_cbu_rates";
                foreach ($xml_today as $ind=>$item) {
                    $rates_char=(string)$item->Ccy;
                    $rates_num=(string)$item->attributes()->{'ID'};
                    $rates_value=(string)$item->Rate;
                    $rates_nominal=(string)$item->Nominal;
                    $val_exists=$wpdb->get_var("SELECT `num` FROM `".$table_name."` WHERE `num`='".$rates_num."'");
                    if ($val_exists==null) {
                        $wpdb->insert($table_name, array(
                            'num'=>$rates_num,
                            'char'=>$rates_char,
                            'nominal'=>$rates_nominal,
                            'value'=>$rates_value
                        ));
                    } else {
                        $wpdb->update(
                            $table_name,
                            array(
                            'num'=>$rates_num,
                            'char'=>$rates_char,
                            'nominal'=>$rates_nominal,
                            'value'=>$rates_value
                        ),
                            array('num'=>$rates_num)
                        );
                    }
                }
                foreach ($xml_yesterday as $item) {
                    $today_value=$wpdb->get_var("SELECT `value` FROM `".$table_name."` WHERE `num`='".(string)$item->attributes()->{'ID'}."'");
                    $yesterday_value=(string)$item->Rate;
                    $difference=$today_value-$yesterday_value;
                    $wpdb->update(
                        $table_name,
                        array(
                        'dif'=>round($difference, 4)
                    ),
                        array('num'=>(string)$item->attributes()->{'ID'})
                    );
                }
                update_option("wp_uci_cbu_date", $current_date);
            }
        }

        /* National Bank of Kazakhstan */
        if (get_option('wp_uci_nbk_date')!=$current_date) {
            $today=date('d.m.Y', current_time('timestamp'));
            $get_xml_today=file_get_contents("http://www.nationalbank.kz/rss/get_rates.cfm?fdate=".$today, 0);
            try {
                $xml_today=new SimplexmlElement($get_xml_today);
            } catch (Exception $e) {
                $error5 = $e;
            }
            if (!isset($error5)) {
                $table_name=$wpdb->prefix."uci_nbk_rates";
                foreach ($xml_today as $ind=>$item) {
                    if ((string)$item->title!="") {
                        $rates_char=(string)$item->title;
                        $rates_num=$currencies[$rates_char];
                        $rates_value=(string)$item->description;
                        $rates_nominal=(string)$item->quant;
                        $rates_dif=(string)$item->change;
                        $rates_dif=str_replace("+", "", $rates_dif);
                        $val_exists=$wpdb->get_var("SELECT `num` FROM `".$table_name."` WHERE `num`='".$rates_num."'");
                        if ($val_exists==null) {
                            $wpdb->insert($table_name, array(
                                'num'=>$rates_num,
                                'char'=>$rates_char,
                                'nominal'=>$rates_nominal,
                                'value'=>$rates_value,
                                'dif'=>round($rates_dif, 4)
                            ));
                        } else {
                            $wpdb->update(
                                $table_name,
                                array(
                                'num'=>$rates_num,
                                'char'=>$rates_char,
                                'nominal'=>$rates_nominal,
                                'value'=>$rates_value,
                                'dif'=>round($rates_dif, 4)
                            ),
                                array('num'=>$rates_num)
                            );
                        }
                    }
                }
                update_option("wp_uci_nbk_date", $current_date);
            }
        }

        /* If SOAP is working continue */
        if (get_option('wp_uci_soap')=="on") {
            /* The Central Bank of Armenia */
            if (get_option('wp_uci_cba_date')!=$current_date) {
                $today=date('Y-m-d', current_time('timestamp'));
                $table_name=$wpdb->prefix."uci_cba_rates";
                try {
                    $client = new SoapClient("http://api.cba.am/exchangerates.asmx?wsdl", array('version' => SOAP_1_1));
                    $today_result = $client->__soapCall("ExchangeRatesByDate", array(array('date' => $today)));
                    if (is_soap_fault($today_result)) {
                        throw new Exception('Failed to get data');
                    } else {
                        $today_data = $today_result->ExchangeRatesByDateResult;
                    }
                } catch (Exception $e) {
                    $error6 = 'Message: ' . $e->getMessage() . "\nTrace:" . $e->getTraceAsString();
                }
                if (!isset($error6)) {
                    $today_rates = $today_data->Rates->ExchangeRate;
                    if (is_array($today_rates) && count($today_rates)>0) {
                        foreach ($today_rates as $rate) {
                            if ($rate->Rate>0) {
                                $rates_char=$rate->ISO;
                                $rates_num=$currencies[$rates_char];
                                $rates_value=$rate->Rate;
                                $rates_nominal=$rate->Amount;
                                $rates_dif=$rate->Difference;
                                $val_exists=$wpdb->get_var("SELECT `num` FROM `".$table_name."` WHERE `num`='".$rates_num."'");
                                if ($val_exists==null) {
                                    $wpdb->insert($table_name, array(
                                        'num'=>$rates_num,
                                        'char'=>$rates_char,
                                        'nominal'=>$rates_nominal,
                                        'value'=>$rates_value,
                                        'dif'=>round($rates_dif, 4)
                                    ));
                                } else {
                                    $wpdb->update(
                                        $table_name,
                                        array(
                                        'num'=>$rates_num,
                                        'char'=>$rates_char,
                                        'nominal'=>$rates_nominal,
                                        'value'=>$rates_value,
                                        'dif'=>round($rates_dif, 4)
                                    ),
                                        array('num'=>$rates_num)
                                    );
                                }
                            }
                        }
                    }
                    update_option("wp_uci_cba_date", $current_date);
                }
            }

            /* National Bank Of Georgia */
            if (get_option('wp_uci_nbg_date')!=$current_date) {
                $table_name=$wpdb->prefix."uci_nbg_rates";
                $cur_supported = 'AED,AMD,AUD,AZN,BGN,BYN,CAD,CHF,EEK,EGP,EUR,GBP,HKD,HUF,ILS,INR,IRR,ISK,JPY,KGS,KWD,KZT,LTL,MDL,NOK,NZD,PLN,RON,RSD,CZK,RUB,SEK,SGD,TJS,TMT,TRY,UAH,USD,UZS,CNY,DKK';
                try {
                    $client = new SoapClient('https://services.nbg.gov.ge/Rates/Service.asmx?wsdl');
                } catch (Exception $e) {
                    $error7 = $e;
                }
                if (!isset($error7)) {
                    $result = $client->GetCurrentRates(array('Currencies'=>$cur_supported));
                    foreach ($result->GetCurrentRatesResult->CurrencyRate as $rate) {
                        $rates_nominal = $rate->Quantity;
                        $rates_value = floatval($rate->Rate);
                        $rates_dif = floatval($rate->Diff);
                        $rates_char = $rate->Code;
                        $rates_num = $currencies[$rates_char];
                        $val_exists=$wpdb->get_var("SELECT `num` FROM `".$table_name."` WHERE `num`='".$rates_num."'");
                        if ($val_exists==null) {
                            $wpdb->insert($table_name, array(
                                'num'=>$rates_num,
                                'char'=>$rates_char,
                                'nominal'=>$rates_nominal,
                                'value'=>$rates_value,
                                'dif'=>round($rates_dif, 4)
                            ));
                        } else {
                            $wpdb->update(
                                $table_name,
                                array(
                                'num'=>$rates_num,
                                'char'=>$rates_char,
                                'nominal'=>$rates_nominal,
                                'value'=>$rates_value,
                                'dif'=>round($rates_dif, 4)
                            ),
                                array('num'=>$rates_num)
                            );
                        }
                    }
                    update_option("wp_uci_nbg_date", $current_date);
                }
            }
        }

        /* National Bank of the Republic of Belarus */
        if (get_option('wp_uci_nbb_date')!=$current_date) {
            $today=date('m/d/Y', current_time('timestamp'));
            $yesterday=date("m/d/Y", strtotime(date("d.m.Y", strtotime(date('d.m.Y', current_time('timestamp'))))."-1 day"));
            $get_xml_today=file_get_contents("http://www.nbrb.by/Services/XmlExRates.aspx?ondate=".$today, 0);
            $get_xml_yesterday=file_get_contents("http://www.nbrb.by/Services/XmlExRates.aspx?ondate=".$yesterday, 0);
            try {
                $xml_today=new SimplexmlElement($get_xml_today);
                $xml_yesterday=new SimplexmlElement($get_xml_yesterday);
            } catch (Exception $e) {
                $error8 = $e;
            }
            if (!isset($error8)) {
                $xml_date=(string)$xml_today->attributes()->{'Date'};
                $table_name=$wpdb->prefix."uci_nbb_rates";
                if ($xml_date==$today) {
                    foreach ($xml_today->Currency as $ind=>$item) {
                        $rates_char=(string)$item->CharCode;
                        $rates_num=(string)$item->NumCode;
                        $rates_value=(string)$item->Rate;
                        $rates_nominal=(string)$item->Scale;
                        $val_exists=$wpdb->get_var("SELECT `num` FROM `".$table_name."` WHERE `num`='".$rates_num."'");
                        if ($val_exists==null) {
                            $wpdb->insert($table_name, array(
                                'num'=>$rates_num,
                                'char'=>$rates_char,
                                'nominal'=>$rates_nominal,
                                'value'=>$rates_value
                            ));
                        } else {
                            $wpdb->update(
                                $table_name,
                                array(
                                'num'=>$rates_num,
                                'char'=>$rates_char,
                                'nominal'=>$rates_nominal,
                                'value'=>$rates_value
                            ),
                                array('num'=>$rates_num)
                            );
                        }
                    }
                    foreach ($xml_yesterday->Currency as $item) {
                        $today_value=$wpdb->get_var("SELECT `value` FROM `".$table_name."` WHERE `num`='".(string)$item->NumCode."'");
                        $yesterday_value=(string)$item->Rate;
                        $difference=$today_value-$yesterday_value;
                        $wpdb->update(
                            $table_name,
                            array(
                            'dif'=>round($difference, 4)
                        ),
                            array('num'=>(string)$item->NumCode)
                        );
                    }
                    update_option("wp_uci_nbb_date", $current_date);
                }
            }
        }
    }
}
