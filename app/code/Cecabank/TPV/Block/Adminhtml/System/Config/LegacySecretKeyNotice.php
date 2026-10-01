<?php

namespace Cecabank\TPV\Block\Adminhtml\System\Config;

use Cecabank\TPV\Model\LegacySecretKey;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Store\Model\ScopeInterface;

/**
 * Renders the legacy secret key notice at the top of the Cecabank payment
 * configuration group, only when the scope being edited uses an 8-character key.
 */
class LegacySecretKeyNotice extends Field
{
    /**
     * @var LegacySecretKey
     */
    protected $legacySecretKey;

    public function __construct(Context $context, LegacySecretKey $legacySecretKey, array $data = [])
    {
        $this->legacySecretKey = $legacySecretKey;
        parent::__construct($context, $data);
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        if (!$this->isLegacyKeyConfiguredInCurrentScope()) {
            return '';
        }

        return '<tr id="row_' . $element->getHtmlId() . '"><td colspan="5">'
            . '<div class="message message-warning">' . $this->legacySecretKey->getNoticeHtml() . '</div>'
            . '</td></tr>';
    }

    /**
     * Resolves the scope being edited from the config page request (store / website / default).
     *
     * @return bool
     */
    protected function isLegacyKeyConfiguredInCurrentScope()
    {
        $storeId = $this->getRequest()->getParam('store');
        if ($storeId) {
            return $this->legacySecretKey->isLegacyKeyConfigured(ScopeInterface::SCOPE_STORES, $storeId);
        }

        $websiteId = $this->getRequest()->getParam('website');
        if ($websiteId) {
            return $this->legacySecretKey->isLegacyKeyConfigured(ScopeInterface::SCOPE_WEBSITES, $websiteId);
        }

        return $this->legacySecretKey->isLegacyKeyConfigured();
    }
}
