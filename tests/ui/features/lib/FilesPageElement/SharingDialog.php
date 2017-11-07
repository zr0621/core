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

namespace Page\FilesPageElement;

use Behat\Mink\Element\NodeElement;
use Page\OwncloudPage;
use Page\FilesPageElement\SharingDialogElement\PublicLinkTab;
use SensioLabs\Behat\PageObjectExtension\PageObject\Exception\ElementNotFoundException;
use Behat\Mink\Session;
use Page\FilesPage;

/**
 * The Sharing Dialog
 *
 */
class SharingDialog extends OwncloudPage {

	/**
	 *
	 * @var string $path
	 */
	protected $path = '/index.php/apps/files/';

	protected $shareWithFieldXpath = ".//*[contains(@class,'shareWithField')]";
	protected $shareWithTooltipXpath = "/..//*[@class='tooltip-inner']";
	protected $shareWithAutocompleteListXpath = ".//ul[contains(@class,'ui-autocomplete')]";
	protected $autocompleteItemsTextXpath = "//*[@class='autocomplete-item-text']";
	protected $shareWithCloseXpath = "//div[@id='app-sidebar']//*[@class='close icon-close']";
	protected $suffixToIdentifyGroups = " (group)";
	protected $suffixToIdentifyRemoteUsers = " (remote)";
	protected $sharerInformationXpath = ".//*[@class='reshare']";
	protected $sharedWithAndByRegEx = "^(?:[A-Z]\s)?Shared with you(?: and the group (.*))? by (.*)$";
	protected $thumbnailContainerXpath = ".//*[contains(@class,'thumbnailContainer')]";
	protected $thumbnailFromContainerXpath = "/a";
	protected $permissionsFieldByUserName = ".//*[@id='shareWithList']//*[@class='has-tooltip username' and .='%s']/..";
	protected $permissionLabelXpath = ".//label[@for='%s']";
	protected $showCrudsXpath = ".//*[@class='showCruds']";
	protected $publicShareTabLinkXpath = ".//li[contains(@class,'subtab-publicshare')]";

	protected $sharedWithGroupAndSharerName = null;

	/**
	 *
	 * @throws ElementNotFoundException
	 * @return NodeElement|NULL
	 */
	private function _findShareWithField() {
		$shareWithField = $this->find("xpath", $this->shareWithFieldXpath);
		if (is_null($shareWithField)) {
			throw new ElementNotFoundException(
				__METHOD__ .
				" xpath $this->shareWithFieldXpath could not find share-with-field"
			);
		}
		return $shareWithField;
	}

	/**
	 * fills the "share-with" input field
	 *
	 * @param string $input
	 * @param Session $session
	 * @param int $timeout_msec how long to wait till the autocomplete comes back
	 * @return NodeElement AutocompleteElement
	 */
	public function fillShareWithField(
		$input, Session $session, $timeout_msec = STANDARDUIWAITTIMEOUTMILLISEC
	) {
		$shareWithField = $this->_findShareWithField();
		$shareWithField->setValue($input);
		$this->waitForAjaxCallsToStartAndFinish($session, $timeout_msec);
		return $this->getAutocompleteNodeElement();
	}

	/**
	 * gets the NodeElement of the autocomplete list
	 *
	 * @return NodeElement
	 * @throws ElementNotFoundException
	 */
	public function getAutocompleteNodeElement() {
		$autocompleteNodeElement = $this->find(
			"xpath",
			$this->shareWithAutocompleteListXpath
		);
		if (is_null($autocompleteNodeElement)) {
			throw new ElementNotFoundException(
				__METHOD__ .
				" xpath $this->shareWithAutocompleteListXpath " .
				"could not find autocompleteNodeElement"
			);
		}
		return $autocompleteNodeElement;
	}

	/**
	 * returns the group names as they could appear in an autocomplete list
	 *
	 * @param string|array $groupNames
	 * @return array
	 */
	public function groupStringsToMatchAutoComplete($groupNames) {
		if (is_array($groupNames)) {
			$autocompleteStrings = array();
			foreach ($groupNames as $groupName) {
				$autocompleteStrings[] = $groupName . $this->suffixToIdentifyGroups;
			}
		} else {
			$autocompleteStrings = $groupNames . $this->suffixToIdentifyGroups;
		}
		return $autocompleteStrings;
	}

	/**
	 * gets the items (users, groups) listed in the autocomplete list as an array
	 *
	 * @return array
	 * @throws ElementNotFoundException
	 */
	public function getAutocompleteItemsList() {
		$itemsArray = array();
		$itemElements = $this->getAutocompleteNodeElement()->findAll(
			"xpath",
			$this->autocompleteItemsTextXpath
		);
		foreach ($itemElements as $item) {
			array_push($itemsArray, $this->getTrimmedText($item));
		}
		return $itemsArray;
	}

