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
 * @author george zheng <xinhaozheng@gmail.com>
 * @more info available on mzcart.com
 */
class Mzcart_Pagbrasil_Block_Payment extends Mage_Core_Block_Template
{

    /**
     * Return checkout session instance
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function _getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Return order instance
     *
     * @return Mage_Sales_Model_Order|null
     */
    protected function _getOrder()
    {
        if ($this->getOrder()) {
            return $this->getOrder();
        } elseif ($orderIncrementId = $this->_getCheckout()->getLastRealOrderId()) {
            return Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
        } else {
            return null;
        }
    }
	public function getPagbrasilMethod() {
	    return $this->_getOrder()->getPayment()->getMethodInstance()->getCode();
	}
	
    /**
     * Get Form data by using ogone payment api
     *
     * @return array
     */
    public function getFormData()
    {
        return $this->_getOrder()->getPayment()->getMethodInstance()->getFormFields();
    }
	
    /**
     * Getting gateway url
     *
     * @return string
     */
    public function getFormAction()
    {
        return $this->_getOrder()->getPayment()->getMethodInstance()->getUrl();
    }
	
    /**
     * Getting Boleto Banc¨¢rio url
     *
     * @return string
     */
    public function getBbUrl()
    {
        return $this->_getOrder()->getPayment()->getMethodInstance()->getBolUrl();
    }	
	
	/**
     * Getting Boleto Banc¨¢rio url
     *
     * @return string
     */
    public function getObtBanks()
    {
        return $this->_getOrder()->getPayment()->getMethodInstance()->getObtBanks();
    }	
	
}
