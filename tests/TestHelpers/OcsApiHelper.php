<?php
/**
 * ownCloud
 *
 * @author Artur Neumann <artur@jankaritech.com>
 * @copyright 2017 Artur Neumann artur@jankaritech.com
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License,
 * as published by the Free Software Foundation;
 * either version 3 of the License, or any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace TestHelpers;

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;

/**
 * Helper to make requests to the OCS API
 * 
 * @author Artur Neumann <artur@jankaritech.com>
 *
 */
class OcsApiHelper {
	/**
	 * @param string $baseUrl
	 * @param string $user if set to null no authentication header will be sent
	 * @param string $password
	 * @param string $method HTTP Method
	 * @param string $path
	 * @param array $body array of key, value pairs e.g ['value' => 'yes']
	 * @param int $apiVersion (1|2) default 2
	 * @return ResponseInterface|null
	 */
	public static function sendRequest(
		$baseUrl, $user, $password, $method, $path, $body = [], $apiVersion = 2
	) {
		$fullUrl = $baseUrl . "/ocs/v{$apiVersion}.php" . $path;
		$client = new Client();
		$options = [];
		if ($user !== null) {
			$options['auth'] = [$user, $password];
		}
		$options['body'] = $body;
		
		try {
			$response = $client->send(new Request($method, $fullUrl), $options);
		} catch (\GuzzleHttp\Exception\ClientException $ex) {
			$response = $ex->getResponse();
		}
		return $response;
	}
	
}