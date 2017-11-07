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

namespace Page\FilesPageElement\SharingDialogElement;

use Behat\Mink\Element\NodeElement;
use SensioLabs\Behat\PageObjectExtension\PageObject\Factory;
use Page\OwncloudPage;
use SensioLabs\Behat\PageObjectExtension\PageObject\Exception\ElementNotFoundException;
use Exception;
use Behat\Mink\Session;
use Behat\Mink\Element\Element;

/**
 * The Public link tab of the Sharing Dialog
 *
 */
class PublicLinkTab extends OwncloudPage {
	/**
	 * @var NodeElement of this tab
	 */
	private $publicLinkTabElement;
	private $publicLinkTabId = "shareDialogLinkList";
	private $createLinkBtnXpath = ".//button[@class='addLink']";
	private $popupXpath = ".//div[@class='oc-dialog' and not(contains(@style,'display: none'))]";

	/**
	 * as it's not possible to run __construct() we need to run this function
	 * every time we get the tab with 
	 * $this->getPage("FilesPageElement\\SharingDialogElement\\PublicLinkTab");
	 * this function finds the tab in the DOM and sets $this->publicLinkTabElement
	 * in the rest of the class we can use $this->publicLinkTabElement to find
	 * other elements to make sure that we are searching in the right place
	 * 
	 * @throws ElementNotFoundException
	 * @return void
	 */
	public function initElement() {
		$publicLinkTab = $this->findById($this->publicLinkTabId);
		if (is_null($publicLinkTab)) {
			throw new ElementNotFoundException(
				__METHOD__ .
				" id $this->publicLinkTabId could not find public link tab"
			);
		}
		$this->publicLinkTabElement = $publicLinkTab;
	}

	/**
	 * 
	 * @param Session $session
	 * @param string $name
	 * @param string $permissions
	 * @param string $password
	 * @param string $expirationDate
	 * @param string $email
	 * @return void
	 */
	public function createLink(
		Session $session,
		$name = null,
		$permissions = null,
		$password = null,
		$expirationDate = null,
		$email = null
	) {
		$createLinkBtn = $this->publicLinkTabElement->find(
			"xpath", $this->createLinkBtnXpath
		);
		if (is_null($createLinkBtn)) {
			throw new ElementNotFoundException(
				__METHOD__ .
				" xpath $this->createLinkBtnXpath" .
				" could not find create public link button"
			);
		}
		$createLinkBtn->click();

		$popupElement = $this->waitTillElementIsNotNull($this->popupXpath);
		/**
		 * 
		 * @var EditPublicLinkPopup $editPublicLinkPopupPageObject
		 */
		$editPublicLinkPopupPageObject = $this->getPage(
			"FilesPageElement\\SharingDialogElement\\EditPublicLinkPopup"
		);
		$editPublicLinkPopupPageObject->setElement($popupElement);
		if (!is_null($name)) {
			$editPublicLinkPopupPageObject->setName($name);
		}
		if (!is_null($permissions)) {
			$editPublicLinkPopupPageObject->setPermissions($permissions);
		}
		if (!is_null($password)) {
			$editPublicLinkPopupPageObject->setPassword($password);
		}
		if (!is_null($expirationDate)) {
			$editPublicLinkPopupPageObject->setExpirationDate($expirationDate);
		}
		if (!is_null($email)) {
			$editPublicLinkPopupPageObject->setEmail($email);
		}
		$editPublicLinkPopupPageObject->save();
		$this->waitForAjaxCallsToStartAndFinish($session);
	}

	/**
	 * 
	 * @param string $name
	 * @param string $newName
	 * @param array $permissions
	 * @param string $password
	 * @param string $expirationDate
	 * @param string $email
	 * @return void
	 */
	public function editLink(
		$name,
		$newName = null,
		$permissions = null,
		$password = null,
		$expirationDate = null,
		$email = null
	) {
		throw new Exception("not implemented");
	}

	/**
	 * 
	 * @return void
	 */
	public function closeSharingPopup() {
		throw new Exception("not implemented");
	}

	/**
	 * 
	 * @param string $name
	 * @return void
	 */
	public function deleteLink($name) {
		throw new Exception("not implemented");
	}

	/**
	 * 
	 * @param string $name
	 * @param string $service
	 * @return void
	 */
	public function shareLink($name, $service) {
		throw new Exception("not implemented");
	}

	/**
	 * 
	 * @param string $name
	 * @return void
	 */
	public function copyLinkToClipboard($name) {
		throw new Exception("not implemented");
	}

	/**
	 *
	 * @param string $name
	 * @return void
	 */
	private function findLinkByName($name) {
		throw new Exception("not implemented");
	}
}
