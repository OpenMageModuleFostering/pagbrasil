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
class Mzcart_Pagbrasil_ProcessingController extends Mage_Core_Controller_Front_Action
{
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
     * Iframe page which submits the payment data to PagBrasil.
     */
    public function placeformAction()
    {
       $this->loadLayout();
       $this->renderLayout();
    }

    /**
     * Show orderPlaceRedirect page which contains the PagBrasil iframe.
     */
    public function paymentAction()
    {
        try {
		    /* $this->getResponse()->setHeader("Content-Type", "text/html; charset=ISO-8859-1",true); */
            $session = $this->_getCheckout();
			
     		$order = Mage::getModel('sales/order');
			$order->loadByIncrementId($session->getLastRealOrderId());
			if (!$order->getId()) {
				Mage::throwException('No order for processing found');
			}
			
			$billing  = $order ->getBillingAddress();
			$vatid = $billing->getVatId();
			
			if($this -> _validaCPF($vatid) || $this -> _validaCNPJ($vatid))
			{
				// $session->addError(Mage::helper('pagbrasil')->__('The CPF OR CNPJ is valid!'));
				// Mage::throwException(Mage::helper('pagbrasil')->__('The CPF OR CNPJ is valid!'));
			}
			else
			{
			    Mage::getModel('sales/quote')->load($session->getQuoteId())->setIsActive(true)->save(); 
			    $session->addError(Mage::helper('pagbrasil')->__('The CPF or CNPJ is not valid!'));
				//parent::_redirect('checkout/cart');
				parent::_redirect('customer/address/');
				// exit();
				Mage::throwException(Mage::helper('pagbrasil')->__('The CPF or CNPJ is not valid!'));
			}
			
			
			
			if($order->getState() != "pending_payment")
			{
				parent::_redirect('checkout/cart');
				//die();
			}
			else
			{
				$order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, Mage_Sales_Model_Order::STATE_PENDING_PAYMENT,
					Mage::helper('pagbrasil')->__('The customer was redirected to PagBrasil.')
				);
				$order->save();

				$session->setPagbrasilQuoteId($session->getQuoteId());
				$session->setPagbrasilRealOrderId($session->getLastRealOrderId());
				$session->getQuote()->setIsActive(true)->save();
				//$session->getQuote()->setIsActive(false)->save();
				//$session->clear();

				$this->loadLayout();
				$this->renderLayout();
			}
        } catch (Exception $e){
            Mage::logException($e);
            parent::_redirect('checkout/cart');
        }
    }
	
	/**
     * Obt:Action to which the customer will be returned when the payment is made.
     */
    public function bankAction()
	{
	    $session = $this->_getCheckout();
	    $session->getQuote()->setIsActive(true)->save();
	    $curl = curl_init('https://www.pagbrasil.com/pagbrasil/addorder.mv');
		$request = '';
		// $pm = '';$url = '';
		$params = $this->getRequest()->getParams();
		if (isset($params['order']))
		{
			$order_id = trim($params['order']);
		}
		else
		{
			die('Illegal Access');
		}
		$_order = Mage::getModel('sales/order')->loadByIncrementId($order_id);
		foreach($params as $k => $v)
		{
		    // if ($k == 'payment_method') {
			    // $pm = trim($v);
			// }
			// if ($k == 'url_return') {
			    // $url = trim($v);
				// error_log("\nurl:" . $url . "\n",3,'logfile');
				// continue;
			// }
			if ($k == 'url_return') {
				continue;
			}
			$request .= $k . '=' . urlencode(trim($v)) . '&';			
		}
        $params['url_return'] .= '?payment_method=' . $params['payment_method'];
        $request .= 'url_return=' . urlencode($params['url_return']);
		error_log("\n\n url_return " . $params['url_return'] . "\n", 3,'logfile');
		//$request .= 'url_return=' . $url . 'payment_method/' . $pm;
		error_log("\n\n request " . $request . "\n",3,'logfile');

		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_TIMEOUT, 50);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		$response = curl_exec($curl);
		error_log("\n" . $response . "\n",3,'logfile');
		$msg = '';
		$ok = 0;
		if ($response!= false && substr($response, 0, 7) == 'http://' )
		{
			$ok = 1;
		}
		
		if ($ok == 1)
		{
			$msg = Mage::helper('pagbrasil')->__('The customer was redirected to bank.');
			$_order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, true, $msg);
			$_order->save();
			$ok = header('Location: ' . $response);
			die();
		}
		else
		{
		    parent::_redirect('checkout/cart');
		}
	}
	
	
	/**
     * Cc(if mode)/Obt:Action to which the customer will be returned when the payment is made.
     */
    public function returnAction()
	{
	    // error_log(print_r($params,1),3,'abc');
	    $params = $this->getRequest()->getParams();
		if (isset($params['order']))
		{
			$order_id = trim($params['order']);
		}
		else
		{
			die('Illegal Access');
		}
		
		$_order = Mage::getModel('sales/order')->loadByIncrementId($order_id);
		
		if (!$_order->getId())
		{
            Mage::throwException('Order not found.');
        }
		
		$curl = curl_init('https://www.pagbrasil.com/pagbrasil/getorder.mv');
		$request = 'secret=' . Mage::getStoreConfig(Mzcart_Pagbrasil_Helper_Data::XML_PATH_SECRET_KEY, $_order->getStoreId());
		$request .= '&order=' . $order_id;
		
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_TIMEOUT, 50);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		
		$response = curl_exec($curl);
		
		
		$xml = new DOMDocument();
		$xml->loadXML($response);
		
		if ( isset($xml->getElementsByTagName('order_status')->item(0)->nodeValue))
		{
			$payment_status = $xml->getElementsByTagName('order_status')->item(0)->nodeValue;
			switch ($payment_status)
			{
				case 'PC':$params['status'] = 2; break;
				case 'PA':$params['status'] = 1; break;
				case 'WP':$params['status'] = 0; break;
				case 'PF':$params['status'] = -2; break;
				case 'PR':$params['status'] = -2; break;
			}
		}	
		// var_dump($params);exit;
		// isset($params['payment_method'])? '': $params['payment_method']='Obt';

		$session = $this->_getCheckout();
		Mage::getModel('sales/quote')->load($session->getQuoteId())->setIsActive(true)->save(); 
		
		$event = Mage::getModel('pagbrasil/event')->setEventData($params);
		$message = $event->processStatusEvent(false);
		//var_dump($params);exit;
		if($params['status'] < 1)
		{
		    if($params["payment_method"] != "R" && $params["payment_method"] != "E" && $params["payment_method"] != "S")
			{
				$session->addError($message);
			}
			//Mage::getModel('sales/quote')->load($session->getQuoteId())->setIsActive(true)->save(); 
            $this->_redirect('checkout/cart');
			//exit();
		}
		elseif($params['status'] == 1)
		{
			$this->_redirect('checkout/onepage/success');
		}
		else
		{
			$quoteId = $event->successEvent();
            $this->_getCheckout()->setLastSuccessQuoteId($quoteId);
			// var_dump($session->getQuoteId());exit;
			// var_dump($quoteId);exit;
			//$quote = Mage::getModel('sales/quote')->load($quoteId);
			//$quote->setIsActive(true)->save();
			$session->setQuoteId($quoteId);
			//Mage::getModel('sales/quote')->load($quoteId)->setIsActive(true)->save(); 
            $this->_redirect('checkout/onepage/success');
		}
		//need a temp
		//check the status action temp
	}
	
	/**
     * Action to which the customer will be returned when the payment is made.
     */
    public function postbackAction()
	{
	    $params = $this->getRequest()->getParams();
		
		error_log(print_r($params,1) . "\n\n",3,'logfile');
		/*
		$params =array(
			'payment_method' => 'B',
			'secret' => 'b1299d4c01b127f02851e3b726b75675615f8081',
			'content' => '<boletos_list><boleto><order>100000033</order><payment_date>02/17/2014</payment_date><amount_paid>12.05</amount_paid><amount_due>12.05</amount_due><param_url>order=100000033</param_url></boleto></boletos_list>'
		);
		*/
		
		if (isset($params['secret']))
		{
			$secret = trim($params['secret']);
		}
		else
		{
			die('Illegal Access');
		}
		
		$payment_method = trim($params['payment_method']);
		switch($payment_method)
		{
			case 'B':
				$content = trim(preg_replace('/\s+/', '',$params['content']));
				$xml = new DOMDocument();
				$content = str_replace('<br />','',nl2br($content));
				$xml->loadXML($content);
				$boletolist = $xml->getElementsByTagName('boleto');
				foreach ($boletolist as $boleto)
				{						
					$order_id = trim($boleto->childNodes->item(0)->nodeValue);
					
					$_order = Mage::getModel('sales/order')->loadByIncrementId($order_id);
					
					if (!$_order->getId()) {
						Mage::throwException('Order not found.');
					}
					$secret2 = Mage::getStoreConfig(Mzcart_Pagbrasil_Helper_Data::XML_PATH_SECRET_KEY, $_order->getStoreId());
					if ($secret != $secret2)
					{
						die('Illegal Access');
					}
				
					$amount_paid = trim($boleto->childNodes->item(2)->nodeValue);
					$amount_due = trim($boleto->childNodes->item(3)->nodeValue);
					$amount_brl = $amount_paid;
					
					if ($amount_paid == $amount_due )
					{
						$msg = Mage::helper('pagbrasil')->__('The order was paid.');
						$_order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true, $msg);
						$_order->save();
						$_order->getPayment()->setLastTransId($order_id);
						$_order->sendNewOrderEmail();
						$_order->setEmailSent(true);
					}
					else 
					{
						$msg = Mage::helper('pagbrasil')->__('The order was partialy paid. Amount Due: %s, Amount Paid: %s.');
						$msg = sprintf($msg, $amount_due, $amount_paid);
						$_order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, true, $msg);
						$_order->getPayment()->setLastTransId($order_id);
					}
					$_order->save();
				}
				break;
			default:
				$payment_status = $params['payment_status'];
				switch ($payment_status)
				{
					case 'A':$params['status'] = $params['cc_auth'] == 1 ? 1 : 2; break;
					case 'F':$params['status'] = -2; break;
					case 'R':$params['status'] = 0; break;
					case 'C':$params['status'] = -1; break;
					case 'P':$params['status'] = -1; break;
				}					
				$event = Mage::getModel('pagbrasil/event')->setEventData($params);
				$message = $event->processStatusEvent();
		}
    
		echo 'Received successfully in '.time();
        die();
    }	

    /**
     * Obt:Action to which the customer will be returned when the payment is made.
     */
    public function successAction()
	{
        $event = Mage::getModel('pagbrasil/event')
                 ->setEventData($this->getRequest()->getParams());
        try
		{
            $quoteId = $event->successEvent();
            $this->_getCheckout()->setLastSuccessQuoteId($quoteId);
            $this->_redirect('checkout/onepage/success');
            return;
        }
		catch (Mage_Core_Exception $e)
		{
            $this->_getCheckout()->addError($e->getMessage());
        }
		catch(Exception $e)
		{
            Mage::logException($e);
        }
        $this->_redirect('checkout/cart');
    }

    /**
     * Action to which the customer will be returned if the payment process is
     * cancelled.
     * Cancel order and redirect user to the shopping cart.
     */
    public function cancelAction()
    {
        $event = Mage::getModel('pagbrasil/event')
                 ->setEventData($this->getRequest()->getParams());
        $message = $event->cancelEvent();

        // set quote to active
        $session = $this->_getCheckout();
        if ($quoteId = $session->getPagbrasilQuoteId()) {
            $quote = Mage::getModel('sales/quote')->load($quoteId);
            if ($quote->getId()) {
                $quote->setIsActive(true)->save();
                $session->setQuoteId($quoteId);
            }
        }

        $session->addError($message);
        $this->_redirect('checkout/cart');
    }

    /**
     * Action to which the transaction details will be posted after the payment
     * process is complete.
     */
    public function statusAction()
    {
        $event = Mage::getModel('pagbrasil/event')
            ->setEventData($this->getRequest()->getParams());
        $message = $event->processStatusEvent();
        $this->getResponse()->setBody($message);
    }

    /**
     * Set redirect into responce. This has to be encapsulated in an JavaScript
     * call to jump out of the iframe.
     *
     * @param string $path
     * @param array $arguments
     */
    protected function _redirect($path, $arguments=array())
    {
        $this->getResponse()->setBody(
            $this->getLayout()
                ->createBlock('pagbrasil/redirect')
                ->setRedirectUrl(Mage::getUrl($path, $arguments))
                ->toHtml()
        );
        return $this;
    }
	
	function _validaCPF($cpf)
	{ 
		$tam_cpf = strlen($cpf); 
		$cpf_limpo = "";
		for ($i=0; $i<$tam_cpf; $i++)
		{ 
			$carac = substr($cpf, $i, 1); 
			if (ord($carac)>=48 && ord($carac)<=57)
			{
				$cpf_limpo .= $carac; 
			}
		} 
		if (strlen($cpf_limpo)!=11)
		{
			return false;
		}

		for($i = 0; $i <= 9; $i++)
		{
			if(str_repeat($i, 11) == $cpf_limpo)
			{
				return false;
			}
		}
		
		// achar o primeiro digito verificador 
		$soma = 0; 
		for ($i=0; $i<9; $i++)
		{
			$soma += (int)substr($cpf_limpo, $i, 1) * (10-$i); 
		}

		if ($soma == 0)
		{
			return false;
		}  

		$primeiro_digito = 11 - $soma % 11; 

		if ($primeiro_digito > 9)
		{
			$primeiro_digito = 0;
		}

		if (substr($cpf_limpo, 9, 1) != $primeiro_digito)
		{
			return false;
		}

		// acha o segundo digito verificador 
		$soma = 0; 
		for ($i=0; $i<10; $i++)
		{
			$soma += (int)substr($cpf_limpo, $i, 1) * (11-$i); 
		}

		$segundo_digito = 11 - $soma % 11; 

		if ($segundo_digito > 9)
		{
			$segundo_digito = 0; 
		}

		if (substr($cpf_limpo, 10, 1) != $segundo_digito)
		{
			return false;
		}

		return true; 
	} 


	function _validaCNPJ($cnpj)
	{
		$pontos = array(',','-','.','','/');
		
		$cnpj = str_replace($pontos,'',$cnpj);
		$cnpj = trim($cnpj);
		if(empty($cnpj) || strlen($cnpj) != 14)
		{
			return false;
		}
		else
		{
			$rev_cnpj = strrev(substr($cnpj, 0, 12));
			$sum = '';
			for ($i = 0; $i <= 11; $i++)
			{
				$i == 0 ? $multiplier = 2 : $multiplier;
				$i == 8 ? $multiplier = 2 : $multiplier;
				$multiply = ($rev_cnpj[$i] * $multiplier);
				$sum = $sum + $multiply;
				$multiplier++;

			}
			$rest = $sum % 11;
			if ($rest == 0 || $rest == 1)  $dv1 = 0;
			else $dv1 = 11 - $rest;
			
			$sub_cnpj = substr($cnpj, 0, 12);
			$rev_cnpj = strrev($sub_cnpj.$dv1);
			unset($sum);
			$sum = '';
			for ($i = 0; $i <= 12; $i++)
			{
				$i == 0 ? $multiplier = 2 : $multiplier;
				$i == 8 ? $multiplier = 2 : $multiplier;
				$multiply = ($rev_cnpj[$i] * $multiplier);
				$sum = $sum + $multiply;
				$multiplier++;
			}
			$rest = $sum % 11;
			if ($rest == 0 || $rest == 1)  $dv2 = 0;
			else $dv2 = 11 - $rest;

			if ($dv1 == $cnpj[12] && $dv2 == $cnpj[13])
			{
				return true; //$cnpj;
			}
			else
			{
				return false;
			}
		}
	}
}
