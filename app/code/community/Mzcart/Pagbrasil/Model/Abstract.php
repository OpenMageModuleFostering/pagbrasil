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
 * PagBrasil payment module
 *
 * @version 1.0
 * @date 02/13/2014
 * @more info available on mzcart.com
 */
abstract class Mzcart_Pagbrasil_Model_Abstract extends Mage_Payment_Model_Method_Abstract
{
    /**
     * unique internal payment method identifier
     */
    protected $_code = 'pagbrasil_abstract';

    protected $_formBlockType = 'pagbrasil/form';
    protected $_infoBlockType = 'pagbrasil/info';
	
    /**
     * Availability options
     */
    protected $_isGateway              = true;
    protected $_canAuthorize           = true;
    protected $_canCapture             = true;
    protected $_canCapturePartial      = false;
    protected $_canRefund              = false;
    protected $_canVoid                = false;
    protected $_canUseInternal         = false;
    protected $_canUseCheckout         = true;
    protected $_canUseForMultishipping = false;

    protected $_paymentMethod    = 'abstract';
    protected $_defaultLocale    = 'en';
    protected $_supportedLocales = array('cn', 'cz', 'da', 'en', 'es', 'fi', 'de', 'fr', 'gr', 'it', 'nl', 'ro', 'ru', 'pl', 'sv', 'tr');
    protected $_hidelogin        = '1';

    protected $_order;

    /**
     * Get order model
     *
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        if (!$this->_order) {
            $this->_order = $this->getInfoInstance()->getOrder();
        }
        return $this->_order;
    }

    /**
     * Return url for redirection after order placed
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('pagbrasil/processing/payment');
    }

    /**
     * Capture payment through PagBrasil api
     *
     * @param Varien_Object $payment
     * @param decimal $amount
     * @return Mzcart_Pagbrasil_Model_Abstract
     */
    public function capture(Varien_Object $payment, $amount)
    {
        $payment->setStatus(self::STATUS_APPROVED)
            ->setTransactionId($this->getTransactionId())
            ->setIsTransactionClosed(0);

        return $this;
    }

    /**
     * Camcel payment
     *
     * @param Varien_Object $payment
     * @return Mzcart_Pagbrasil_Model_Abstract
     */
    public function cancel(Varien_Object $payment)
    {
        $payment->setStatus(self::STATUS_DECLINED)
            ->setTransactionId($this->getTransactionId())
            ->setIsTransactionClosed(1);

        return $this;
    }

    /**
     * Return url of payment method
     *
     * @return string
     */
    public function getUrl()
    {
         return 'https://www.pagbrasil.com/pagbrasil/addorder.mv';
    }

    /**
     * Return url of payment method
     *
     * @return string
     */
    public function getLocale()
    {
        $locale = explode('_', Mage::app()->getLocale()->getLocaleCode());
        if (is_array($locale) && !empty($locale) && in_array($locale[0], $this->_supportedLocales)) {
            return $locale[0];
        }
        return $this->getDefaultLocale();
    }

    /**
     * prepare params array to send it to gateway page via POST
     *
     * @return array
     */
	//@todo _form
    public function _getCommonFields() {
        $order_id = $this->getOrder()->getRealOrderId();
        $billing  = $this->getOrder()->getBillingAddress();
		
        if ($billing->getEmail()) {
            $email = $billing->getEmail();
        } else {
            $email = $this->getOrder()->getCustomerEmail();
        }
		
        $_order = $this->getOrder();
		//prepare amount in BRL
		$storeCurrency = Mage::getSingleton('directory/currency')->load($_order->getBaseCurrencyCode());
		$currency = Mage::app()->getStore()->getCurrentCurrencyCode();
		$allowed_cur = array('BRL');
		if ( !in_array($currency, $allowed_cur)) {
		    $amount =  sprintf('%.2f', $storeCurrency->convert($_order->getBaseGrandTotal(), 'BRL'));
		} else {
		    $amount =  round($this->getOrder()->getGrandTotal(), 2);
		}
		
		//prepare product
		$strProducts = '';
		foreach($_order->getAllItems() as $item) {
		    	$pname = trim(str_replace('"', '', $item->getName()));
 
			    if ( $pname == '' && $strProducts == '') {
				    $pname = 'Order ' . $order_id;
					$qty = $item->getQtyOrdered()*1;
					break;
				}
 
				$qty = $item->getQtyOrdered()*1;
				$strProducts .=  $pname . "(Qty: " . $qty . ")\n";
		
		}
		
		// error_log(print_r($billing,1),3,'aaa');
        $params = array(
            // 'secret'       			=> Mage::getStoreConfig('secret_key'),
			'order'        			=> $order_id,  //date('His') . '-' .
            'payment_method'        => $this->_paymentMethod,
            'product_name'          => $strProducts, 
            'customer_name'         => $billing->getFirstname() . ' ' . $billing->getLastname(),
            'customer_taxid'        => preg_replace('/[^0-9]+/', '',$billing->getVatId()),
            'customer_email'        => $email,
            'customer_phone'        => $billing->getTelephone(),
			'address_street'        => $billing->getStreet(-1),
            'address_zip'           => preg_replace('/[^0-9]+/', '',$billing->getPostcode()),
            'address_city'          => $billing->getCity(),
            'address_state'         => $billing->getRegionCode(),
            'amount_brl'            => $amount
        );
		return $params;
    }
    /**
     * Get initialized flag status
     * @return true
     */
    public function isInitializeNeeded()
    {
        return true;
    }

    /**
     * Instantiate state and set it to state onject
     * //@param
     * //@param
     */
    public function initialize($paymentAction, $stateObject)
    {
        $state = Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
        $stateObject->setState($state);
        $stateObject->setStatus(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT);
        $stateObject->setIsNotified(false);
    }

    /**
     * Get config action to process initialization
     *
     * @return string
     */
    public function getConfigPaymentAction()
    {
        $paymentAction = $this->getConfigData('payment_action');
        return empty($paymentAction) ? true : $paymentAction;
    }
}
