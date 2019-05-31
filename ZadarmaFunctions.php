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


  // функция возвращает общую статистику за период $start - $end, фильтр по номеру $sip
function GetZadarmaStatistics($start='',$end='',$sip='') {
    // проверка входных данных
  $start = ($start) ? date('Y-m-d 00:00:00',strtotime($start)) : date('Y-m-d 00:00:00');
  $end = ($end) ? date('Y-m-d 23:59:59',strtotime($end)) : date('Y-m-d 23:59:59');
    // готовим запрос
  $params = array('start'=>$start, 'end'=>$end);
  if ($sip) $params['sip'] = $sip;
  $stat = GetZadarmaData('/v1/statistics/', 'GET', $params);
  if (!$stat) return false;
  $stat = json_decode($stat, true);
  return ('success'==$stat['status']) ? $stat['stats'] : false;
}


  // функция возвращает статистику по ВАТС за период $start - $end
function GetZadarmaVPBXStatistics($start='',$end='') {
    // проверка входных данных
  $start = ($start) ? date('Y-m-d 00:00:00',strtotime($start)) : date('Y-m-d 00:00:00');
  $end = ($end) ? date('Y-m-d 23:59:59',strtotime($end)) : date('Y-m-d 23:59:59');
    // готовим запрос
  $params = array('start'=>$start, 'end'=>$end);
  $params['version'] = 2;
  $stat = GetZadarmaData('/v1/statistics/pbx/', 'GET', $params);
  if (!$stat) return false;
  $stat = json_decode($stat, true);
  return ('success'==$stat['status']) ? $stat['stats'] : false;
}


  // функция инициирует звонок с внутреннего $from на номер $to через линию $sip
function MakeZadarmaCallback($from='',$to='',$sip='') {
    // проверка входных данных
  if (!$from || !$to) return false;
    // запрос
  $params = array('from'=>$from, 'to'=>$to);
  if ($sip) $params['sip'] = $sip;
  $callback = GetZadarmaData('/v1/request/callback/', 'GET', $params);
  if ($callback) $callback = json_decode($callback, true);
  else return false;
  return ('success'==$callback['status']) ? $callback : false;  
}


  // функция возвращает массив ссылок на аудиофайлы записи звонка $pbx_call_id
function GetCallRecords($pbx_call_id) {
    // проверка входных данных
  if (!$pbx_call_id) return false;
    // запрос
  $params = array('pbx_call_id'=>$pbx_call_id, 'lifetime'=>5184000);
  $records = GetZadarmaData('/v1/pbx/record/request/', 'GET', $params);
  if ($records) $records = json_decode($records, true);
  else return false;
  if ('success'!=$records['status']) return false;
    // собираем массив $tmp со ссылками на файлы
  if ($records['link']) $tmp[] = $records['link'];
  foreach ($records['links'] as $link) $tmp[] = $link;
  return $tmp;  
}









?>