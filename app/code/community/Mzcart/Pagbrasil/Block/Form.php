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
class Mzcart_Pagbrasil_Block_Form extends Mage_Payment_Block_Form
{
    /**
     * Available locales for content URL generation
     *
     * @var array
     */
    protected $_supportedInfoLocales = array('de');

    /**
     * Default locale for content URL generation
     *
     * @var string
     */
    protected $_defaultInfoLocale = 'en';

    /**
     * Constructor. Set template.
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('pagbrasil/form.phtml');
    }

    /**
     * Return payment logo image src
     *
     * @param string $payment Payment Code
     * @return string|bool
     */
    public function getPaymentImageSrc($payment)
    {
        // if ($payment == 'pagbrasil_obt') {
            // $payment .= '_'.$this->getInfoLocale();
        // }
    
        $imageFilename = Mage::getDesign()
            ->getFilename('images' . DS . 'pagbrasil' . DS . $payment, array('_type' => 'skin'));

        if (file_exists($imageFilename . '.png')) {
            return $this->getSkinUrl('images/pagbrasil/' . $payment . '.png');
        } else if (file_exists($imageFilename . '.gif')) {
            return $this->getSkinUrl('images/pagbrasil/' . $payment . '.gif');
        }

        return false;
    }

    /**
     * Return supported locale for information text
     *
     * @return string
     */
    public function getInfoLocale()
    {
        $locale = substr(Mage::app()->getLocale()->getLocaleCode(), 0 ,2);
        if (!in_array($locale, $this->_supportedInfoLocales)) {
            $locale = $this->_defaultInfoLocale;
        }
        return $locale;
    }

    /**
     * Return info URL for eWallet payment
     *
     * @return string
     */
    public function getBbInfoUrl()
    {
        $locale = substr(Mage::app()->getLocale()->getLocaleCode(), 0 ,2);
        return 'http://www.pagbrasil.com/pb/services/payment-method/boleto-bancario.html?l=' . strtoupper($locale);
    }
}
