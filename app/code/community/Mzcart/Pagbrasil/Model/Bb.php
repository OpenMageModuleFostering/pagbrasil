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
 * PagBrasil payment module  -- Bolleto Bancario Payment
 *
 * @version 1.0
 * @date 02/13/2014
 * @author george zheng <xinhaozheng@gmail.com>
 * @more info available on mzcart.com
 */
class Mzcart_Pagbrasil_Model_Bb extends Mzcart_Pagbrasil_Model_Abstract {
    /**
     * unique internal payment method identifier
     */
    protected $_code			= 'pagbrasil_bb';
    protected $_paymentMethod	= 'B';
	
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
            /* 'bol_expiration'	=> Mage::getStoreConfig(Mzcart_Pagbrasil_Helper_Data::XML_PATH_BOL_EXPIRATION, $_order->getStoreId()),*/
            'param_url'			=> '?order=' . $order_id,
		);
		$params = array_merge($params, $this->_getCommonFields());
        return $params;
    }	


	/**
     * 
     *
     * @return array
     */
    public function getBolUrl() {
	    $params = $this->getFormFields();
		$curl = curl_init($this->getUrl());
		$request = '';
		foreach($params as $k => $v) {
			$request .= $k . '=' . trim($v) . '&';			
		}
		
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_TIMEOUT, 50);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		$response = curl_exec($curl);
        if (substr($response, 0, 7) == 'http://' ) {
		    return $response;
		} else {
		    return false;
		}
    }	
}
