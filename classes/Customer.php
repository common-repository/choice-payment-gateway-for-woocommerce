<?php

namespace ChoicePayment;

class Customer {
	private $_first_name = '';
	private $_last_name  = '';
	private $_phone      = '';
	private $_city       = '';
	private $_state      = '';
	private $_email      = '';
	private $_address1   = '';
	private $_address2   = '';
	private $_zip        = '';

	/**
	 * @return string
	 */
	public function getFirstName(): string {
		return $this->_first_name;
	}

	/**
	 * @param string $first_name
	 *
	 * @return Customer
	 */
	public function setFirstName( string $first_name ): Customer {
		$this->_first_name = $first_name;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getLastName(): string {
		return $this->_last_name;
	}

	/**
	 * @param string $last_name
	 *
	 * @return Customer
	 */
	public function setLastName( string $last_name ): Customer {
		$this->_last_name = $last_name;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getPhone(): string {
		return $this->_phone;
	}

	/**
	 * @param string $phone
	 *
	 * @return Customer
	 */
	public function setPhone( string $phone ): Customer {
		$this->_phone = $phone;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getCity(): string {
		return $this->_city;
	}

	/**
	 * @param string $city
	 *
	 * @return Customer
	 */
	public function setCity( string $city ): Customer {
		$this->_city = $city;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getState(): string {
		return $this->_state;
	}

	/**
	 * @param string $state
	 *
	 * @return Customer
	 */
	public function setState( string $state ): Customer {
		$this->_state = $state;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getEmail(): string {
		return $this->_email;
	}

	/**
	 * @param string $email
	 *
	 * @return Customer
	 */
	public function setEmail( string $email ): Customer {
		$this->_email = $email;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getAddress1(): string {
		return $this->_address1;
	}

	/**
	 * @param string $address1
	 *
	 * @return Customer
	 */
	public function setAddress1( string $address1 ): Customer {
		$this->_address1 = $address1;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getAddress2(): string {
		return $this->_address2;
	}

	/**
	 * @param string $address2
	 *
	 * @return Customer
	 */
	public function setAddress2( string $address2 ): Customer {
		$this->_address2 = $address2;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getZip(): string {
		return $this->_zip;
	}

	/**
	 * @param string $zip
	 *
	 * @return Customer
	 */
	public function setZip( string $zip ): Customer {
		$this->_zip = $zip;

		return $this;
	}


}
