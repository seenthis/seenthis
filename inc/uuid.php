<?php

/*
 *  Fonctions pour gerer des UUID
 */

/*
var_dump(UUID::getuuid("yo"));
var_dump(UUID::getuuid("508a463f-58d0-42b1-8144-86372dacd8d8"));
var_dump(UUID::Valid(UUID::getuuid())); #true
*/

class UUID {
	const REGEXP_UUID = '[A-Fa-f0-9]{8}-[A-Fa-f0-9]{4}-[A-Fa-f0-9]{4}-[A-Fa-f0-9]{4}-[A-Fa-f0-9]{12}';

	public static function Valid($what) {
		return preg_match(',^'.UUID::REGEXP_UUID.'$,i', $what);
	}


	public static function getuuid($what = null) {
		if (is_null($what))
			return UUID::random_uuid();

		if (UUID::Valid($what))
			return strtolower($what);

		$m = md5($what);

		$uuid = sprintf(
			"%s-%s-%s-%s-%s",
				substr($m,0,8),
				substr($m,8,4),
				substr($m,12,4),
				substr($m,16,4),
				substr($m,20,32)
		);

		return $uuid;
	}

/**
 *
 * code extrait de cakephp (licence MIT)
 *
 * http://book.cakephp.org/1.3/view/1481/uuid
 * https://github.com/cakephp/cakephp/blob/master/lib/Cake/Utility/String.php
 *
 */

/**
 * Generate a random UUID
 *
 * @see http://www.ietf.org/rfc/rfc4122.txt
 * @return RFC 4122 UUID
 *
 */
	public static function random_uuid() {
		$node = $_ENV['SERVER_ADDR'];

		if (strpos($node, ':') !== false) {
			if (substr_count($node, '::')) {
				$node = str_replace(
					'::', str_repeat(':0000', 8 - substr_count($node, ':')) . ':', $node
				);
			}
			$node = explode(':', $node);
			$ipSix = '';

			foreach ($node as $id) {
				$ipSix .= str_pad(base_convert($id, 16, 2), 16, 0, STR_PAD_LEFT);
			}
			$node = base_convert($ipSix, 2, 10);

			if (strlen($node) < 38) {
				$node = null;
			} else {
				$node = crc32($node);
			}
		} elseif (empty($node)) {
			$host = $_ENV['HOSTNAME'];

			if (empty($host)) {
				$host = $_ENV['HOST'];
			}

			if (!empty($host)) {
				$ip = gethostbyname($host);

				if ($ip === $host) {
					$node = crc32($host);
				} else {
					$node = ip2long($ip);
				}
			}
		} elseif ($node !== '127.0.0.1') {
			$node = ip2long($node);
		} else {
			$node = null;
		}

		if (empty($node)) {
			# $node = crc32(Configure::read('Security.salt'));
			$node = crc32(rand(0,255));
		}

		if (function_exists('hphp_get_thread_id')) {
			$pid = hphp_get_thread_id();
		} elseif (function_exists('zend_thread_id')) {
			$pid = zend_thread_id();
		} else {
			$pid = getmypid();
		}

		if (!$pid || $pid > 65535) {
			$pid = mt_rand(0, 0xfff) | 0x4000;
		}

		list($timeMid, $timeLow) = explode(' ', microtime());
		$uuid = sprintf(
			"%08x-%04x-%04x-%02x%02x-%04x%08x", (int)$timeLow, (int)substr($timeMid, 2) & 0xffff,
			mt_rand(0, 0xfff) | 0x4000, mt_rand(0, 0x3f) | 0x80, mt_rand(0, 0xff), $pid, $node
		);

		return $uuid;
	}
}

