<?php
error_reporting(-1);
set_time_limit(3000000); 

// Same as error_reporting(E_ALL);
ini_set('error_reporting', E_ALL);

$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "api";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 




function checkUser($email){
	GLOBAL $conn;
	$sql = "SELECT user_id from users where email = '". $email ."'";
	$rs = $conn->query($sql);
	$row = $rs->fetch_array(MYSQLI_NUM);
	if(isset($row[0]))
	{
		return $row[0];
	}
	else
	{
		return -1;
	}
}

function createUser($email){
	GLOBAL $conn;
	$sql = "INSERT INTO users (email) VALUES ('". $email ."')";
	$rs = $conn->query($sql);
	if($rs)
	{
		return $conn->insert_id;
	}
	else
	{
		return -1;
	}
}


function createOrder($user_id, $product_id, $price){
	GLOBAL $conn;
	$sql = "INSERT INTO order_history (user_id, product_id, price) VALUES ('". $user_id ."', '". $product_id ."', '". $price ."')";
	$rs = $conn->query($sql);
	if($rs)
	{
		return $conn->insert_id;
	}
	else
	{
		return -1;
	}
}


function getListOrder($email){
	GLOBAL $conn;
	$user_id = checkUser($email);


	if($check == -1)
	{
		echo false;
		die;
	}

	$sql = "SELECT * from order_history where user_id = '". $user_id ."'";
	$rs = $conn->query($sql);
	$row = mysqli_fetch_all($rs,MYSQLI_ASSOC);
	if(isset($row[0]))
	{
		echo json_encode($row);
	}
	else
	{
		echo false;
	}
	die;
}

/**
*
* example目录下为简单的支付样例，仅能用于搭建快速体验微信支付使用
* 样例的作用仅限于指导如何使用sdk，在安全上面仅做了简单处理， 复制使用样例代码时请慎重
* 请勿直接直接使用样例对外提供服务
* 
**/

require_once "../lib/WxPay.Api.php";
require_once "WxPay.NativePay.php";
require_once 'log.php';

//初始化日志
$logHandler= new CLogFileHandler("../logs/".date('Y-m-d').'.log');
$log = Log::Init($logHandler, 15);


function createCodeURL($email, $produc_id, $price){
	//模式一
	//不再提供模式一支付方式
	/**

	 * 流程：
	 * 1、组装包含支付信息的url，生成二维码
	 * 2、用户扫描二维码，进行支付
	 * 3、确定支付之后，微信服务器会回调预先配置的回调地址，在【微信开放平台-微信支付-支付配置】中进行配置
	 * 4、在接到回调通知之后，用户进行统一下单支付，并返回支付信息以完成支付（见：native_notify.php）
	 * 5、支付完成之后，微信服务器会通知支付成功
	 * 6、在支付成功通知中需要查单确认是否真正支付成功（见：notify.php）
	 */

	$notify = new NativePay();

	//模式二
	/**
	 * 流程：
	 * 1、调用统一下单，取得code_url，生成二维码
	 * 2、用户扫描二维码，进行支付
	 * 3、支付完成之后，微信服务器会通知支付成功
	 * 4、在支付成功通知中需要查单确认是否真正支付成功（见：notify.php）
	 */
	$input = new WxPayUnifiedOrder();
	$input->SetBody("Iphone 6Plus");
	$input->SetAttach("Iphone 6plus");
	$input->SetOut_trade_no("sdkphp123456789".date("YmdHis"));
	$input->SetTotal_fee("1");
	$input->SetTime_start(date("YmdHis"));
	$input->SetTime_expire(date("YmdHis", time() + 36000));
	$input->SetGoods_tag("Iphone 6plus Tag");

	$input->SetNotify_url("http://18.179.53.198/api/example/notify.php");
	$input->SetTrade_type("NATIVE");
	$input->SetProduct_id(rand(111111,999999));

	$user_id = checkUser($email);
	if($user_id == -1)
	{
		$user_id = createUser($email);
	}
	createOrder($user_id, $produc_id, $price);

	$result = $notify->GetPayUrl($input);
	if(isset($result["code_url"]))
	{
  		echo $result["code_url"];
	}
	else
	{
		echo null;
	}
	die;
}

