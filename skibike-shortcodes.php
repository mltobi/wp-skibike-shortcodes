<?php

// register shortcode
add_shortcode('list_orders_of_product', 'func_list_orders_of_product');
add_shortcode('list_orders_summary', 'func_list_orders_summary');

// list all orders of a product
function func_list_orders_of_product($atts)
{

    $retStr = '';
    $html_th = '<td style="border:solid;padding:4px;font-size:small;font-size:0.8em;">';
    $html_td = '<td style="border:solid;padding:4px;white-space: nowrap;font-size:0.8em;">';
    $five_months = 5 * 30 * 24 * 60 * 60; // 5 months in seconds

    // get all products
    $products = wc_get_products(array('numberposts' => -1));

    // get selected product id
    $product_selected = '-1';
    if (isset($_POST['content'])) {
        $product_selected = intval($_POST['content']);
    }

    // selection field for all products
    $retStr = $retStr . '<form method="post" id="submitForm">';
    $retStr = $retStr . '  <select name="content" onchange="document.getElementById(&quot;submitForm&quot;).submit();">';
    $retStr = $retStr . '    <option value=\'\'>Bitte Fahrt ausw&auml;hlen</option>';

    // add all product to selection field
    foreach ($products as $product) {
        $product_data = $product->get_data();
        $retStr = $retStr . '    <option value="' . $product->get_id() . '">' . $product_data['name'] . '</option>';
    }

    $retStr = $retStr . '  </select>';
    $retStr = $retStr . '</form>';

    // show orders for selected product
    if ($product_selected != -1) {

        // create lookup table product id to name
        $product_id2name = [];
        foreach ($products as $product) {
            $product_data = $product->get_data();
            $product_id2name[$product->get_id()] = $product_data['name'];
        }

        $retStr = $retStr . '<h3>Ausgew&auml;hlte Fahrt: <i>' . $product_id2name[$product_selected] . '</i></h3>';
        $retStr = $retStr . '<p>Hinweis: Die Tabelle hat weitere Spalten weiter rechts -> Scrollbar am unteren Rand der Tabelle verwenden.</p>';

        // get all orders
        $orders = wc_get_orders(array('numberposts' => -1));
//    $retStr = $retStr . '<pre>' . print_r($orders, true) . '</pre>';

        // create table for the product orders
        $retStr = $retStr . '<div style="width:100%;overflow-x: auto;border-right:solid;">';
        $retStr = $retStr . '<table>';
        $table_header = 0;
        $invoice_number = 'not found';

        // loop through all orders
        $order_t = 14;
        foreach ($orders as $order) {
            $order_t = $order;

            // get order creation timestamp
            $date_created = $order->get_date_created();
            $date_created_timestamp = $date_created->getTimestamp();

            // get current timestamp of "now"
            $now = new WC_DateTime();
            $now->setTimezone($date_created->getTimezone());
            $now_timestamp = $now->getTimestamp();

            // loop through all order items of current order
            foreach ($order->get_items() as $item_key => $item_values) {

                // get product id of current orders
                $product_id = $item_values->get_product_id();

                // check if product id was selected by user
                if ($product_id == $product_selected) {

                    // get order data of current order
                    $order_data = $order->get_data();

                    if (($now_timestamp - $five_months) < $date_created_timestamp) {
                        // eval invoice number of current order
                        foreach ($order->get_meta_data('_wcpdf_invoice_number_data') as $meta_data) {
                            if ($meta_data->key == '_wcpdf_invoice_number_data') {
                                $invoice_number = $meta_data->value['formatted_number'];
                            }
                        }

                        // add table header
                        if ($table_header == 0) {
                            $table_header = 1;
                            $retStr = $retStr . '<tr style="background-color:lightgrey;">';
                            $retStr = $retStr . $html_th . 'Rechnungsnr.</th>';
                            // add header for custom field data dependent columns
                            foreach ($item_values->get_meta_data('') as $meta_data) {
                                if (!str_starts_with($meta_data->key, '_')) {
                                    if (!str_starts_with($meta_data->key, 'Preis')) {
                                        $retStr = $retStr . $html_th . $meta_data->key . '</th>';
                                    }
                                }
                            }
                            $retStr = $retStr . $html_th . 'E-Mail</th>';
                            $retStr = $retStr . $html_th . 'Telefon</th>';
                            $retStr = $retStr . $html_th . 'Hinweis</th>';
                            $retStr = $retStr . $html_th . 'Preis</th>';
                            $retStr = $retStr . $html_th . 'Status</th>';
                            $retStr = $retStr . $html_th . 'Anmelder</th>';
                            $retStr = $retStr . $html_th . 'Menge</th>';
                            $retStr = $retStr . '</tr>';
                        }

                        if ($order_data['status'] == "completed") {

                            // add order item data
                            $retStr = $retStr . '<tr>';
                            $retStr = $retStr . $html_td . pdf_download_link($invoice_number, $order_data['id']) . '</td>';

                            // add custom field data
                            $price = '';
                            foreach ($item_values->get_meta_data('') as $meta_data) {
                                if (!str_starts_with($meta_data->key, '_')) {
                                    if (!str_starts_with($meta_data->key, 'Preis')) {
                                        $retStr = $retStr . $html_td . $meta_data->value . '</td>';
                                    } else {
                                        $price = $meta_data->value;
                                    }
                                }
                            }

                            $retStr = $retStr . $html_td . '<a href="mailto:' . $order_data['billing']['email'] . '">' . $order_data['billing']['email'] . '</a></td>';
                            $retStr = $retStr . $html_td . '<a href="tel:' . $order_data['billing']['phone'] . '">' . $order_data['billing']['phone'] . '</a></td>';
                            $retStr = $retStr . $html_td . $order_data['customer_note'] . '</td>';
                            $retStr = $retStr . $html_td . $price . '</td>';
                            $retStr = $retStr . $html_td . $order_data['status'] . '</td>';
                            $retStr = $retStr . $html_td . $order_data['billing']['first_name'] . ' ' . $order_data['billing']['last_name'] . '</td>';
                            $retStr = $retStr . $html_td . $item_values['quantity'] . '</td>';

                            $retStr = $retStr . '</tr>';
                        }
                    }
                }
            }
        }
        $retStr = $retStr . '</table>';
        $retStr = $retStr . '</div>';
    }

    return $retStr;
}

