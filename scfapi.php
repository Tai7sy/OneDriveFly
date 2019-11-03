<?php
// https://cloud.tencent.com/document/api/583/17235

function getfunctioninfo($function_name, $Region)
{
    $meth = 'GET';
    $url = 'scf.tencentcloudapi.com/'; // need
    $url .= '?Action=GetFunction'; // need
    $url .= '&FunctionName='.$function_name; // need
    $url .= '&Nonce='.time(); // need
    $url .= '&Region='.$Region; // need
    $url .= '&SecretId='.getenv('SecretId'); // need
    $url .= '&Timestamp='.time(); // need
    $url .= '&Token=';
    $url .= '&Version=2018-04-16'; // need
    //echo $url;

    $signStr = base64_encode(hash_hmac('sha1', $meth.$url, getenv('secretKey'), true));
    //echo urlencode($signStr);
    return file_get_contents('https://'.$url.'&Signature='.urlencode($signStr));
}

function updataEnvironment($function_name, $Region, $Envs)
{
    //json_decode($a,true)['Response']['Environment']['Variables'][0]['Key']
    $tmp = json_decode(getfunctioninfo($function_name, $Region),true)['Response']['Environment']['Variables'];
    foreach ($tmp as $tmp1) {
        $tmp_env[$tmp1['Key']] = $tmp1['Value'];
    }
    //echo json_encode($Envs);
    foreach ($Envs as $key1 => $value1) {
        $tmp_env[$key1] = $value1;
    }
    //echo json_encode($tmp_env);
    $tmp_env = array_filter($tmp_env); // 清除空值
    $tmp_env['Region'] = $Region;
    $tmp_env1 = [];
    $i = 0;
    foreach ($tmp_env as $key1 => $value1) {
        //array_push($Environment['Variables'],[ 'Key' => $key1, 'Value' => $value1 ]);
        $tmp_env1['Environment.Variables.'.$i.'.Key'] = $key1;
        $tmp_env1['Environment.Variables.'.$i.'.Value'] = $value1;
        $i++;
    }
    ksort($tmp_env1);
    $Environment = '';
    foreach ($tmp_env1 as $key1 => $value1) {
        $Environment .= '&' . $key1 . '=' . $value1;
    }
    //echo $Environment;
    //$Environment = json_encode($Environment);

    $meth = 'GET';
    $host = 'scf.tencentcloudapi.com'; // need
    $data = 'Action=UpdateFunctionConfiguration'; // need
    //$data .= '&Environment='.json_encode($Environment);
    $data .= $Environment;
    $data .= '&FunctionName='.$function_name; // need
    $data .= '&Nonce='.time(); // need
    $data .= '&Region='.$Region; // need
    $data .= '&SecretId='.getenv('SecretId'); // need
    $data .= '&Timestamp='.time(); // need
    $data .= '&Token=';
    $data .= '&Version=2018-04-16'; // need
    $tmpStr = $meth . $host . '/?' . $data;
    echo $data;

    $signStr = base64_encode(hash_hmac('sha1', $tmpStr, getenv('secretKey'), true));
    //echo urlencode($signStr);
    return file_get_contents('https://'.$host . '/?' . $data.'&Signature='.urlencode($signStr));
    //return curl_request('https://'.$host, $data.'&Signature='.urlencode($signStr));
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

    $signStr = base64_encode(hash_hmac('sha1', $updatameth.$updataurl, getenv('secretKey'), true));
    //echo urlencode($signStr);
    return file_get_contents('https://'.$updataurl.'&Signature='.urlencode($signStr));
}

?>