function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}


function sendMail($email = null){
	// 
	
	$listToken = [];
	$token = generateRandomString(30);
	$content = file_get_contents(dirname(__FILE__)."/token.txt");
	if(isset($content))
	{
		$listToken = json_decode($content);
	}
	$tokenfile = fopen(dirname(__FILE__)."/token.txt", "w+") or die("Unable to open file!");
	$listToken[] = $token;
	fwrite($tokenfile, json_encode($listToken));
	fclose($tokenfile);

	$adminBody="Get Reward\n\n<br \>";
	$adminBody .="＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝\n\n<br \>";
	$adminBody .="Link get reward: http://52.199.160.114 \n\n<br \>";
	$adminBody .="Token Code: ".$token;


	include_once(dirname(__FILE__).'/transmitmail/PHPMailer/PHPMailerAutoload.php');	
	
	//Create a new PHPMailer instance
	$mail = new PHPMailer;
	$mail->isSMTP();
	$mail->CharSet = "UTF-8";
	$mail->SMTPDebug = 0;
	//$mail->Host = "mail.mor.vn";
	//$mail->Port = 25;
	$mail->Host = 'tls://smtp.gmail.com:587';
	$mail->SMTPOptions = array(
			'ssl' => array(
					'verify_peer' => false,
					'verify_peer_name' => false,
					'allow_self_signed' => true
			)
	);	
	$mail->SMTPAuth = true;
	$mail->Username = "contact@mor.vn";
	$mail->Password = ".(c'fQ9(<VZ`v7!q";
	$mail->setFrom(strip_tags('contact@mor.vn'), 'DEMO Wechat Pay' );
	$mail->Subject = "Get Reward";
	$mail->addAddress(strip_tags($email)); 
	$mail->msgHTML($adminBody);
	
	//send the message, check for errors
	// $mail->send();
    
    echo true;
    die;
}

function checkToken ($token)
{
	$content = file_get_contents(dirname(__FILE__)."/token.txt");
	if(isset($content))
	{
		$listToken = json_decode($content);
		if(in_array($token, $listToken))
		{
			echo true;
		}
	}
	echo false;
	die;
}

function delToken ($token)
{
	$content = file_get_contents(dirname(__FILE__)."/token.txt");
	if(isset($content))
	{
		if(isset($content))
		{
			$listTokenNew = [];
			$listToken = json_decode($content);
			foreach ($listToken as $key => $value) {
				if($value != $token)
				{
					$listTokenNew[] = $value;
				}
			}
			$tokenfile = fopen(dirname(__FILE__)."/token.txt", "w+") or die("Unable to open file!");
			fwrite($tokenfile, json_encode($listTokenNew));
			fclose($tokenfile);
		}
		
		
	}
	echo true;
	die;
}

if(isset($_GET['action']))
{
	if($_GET['action'] == "createCodeURL")
	{
		$email = null;
		if(isset($_GET['email']))
		{
			$email = $_GET['email'];
		}
		$product_id = null;
		if(isset($_GET['product_id']))
		{
			$product_id = $_GET['product_id'];
		}
		$price = null;
		if(isset($_GET['price']))
		{
			$price = $_GET['price'];
		}
		createCodeURL($email, $product_id, $price);
	}
	elseif($_GET['action'] == "sendMail")
	{
		$email = null;
		if(isset($_GET['email']))
		{
			$email = $_GET['email'];
		}
		sendMail($email);
	}
	elseif($_GET['action'] == "checkToken")
	{
		$token = null;
		if(isset($_GET['token']))
		{
			$token = $_GET['token'];
		}
		checkToken($token);
	}
	elseif($_GET['action'] == "delToken")
	{
		$token = null;
		if(isset($_GET['token']))
		{
			$token = $_GET['token'];
		}
		delToken($token);
	}
	elseif($_GET['action'] == "getListOrder")
	{
		$email = null;
		if(isset($_GET['email']))
		{
			$email = $_GET['email'];
		}
		getListOrder($email);
	}	
	die;
}

$conn->close();

?>