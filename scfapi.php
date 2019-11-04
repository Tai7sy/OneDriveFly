<?php
// https://cloud.tencent.com/document/api/583/17235

function getfunctioninfo($function_name, $Region)
{
    $meth = 'GET';
    $host = 'scf.tencentcloudapi.com';
    $tmpdata['Action'] = 'GetFunction';
    $tmpdata['FunctionName'] = $function_name;
    $tmpdata['Nonce'] = time();
    $tmpdata['Region'] = $Region;
    $tmpdata['SecretId'] = getenv('SecretId');
    $tmpdata['Timestamp'] = time();
    $tmpdata['Token'] = '';
    $tmpdata['Version'] = '2018-04-16';
    ksort($tmpdata);
    foreach ($tmpdata as $key1 => $value1) {
        $data .= '&' . $key1 . '=' . $value1;
    }
    $data = substr($data, 1); // 去掉第一个&
    $url = $host.'/?'.$data;
    //echo $meth.$url;
    $signStr = base64_encode(hash_hmac('sha1', $meth.$url, getenv('SecretKey'), true));
    //echo urlencode($signStr);
    return file_get_contents('https://'.$url.'&Signature='.urlencode($signStr));
}

function updataEnvironment($function_name, $Region, $Envs)
{
    //print_r($Envs);
    //json_decode($a,true)['Response']['Environment']['Variables'][0]['Key']
    $tmp = json_decode(getfunctioninfo($function_name, $Region),true)['Response']['Environment']['Variables'];
    foreach ($tmp as $tmp1) {
        $tmp_env[$tmp1['Key']] = $tmp1['Value'];
    }
    foreach ($Envs as $key1 => $value1) {
        $tmp_env[$key1] = $value1;
    }
    $tmp_env = array_filter($tmp_env); // 清除空值
    $tmp_env['Region'] = $Region;
    ksort($tmp_env);

    $i = 0;
    foreach ($tmp_env as $key1 => $value1) {
        //array_push($Environment['Variables'],[ 'Key' => $key1, 'Value' => $value1 ]);
        $tmpdata['Environment.Variables.'.$i.'.Key'] = $key1;
        $tmpdata['Environment.Variables.'.$i.'.Value'] = $value1;
        $i++;
    }
    $meth = 'GET';
    $host = 'scf.tencentcloudapi.com';
    $tmpdata['Action'] = 'UpdateFunctionConfiguration';
    $tmpdata['FunctionName'] = $function_name;
    $tmpdata['Nonce'] = time();
    $tmpdata['Region'] = $Region;
    $tmpdata['SecretId'] = getenv('SecretId');
    $tmpdata['Timestamp'] = time();
    $tmpdata['Token'] = '';
    $tmpdata['Version'] = '2018-04-16';
    ksort($tmpdata);
    foreach ($tmpdata as $key1 => $value1) {
        $data .= '&' . $key1 . '=' . $value1;
    }
    $data = substr($data, 1); // 去掉第一个&
    $url = $host.'/?'.$data;

    $signStr = base64_encode(hash_hmac('sha1', $meth.$url, getenv('SecretKey'), true));
    //echo urlencode($signStr);
    return file_get_contents('https://'.$url.'&Signature='.urlencode($signStr));
}

function updataProgram($function_name, $Region)
{
    $updatameth = 'GET';
    $updataurl = 'scf.tencentcloudapi.com/';
    $updataurl .= '?Action=UpdateFunctionCode';
    $updataurl .= '&Code.GitUrl=https://github.com/qkqpttgf/OneDrive_SCF';
    $updataurl .= '&CodeSource=Git';
    $updataurl .= '&FunctionName='.$function_name;
    $updataurl .= '&Handler=index.main_handler';
    $updataurl .= '&Nonce='.time();
    $updataurl .= '&Region='.$Region;
    $updataurl .= '&SecretId='.getenv('SecretId');
    $updataurl .= '&Timestamp='.time();
    $updataurl .= '&Token=';
    $updataurl .= '&Version=2018-04-16';
    //echo $updataurl;

    $signStr = base64_encode(hash_hmac('sha1', $updatameth.$updataurl, getenv('SecretKey'), true));
    //echo urlencode($signStr);
    return file_get_contents('https://'.$updataurl.'&Signature='.urlencode($signStr));
}

?>
