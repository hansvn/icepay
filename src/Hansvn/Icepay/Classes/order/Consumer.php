<?php namespace Hansvn\Icepay\Order;

/**
 * \Order\Consumer
 * 
 * The consumer class contains all information about the consumer
 *  
 * @version 1.0.0 
 * 
 * @author Wouter van Tilburg
 * @author Olaf Abbenhuis
 * @copyright Copyright (c) 2011-2012, ICEPAY
 */
class Consumer {

    public $consumerID = '';
    public $email = '';
    public $phone = '';

    /**
     * Creates and returns a new \Order\Consumer
     * 
     * @since 1.0.0     
     * @return \Order\Consumer
     */
    public static function create() {
        return new self();
    }

    /**
     * Sets the consumer ID
     * 
     * @since 1.0.0
     * @param string A string containing the consumerID
     * @return \Order\Consumer
     * @throws \Exception when empty
     */
    public function setConsumerID($consumerID) {
        if (empty($consumerID))
            throw new \Exception('Consumer ID must be set and cannot be empty.');

        $this->consumerID = $consumerID;

        return $this;
    }

    /**
     * Sets the consumer's email
     * 
     * @since 1.0.0
     * @param string A string containing the consumer's email address.
     * @return \Order\Consumer
     * @throws \Exception when empty
     */
    public function setEmail($email) {
        if (empty($email))
            throw new \Exception('Email must be set and cannot be empty.');

        $this->email = $email;

        return $this;
    }

    /**
     * Sets the consumer's phonenumber
     * 
     * @since 1.0.0
     * @param string A string containing the consumer's phonenumber
     * @return \Order\Consumer
     * @throws \Exception when empty
     */
    public function setPhone($phone) {
        $phone = trim(str_replace('-', '', $phone));

        if (empty($phone))
            throw new \Exception('Phone must be set and cannot be empty.');

        $this->phone = $phone;

        return $this;
    }

}