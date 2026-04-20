<?php
$base_url = 'http://localhost/AT-AMS';

function curl_request($url, $post_data = null, $cookie_file = 'cookies.txt') {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
    
    // Follow redirects
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
    
    if ($post_data) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
    }
    
    $result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ['code' => $http_code, 'body' => $result];
}

$cookie_file = __DIR__ . '/cookies.txt';
if(file_exists($cookie_file)) unlink($cookie_file);

// 1. Visit Login
echo "Testing Login Form...\n";
$res = curl_request("$base_url/pages/auth/login.php", null, $cookie_file);
echo "Status: " . $res['code'] . "\n";

// 2. Perform Login
echo "Submitting Login Form...\n";
$post_data = [
    'username' => 'superadmin',
    'password' => 'admin123',
    'login' => '1'
];
$res = curl_request("$base_url/pages/auth/login.php", $post_data, $cookie_file);
echo "Status: " . $res['code'] . "\n";
// Because we follow location, it should be at dashboard now
$is_logged_in = strpos($res['body'], 'superadmin') !== false || strpos($res['body'], 'connexion') === false;

echo "Logged in: " . ($is_logged_in ? "Yes" : "No (maybe still on login page?)\n");

function check_php_errors($html) {
    if (strpos($html, 'Fatal error') !== false) return "Fatal error found";
    if (strpos($html, 'Parse error') !== false) return "Parse error found";
    if (strpos($html, 'Warning:') !== false) return "Warning found";
    if (strpos($html, 'Uncaught mysqli_sql_exception') !== false) return "MySQL Exception";
    if (strpos($html, 'Notice:') !== false) return "Notice found";
    return false;
}

if ($err = check_php_errors($res['body'])) {
    echo "Login Error: $err\n";
    preg_match('/<br \/>\n<b>(.*?)<\/b>:  (.*?)<br \/>/s', $res['body'], $matches);
    if($matches) echo "Details: " . trim(strip_tags($matches[0])) . "\n";
    else {
        // Find general error messages
        preg_match('/(Fatal error|Warning|Notice|Parse error|Uncaught mysqli_sql_exception)(.*?)(<br|\n)/i', strip_tags($res['body']), $matches);
        if($matches) echo "Details: " . $matches[0] . "\n";
    }
}

// 4. Test other known pages statically
$pages_to_test = [
    '/pages/dashboard/index.php',
    '/pages/dashboard/admin.php',
    '/pages/admin/users.php',
    '/pages/admin/departments.php',
    '/pages/documents/index.php',
    '/pages/documents/upload.php',
    '/pages/profile/index.php'
];

foreach($pages_to_test as $page) {
    echo "Testing $page...\n";
    $p_res = curl_request($base_url . $page, null, $cookie_file);
    echo "Status: " . $p_res['code'] . " - Length: " . strlen($p_res['body']) . "\n";
    if ($err = check_php_errors($p_res['body'])) {
        echo "Error on $page: $err\n";
        preg_match('/<br \/>\n<b>(.*?)<\/b>:  (.*?)<br \/>/s', $p_res['body'], $matches);
        if($matches) echo "Details: " . trim(strip_tags($matches[0])) . "\n";
        else {
             preg_match('/(Fatal error|Warning|Notice|Parse error|Uncaught mysqli_sql_exception)[^\n<]+/i', strip_tags($p_res['body']), $matches);
             if($matches) echo "Details: " . $matches[0] . "\n";
        }
    }
}
unlink($cookie_file);
?>