	/**
	 *
	 * @param string $nameToType what to type in the share with field
	 * @param string $nameToMatch what exact item to select
	 * @param Session $session
	 * @param int $maxRetries
	 * @param boolean $quiet
	 * @throws ElementNotFoundException
	 * @return void
	 */
	private function shareWithUserOrGroup(
		$nameToType, $nameToMatch, Session $session, $maxRetries = 5, $quiet = false
	) {
		for ($retryCounter = 0; $retryCounter < $maxRetries; $retryCounter++) {
			$autocompleteNodeElement = $this->fillShareWithField($nameToType, $session);
			$userElements = $autocompleteNodeElement->findAll(
				"xpath", $this->autocompleteItemsTextXpath
			);
	
			$userFound = false;
			foreach ($userElements as $user) {
				if ($this->getTrimmedText($user) === $nameToMatch) {
					$user->click();
					$this->waitForAjaxCallsToStartAndFinish($session);
					$userFound = true;
					break;
				}
			}
			if ($userFound === true) {
				break;
			} elseif ($quiet === false) {
				error_log("Error while sharing file");
			}
		}
		if ($retryCounter > 0 && $quiet === false) {
			$message = "INFORMATION: retried to share file " . $retryCounter . " times";
			echo $message;
			error_log($message);
		}
		if ($userFound !== true) {
			throw new ElementNotFoundException(
				__METHOD__ . " could not share with '$nameToMatch'"
			);
		}
	}

	/**
	 *
	 * @param string $name
	 * @param Session $session
	 * @param int $maxRetries
	 * @param boolean $quiet
	 * @throws ElementNotFoundException
	 * @return void
	 */
	public function shareWithUser(
		$name, Session $session, $maxRetries = 5, $quiet = false
	) {
		$this->shareWithUserOrGroup(
			$name, $name, $session, $maxRetries, $quiet
		);
	}

	/**
	 *
	 * @param string $name
	 * @param Session $session
	 * @param int $maxRetries
	 * @param boolean $quiet
	 * @throws ElementNotFoundException
	 * @return void
	 */
	public function shareWithRemoteUser(
		$name, Session $session, $maxRetries = 5, $quiet = false
	) {
		$this->shareWithUserOrGroup(
			$name, $name . $this->suffixToIdentifyRemoteUsers,
			$session, $maxRetries, $quiet
		);
	}

	/**
	 *
	 * @param string $name
	 * @param Session $session
	 * @param int $maxRetries
	 * @param boolean $quiet
	 * @throws ElementNotFoundException
	 * @return void
	 */
	public function shareWithGroup(
		$name, Session $session, $maxRetries = 5, $quiet = false
	) {
		$this->shareWithUserOrGroup(
			$name, $name . $this->suffixToIdentifyGroups,
			$session, $maxRetries, $quiet
		);
	}

	/**
	 *
	 * @param string $shareReceiverName
	 * @param array $permissions [['permission' => 'yes|no']]
	 * @throws ElementNotFoundException
	 * @return void
	 */
	public function setSharingPermissions(
		$shareReceiverName,
		$permissions
	) {
		$xpathLocator = sprintf($this->permissionsFieldByUserName, $shareReceiverName);
		$permissionsField = $this->find("xpath", $xpathLocator);
		if (is_null($permissionsField)) {
			throw new ElementNotFoundException(
				__METHOD__
				. " xpath $xpathLocator could not find share permissions field for user "
				. $shareReceiverName
			);
		}
		$showCrudsBtn = $permissionsField->find("xpath", $this->showCrudsXpath);
		if (is_null($showCrudsBtn)) {
			throw new ElementNotFoundException(
				__METHOD__
				. " xpath $this->showCrudsXpath could not find show-cruds button for user "
				. $shareReceiverName
			);
		}
		foreach ($permissions as $permission => $value) {
			//the additional permission disappear again after they are changed
			//so we need to open them again and again
			$showCrudsBtn->click();
			$value = strtolower($value);

			//to find where to click is a little bit complicated
			//just setting the checkbox does not work
			//because the actual checkbox is not visible (left: -10000px;)
			//so we first find the checkbox, then get its id and find the label
			//that is associated with that id, that label is finally what we click
			$permissionCheckBox = $permissionsField->findField($permission);
			if (is_null($permissionCheckBox)) {
				throw new ElementNotFoundException(
					__METHOD__ .
					"could not find the permission check box for permission " .
					"'$permission' and user '$shareReceiverName'"
				);
			}
			$checkBoxId = $permissionCheckBox->getAttribute("id");
			if (is_null($checkBoxId)) {
				throw new ElementNotFoundException(
					__METHOD__ .
					"could not find the id of the permission check box of " .
					"permission '$permission' and user '$shareReceiverName'"
				);
			}

			$xpathLocator = sprintf($this->permissionLabelXpath, $checkBoxId);
			$permissionLabel = $permissionsField->find("xpath", $xpathLocator);

			if (is_null($permissionLabel)) {
				throw new ElementNotFoundException(
					__METHOD__ .
					" xpath $xpathLocator " .
					"could not find the label of the permission check box of " .
					"permission '$permission' and user '$shareReceiverName'"
				);
			}

			if (($value === "yes" && !$permissionCheckBox->isChecked())
				|| ($value === "no" && $permissionCheckBox->isChecked())
			) {
				$permissionLabel->click();
			}
		}
	}

