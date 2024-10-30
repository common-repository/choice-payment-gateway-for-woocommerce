<?php

namespace ChoicePayment;

class BankInfo {
    public $AccountName = '';
    public $RoutingNumber = '';
    public $AccountNumber = '';

    /**
     * @param string $AccountName
     * @param string $RoutingNumber
     * @param string $AccountNumber
     */
    public function __construct( string $AccountName, string $RoutingNumber, string $AccountNumber ) {
        $this->AccountName   = $AccountName;
        $this->RoutingNumber = $RoutingNumber;
        $this->AccountNumber = $AccountNumber;
    }
}
