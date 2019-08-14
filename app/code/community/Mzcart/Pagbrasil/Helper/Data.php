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
class Mzcart_Pagbrasil_Helper_Data extends Mage_Payment_Helper_Data
{
    const XML_PATH_EMAIL        = 'pagbrasil/settings/pagbrasil_email';
    const XML_PATH_SECRET_KEY   = 'pagbrasil/settings/secret_key';
    const XML_PATH_PAGBRASIL_TOKEN   = 'pagbrasil/settings/pagbrasil_token';
}