// list orders summary
function func_list_orders_summary($atts)
{

    $retStr = '';
    $html_th = '<td style="border:solid;padding:4px;font-size:small;font-size:0.8em;">';
    $html_td = '<td style="border:solid;padding:4px;white-space: nowrap;font-size:0.8em;">';
    $five_months = 5 * 30 * 24 * 60 * 60; // 5 months in seconds

    // get all products
    $products = wc_get_products(array('numberposts' => -1));

    $retStr = $retStr . '<h3>&Uuml;bersicht</h3>';

    $retStr = $retStr . '<div style="width:100%;overflow-x: auto;border-right:solid;">';
    $retStr = $retStr . '<table>';
    $retStr = $retStr . '<tr style="background-color:lightgrey;">';
    $retStr = $retStr . $html_th . 'Fahrt</th>';
    $retStr = $retStr . $html_th . 'Gebucht</th>';
    $retStr = $retStr . $html_th . 'Bestand</th>';
    $retStr = $retStr . $html_th . 'Anteil</th>';
    $retStr = $retStr . $html_th . 'Summe</th>';
    $retStr = $retStr . '</tr>';

    // get all orders
    $orders = wc_get_orders(array('numberposts' => -1));
//  $retStr = $retStr . '<pre>' . print_r($products, true) . '</pre>';
//  $retStr = $retStr . '<pre>' . print_r($orders, true) . '</pre>';

    // create lookup table product id to name
    $product_id2name = [];
    foreach ($products as $product) {

        $product_data = $product->get_data();
        $product_id2name[$product->get_id()] = $product_data['name'];
        $quantity = 0;
        $total = 0;

        // loop through all orders
        $order_t = 14;
        foreach ($orders as $order) {
            $order_t = $order;

            // get order creation timestamp
            $date_created = $order->get_date_created();
            $date_created_timestamp = $date_created->getTimestamp();

            // get current timestamp of "now"
            $now = new WC_DateTime();
            $now->setTimezone($date_created->getTimezone());
            $now_timestamp = $now->getTimestamp();

            if (($now_timestamp - $five_months) < $date_created_timestamp) {
                // loop through all order items of current order
                foreach ($order->get_items() as $item_key => $item_values) {

                    // get product id of current order
                    $product_id = $item_values->get_product_id();

                    // check if product id was selected by user
                    if ($product_id == $product->get_id()) {

                        // get order data of current order
                        $order_data = $order->get_data();

                        if ($order_data['status'] == "completed") {
                            $quantity = $quantity + $item_values['quantity'];
                            $total = $total + $item_values['total'];
                        }
                    }
                }
            }
        }

        // add order item data
        $retStr = $retStr . '<tr>';
        $retStr = $retStr . $html_td . $product_data['name'] . '</td>';
        $retStr = $retStr . $html_td . $quantity . '</td>';
        $retStr = $retStr . $html_td . $product_data['stock_quantity'] . '</td>';
        if ($product_data['stock_quantity'] != 0) {
            $retStr = $retStr . $html_td . number_format(100 * $quantity / ($quantity + $product_data['stock_quantity']), 1, '.', '') . ' %</td>';
        } else {
            $retStr = $retStr . $html_td . '-</td>';
        }
        $retStr = $retStr . $html_td . $total . ' €</td>';
        $retStr = $retStr . '</tr>';

    }
    $retStr = $retStr . '</table>';
    $retStr = $retStr . '</div>';

    return $retStr;
}

function pdf_download_link($link_text, $id)
{

    if (is_user_logged_in()) {
        $pdf_url = wp_nonce_url(add_query_arg(array(
            'action' => 'generate_wpo_wcpdf',
            'document_type' => 'invoice',
            'order_ids' => $id,
            'my-account' => true,
        ), admin_url('admin-ajax.php')), 'generate_wpo_wcpdf');
        $text = '<a href="' . esc_attr($pdf_url) . '">' . esc_html($link_text) . '</a>';
    } else {
        $text = $link_text;
    }

    return $text;
}
