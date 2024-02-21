<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       www.dhairyasharma.com
 * @since      1.0.0
 *
 * @package    Fulfillmen
 * @subpackage Fulfillmen/admin/partials
 */

#<!-- This file should primarily consist of HTML with a little bit of PHP. -->
global $wpdb;
$prefix = 'fulfillmen_';
$GLOBALS['hide_save_button'] = true;
global $product;
global $post;
$APIuserID = get_option($prefix . 'fulfillmen_userID');
$apiKey = get_option($prefix . 'fulfillmen_apikey');
$TrackingUrl = get_option($prefix . 'customtrackingurl');
$iFautoMated = get_option($prefix . 'automation_fw');
$userID = get_option($prefix . 'fulfillmen_username');
$userPass = get_option($prefix . 'fulfillmen_password');
$warehouse = get_option($prefix . 'warehouse_ID');
$skuTable = $wpdb->prefix . 'fmSKU';

if(!class_exists('WC_Product_Variable')){
    include(plugin_url.'/woocommerce/includes/class-wc-product-variable.php');// adjust the link
}



//function to submit SKU
function wf_post_sku_to_ff(string $params, string $apiurl, string $sku, array $credentials)
{
    $body = null;
    $response  = wp_remote_post($apiurl, array(
        'headers'     => array('Content-Type' => 'application/json; charset=utf-8'),
        'body'        => $params,
        'method'      => 'POST',
        'data_format' => 'body',
    ));
    $body = wp_remote_retrieve_body( $response );
    //var_dump($body);
    //return $body;
    $responseArr = ["Body"=>$body, "debug"=>$apiurl." req: ".$params];
    return $responseArr;
}

$credentials = array('custid' => $APIuserID, 'apikey' => $apiKey);

