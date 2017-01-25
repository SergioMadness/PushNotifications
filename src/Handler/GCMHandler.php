<?php
/**
 * @author Richard Weinhold
 */

namespace ricwein\PushNotification\Handler;

use ricwein\PushNotification\PushHandler;

/**
 * PushHandler for Google Cloud Messaging
 */
class GCMHandler extends PushHandler {

	/**
	 * @var array
	 */
	protected $_server = [
		'token' => '',
		'url'   => 'https://gcm-http.googleapis.com/gcm/send',
	];

	/**
	 * send notification to Googles GCM servers
	 * @param string $message
	 * @param array $payload
	 * @param array $devices
	 * @return bool
	 */
	public function send($message, array $payload = [], array $devices) {

		// build payload
		$payload = [
			'data' => array_merge([
				'message' => trim(stripslashes($message)),
			], $payload),
		];

		return $this->sendRaw($payload, $devices);
	}

	/**
	 * build and send Notification from raw payload
	 * @param  array $payload
	 * @param  array $devices
	 * @return bool
	 * @throws \RuntimeException
	 */
	public function sendRaw(array $payload, array $devices) {

		$payload = array_merge([
			'registration_ids' => $devices,
		], $payload);

		// init http-headers
		$headers = [
			'Authorization: key=' . $this->_server['token'],
			'Content-Type: application/json',
		];

		// open curl connection
		$curl = curl_init();

		// set url
		curl_setopt($curl, CURLOPT_URL, $this->_server['url']);

		// apply headers and set type to POST
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

		// return response instead of status
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

		// append payload
		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload));

		// check certificates
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);

		// send request
		$result = curl_exec($curl);

		if ($result === false) {
			$error = curl_error($curl);
			curl_close($curl);
			throw new \RuntimeException('error processing GCM: ' . $error, 500);
		}

		// remeber to close the connection when finished
		curl_close($curl);

		// decode response and check if sending to all devices succeeded
		$result = @json_decode($result, true);
		return (isset($result['success']) && (int) $result['success'] === count($devices));

	}

}
