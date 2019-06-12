<?php
date_default_timezone_set('Europe/London');
define('API_KEY','');
define('API_SECRET','');
define('API_ENDPOINT','https://api.exchange.trade.io/api/v1');
reset:
$min_btc = 0.001;
$max_btc = 0.015;
$min_eth = 0.01;
$max_eth = 0.50;
$min_usdt = 10;
$max_usdt = 60;
$min_profit = 1.0070;

function generateSignature($formData,$type)
    {
                $data = "?".utf8_encode(http_build_query($formData));
                if($type=='POST')
                        $data = json_encode($formData);
                $signature = hash_hmac('sha512', $data, API_SECRET);
        return $signature;
    }
function tradeIO($type, $url, $data, $auth=false){
   $curl = curl_init();
   $headers=array(
      'Content-Type: application/json',
      'Accept: application/json',
   );
   if($auth){
            $time= time()*1000;
                $data['ts']=$time;
                $sign=generateSignature($data,$type);
                $headers[]='Key: '. API_KEY;
                $headers[]='Sign: '.$sign;
   }
   $url= API_ENDPOINT . $url;
   switch ($type){
      case "POST":
         curl_setopt($curl, CURLOPT_POST, 1);
         if ($data)
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
         break;
      case "PUT":
         curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
         if ($data)
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
         break;
      case "DELETE":
         curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
         if ($data){
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                        $url = sprintf("%s?%s", $url, http_build_query($data));
                 }
         break;
      default:
         if ($data)
            $url = sprintf("%s?%s", $url, http_build_query($data));
   }
   curl_setopt($curl, CURLOPT_URL, $url);
   curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
   curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
   curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
   $result = curl_exec($curl);
        if (curl_error($curl)) {
                $error_msg = curl_error($curl);
                print_r($error_msg);
        }
        //print_r($result);
   if(!$result){die("<br>Request Failed");}
   curl_close($curl);
   //goto restart;
   return $result;
}
function get($url, $data, $auth=false){
        return tradeIO('GET', $url, $data, $auth);
}
function post($url, $data, $auth=false){
        return tradeIO('POST', $url, $data, $auth);
}
function put($url, $data, $auth=false){
        return tradeIO('PUT', $url, $data, $auth);
}
function delete($url, $data, $auth=false){
        return tradeIO('DELETE', $url, $data, $auth);
}

/***** API Functions *****/

function about(){
        $data= array();
        $url='/about';
        return get($url,$data);
}
function serverTime(){
        $data= array();
        $url='/time';
        return get($url,$data);
}
function pairs(){
        $data= array();
        $url='/pairs';
        return get($url,$data);
}
function info(){
        $data= array();
        $url='/info';
        return get($url,$data);
}
function klines($symbol,$interval,$start,$end,$limit){
        $data= array(
                'start'=>$start,
                'end'=>$end,
                'limit'=>$limit,
        );
        $url='/klines/'.$symbol.'/'.$interval;
        return get($url,$data);
}
function depth($symbol,$limit){
        $data= array(
                'limit'=>$limit,
        );
        $url='/depth/'.$symbol;
        return get($url,$data);
}
function ticker($symbol){
        $data= array();
        $url='/ticker/'.$symbol;
        return get($url,$data);
}
function tickers(){
        $data= array();
        $url='/tickers';
        return get($url,$data);
}
function trades($symbol,$limit){
        $data= array(
                'limit'=>$limit,
        );
        $url='/trades/'.$symbol;
        return get($url,$data);
}
function order($data){
        $data= $data;
        $url='/order';
        return post($url,$data,true);
}
function deleteOrder($orderID){
        $data= array();
        $url='/order/'.$orderID;
        return delete($url,$data,true);
}
function openOrders($symbol){
        $data= array();
        $url='/openOrders/'.$symbol;
        return get($url,$data,true);
}
function closedOrders($symbol,$start,$end,$page,$perPage){
        $data= array(
                'start'=>$start,
                'end'=>$end,
                'page'=>$page,
                'perPage'=>$perPage,
        );
        $url='/closedOrders/'.$symbol;
        return get($url,$data,true);
}
function account(){
        $data= array();
        $url='/account';
        return get($url,$data,true);
}

//print_r(account());

//print_r(ticker("tiox_btc"));

function build_order($symbol, $side, $price, $quantity){
        $order = array(
        "Symbol" => $symbol,
        "Side" => $side,
        "Type" => "limit",
        "Price" => $price,
        "Quantity" => $quantity
        );
        //print_r($order);
        return order($order);
}


$j=1;
$start = time();
$loop = 0;
$missed = 0;

