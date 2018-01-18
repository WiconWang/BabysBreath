<?php

class Comm_Validate {
	public static function isEmail($email) {
		return !empty($email) && preg_match(Comm_Tools::cleanNonUnicodeSupport('/^[a-z\p{L}0-9!#$%&\'*+\/=?^`{}|~_-]+[.a-z\p{L}0-9!#$%&\'*+\/=?^`{}|~_-]*@[a-z\p{L}0-9]+[._a-z\p{L}0-9-]*\.[a-z0-9]+$/ui'), $email);
	}

	public static function isCaptcha($captcha) {
		return preg_match("/^[a-f0-9A-F]{6}$/", $captcha);
	}
	public static function isMobilePhone($mobilePhone) {
		return preg_match("/^1[34578][0-9]{9}$/", $mobilePhone);
	}

	public static function isChinese($data) {
		return preg_match("/^[\x{4e00}-\x{9fa5}a-zA-Z_]+$/u", $data);
	}

	public static function isMd5($md5) {
		return preg_match('/^[a-f0-9A-F]{32}$/', $md5);
	}

	public static function isSha1($sha1) {
		return preg_match('/^[a-fA-F0-9]{40}$/', $sha1);
	}

	public static function isToken($token) {
		return preg_match('/^[a-zA-Z0-9=]+$/', $token);
	}

	public static function isFloat($float) {
		return strval((float)$float) == strval($float);
	}

	public static function isUnsignedFloat($float) {
		return strval((float)$float) == strval($float) && $float >= 0;
	}

	public static function isOptFloat($float) {
		return empty($float) || Comm_Validate::isFloat($float);
	}

	public static function isName($name) {
		return preg_match(Comm_Tools::cleanNonUnicodeSupport('/^[^!<>,;?=+()@#"°{}$%:]+$/u'), stripslashes($name));
	}

	public static function isAlias($alias) {
		return preg_match('/^[a-zA-Z-]{4-12}$/u', $alias);
	}

	public static function isPrice($price) {
		return preg_match('/^[0-9]{1,10}(\.[0-9]{1,9})?$/', $price);
	}

	public static function isNegativePrice($price) {
		return preg_match('/^[-]?[0-9]{1,10}(\.[0-9]{1,9})?$/', $price);
	}

	public static function isSearch($search) {
		return preg_match('/^[^<>;=#{}]{1,64}$/u', $search);
	}

	public static function isGenericName($name) {
		return preg_match(Comm_Tools::cleanNonUnicodeSupport('/^[^<>;=+@#"°{}$%:]+$/u'), stripslashes($name));
	}
	public static function isBankNo($bankno) {
		return preg_match('/^[0-9]{12,19}$/', $bankno);
	}
	public static function isMessage($message) {
		return !empty($message) && !preg_match('/[<>{}]/i', $message);
	}

	public static function isCleanHtml($html) {
		$events = 'onmousedown|onmousemove|onmmouseup|onmouseover|onmouseout|onload|onunload|onfocus|onblur|onchange';
		$events .= '|onsubmit|ondblclick|onclick|onkeydown|onkeyup|onkeypress|onmouseenter|onmouseleave|onerror|onselect|onreset|onabort|ondragdrop|onresize|onactivate|onafterprint|onmoveend';
		$events .= '|onafterupdate|onbeforeactivate|onbeforecopy|onbeforecut|onbeforedeactivate|onbeforeeditfocus|onbeforepaste|onbeforeprint|onbeforeunload|onbeforeupdate|onmove';
		$events .= '|onbounce|oncellchange|oncontextmenu|oncontrolselect|oncopy|oncut|ondataavailable|ondatasetchanged|ondatasetcomplete|ondeactivate|ondrag|ondragend|ondragenter|onmousewheel';
		$events .= '|ondragleave|ondragover|ondragstart|ondrop|onerrorupdate|onfilterchange|onfinish|onfocusin|onfocusout|onhashchange|onhelp|oninput|onlosecapture|onmessage|onmouseup|onmovestart';
		$events .= '|onoffline|ononline|onpaste|onpropertychange|onreadystatechange|onresizeend|onresizestart|onrowenter|onrowexit|onrowsdelete|onrowsinserted|onscroll|onsearch|onselectionchange';
		$events .= '|onselectstart|onstart|onstop';

		return (!preg_match('/<[ \t\n]*script/ims', $html) && !preg_match('/(' . $events . ')[ \t\n]*=/ims', $html) && !preg_match('/.*script\:/ims', $html) && !preg_match('/<[ \t\n]*i?frame/ims', $html));
	}

	public static function isPasswd($passwd, $size = 6) {
		return preg_match('/^[.A-Za-z_0-9-!@#$%\^&*()]{' . $size . ',12}$/ui', $passwd);
	}

