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
 * PagBrasil payment module  -- Credit Card Payment
 *
 * @version 1.0
 * @date 02/13/2014
 * @more info available on mzcart.com
 */
class Mzcart_Pagbrasil_Model_Acc extends Mzcart_Pagbrasil_Model_Abstract
{
    /**
     * unique internal payment method identifier
     */
    protected $_code			= 'pagbrasil_acc';
    protected $_paymentMethod	= 'C';
	
	/**
     * prepare params array to send it to gateway page via POST
     *
     * @return array
     */
    public function getFormFields()
	{
	    $_order = $this->getOrder();
		// $order_id = $_order->getRealOrderId();
        $params = array
		(
            'pbtoken'			=> Mage::getStoreConfig(Mzcart_Pagbrasil_Helper_Data::XML_PATH_PAGBRASIL_TOKEN, $_order->getStoreId()),
            'cc_installments'	=> 0,			
            'url_return'		=> Mage::getUrl('pagbrasil/processing/return'),	
			'cc_auth'			=> Mage::getStoreConfig("payment/pagbrasil_acc/preauth") == '1' ? '1' : '',
			'responsive'		=> '1'
        );
		$params = array_merge($params, $this->_getCommonFields());
		return $params;
    }
	
    /**
     * Return url of payment method
     *
     * @return string
     */
    public function getUrl()
    {
         // return 'https://www.pagbrasil.com/pagbrasil/addorder.mv';
         return 'https://www.pagbrasil.com/pagbrasil/iframe/checkoutframe.mv';
    }

}
