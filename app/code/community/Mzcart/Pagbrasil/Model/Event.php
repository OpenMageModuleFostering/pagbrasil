<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @category    Mzcart
 * @package     Mzcart_Pagbrasil
 * @copyright   Copyright (c) 2013 Mzcart Medien GmbH & Co. KG (http://www.phoenix-medien.de)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * PagBrasil notification processor model
 */
class Mzcart_Pagbrasil_Model_Event
{
    const PAGBRASIL_STATUS_FAIL = -2;
    const PAGBRASIL_STATUS_CANCEL = -1;
    const PAGBRASIL_STATUS_PENDING = 0;
    const PAGBRASIL_STATUS_PRE_AUTHORIZE = 1;
	const PAGBRASIL_STATUS_SUCCESS = 2;

    /*
     * @param Mage_Sales_Model_Order
     */
    protected $_order = null;

    /**
     * Event request data
     * @var array
     */
    protected $_eventData = array();

    /**
     * Enent request data setter
     * @param array $data
     * @return Mzcart_Pagbrasil_Model_Event
     */
    public function setEventData(array $data)
    {
        $this->_eventData = $data;
        return $this;
    }

    /**
     * Event request data getter
     * @param string $key
     * @return array|string
     */
    public function getEventData($key = null)
    {
        if (null === $key) {
            return $this->_eventData;
        }
        return isset($this->_eventData[$key]) ? $this->_eventData[$key] : null;
    }

    /**
     * Get singleton of Checkout Session Model
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function _getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Process status notification from Monebookers server
     *
     * @return String
     */
    public function processStatusEvent($check=true)
    {
        try
		{
            $params = $this->_validateEventData($check);
            $msg = '';
            switch($params['status'])
			{
                case self::PAGBRASIL_STATUS_FAIL: //fail
                    $msg = Mage::helper('pagbrasil')->__('Payment failed.');
                    $this->_processCancel($msg);
                    break;
                case self::PAGBRASIL_STATUS_CANCEL: //cancel
                    $msg = Mage::helper('pagbrasil')->__('Payment was canceled.');
                    $this->_processCancel($msg);
                    break;
                case self::PAGBRASIL_STATUS_PENDING: //pending
                    $msg = Mage::helper('pagbrasil')->__('Pending order #%s created.', $params['order']);
                    $this->_processSale($params['status'], $msg);
                    break;
				case self::PAGBRASIL_STATUS_PRE_AUTHORIZE: //Pre-authorize
                    $msg = Mage::helper('pagbrasil')->__('Payment pre-authorized but not captured yet.', $params['order']);
                    $this->_processSale($params['status'], $msg);
                    break;
                case self::PAGBRASIL_STATUS_SUCCESS: //ok
                    $msg = Mage::helper('pagbrasil')->__('Payment authorized.');
                    $this->_processSale($params['status'], $msg);
                    break;
            }
            return $msg;
        }
		catch (Mage_Core_Exception $e)
		{
            return $e->getMessage();
        }
		catch(Exception $e)
		{
            Mage::logException($e);
        }
        return;
    }

    /**
     * Process cancelation
     */
    public function cancelEvent() {
        try {
            $this->_validateEventData(false);
            $this->_processCancel('Payment was canceled.');
            return Mage::helper('pagbrasil')->__('The order has been canceled.');
        } catch (Mage_Core_Exception $e) {
            return $e->getMessage();
        } catch(Exception $e) {
            Mage::logException($e);
        }
        return '';
    }

    /**
     * Validate request and return QuoteId
     * Can throw Mage_Core_Exception and Exception
     *
     * @return int
     */
    public function successEvent(){
        $this->_validateEventData(false);
		// var_dump($this->_order->getQuoteId());exit;
        return $this->_order->getQuoteId();
    }

    /**
     * Processed order cancelation
     * @param string $msg Order history message
     */
    protected function _processCancel($msg)
    {
        $this->_order->cancel();
        $this->_order->addStatusToHistory(Mage_Sales_Model_Order::STATE_CANCELED, $msg);
        $this->_order->save();
    }

    /**
     * Processes payment confirmation, creates invoice if necessary, updates order status,
     * sends order confirmation to customer
     * @param string $msg Order history message
     */
    protected function _processSale($status, $msg)
    {
        switch ($status) {
            case self::PAGBRASIL_STATUS_SUCCESS:
                $this->_createInvoice();
                $this->_order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true, $msg);
                // save transaction ID
                $this->_order->getPayment()->setLastTransId($this->getEventData('order'));
                // send new order email
				$this->_order->save();
                $this->_order->sendNewOrderEmail();
                $this->_order->setEmailSent(true);
                break;
			case self::PAGBRASIL_STATUS_PRE_AUTHORIZE:
            case self::PAGBRASIL_STATUS_PENDING:
                $this->_order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, true, $msg);
                // save transaction ID
                $this->_order->getPayment()->setLastTransId($this->getEventData('order'));
                break;
        }
        $this->_order->save();
    }

    /**
     * Builds invoice for order
     */
    protected function _createInvoice()
    {
        if (!$this->_order->canInvoice()) {
            return;
        }
        $invoice = $this->_order->prepareInvoice();
        $invoice->register()->capture();
        $this->_order->addRelatedObject($invoice);
    }

    /**
     * Checking returned parameters
     * Thorws Mage_Core_Exception if error
     * @param bool $fullCheck Whether to make additional validations such as payment status, transaction signature etc.
     *
     * @return array  $params request params
     */
    protected function _validateEventData($fullCheck = true)
    {
        // get request variables
        $params = $this->_eventData;
        if (empty($params)) {
            Mage::throwException('Request does not contain any elements.');
        }
	
        if (empty($params['order'])) {
            Mage::throwException('Missing or invalid order ID.');
        }
		// error_log("params:" . print_r($params,1) . "\n" , 3, 'logfile');
		
        // load order for further validation
        $this->_order = Mage::getModel('sales/order')->loadByIncrementId($params['order']);
        if (!$this->_order->getId()) {
            Mage::throwException('Order not found.');
        }

        if (0 !== strpos($this->_order->getPayment()->getMethodInstance()->getCode(), 'pagbrasil_')) {
            Mage::throwException('Unknown payment method.');
        }

        // make additional validation
        if ($fullCheck) {
            // check payment status
            if (empty($params['status'])) {
                Mage::throwException('Unknown payment status.');
            }

            $checkParams = array('secret');
            foreach ($checkParams as $key) {
                if ($key == 'secret') {
                    $secretKey = Mage::getStoreConfig(
                        Mzcart_Pagbrasil_Helper_Data::XML_PATH_SECRET_KEY,
                        $this->_order->getStoreId()
                    );

                    if (empty($secretKey)) {
                        Mage::throwException('Secret key is empty.');
                    }
					break;
                }
            }

            if ($secretKey != $params[$key]) {
                Mage::throwException('Hash is not valid.');
            }
        }
        return $params;
    }
}
