<?php

  // Интеграция CRM Clientbase с ВАТС Задарма (Zadarma)
  // https://ClientbasePro.ru
  // https://zadarma.com/ru/support/api/
  

  // функция реализует произвольный запрос к Zadarma
function GetZadarmaData($method='', $type='POST', $params=[]) {
    // проверка наличия метода
  if (!$method) return false;
  if (!in_array($type, array('GET','POST','PUT','DELETE'))) $type = 'POST'; 
    // готовим запрос
  if (!$params['format']) $params['format'] = 'json';
  $params = array_filter($params, function($a){return !is_object($a); });
  ksort($params);
  $paramsString = http_build_query($params, null, '&', PHP_QUERY_RFC1738);
  $signature = base64_encode(hash_hmac('sha1', $method.$paramsString.md5($paramsString), ZADARMA_SECRET));
  $auth = array('Authorization: '.ZADARMA_KEY.':'.$signature);
  $options = array(
    CURLOPT_URL            => ZADARMA_URL.$method,
    CURLOPT_CUSTOMREQUEST  => $type,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    //CURLOPT_HEADERFUNCTION => array($this, 'parseHeaders'),
    CURLOPT_HTTPHEADER     => $auth
  );
  $ch = curl_init();
  if ('GET'==$type) $options[CURLOPT_URL] .= '?'.http_build_query($params, null, '&', PHP_QUERY_RFC1738);
  else {
    $options[CURLOPT_POST] = true;
    if(array_filter($params,'is_object')) $options[CURLOPT_POSTFIELDS] = $params;
    else $options[CURLOPT_POSTFIELDS] = http_build_query($params, null, '&', PHP_QUERY_RFC1738);
  }
  curl_setopt_array($ch, $options);
  $response = curl_exec($ch);
  curl_close($ch);        
  return (curl_error($ch)) ? false : $response;  
}


  // функция возвращает статистику по ВАТС за период $start - $end, фильтр по номеру $sip
function GetZadarmaStatistics($start='',$end='',$sip='') {
    // проверка входных данных
  $start = ($start) ? date('Y-m-d 00:00:00',strtotime($start)) : date('Y-m-d 00:00:00');
  $end = ($end) ? date('Y-m-d 00:00:00',strtotime($end)) : date('Y-m-d 00:00:00');
    // готовим запрос
  $params = array('from'=>$start, 'to'=>$end);
  if ($sip) $params['sip'] = $sip;
  $stat = GetZadarmaData('/v1/statistics/', 'GET', $params);
  if (!$stat) return false;
  $stat = json_decode($stat, true);
  return ('success'==$stat['status']) ? $stat['stats'] : false;
}









?>