	public static function isPayPass($passwd, $size = 6) {
		return preg_match('/^[0-9]{'.$size.'}$/ui', $passwd);
	}

	public static function isDateFormat($date) {
		return (bool)preg_match('/^([0-9]{4})-((0?[0-9])|(1[0-2]))-((0?[0-9])|([1-2][0-9])|(3[01]))( [0-9]{2}:[0-9]{2}:[0-9]{2})?$/', $date);
	}

	public static function isDate($date) {
		if (!preg_match('/^([0-9]{4})-((0?[1-9])|(1[0-2]))-((0?[1-9])|([1-2][0-9])|(3[01]))( [0-9]{2}:[0-9]{2}:[0-9]{2})?$/ui', $date, $matches))
			return false;

		return checkdate(intval($matches[2]), intval($matches[5]), intval($matches[0]));
	}

	public static function isTimestamp($time) {
		//return ctype_digit($time) && $time <= 2147483647;
		return (int)$time > 0 && strtotime(date('Y-m-d H:i:s', $time)) === (int)$time;
	}

	public static function isBirthDate($date) {
		if (empty($date) || $date == '0000-00-00')
			return true;
		if (preg_match('/^([0-9]{4})-((?:0?[1-9])|(?:1[0-2]))-((?:0?[1-9])|(?:[1-2][0-9])|(?:3[01]))([0-9]{2}:[0-9]{2}:[0-9]{2})?$/', $date, $birth_date))
		{
			if ($birth_date[1] > date('Y') && $birth_date[2] > date('m') && $birth_date[3] > date('d'))
				return false;

			return true;
		}

		return false;
	}

	public static function isBool($bool) {
		return $bool === null || is_bool($bool) || preg_match('/^0|1$/', $bool);
	}

	public static function isOrderWay($way) {
		return ($way === 'ASC' | $way === 'DESC' | $way === 'asc' | $way === 'desc');
	}

	public static function isInt($value) {
		return ((string)(int)$value === (string)$value || $value === false);
	}

	public static function isPercent($value) { //0.00-100.00
		return (preg_match('/^([1]00|([1-9]\d{1}|\d{1}|0)\.\d{1,2})/', $value));
	}

	public static function isUnsignedInt($value) {
		return (preg_match('#^[0-9]+$#', (string)$value) && $value < 4294967296 && $value >= 0);
	}

	public static function isPercentage($value) {
		return (Comm_Validate::isFloat($value) && $value >= 0 && $value <= 100);
	}

	public static function isUnsignedId($id) {
		return Comm_Validate::isUnsignedInt($id); /* Because an id could be equal to zero when there is no association */
	}

	public static function isNullOrUnsignedId($id) {
		return $id === null || Comm_Validate::isUnsignedId($id);
	}

	public static function isLoadedObject($object) {
		return is_object($object) && $object->id;
	}

	public static function isUrl($url) {
		return preg_match('/^[~:#,%&_=\(\)\.\? \+\-@\/a-zA-Z0-9]+$/', $url);
	}

	public static function isUrlOrEmpty($url) {
		return empty($url) || self::isUrl($url);
	}

	public static function isAbsoluteUrl($url) {
		return preg_match('/^https?:\/\/[!,:#%&_=\(\)\.\? \+\-@\/a-zA-Z0-9]+$/', $url);
	}

	public static function isMySQLEngine($engine) {
		return (in_array($engine, array('InnoDB', 'MyISAM')));
	}

	public static function isUnixName($data) {
		return preg_match('/^[a-z0-9\._-]+$/ui', $data);
	}

	public static function isFileName($name) {
		return preg_match('/^[a-zA-Z0-9_.-]+$/', $name);
	}

	public static function isDirName($dir) {
		return self::isFileName($dir);
	}

	public static function isOptUnsignedId($id) {
		return is_null($id) OR self::isUnsignedId($id);
	}

	public static function isString($data) {
		return !empty($data) && is_string($data);
	}

	public static function isSerializedArray($data) {
		return $data === null || (is_string($data) && preg_match('/^a:[0-9]+:{.*;}$/s', $data));
	}

	public static function isIpAddress($data) {
		$ary = explode('.', $data);
		if (!preg_match('/[^\.\d]/', $data) && count($ary) == 4 && $ary[0] >= 0 && $ary[1] >= 0 && $ary[2] >= 0 && $ary[3] >= 0 && $ary[0] <= 255 && $ary[1] <= 255 && $ary[2] <= 255 && $ary[3] <= 255
			)
			return true;
		else
			return false;
	}

	public static function isIMEI($data) {
		return preg_match('/^[0-9a-z]{15}$/i', $data);
	}

	public static function isISBN($isbn) {
		return preg_match('/^[0-9]{13}$/', $isbn);
	}
	public static function isGUID($guid) {
		return preg_match('/^[a-f0-9]{8}(-[a-f0-9]{4}){3}-[a-f0-9]{12}$/', $guid);
	}

