<?php

require __DIR__ . '/../../../../lib/composer/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

trait Checksums {

	/**
	 * @When user :user uploads file :source to :destination with checksum :checksum
	 * @param string $user
	 * @param string $source
	 * @param string $destination
	 * @param string $checksum
	 */
	public function userUploadsFileToWithChecksum($user, $source, $destination, $checksum) {
		$file = file_get_contents($source);
		$this->response = $this->makeDavRequest($user,
							  'PUT',
							  $destination,
							  ['OC-Checksum' => $checksum],
							  $file,
							  "files");
	}

	/**
	 * @Then the webdav response should have a status code :statusCode
	 * @param int $statusCode
	 * @throws \Exception
	 */
	public function theWebdavResponseShouldHaveAStatusCode($statusCode) {
		if ((int)$statusCode !== $this->response->getStatusCode()) {
			throw new \Exception("Expected $statusCode, got ".$this->response->getStatusCode());
		}
	}

	/**
	 * @When user :user requests the checksum of :path via propfind
	 * @param string $user
	 * @param string $path
	 */
	public function userRequestsTheChecksumOfViaPropfind($user, $path) {
		$client = new Client();
		$options = [
			'auth' => [
				$user,
				$this->getPasswordForUser($user),
			]
		];
		$request = new Request(
			'PROPFIND',
			substr($this->baseUrl, 0, -4) . $this->getDavFilesPath($user) . $path,
			[],
			'<?xml version="1.0"?>
<d:propfind  xmlns:d="DAV:" xmlns:oc="http://owncloud.org/ns">
  <d:prop>
    <oc:checksums />
  </d:prop>
</d:propfind>'
		);
		$this->response = $client->send($request, $options);
	}

	/**
	 * @Then the webdav checksum should match :checksum
	 * @param string $checksum
	 * @throws \Exception
	 */
	public function theWebdavChecksumShouldMatch($checksum)
	{
		$service = new Sabre\Xml\Service();
		$parsed = $service->parse($this->response->getBody()->getContents());

		/*
		 * Fetch the checksum array
		 * Maybe we want to do this a bit cleaner ;)
		 */
		$checksums = $parsed[0]['value'][1]['value'][0]['value'][0];

		if ($checksums['value'][0]['value'] !== $checksum) {
			throw new \Exception("Expected $checksum, got ".$checksums['value'][0]['value']);
		}
	}

	/**
	 * @Then the header checksum should match :checksum
	 * @param string $checksum
	 * @throws \Exception
	 */
	public function theHeaderChecksumShouldMatch($checksum)
	{
		if ($this->response->getHeader('OC-Checksum')[0] !== $checksum) {
			throw new \Exception("Expected $checksum, got ".$this->response->getHeader('OC-Checksum'));
		}
	}

	/**
	 * @Given user :user copied file :source to :destination
	 * @param string $user
	 * @param string $source
	 * @param string $destination
	 */
	public function userCopiedFileTo($user, $source, $destination) {
		$client = new Client();
		$options = [
			'auth' => [
				$user,
				$this->getPasswordForUser($user),
			],
		];
		$request = new Request(
			'COPY',
			substr($this->baseUrl, 0, -4) . $this->davPath . $source,
			['Destination' => substr($this->baseUrl, 0, -4) . $this->davPath . $destination]
		);
		$this->response = $client->send($request, $options);
	}

	/**
	 * @Then the webdav checksum should be empty
	 */
	public function theWebdavChecksumShouldBeEmpty()
	{
		$service = new Sabre\Xml\Service();
		$parsed = $service->parse($this->response->getBody()->getContents());

		/*
		 * Fetch the checksum array
		 * Maybe we want to do this a bit cleaner ;)
		 */
		$status = $parsed[0]['value'][1]['value'][1]['value'];

		if ($status !== 'HTTP/1.1 404 Not Found') {
			throw new \Exception("Expected 'HTTP/1.1 404 Not Found', got ".$status);
		}
	}

	/**
	 * @Then the OC-Checksum header should not be there
	 */
	public function theOcChecksumHeaderShouldNotBeThere()
	{
		if ($this->response->hasHeader('OC-Checksum')) {
			throw new \Exception("Expected no checksum header but got ".$this->response->getHeader('OC-Checksum'));
		}
	}

	/**
	 * @Given user :user uploads chunk file :num of :total with :data to :destination with checksum :checksum
	 * @param string $user
	 * @param int $num
	 * @param int $total
	 * @param string $data
	 * @param string $destination
	 * @param string $checksum
	 */
	public function userUploadsChunkFileOfWithToWithChecksum($user, $num, $total, $data, $destination, $checksum) {
		try {
			$num -= 1;
			$file = $destination . '-chunking-42-' . $total . '-' . $num;
			$this->response = $this->makeDavRequest($user,
								  'PUT',
								  $file,
								  ['OC-Checksum' => $checksum, 'OC-Chunked' => '1'],
								  $data,
								  "files");
		} catch (\GuzzleHttp\Exception\RequestException $ex) {
			$this->response = $ex->getResponse();
		}
	}
}