if (!empty($userID && $userPass)) {
    echo "<h3>Sync Products with Fulfillmen</h3>";
    if (!empty($_POST)) {
        //Sending the data to FUlfillmen
        //var_dump($_POST);
        foreach ($_POST['data'] as $row) {
            // var_dump($_POST);
            // wp_die();
            // If the checkbox is on
            if (isset($row['select'])) {
                $sku = $row['SKU'];
                $name = $row['ProductEnName'];
                $remarks = $row['ProductEnName'];
                $postid = $row['wpid'];
                $price = 1;
                $weight = '0.10';
                $length = '10';
                $width = '10';
                $height = '10';
                $minqty = '50';
                $img = $row['img'];
                if(empty($img)|| $img == ""){
                    $img = "";
                }
                //$product = new WC_Product_Variable($postid);
               $product =  wc_get_product($postid);
                if ($product->is_type('variable')) {
                    //echo 111;
                    $args = array(
                        'post_type' => 'product_variation',
                        'post_status' => array('private', 'publish'),
                        'numberposts' => -1,
                        'orderby' => 'menu_order',
                        'order' => 'asc',
                        'post_parent' => $postid, // get parent post-ID
                    );
                    
                    $variations = get_posts($args);
                    foreach ($variations as $variation) {
                        $variation_ID = $variation->ID;
                        $vproduct = new WC_Product_Variation($variation_ID);
                        $sku = $vproduct->get_sku();
                        
                        //$params = "SKU:$sku,GoodsCode:$sku,EnName:$name,CnName:$name,Remark:$remarks,Price:$price,Weight:$weight,Length:$length,Width:$width,High:$height,CustomcCode:1,ProducingArea:CN,ExpectNum:$minqty,Brand:FM,GoodsStatus:1;";
                       // $params = '{"CustomerID":"'.$APIuserID.'","SKU":"'.$sku.'","Barcode":"'.$sku.'","EnName":"'.$name.'","CnName":"'.$name.'","Remark":"'.$remarks.'","Price":'.$price.',"Weight":'.$weight.',"Length":'.$length.',"Width":'.$width.',"Height":'.$height.',"HSCode":"000000","Origin":"CN","Brand":"Brand","Battery":"0","ExpectNum":1}';
                        $params = [
                            "CustomerID"=>  $APIuserID,
                            "SKU"=> $sku,
                            "Barcode"=> $sku,
                            "EnName"=> $name,
                            "CnName"=> $name,
                            "Remark"=> $remarks,
                            "Price"=> $price,
                            "Weight"=> $weight,
                            "Length"=> $length,
                            "Width"=> $width,
                            "Height"=> $height,
                            "HSCode"=> "000000",
                            "Origin"=> "CN",
                            "Brand"=> "Brand",
                            "Battery"=> "0",
                            "ExpectNum"=> 1, 
                            "ImageUrl"=>$img
                        ];
                        $params = json_encode($params,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
                        //echo "variable product";
                        $response = wf_post_sku_to_ff($params, "http://wms.fulfillmen.com/api-json/CreateGoods.aspx?Key=$apiKey", $sku, $credentials);
                    }
                } 

                if ($product->is_type('simple')){
                    //echo 222;
                    //$params = "SKU:$sku,GoodsCode:$sku,EnName:$name,CnName:$name,Remark:$remarks,Price:$price,Weight:$weight,Length:$length,Width:$width,High:$height,CustomcCode:1,ProducingArea:CN,ExpectNum:$minqty,Brand:FM,GoodsStatus:1;";
                    //$params = '{"CustomerID":"'.$APIuserID.'","SKU":"'.$sku.'","Barcode":"'.$sku.'","EnName":"'.$name.'","CnName":"'.$name.'","Remark":"'.$remarks.'","Price":'.$price.',"Weight":'.$weight.',"Length":'.$length.',"Width":'.$width.',"Height":'.$height.',"HSCode":"000000","Origin":"CN","Brand":"Brand","Battery":"0","ExpectNum":1}';
                    $params = [
                        "CustomerID"=>  $APIuserID,
                        "SKU"=> $sku,
                        "Barcode"=> $sku,
                        "EnName"=> $name,
                        "CnName"=> $name,
                        "Remark"=> $remarks,
                        "Price"=> $price,
                        "Weight"=> $weight,
                        "Length"=> $length,
                        "Width"=> $width,
                        "Height"=> $height,
                        "HSCode"=> "000000",
                        "Origin"=> "CN",
                        "Brand"=> "Brand",
                        "Battery"=> "0",
                        "ExpectNum"=> 1,
                        "ImageUrl"=>$img
                    ];
                    //echo "normal product";
                    $params = json_encode($params,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
                    $response = wf_post_sku_to_ff($params, "http://wms.fulfillmen.com/api-json/CreateGoods.aspx?Key=$apiKey", $sku, $credentials);
                    // var_dump($response);
                    // wp_die();
                }


                $res = json_decode($response['Body']);
                if ('success' == $res->success) {
                    $sql = "INSERT INTO $skuTable (StoreSKU,isSynced) Values ('$sku',1)";
                    $wpdb->query($sql);
                    echo "SKU Has been synced! <br> <br>";
                    echo "<a href='#' onclick='history.go(-1);' class='button-secondary'>Back</a>";
                } else {
                    // echo 'Error: Exception occured!' . $result->success . " " . $result->message;
                    $debugresp = $response['debug'];
                    echo "<div class='apiresponse'> Error Occured: " . $res->message . " <br> Payload: $debugresp</div>";
                    echo "<br><a href='#' onclick='history.go(-1);' class='button-secondary'>Back</a>";
                }
            } 
        }
    } else {
        //Printing the Form
        echo '<form class="syncSKU" action="" method="POST">';
        echo '<style>.prodthumb>img {height:100px !important; width:100px !important;}</style>';
        echo '<table class="wp-list-table widefat fixed striped posts">';
        echo '<td class="manage-column column-cb check-column" style="padding: 8px 10px;"><input type="checkbox" id="allcb"></td> <td>Product Name</td> <td>Product SKU</td> <td>Status</td> <td>Image</td>';

        if (isset($_GET['paged'])) {
            $paged = ($_GET['paged']);
        } else {
            $paged = 1;
        }

        $args = array(
            'post_type' => 'product',
            'posts_per_page' => 15,
            'paged' => $paged,
        );
        $loop = new WP_Query($args);
        while ($loop->have_posts()): $loop->the_post();
            global $product;
            $sku = get_post_meta(get_the_ID(), '_sku', true);
            if (empty($sku)) {
                $skuCheck = "Product Doesnt have a Valid SKU";
            } else {
                $skuCheck = $sku;
            }
            $ifSKU = $wpdb->get_results("SELECT * from $skuTable where StoreSKU='$sku' AND isSynced=1 ");
            echo '<tr>';
            $imageURL = get_the_post_thumbnail_url(get_the_ID(), 'full');
            if (count($ifSKU) == 0 and !empty($sku)) {
                echo '<td><input type="checkbox" name="data[' . $sku . '][select]" class="wf-sku-input-checkbox" id="' . $sku . '"></td>';
            } else {
                echo '<td><input type="radio"  class="" disabled></td>';
            }
            echo '<td><a href="' . get_permalink() . '">' . get_the_title() . '</a></td>
                    <td>' . $skuCheck . '</td>
                    <input id="' . $sku . '-wh" type="hidden" name="data[' . $sku . '][warehouse]" value="' . $warehouse . '">
                    <input id="' . $sku . '-sku" type="hidden" name="data[' . $sku . '][SKU]" value="' . $sku . '">
                    <input id="' . $sku . '-barcode" type="hidden" name="data[' . $sku . '][ProdcutBarcode]" value="' . $sku . '">
                    <input id="' . $sku . '-name" type="hidden" name="data[' . $sku . '][ProductEnName]" value="' . get_the_title() . '">
                    <input id="' . $sku . '-cnname" type="hidden" name="data[' . $sku . '][ProductCNName]" value="' . get_the_title() . '">
                    <input id="' . $sku . '-desc" type="hidden" name="data[' . $sku . '][description]" value="' . wp_trim_words($product->get_short_description(), 15, '') . '">
                    <input id="' . $sku . '-val" type="hidden" name="data[' . $sku . '][value]" value="1">
                    <input id="' . $sku . '-weight" type="hidden" name="data[' . $sku . '][weight]" value="0.100">
                    <input id="' . $sku . '-length" type="hidden" name="data[' . $sku . '][length]" value="10">
                    <input id="' . $sku . '-w" type="hidden" name="data[' . $sku . '][width]" value="10">
                    <input id="' . $sku . '-h" type="hidden" name="data[' . $sku . '][height]" value="10">
                    <input id="' . $sku . '-hsn" type="hidden" name="data[' . $sku . '][customsCode]" value="1">
                    <input id="' . $sku . '-og" type="hidden" name="data[' . $sku . '][Origin]" value="CN">
                    <input id="' . $sku . '-warn" type="hidden" name="data[' . $sku . '][InverntoryWarning]" value="50">
                    <input id="' . $sku . '-brand" type="hidden" name="data[' . $sku . '][Brand]" value="FM">
                    <input id="' . $sku . '-status" type="hidden" name="data[' . $sku . '][Status]" value="1">
                    <input id="' . $sku . '-id" type="hidden" name="data[' . $sku . '][wpid]" value="' . get_the_ID() . '">';
                   echo "<input id='$sku-img' type='hidden' name='data[$sku][img]' value='$imageURL'>";
                   
                // <input id="' . $sku . '-img" type="hidden" name="data[' . $sku . '][img]" value="'.the_post_thumbnail_url().'" 
            if (count($ifSKU) == 0) {
                echo '<td class="order_status column-order_status"><span class="order-status status-processing " style="padding:2px 5px;">Not Synced</span></td>';
            } else {
                echo '<td class="order_status column-order_status"><span class="order-status status-completed " style="padding:2px 5px;">Synced</span></td>';
            }
            echo '<td class="prodthumb" style="width: 100px;height: 100px;">' . woocommerce_get_product_thumbnail('shop_thumbnail') . '</td>';
            echo '</tr>';
        endwhile;

        echo '</table>';
        echo '<button type="submit" class="wf-sync-sku button-secondary" >Sync SKU</button>';
        echo '</form>';
        echo '<br>';
        $max_pages = $loop->max_num_pages;
        $nextpage = $paged + 1;
        $prevpage = max(($paged - 1), 0); //max() will discard any negative value
        if ($prevpage !== 0) {
            echo '<a class="pagiantion" style="margin-right:5px;" href="admin.php?page=wc-settings&tab=fulfillmen&section=inventory&paged=' . $prevpage . '">Previous page</a>';
        }

        if ($max_pages > $paged) {
            echo '<a class="pagiantion" style="margin-right:5px;" href="admin.php?page=wc-settings&tab=fulfillmen&section=inventory&paged=' . $nextpage . '">Next Page</a>';
        }

        wp_reset_query();
    }
} else {
    echo "<h3>Invalid Username and Or Password!</h3>";
}
