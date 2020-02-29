<?php
// https://cloud.tencent.com/document/api/583/17235

function post2url($url, $data)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    $response = curl_exec($ch);
    curl_close($ch);
    //echo $response;
    return $response;
}

function params($arr)
{
    $str = '';
    ksort($arr);
    foreach ($arr as $k1 => $v1) {
        $str .= '&' . $k1 . '=' . $v1;
    }
    $str = substr($str, 1);
    return $str;
}

function scf_get_function($function_name, $Region, $Namespace)
{
    //$meth = 'GET';
    $meth = 'POST';
    $host = 'scf.tencentcloudapi.com';
    $tmpdata['Action'] = 'GetFunction';
    $tmpdata['FunctionName'] = $function_name;
    $tmpdata['Namespace'] = $Namespace;
    $tmpdata['Nonce'] = time();
    $tmpdata['Region'] = getenv('Region');
    $tmpdata['SecretId'] = getenv('SecretId');
    $tmpdata['Timestamp'] = time();
    $tmpdata['Token'] = '';
    $tmpdata['Version'] = '2018-04-16';
    $data = params($tmpdata);
    $signStr = base64_encode(hash_hmac('sha1', $meth . $host . '/?' . $data, getenv('SecretKey'), true));
    //echo urlencode($signStr);
    //return file_get_contents('https://'.$url.'&Signature='.urlencode($signStr));
    return post2url('https://' . $host, $data . '&Signature=' . urlencode($signStr));
}

function scf_update_env($Envs, $function_name, $Region, $Namespace)
{
    //print_r($Envs);
    //json_decode($a,true)['Response']['Environment']['Variables'][0]['Key']
    $tmp = json_decode(scf_get_function($function_name, $Region, $Namespace), true)['Response']['Environment']['Variables'];
    $tmp_env = [];
    foreach ($tmp as $tmp1) {
        $tmp_env[$tmp1['Key']] = $tmp1['Value'];
    }
    foreach ($Envs as $key1 => $value1) {
        $tmp_env[$key1] = $value1;
    }
    $tmp_env = array_filter($tmp_env, function ($arr) {
        return !empty($arr);
    });
    $tmp_env['Region'] = getenv('Region');
    ksort($tmp_env);

    $i = 0;
    foreach ($tmp_env as $key1 => $value1) {
        $tmpdata['Environment.Variables.' . $i . '.Key'] = $key1;
        $tmpdata['Environment.Variables.' . $i . '.Value'] = $value1;
        $i++;
    }
    $meth = 'POST';
    $host = 'scf.tencentcloudapi.com';
    $tmpdata['Action'] = 'UpdateFunctionConfiguration';
    $tmpdata['FunctionName'] = $function_name;
    $tmpdata['Namespace'] = $Namespace;
    $tmpdata['Nonce'] = time();
    $tmpdata['Region'] = getenv('Region');
    $tmpdata['SecretId'] = getenv('SecretId');
    $tmpdata['Timestamp'] = time();
    $tmpdata['Token'] = '';
    $tmpdata['Version'] = '2018-04-16';
    $data = params($tmpdata);
    $signStr = base64_encode(hash_hmac('sha1', $meth . $host . '/?' . $data, getenv('SecretKey'), true));
    //echo urlencode($signStr);
    return post2url('https://' . $host, $data . '&Signature=' . urlencode($signStr));
}

function scf_update_configuration($function_name, $Region, $Namespace)
{
    $meth = 'POST';
    $host = 'scf.tencentcloudapi.com';
    $tmpdata['Action'] = 'UpdateFunctionConfiguration';
    $tmpdata['FunctionName'] = $function_name;
    $tmpdata['Description'] = 'Onedrive index in SCF. SCF上的Onedrive目录网站程序。';
    $tmpdata['MemorySize'] = 128;
    $tmpdata['Timeout'] = 30;
    $tmpdata['Namespace'] = $Namespace;
    $tmpdata['Nonce'] = time();
    $tmpdata['Region'] = getenv('Region');
    $tmpdata['SecretId'] = getenv('SecretId');
    $tmpdata['Timestamp'] = time();
    $tmpdata['Token'] = '';
    $tmpdata['Version'] = '2018-04-16';
    $data = params($tmpdata);
    $signStr = base64_encode(hash_hmac('sha1', $meth . $host . '/?' . $data, getenv('SecretKey'), true));
    //echo urlencode($signStr);
    return post2url('https://' . $host, $data . '&Signature=' . urlencode($signStr));
}