	public static function isPublishTime($time) {
		return preg_match('/^[0-9]{4}-[0-9]{2}$/', $time);
	}

	public static function isNickname($data) {
		return preg_match("/^[\x{4e00}-\x{9fa5}a-zA-Z_0-9]{2,16}$/u", $data);
	}

	public static function isOptNickname($data) {
		if ($data == null || self::isNickname($data))
		{
			return true;
		}

		return false;
	}

	public static function isNumber($data) {
		return preg_match("/^-?[0-9]+$/u", $data);
	}

	public static function isExpressNumber($data) {
		return preg_match('/^[0-9A-Za-z]+$/', $data);
	}
	public static function isIdCardNew($card) {
		return preg_match("/^[1-9]{1}[0-9]{16}[0-9xX]{1}$/", $card);
	}
	public static function isIdCard($idcard) {
		if (empty ( $idcard )) {
			return false;
		}
		$City = array (
			11 => "北京",
			12 => "天津",
			13 => "河北",
			14 => "山西",
			15 => "内蒙古",
			21 => "辽宁",
			22 => "吉林",
			23 => "黑龙江",
			31 => "上海",
			32 => "江苏",
			33 => "浙江",
			34 => "安徽",
			35 => "福建",
			36 => "江西",
			37 => "山东",
			41 => "河南",
			42 => "湖北",
			43 => "湖南",
			44 => "广东",
			45 => "广西",
			46 => "海南",
			50 => "重庆",
			51 => "四川",
			52 => "贵州",
			53 => "云南",
			54 => "西藏",
			61 => "陕西",
			62 => "甘肃",
			63 => "青海",
			64 => "宁夏",
			65 => "新疆",
			71 => "台湾",
			81 => "香港",
			82 => "澳门",
			91 => "国外"
			);
		$iSum = 0;
		$idCardLength = strlen ( $idcard );
		// 长度验证
		if (! preg_match ( '/^\d{17}(\d|x)$/i', $idcard ) and ! preg_match ( '/^\d{15}$/i', $idcard )) {
			return false;
		}
		// 地区验证
		if (! array_key_exists ( intval ( substr ( $idcard, 0, 2 ) ), $City )) {
			return false;
		}
		// 15位身份证验证生日，转换为18位
		if ($idCardLength == 15) {
			$sBirthday = '19' . substr ( $idcard, 6, 2 ) . '-' . substr ( $idcard, 8, 2 ) . '-' . substr ( $idcard, 10, 2 );
			$d = new DateTime ( $sBirthday );
			$dd = $d->format ( 'Y-m-d' );
			if ($sBirthday != $dd) {
				return false;
			}
			$idcard = substr ( $idcard, 0, 6 ) . "19" . substr ( $idcard, 6, 9 ); // 15to18
			$Bit18 = self::getVerifyBit ( $idcard ); // 算出第18位校验码
			$idcard = $idcard . $Bit18;
		}
		// 判断是否大于2078年，小于1900年
		$year = substr ( $idcard, 6, 4 );
		if ($year < 1900 || $year > 2078) {
			return false;
		}

		// 18位身份证处理
		$sBirthday = substr ( $idcard, 6, 4 ) . '-' . substr ( $idcard, 10, 2 ) . '-' . substr ( $idcard, 12, 2 );
		$d = new DateTime ( $sBirthday );
		$dd = $d->format ( 'Y-m-d' );
		if ($sBirthday != $dd) {
			return false;
		}
		// 身份证编码规范验证
		$idcard_base = substr ( $idcard, 0, 17 );
		if (strtoupper ( substr ( $idcard, 17, 1 ) ) != self::getVerifyBit ( $idcard_base )) {
			return false;
		}
		return true;
	}

	// 计算身份证校验码，根据国家标准GB 11643-1999
	public static function getVerifyBit($idcard_base) {
		if (strlen ( $idcard_base ) != 17) {
			return false;
		}
		// 加权因子
		$factor = array (
			7,
			9,
			10,
			5,
			8,
			4,
			2,
			1,
			6,
			3,
			7,
			9,
			10,
			5,
			8,
			4,
			2
			);
		// 校验码对应值
		$verify_number_list = array (
			'1',
			'0',
			'X',
			'9',
			'8',
			'7',
			'6',
			'5',
			'4',
			'3',
			'2'
			);
		$checksum = 0;
		for($i = 0; $i < strlen ( $idcard_base ); $i ++) {
			$checksum += substr ( $idcard_base, $i, 1 ) * $factor [$i];
		}
		$mod = $checksum % 11;
		$verify_number = $verify_number_list [$mod];
		return $verify_number;
	}

	public static function isImg($url){
		return preg_match('/(\.jpg|\.jpeg|\.png|\.gif)$/i', $url);
	}
}