//$pairs = json_decode(pairs());
$infos = json_decode(info());
//print_r($infos);

for ($x = 0; $x < count($infos->symbols); $x++){
        $ticker = $infos->symbols[$x]->symbol;
        $pairs_infos[$ticker]['status'] = $infos->symbols[$x]->status;
        $pairs_infos[$ticker]['baseAssetPrecision'] = $infos->symbols[$x]->baseAssetPrecision;
        $pairs_infos[$ticker]['quoteAssetPrecision'] = $infos->symbols[$x]->quoteAssetPrecision;
}
//print_r($pairs_infos);
//echo $pairs_infos['ltc_eth']['baseAssetPrecision'];
$weight = 0;
while ($j < 20000){
    restart:
	if (intval(date('s')) < 30 && date('s') != "00"){		
        if (intval(date('s')) > 0){
                        $date = date('d-m-Y H:i:s', time());
                        //if($j%1000 == 0){
                        //print($date); print(" : "); print($j); print(" - "); print($weight); print("\n");
                        //}
                        //print("$weight \n");
                        if($weight > 900){
                                //print("I sleep because I m too fast \n"); //print(time()); print("-"); print($start); print("\n");
								//print("I need to sleep \n");
								exit();
                                sleep(30);
								//print("Done sleeping \n");
                                $weight = 0;
                                goto restart;
                        }

                        $tickers = json_decode(tickers());
                        $weight += 20;
                        //print_r($tickers);
                        for ($x = 0; $x < count($tickers->tickers); $x++){
                                $ticker = $tickers->tickers[$x]->symbol;
                                $ticker_list[$ticker]['askPrice'] = $tickers->tickers[$x]->askPrice;
                                $ticker_list[$ticker]['askQty'] = $tickers->tickers[$x]->askQty;
                                $ticker_list[$ticker]['bidPrice'] =     $tickers->tickers[$x]->bidPrice;
                                $ticker_list[$ticker]['bidQty'] = $tickers->tickers[$x]->bidQty;
                        }
                        $val_btc = $ticker_list['btc_usdt']['askPrice'];
                        $val_btc_eth = $ticker_list['eth_btc']['askPrice'];
                        $val_eth = $ticker_list['eth_usdt']['askPrice'];
                        $val_usdt_eth = $ticker_list['eth_usdt']['askPrice'];
                        //print_r($ticker_list);



                        foreach ($ticker_list as $key=>$value){


        /////////////////////////////////////////////
        /////// USDT TO XXX TO BTC TO USDT //////////
        ////////////////////////////////////////////
                                if (strpos($key, 'usdt') !== false) { //only check usdt pairs
                                        $output = ""; //reset output string
                                        $tick = substr($key, 0, -5);
                                        $tickusdt = $tick . "_usdt"; //get trading pair name xxxusdt
                                        $tickbtc = $tick . "_btc"; // get trading pair name xxxbtc
                                        //trade usdt to XXX to btc to usdt
                                        if (array_key_exists($tickusdt, $ticker_list) && array_key_exists($tickbtc, $ticker_list) && $tick != "btnt" && $tick != "ktos" && $tick != "btc" && $tick != "coy" && $ticker_list[$tickusdt]['askPrice'] > 0 && $ticker_list[$tickbtc]['bidPrice'] > 0){ //check if trading pair exist (both in btc and usdt and that it s not btc, before donig some math on it
                                                $perc = round($ticker_list['btc_usdt']['bidPrice']*$ticker_list[$tickbtc]['bidPrice']/$ticker_list[$tickusdt]['askPrice'],5);
                                                //if ($perc > 0 && $perc < 0.4)
                                                //      print("Missed the trade $tickbtc, because perc : $perc \n");
                                                if ($perc > $min_profit){
                                                //////////////////////////////on a un trade positif, on y va !
                                                                if ($ticker_list[$tickusdt]['askPrice']*$ticker_list[$tickusdt]['askQty'] > $min_usdt && $ticker_list[$tickbtc]['bidQty']*$ticker_list[$tickbtc]['bidPrice'] > $min_btc && $ticker_list[$tickbtc]['bidPrice']*$ticker_list[$tickbtc]['bidQty']*$val_btc > $min_usdt){
                                                                        //print("------------------------------------\n");
                                                                        //print("$date : Initiate trade : $tickusdt ($perc) \n");
                                                                        $price = $ticker_list[$tickusdt]['askPrice'];
                                                                        $quantity = min($max_usdt/$price, $ticker_list[$tickusdt]['askQty'], $ticker_list[$tickbtc]['bidQty']);
                                                                        $quantity = round($quantity,$pairs_infos[$tickusdt]['baseAssetPrecision'],PHP_ROUND_HALF_DOWN);
                                                                        $quantity_ini = round(min($max_usdt/$price, $ticker_list[$tickusdt]['askQty'], $ticker_list[$tickbtc]['bidQty']), $pairs_infos[$tickbtc]['baseAssetPrecision'],PHP_ROUND_HALF_UP);
                                                                        //$check_btc = json_decode(ticker($tickbtc));
                                                                        // if ($check_btc->ticker->bidPrice == $ticker_list[$tickbtc]['bidPrice']){
                                                                                $res1 = build_order($tickusdt, "buy", $price, $quantity);
                                                                                $order1 = json_decode($res1);
                                                                                if ($order1->order->status == "Completed"){
                                                                                        //print("Order 1 : $tickusdt - $quantity @ $price \n");
                                                                                        $price = $ticker_list[$tickbtc]['bidPrice'];
                                                                                        $quantity = $quantity_ini;
                                                                                        $quantity = round($quantity_ini/1.001, $pairs_infos[$tickbtc]['baseAssetPrecision'],PHP_ROUND_HALF_DOWN);
                                                                                        $res2 = build_order($tickbtc, "sell", $price, $quantity);
                                                                                        $weight += 1;
                                                                                        $order2 = json_decode($res2);
                                                                                        //print("Order 2 : $tickbtc - $quantity @ $price \n");
                                                                                        $price = $ticker_list['btc_usdt']['bidPrice'];
                                                                                        //$price = $ticker_list['btc_usdt']['bidPrice'];
                                                                                        $quantity = round(($quantity_ini)*$ticker_list[$tickbtc]['bidPrice'], $pairs_infos["btc_usdt"]['baseAssetPrecision'],PHP_ROUND_HALF_UP);
                                                                                        $res3 = build_order("btc_usdt", "sell", $price, $quantity);
                                                                                        $order3 = json_decode($res3);
                                                                                        $weight += 1;
                                                                                        //print("Order 3 : btc_usdt - $quantity @ $price \n");
                                                                                        print($date); print(" : "); print("2"); print(" - "); print("\033[32m Success trade $tickbtc (usdt) ( $perc ) !\033[0m \n");
                                                                                        //print_r($order1);
                                                                                        //print_r($order2);
                                                                                        //print_r($order3);
                                                                                usleep(400);
                                                                                        goto restart;
                                                                                }
                                                                                else {
                                                                                        $weight += 1;
                                                                                        $delete = deleteOrder($order1->order->orderId);
                                                                                        print("Missed the trade $ticketh, deleted it...\n");
                                                                                        goto restart;
                                                                                }


/*                                                                        }
                                                                         else {
                                                                                print("Counter trade out, missed the trade...\n");
                                                                                goto restart;
                                                                        } */
                                                                }
                                                }
                                        }
                                }







        /////////////////////////////////////////////
        ///////  BTC TO XXX TO USDT TO BTC //////////
        ////////////////////////////////////////////
                                if (strpos($key, 'btc') !== false) { //only check btc pairs
                                        $output = ""; //reset output string
                                        $tick = substr($key, 0, -4);
                                        $tickusdt = $tick . "_usdt"; //get trading pair name xxxusdt
                                        $tickbtc = $tick . "_btc"; // get trading pair name xxxbtc
                                        //trade usdt to XXX to btc to usdt
                                        if (array_key_exists($tickusdt, $ticker_list) && array_key_exists($tickbtc, $ticker_list) && $tick != "btnt" && $tick != "ktos" && $tick != "coy" && $tick != "btc" && $ticker_list[$tickusdt]['bidPrice'] > 0 && $ticker_list[$tickbtc]['askPrice'] > 0){ //check if trading pair exist (both in btc and usdt and that it s not btc, before donig some math on it
                                                $perc = round($ticker_list[$tickusdt]['bidPrice']/$ticker_list[$tickbtc]['askPrice']/$ticker_list['btc_usdt']['askPrice'],5);
                                                //if ($perc > 0 && $perc < 0.4)
                                                //      print("Missed the trade $tickbtc, because perc : $perc \n");
                                                if ($perc > $min_profit){
                                                //////////////////////////////on a un trade positif, on y va !
                                                                if ($ticker_list[$tickusdt]['bidPrice']*$ticker_list[$tickusdt]['bidQty'] > $min_usdt && $ticker_list[$tickbtc]['askQty']*$ticker_list[$tickbtc]['askPrice'] > $min_btc && $ticker_list[$tickbtc]['askPrice']*$ticker_list[$tickbtc]['askQty']*$val_btc > $min_usdt){
                                                                        //print("------------------------------------\n");
                                                                        //print("$date : Initiate trade : $tickusdt ($perc) \n");
                                                                        $price = $ticker_list[$tickbtc]['askPrice'];
                                                                        $quantity = min($max_btc/$price, $ticker_list[$tickusdt]['bidQty'], $ticker_list[$tickbtc]['askQty']);
                                                                        $quantity = round($quantity,$pairs_infos[$tickbtc]['baseAssetPrecision'],PHP_ROUND_HALF_DOWN);
                                                                        $quantity_ini = round(min($max_btc/$price, $ticker_list[$tickbtc]['askQty'], $ticker_list[$tickusdt]['bidQty']), $pairs_infos[$tickbtc]['baseAssetPrecision'],PHP_ROUND_HALF_UP);



                                                                        //$check_usdt = json_decode(ticker($tickusdt));
                                                                        //if ($check_usdt->ticker->bidPrice == $ticker_list[$tickusdt]['bidPrice']){
                                                                                $res1 = build_order($tickbtc, "buy", $price, $quantity);
                                                                                $order1 = json_decode($res1);
                                                                                if ($order1->order->status == "Completed"){
                                                                                        //print("Order 1 : $tickusdt - $quantity @ $price \n");
                                                                                        $price = $ticker_list[$tickusdt]['bidPrice'];
                                                                                        $quantity = $quantity_ini;
                                                                                        $quantity = round($quantity_ini/1.001, $pairs_infos[$tickusdt]['baseAssetPrecision'],PHP_ROUND_HALF_DOWN);
                                                                                        $res2 = build_order($tickusdt, "sell", $price, $quantity);
                                                                                        $weight += 1;
                                                                                        $order2 = json_decode($res2);
                                                                                        //print("Order 2 : $tickbtc - $quantity @ $price \n");
                                                                                        $price = $ticker_list['btc_usdt']['askPrice'];
                                                                                        //$price = $ticker_list['btc_usdt']['bidPrice'];
                                                                                        $quantity = round(($quantity_ini)*$ticker_list[$tickbtc]['askPrice'], $pairs_infos["btc_usdt"]['baseAssetPrecision'],PHP_ROUND_HALF_UP);
                                                                                        $res3 = build_order("btc_usdt", "buy", $price, $quantity);
                                                                                        $order3 = json_decode($res3);
                                                                                        $weight += 1;
                                                                                        //print("Order 3 : btc_usdt - $quantity @ $price \n");
                                                                                        print($date); print(" : "); print("3"); print(" - "); print("\033[32m Success trade $tickbtc (usdt) ( $perc ) !\033[0m \n");
                                                                                        //print_r($order1);
                                                                                        //print_r($order2);
                                                                                        //print_r($order3);
                                                                                usleep(400);
                                                                                        goto restart;
                                                                                }
                                                                                else {
                                                                                        $weight += 1;
                                                                                        $delete = deleteOrder($order1->order->orderId);
                                                                                        print("Missed the trade $ticketh, deleted it...\n");
                                                                                        goto restart;
                                                                                }

/*                                                                         }
                                                                        else {
                                                                                print("Counter trade out, missed the trade...\n");
                                                                                goto restart;
                                                                        } */
                                                                }
                                                }
                                        }
                                }
















        /////////////////////////////////////////////
        ///////// BTC TO XXX TO ETH TO BTC //////////
        ////////////////////////////////////////////
                                if (strpos($key, 'btc') !== false) { //only check BTC pairs
                                        $output = ""; //reset output string
                                        $tick = substr($key, 0, -4);
                                        $tickbtc = $tick . "_btc"; //get trading pair name xxxbtc
                                        $ticketh = $tick . "_eth"; // get trading pair name xxxeth
                                        //trade BTC to XXX to ETH to BTC
                                        if (array_key_exists($tickbtc, $ticker_list) && array_key_exists($ticketh, $ticker_list) && $tick != "btnt" && $tick != "ktos" && $tick != "eth" && $tick != "coy" && $ticker_list[$tickbtc]['askPrice'] > 0 && $ticker_list[$ticketh]['bidPrice'] > 0){ //check if trading pair exist (both in eth and btc and that it s not ETH, before donig some math on it
                                                $perc = round($ticker_list[$ticketh]['bidPrice']*$ticker_list['eth_btc']['bidPrice']/$ticker_list[$tickbtc]['askPrice'],5);
                                                //if ($perc > 0 && $perc < 0.4)
                                                //      print("Missed the trade $tickbtc, because perc : $perc \n");
                                                if ($perc > $min_profit){
                                                        //////////////////////////////on a un trade positif, on y va !
                                                                if ($ticker_list[$tickbtc]['askPrice']*$ticker_list[$tickbtc]['askQty'] > $min_btc && $ticker_list[$ticketh]['bidQty']*$ticker_list[$ticketh]['bidPrice'] > $min_eth && $ticker_list[$ticketh]['bidPrice']*$ticker_list[$ticketh]['bidQty']*$val_btc_eth > $min_btc){
                                                                        //print("------------------------------------\n");
                                                                        //print("$date : Initiate trade : $tickbtc ($perc) \n");
                                                                        $price = $ticker_list[$tickbtc]['askPrice'];
                                                                        $quantity = min($max_btc/$price, $ticker_list[$tickbtc]['askQty'], $ticker_list[$ticketh]['bidQty']);
                                                                        $quantity = round($quantity, $pairs_infos[$tickbtc]['baseAssetPrecision'],PHP_ROUND_HALF_DOWN);
                                                                        $quantity_ini = round(min($max_btc/$price, $ticker_list[$tickbtc]['askQty'], $ticker_list[$ticketh]['bidQty']), $pairs_infos[$ticketh]['baseAssetPrecision'],PHP_ROUND_HALF_UP);
                                                                        $weight += 1;

                                                                        // $check_eth = json_decode(ticker($ticketh));
                                                                        // if ($check_eth->ticker->bidPrice == $ticker_list[$ticketh]['bidPrice']){
                                                                                $res1 = build_order($tickbtc, "buy", $price, $quantity);
                                                                                $order1 = json_decode($res1);
                                                                                if ($order1->order->status == "Completed"){
                                                                                        //print("Order 1 : $tickbtc - $quantity @ $price \n");
                                                                                        $price = $ticker_list[$ticketh]['bidPrice'];
                                                                                        $quantity = $quantity_ini;
                                                                                        $quantity = round($quantity_ini/1.001, $pairs_infos[$ticketh]['baseAssetPrecision'],PHP_ROUND_HALF_DOWN);
                                                                                        $res2 = build_order($ticketh, "sell", $price, $quantity);
                                                                                        $weight += 1;
                                                                                        //print("Order 2 : $ticketh - $quantity @ $price \n");
                                                                                        $price = $ticker_list['eth_btc']['bidPrice'];
                                                                                        $quantity = round(($quantity_ini)*$ticker_list[$ticketh]['bidPrice'], $pairs_infos["eth_btc"]['baseAssetPrecision'],PHP_ROUND_HALF_UP);
                                                                                        $res3 = build_order("eth_btc", "sell", $price, $quantity);
                                                                                        //print("Order 3 : eth_btc - $quantity @ $price \n");
                                                                                        print($date); print(" : "); print("1"); print(" - "); print("\033[32m Success trade $tickbtc (eth) ( $perc ) !\033[0m \n");
                                                                                        $order2 = json_decode($res2);
                                                                                        $order3 = json_decode($res3);
                                                                                        //print_r($order1);
                                                                                        //print_r($order2);
                                                                                        //print_r($order3);
                                                                                usleep(400);
                                                                                        goto restart;
                                                                                }
                                                                                else {
                                                                                        $weight += 1;
                                                                                        $delete = deleteOrder($order1->order->orderId);
                                                                                        print("Missed the trade $ticketh, deleted it...\n");
                                                                                        goto restart;

                                                                                }
/*                                                                         }
                                                                        else {
                                                                                print("Counter trade out, missed the trade...\n");
                                                                                goto restart;
                                                                        } */
                                                                        //print("------------------------------------\n");
                                                                }
                                                }
                                        }
                                }







        /////////////////////////////////////////////
        ///////// ETH TO XXX TO BTC TO ETH //////////
        ////////////////////////////////////////////
                                if (strpos($key, 'eth') !== false) { //only check BTC pairs
                                        $output = ""; //reset output string
                                        $tick = substr($key, 0, -4);
                                        $ticketh = $tick . "_eth"; // get trading pair name xxxeth
                                        $tickbtc = $tick . "_btc"; //get trading pair name xxxbtc
                                        //ETH TO XXX TO BTC TO ETH
                                        if (array_key_exists($ticketh, $ticker_list) && array_key_exists($tickbtc, $ticker_list) && $tick != "btnt" && $tick != "eth" && $tick != "coy" && $ticker_list[$ticketh]['askPrice'] > 0 && $ticker_list[$tickbtc]['bidPrice'] > 0){ //check if trading pair exist (both in eth and btc and that it s not ETH, before donig some math on it
                                                $perc = round($ticker_list[$tickbtc]['bidPrice']/$ticker_list[$ticketh]['askPrice']/$ticker_list['eth_btc']['askPrice'],5);
                                                if ($perc > $min_profit){
                                                        //////////////////////////////on a un trade positif, on y va !
                                                                if ($ticker_list[$tickbtc]['askPrice']*$ticker_list[$tickbtc]['bidQty'] > $min_btc && $ticker_list[$ticketh]['askQty']*$ticker_list[$ticketh]['askPrice'] > $min_eth && $ticker_list[$ticketh]['askPrice']*$ticker_list[$ticketh]['askQty']*$val_btc_eth > $min_btc){
                                                                        //print("------------------------------------\n");
                                                                        //print("$date : Initiate trade : $ticketh ($perc) \n");
                                                                        $price = $ticker_list[$ticketh]['askPrice'];
                                                                        $quantity = min(round($max_eth/$price, $pairs_infos[$ticketh]['baseAssetPrecision'],PHP_ROUND_HALF_UP), $ticker_list[$ticketh]['askQty'], $ticker_list[$tickbtc]['bidQty']);
                                                                        $quantity = round($quantity,$pairs_infos[$ticketh]['baseAssetPrecision'],PHP_ROUND_HALF_DOWN);
                                                                        $quantity_ini = round(min($max_eth/$price, $ticker_list[$ticketh]['askQty'], $ticker_list[$tickbtc]['bidQty']), $pairs_infos[$tickbtc]['baseAssetPrecision'],PHP_ROUND_HALF_UP);

                                                                        // $check_btc = json_decode(ticker($tickbtc));
                                                                        // if ($check_btc->ticker->bidPrice == $ticker_list[$tickbtc]['bidPrice']){
                                                                                $res1 = build_order($ticketh, "buy", $price, $quantity);
                                                                                $order1 = json_decode($res1);
                                                                                if ($order1->order->status == "Completed"){
                                                                                        //print("Order 1 : $ticketh - $quantity @ $price \n");
                                                                                        $price = $ticker_list[$tickbtc]['bidPrice'];
                                                                                        $quantity = $quantity_ini;
                                                                                        $quantity = round($quantity_ini/1.001, $pairs_infos[$tickbtc]['baseAssetPrecision'],PHP_ROUND_HALF_DOWN);
                                                                                        $res2 = build_order($tickbtc, "sell", $price, $quantity);
                                                                                        $weight += 1;
                                                                                        //print("Order 2 : $tickbtc - $quantity @ $price \n");
                                                                                        $price = $ticker_list['eth_btc']['askPrice'];
                                                                                        $quantity = round(($quantity_ini)*$ticker_list[$ticketh]['askPrice'], $pairs_infos["eth_btc"]['baseAssetPrecision'],PHP_ROUND_HALF_UP);
                                                                                        $res3 = build_order("eth_btc", "buy", $price, $quantity);
                                                                                        //print("Order 3 : eth_btc - $quantity @ $price \n");
                                                                                        print($date); print(" : "); print("4"); print(" - "); print("\033[32m Success trade $ticketh ( $perc ) !\033[0m \n");
                                                                                        $order2 = json_decode($res2);
                                                                                        $order3 = json_decode($res3);
                                                                                        //print_r($order1);
                                                                                        //print_r($order2);
                                                                                        //print_r($order3);
                                                                                usleep(400);
                                                                                        goto restart;
                                                                                }
                                                                                else {
                                                                                        $weight += 1;
                                                                                        $delete = deleteOrder($order1->order->orderId);
                                                                                        print("Missed the trade $ticketh, deleted it...\n");
                                                                                        goto restart;
                                                                                }


/*                                                                         }
                                                                        else {
                                                                                print("Counter trade out, missed the trade...\n");
                                                                                goto restart;
                                                                        } */
                                                                }
                                                }
                                        }
                                }








        /////////////////////////////////////////////
        /////// USDT TO xxx TO ETH TO USDT //////////
        ////////////////////////////////////////////
                                if (strpos($key, 'usdt') !== false) { //only check usdt pairs
                                        $output = ""; //reset output string
                                        $tick = substr($key, 0, -5);
                                        $tickusdt = $tick . "_usdt"; //get trading pair name xxxusdt
                                        $ticketh = $tick . "_eth"; // get trading pair name xxxeth
                                        //trade usdt to XXX to eth to usdt
                                        if (array_key_exists($tickusdt, $ticker_list) && array_key_exists($ticketh, $ticker_list) && $tick != "btnt" && $tick != "ktos" && $tick != "eth" && $tick != "coy" && $ticker_list[$tickusdt]['askPrice'] > 0 && $ticker_list[$ticketh]['bidPrice'] > 0){ //check if trading pair exist (both in eth and usdt and that it s not eth, before donig some math on it
                                                $perc = round($ticker_list['eth_usdt']['bidPrice']*$ticker_list[$ticketh]['bidPrice']/$ticker_list[$tickusdt]['askPrice'],5);
                                                //if ($perc > 0 && $perc < 0.4)
                                                //      print("Missed the trade $ticketh, because perc : $perc \n");
                                                if ($perc > $min_profit){
                                                //////////////////////////////on a un trade positif, on y va !
                                                                if ($ticker_list[$tickusdt]['askPrice']*$ticker_list[$tickusdt]['askQty'] > $min_usdt && $ticker_list[$ticketh]['bidQty']*$ticker_list[$ticketh]['bidPrice'] > $min_eth && $ticker_list[$ticketh]['bidPrice']*$ticker_list[$ticketh]['bidQty']*$val_eth > $min_usdt){
                                                                        //print("------------------------------------\n");
                                                                        //print("$date : Initiate trade : $tickusdt ($perc) \n");
                                                                        $price = $ticker_list[$tickusdt]['askPrice'];
                                                                        $quantity = min($max_usdt/$price, $ticker_list[$tickusdt]['askQty'], $ticker_list[$ticketh]['bidQty']);
                                                                        $quantity = round($quantity,$pairs_infos[$tickusdt]['baseAssetPrecision'],PHP_ROUND_HALF_DOWN);
                                                                        $quantity_ini = round(min($max_usdt/$price, $ticker_list[$tickusdt]['askQty'], $ticker_list[$ticketh]['bidQty']), $pairs_infos[$ticketh]['baseAssetPrecision'],PHP_ROUND_HALF_UP);


                                                                        // $check_eth = json_decode(ticker($ticketh));
                                                                        // if ($check_eth->ticker->bidPrice == $ticker_list[$ticketh]['bidPrice']){
                                                                                $res1 = build_order($tickusdt, "buy", $price, $quantity);
                                                                                $order1 = json_decode($res1);
                                                                                if ($order1->order->status == "Completed"){
                                                                                        //print("Order 1 : $tickusdt - $quantity @ $price \n");
                                                                                        $price = $ticker_list[$ticketh]['bidPrice'];
                                                                                        $quantity = $quantity_ini;
                                                                                        $quantity = round($quantity_ini/1.001, $pairs_infos[$ticketh]['baseAssetPrecision'],PHP_ROUND_HALF_DOWN);
                                                                                        $res2 = build_order($ticketh, "sell", $price, $quantity);
                                                                                        $weight += 1;
                                                                                        $order2 = json_decode($res2);
                                                                                        //print("Order 2 : $ticketh - $quantity @ $price \n");
                                                                                        $price = $ticker_list['eth_usdt']['bidPrice'];
                                                                                        //$price = $ticker_list['eth_usdt']['bidPrice'];
                                                                                        $quantity = round(($quantity_ini)*$ticker_list[$ticketh]['bidPrice'], $pairs_infos["eth_usdt"]['baseAssetPrecision'],PHP_ROUND_HALF_UP);
                                                                                        $res3 = build_order("eth_usdt", "sell", $price, $quantity);
                                                                                        $order3 = json_decode($res3);
                                                                                        $weight += 1;
                                                                                        //print("Order 3 : eth_usdt - $quantity @ $price \n");
                                                                                        print($date); print(" : "); print("5"); print(" - "); print("\033[32m Success trade $ticketh (usdt) ( $perc ) !\033[0m \n");
                                                                                        //print_r($order1);
                                                                                        //print_r($order2);
                                                                                        //print_r($order3);
                                                                                usleep(400);
                                                                                        goto restart;
                                                                                }
                                                                                else {
                                                                                        $weight += 1;
                                                                                        $delete = deleteOrder($order1->order->orderId);
                                                                                        print("Missed the trade $ticketh, deleted it...\n");
                                                                                        goto restart;
                                                                                }


                                                                        // }
                                                                        // else {
                                                                                // print("Counter trade out, missed the trade...\n");
                                                                                // goto restart;
                                                                        // }
                                                                }
                                                }
                                        }
                                }











        /////////////////////////////////////////////
        ///////// ETH TO XXX TO USDT TO ETH /////////
        ////////////////////////////////////////////
                                if (strpos($key, 'eth') !== false) { //only check usdt pairs
                                        $output = ""; //reset output string
                                        $tick = substr($key, 0, -4);
                                        $ticketh = $tick . "_eth"; // get trading pair name xxxeth
                                        $tickusdt = $tick . "_usdt"; //get trading pair name xxxusdt
                                        //ETH TO XXX TO usdt TO ETH
                                        if (array_key_exists($ticketh, $ticker_list) && array_key_exists($tickusdt, $ticker_list) && $tick != "btnt" && $tick != "eth" && $tick != "coy" && $ticker_list[$ticketh]['askPrice'] > 0 && $ticker_list[$tickusdt]['bidPrice'] > 0){ //check if trading pair exist (both in eth and usdt and that it s not ETH, before donig some math on it
                                                $perc = round($ticker_list[$tickusdt]['bidPrice']/$ticker_list[$ticketh]['askPrice']/$ticker_list['eth_usdt']['askPrice'],5);
                                                if ($perc > $min_profit){
                                                        //////////////////////////////on a un trade positif, on y va !
                                                                if ($ticker_list[$tickusdt]['askPrice']*$ticker_list[$tickusdt]['bidQty'] > $min_usdt && $ticker_list[$ticketh]['askQty']*$ticker_list[$ticketh]['askPrice'] > $min_eth && $ticker_list[$ticketh]['askPrice']*$ticker_list[$ticketh]['askQty']*$val_usdt_eth > $min_usdt){
                                                                        //print("------------------------------------\n");
                                                                        //print("$date : Initiate trade : $ticketh ($perc) \n");
                                                                        $price = $ticker_list[$ticketh]['askPrice'];
                                                                        $quantity = min(round($max_eth/$price, $pairs_infos[$ticketh]['baseAssetPrecision'],PHP_ROUND_HALF_UP), $ticker_list[$ticketh]['askQty'], $ticker_list[$tickusdt]['bidQty']);
                                                                        $quantity = round($quantity,$pairs_infos[$ticketh]['baseAssetPrecision'],PHP_ROUND_HALF_DOWN);
                                                                        $quantity_ini = round(min($max_eth/$price, $ticker_list[$ticketh]['askQty'], $ticker_list[$tickusdt]['bidQty']), $pairs_infos[$tickusdt]['baseAssetPrecision'],PHP_ROUND_HALF_UP);



                                                                        // $check_usdt = json_decode(ticker($tickusdt));
                                                                        // if ($check_usdt->ticker->bidPrice == $ticker_list[$tickusdt]['bidPrice']){
                                                                                $res1 = build_order($ticketh, "buy", $price, $quantity);
                                                                                $order1 = json_decode($res1);
                                                                                if ($order1->order->status == "Completed"){
                                                                                        //print("Order 1 : $ticketh - $quantity @ $price \n");
                                                                                        $price = $ticker_list[$tickusdt]['bidPrice'];
                                                                                        $quantity = $quantity_ini;
                                                                                        $quantity = round($quantity_ini/1.001, $pairs_infos[$tickusdt]['baseAssetPrecision'],PHP_ROUND_HALF_DOWN);
                                                                                        $res2 = build_order($tickusdt, "sell", $price, $quantity);
                                                                                        $weight += 1;
                                                                                        //print("Order 2 : $tickusdt - $quantity @ $price \n");
                                                                                        $price = $ticker_list['eth_usdt']['askPrice'];
                                                                                        $quantity = round(($quantity_ini)*$ticker_list[$ticketh]['askPrice'], $pairs_infos["eth_usdt"]['baseAssetPrecision'],PHP_ROUND_HALF_UP);
                                                                                        $res3 = build_order("eth_usdt", "buy", $price, $quantity);
                                                                                        //print("Order 3 : eth_usdt - $quantity @ $price \n");
                                                                                        print($date); print(" : "); print("6"); print(" - "); print("\033[32m Success trade $ticketh ( $perc ) !\033[0m \n");
                                                                                        $order2 = json_decode($res2);
                                                                                        $order3 = json_decode($res3);
                                                                                        //print_r($order1);
                                                                                        //print_r($order2);
                                                                                        //print_r($order3);
                                                                                usleep(400);
                                                                                        goto restart;
                                                                                }
                                                                                else {
                                                                                        $weight += 1;
                                                                                        $delete = deleteOrder($order1->order->orderId);
                                                                                        print("Missed the trade $ticketh, deleted it...\n");
                                                                                        goto restart;

                                                                                }



/*                                                                         }
                                                                        else {
                                                                                print("Counter trade out, missed the trade...\n");
                                                                                goto restart;
                                                                        } */
                                                                }
                                                }
                                        }
                                }













                        }
                        $j++;

        }
	}
	sleep(1);
}

?>
