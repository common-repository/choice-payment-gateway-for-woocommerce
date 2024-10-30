<?php

namespace ChoicePayment;

use ChoicePayment\Customer;

class Payment {
	private $_device_credit_card_guid = '';
	private $_device_ach_guid         = '';
	private $_merchant_name           = '';
	private $_description             = '';
	private $_amount                  = '';
	private $_other_url               = '';
	private $_success_url             = '';
	private $_cancel_url              = '';
	private $_other_info              = '';
	private $_customer                = null;
	private $_temp_token              = '';
	private $_expiration              = '';

	/**
	 * @return string
	 */
	public function getDeviceCreditCardGuid(): string {
		return $this->_device_credit_card_guid;
	}

	/**
	 * @param string $device_credit_card_guid
	 *
	 * @return Payment
	 */
	public function setDeviceCreditCardGuid( string $device_credit_card_guid ): Payment {
		$this->_device_credit_card_guid = $device_credit_card_guid;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getDeviceAchGuid(): string {
		return $this->_device_ach_guid;
	}

	/**
	 * @param string $device_ach_guid
	 *
	 * @return Payment
	 */
	public function setDeviceAchGuid( string $device_ach_guid ): Payment {
		$this->_device_ach_guid = $device_ach_guid;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getMerchantName(): string {
		return $this->_merchant_name;
	}

	/**
	 * @param string $merchant_name
	 *
	 * @return Payment
	 */
	public function setMerchantName( string $merchant_name ): Payment {
		$this->_merchant_name = $merchant_name;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getDescription(): string {
		return $this->_description;
	}

	/**
	 * @param string $description
	 *
	 * @return Payment
	 */
	public function setDescription( string $description ): Payment {
		$this->_description = $description;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getAmount(): string {
		return $this->_amount;
	}

	/**
	 * @param string $amount
	 *
	 * @return Payment
	 */
	public function setAmount( string $amount ): Payment {
		$this->_amount = $amount;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getOtherUrl(): string {
		return $this->_other_url;
	}

	/**
	 * @param string $other_url
	 *
	 * @return Payment
	 */
	public function setOtherUrl( string $other_url ): Payment {
		$this->_other_url = $other_url;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getSuccessUrl(): string {
		return $this->_success_url;
	}

	/**
	 * @param string $success_url
	 *
	 * @return Payment
	 */
	public function setSuccessUrl( string $success_url ): Payment {
		$this->_success_url = $success_url;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getCancelUrl(): string {
		return $this->_cancel_url;
	}

	/**
	 * @param string $cancel_url
	 *
	 * @return Payment
	 */
	public function setCancelUrl( string $cancel_url ): Payment {
		$this->_cancel_url = $cancel_url;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getOtherInfo(): string {
		return $this->_other_info;
	}

	/**
	 * @param string $other_info
	 *
	 * @return Payment
	 */
	public function setOtherInfo( string $other_info ): Payment {
		$this->_other_info = $other_info;

		return $this;
	}

	/**
	 * @return \ChoicePayment\Customer
	 */
	public function getCustomer(): Customer {
		return $this->_customer;
	}

	/**
	 * @param \ChoicePayment\Customer $customer
	 *
	 * @return Payment
	 */
	public function setCustomer( Customer $customer ): Payment {
		$this->_customer = $customer;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getTempToken(): string {
		return $this->_temp_token;
	}

	/**
	 * @param string $temp_token
	 *
	 * @return Payment
	 */
	public function setTempToken( string $temp_token ): Payment {
		$this->_temp_token = $temp_token;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getExpiration(): string {
		return $this->_expiration;
	}

	/**
	 * @param string $expiration
	 *
	 * @return Payment
	 */
	public function setExpiration( string $expiration ): Payment {
		$this->_expiration = $expiration;

		return $this;
	}


}