	/**
	 * gets the text of the tooltip associated with the "share-with" input
	 *
	 * @throws ElementNotFoundException
	 * @return string
	 */
	public function getShareWithTooltip() {
		$shareWithField = $this->_findShareWithField();
		$shareWithTooltip = $shareWithField->find(
			"xpath", $this->shareWithTooltipXpath
		);
		if (is_null($shareWithTooltip)) {
			throw new ElementNotFoundException(
				__METHOD__ .
				" xpath $this->shareWithTooltipXpath " .
				"could not find share-with-tooltip"
			);
		}
		return $this->getTrimmedText($shareWithTooltip);
	}

	/**
	 * gets the Element with the information about who has shared the current
	 * file/folder. This Element will contain the Avatar and some text.
	 *
	 * @throws ElementNotFoundException
	 * @return NodeElement
	 */
	public function findSharerInformationItem() {
		$sharerInformation = $this->find("xpath", $this->sharerInformationXpath);
		if (is_null($sharerInformation)) {
			throw new ElementNotFoundException(
				__METHOD__ .
				" xpath $this->sharerInformationXpath " .
				"could not find sharer information"
			);
		}
		return $sharerInformation;
	}

	/**
	 * gets the group that the file/folder was shared with
	 * and the user that shared it
	 *
	 * @throws \Exception
	 * @return array ["sharedWithGroup" => string, "sharer" => string]
	 */
	public function getSharedWithGroupAndSharerName() {
		if (is_null($this->sharedWithGroupAndSharerName)) {
			$text = $this->getTrimmedText($this->findSharerInformationItem());
			if (preg_match("/" . $this->sharedWithAndByRegEx . "/", $text, $matches)) {
				$this->sharedWithGroupAndSharerName = [
					"sharedWithGroup" => $matches [1],
					"sharer" => $matches [2]
				];
			} else {
				throw new \Exception(
					__METHOD__ .
					"could not find shared with group or sharer name"
				);
			}
		}
		return $this->sharedWithGroupAndSharerName;
	}

	/**
	 * gets the group that the file/folder was shared with
	 *
	 * @return mixed
	 */
	public function getSharedWithGroupName() {
		return $this->getSharedWithGroupAndSharerName()["sharedWithGroup"];
	}

	/**
	 * gets the display name of the user that has shared the current file/folder
	 *
	 * @throws \Exception
	 * @return string
	 */
	public function getSharerName() {
		return $this->getSharedWithGroupAndSharerName()["sharer"];
	}

	/**
	 *
	 * @throws ElementNotFoundException
	 * @return NodeElement of the whole container holding the thumbnail
	 */
	public function findThumbnailContainer() {
		$thumbnailContainer = $this->find("xpath", $this->thumbnailContainerXpath);
		if (is_null($thumbnailContainer)) {
			throw new ElementNotFoundException(
				__METHOD__ .
				" xpath $this->thumbnailContainerXpath " .
				"could not find thumbnailContainer"
			);
		}
		return $thumbnailContainer;
	}

	/**
	 *
	 * @throws ElementNotFoundException
	 * @return NodeElement
	 */
	public function findThumbnail() {
		$thumbnailContainer = $this->findThumbnailContainer();
		$thumbnail = $thumbnailContainer->find(
			"xpath", $this->thumbnailFromContainerXpath
		);
		if (is_null($thumbnail)) {
			throw new ElementNotFoundException(
				__METHOD__ .
				" xpath $this->thumbnailFromContainerXpath " .
				"could not find thumbnail"
			);
		}
		return $thumbnail;
	}

	/**
	 * 
	 * @throws ElementNotFoundException
	 * @return PublicLinkTab
	 */
	public function openPublicShareTab() {
		$publicShareTabLink = $this->find("xpath", $this->publicShareTabLinkXpath);
		if (is_null($publicShareTabLink)) {
			throw new ElementNotFoundException(
				__METHOD__ .
				" xpath $this->publicShareTabLinkXpath " .
				"could not find link to open public share tab"
			);
		}
		$publicShareTabLink->click();
		$publicLinkTab = $this->getPage(
			"FilesPageElement\\SharingDialogElement\\PublicLinkTab"
		);
		$publicLinkTab->initElement();
		return $publicLinkTab;
	}

	/**
	 * closes the sharing dialog panel
	 *
	 * @throws ElementNotFoundException
	 * @return void
	 */
	public function closeSharingDialog() {
		$shareDialogCloseButton = $this->find("xpath", $this->shareWithCloseXpath);
		if (is_null($shareDialogCloseButton)) {
			throw new ElementNotFoundException(
				__METHOD__ .
				" xpath $this->shareWithCloseXpath " .
				"could not find share-dialog-close-button"
			);
		}
		$shareDialogCloseButton->click();
	}
}