function scf_update_code($function_name, $Region, $Namespace)
{
    $meth = 'POST';
    $host = 'scf.tencentcloudapi.com';
    $tmpdata['Action'] = 'UpdateFunctionCode';
    $tmpdata['Code.GitUrl'] = 'https://github.com/qkqpttgf/OneManager-php';
    $tmpdata['CodeSource'] = 'Git';
    $tmpdata['FunctionName'] = $function_name;
    $tmpdata['Handler'] = 'index.main_handler';
    $tmpdata['Namespace'] = $Namespace;
    $tmpdata['Nonce'] = time();
    $tmpdata['Region'] = getenv('Region');
    $tmpdata['SecretId'] = getenv('SecretId');
    $tmpdata['Timestamp'] = time();
    $tmpdata['Token'] = '';
    $tmpdata['Version'] = '2018-04-16';
    $data = params($tmpdata);
    $signStr = base64_encode(hash_hmac('sha1', $meth . $host . '/?' . $data, getenv('SecretKey'), true));
    //echo urlencode($signStr);
    return post2url('https://' . $host, $data . '&Signature=' . urlencode($signStr));
}



function EnvOpt($function_name, $Region, $namespace = 'default', $needUpdate = 0)
{
    $constEnv = [
        //'admin',
        'adminloginpage', 'domain_path', 'imgup_path', 'passfile', 'private_path', 'public_path', 'sitename', 'language'
    ];
    asort($constEnv);
    $html = '<title>SCF ' . trans('Setup') . '</title>';
    if ($_POST['updateProgram'] == trans('updateProgram')) {
        $response = json_decode(scf_update_code($function_name, $Region, $namespace), true)['Response'];
        if (isset($response['Error'])) {
            $html = $response['Error']['Code'] . '<br>
' . $response['Error']['Message'] . '<br><br>
function_name:' . $_SERVER['function_name'] . '<br>
Region:' . $_SERVER['Region'] . '<br>
namespace:' . $namespace . '<br>
<button onclick="location.href = location.href;">' . trans('Reflesh') . '</button>';
            $title = 'Error';
        } else {
            $html .= trans('UpdateSuccess') . '<br>
<button onclick="location.href = location.href;">' . trans('Reflesh') . '</button>';
            $title = trans('Setup');
        }
        return message($html, $title);
    }
    if ($_POST['submit1']) {
        foreach ($_POST as $k => $v) {
            if (in_array($k, $constEnv)) {
                $tmp[$k] = $v;
            }
        }
        echo scf_update_env($tmp, $function_name, $Region, $namespace);
        $html .= '<script>location.href=location.href</script>';
    }
    if ($_GET['preview']) {
        $preurl = $_SERVER['PHP_SELF'] . '?preview';
    } else {
        $preurl = path_format($_SERVER['PHP_SELF'] . '/');
    }
    $html .= '
        <a href="' . $preurl . '">' . trans('Back') . '</a>&nbsp;&nbsp;&nbsp;
        <a href="https://github.com/qkqpttgf/OneDrive_SCF">Github</a><br>';
    if ($needUpdate) {
        $html .= '<pre>' . $_SERVER['github_version'] . '</pre>
        <form action="" method="post">
            <input type="submit" name="updateProgram" value="' . trans('updateProgram') . '">
        </form>';
    } else {
        $html .= trans('NotNeedUpdate');
    }
    $html .= '
    <form action="" method="post">
    <table border=1 width=100%>';
    foreach ($constEnv as $key) {
        if ($key == 'language') {
            $html .= '
        <tr>
            <td><label>' . $key . '</label></td>
            <td width=100%>
                <select name="' . $key . '">';
            foreach (Lang::all()['languages'] as $key1 => $value1) {
                $html .= '
                    <option value="' . $key1 . '" ' . ($key1 == getenv($key) ? 'selected="selected"' : '') . '>' . $value1 . '</option>';
            }
            $html .= '
                </select>
            </td>
        </tr>';
        } else $html .= '
        <tr>
            <td><label>' . $key . '</label></td>
            <td width=100%><input type="text" name="' . $key . '" value="' . getenv($key) . '" placeholder="' . trans('EnvironmentsDescription.' . $key) . '" style="width:100%"></td>
        </tr>';
    }
    $html .= '</table>
    <input type="submit" name="submit1" value="' . trans('Setup') . '">
    </form>';
    return message($html, trans('Setup'));
}