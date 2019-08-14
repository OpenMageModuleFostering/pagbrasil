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
 * PagBrasil payment module  -- Online Bank Transfer Payment
 *
 * @version 1.0
 * @date 02/13/2014
 * @author george zheng <xinhaozheng@gmail.com>
 * @more info available on mzcart.com
 */
class Mzcart_Pagbrasil_Model_Obt extends Mzcart_Pagbrasil_Model_Abstract
{
    /**
     * unique internal payment method identifier
     */
    protected $_code			= 'pagbrasil_obt';
    protected $_paymentMethod	= 'R';//RES
	
	/**
     * prepare params array to send it to gateway page via POST
     *
     * @return array
     */
    public function getFormFields() {
	    $_order = $this->getOrder();
		$order_id = $_order->getRealOrderId();
        $params = array(
            'secret'			=> Mage::getStoreConfig(Mzcart_Pagbrasil_Helper_Data::XML_PATH_SECRET_KEY, $_order->getStoreId()),
            // 'url_return'		=> Mage::getUrl('pagbrasil/processing/return'),	
            'url_return'		=> Mage::getUrl('pagbrasil/processing/return', array('order' => $order_id)),	
            'param_url'			=> '',				
            // 'param_url'			=> '?order=' . $order_id,				
		);
		$params = array_merge($params, $this->_getCommonFields());
        return $params;
    }
	
    /**
     * 
     *
     * @return array
     */
    public function getObtBanks() {
        return array(
		    'R' => Mage::helper('pagbrasil')->__('pagbrasil_bank_r'),
		    'E' => Mage::helper('pagbrasil')->__('pagbrasil_bank_e'),
		    'S' => Mage::helper('pagbrasil')->__('pagbrasil_bank_s'),
		);
    }
}
