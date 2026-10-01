<?php

namespace Cecabank\TPV\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Detects merchants still configured with the legacy 8-character Cecabank
 * secret key (SHA2 signature) instead of the new 32-character HMAC key, and
 * builds the "action required" notice shown in the admin.
 */
class LegacySecretKey
{
    const CONFIG_PATH = 'payment/cecabank/secretkey';

    /**
     * Key length that makes CecabankModel::getClientConfig() select SHA2.
     */
    const LEGACY_KEY_LENGTH = 8;

    const URL_TPV_PORTAL = 'https://comercios.ceca.es/';
    const URL_SECURITY_BULLETIN = 'https://comercios.ceca.es/docs_constpv/seguridad/TPV_Virtual_Boletin_de_Seguridad_0525_001.pdf';
    const URL_PLUGIN_MANUAL = 'https://comercios.ceca.es/resourcesPortal/descargables_portal/manual_magento.pdf';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    public function __construct(ScopeConfigInterface $scopeConfig, StoreManagerInterface $storeManager)
    {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
    }

    /**
     * @param mixed $secretKey
     * @return bool
     */
    public function isLegacyKey($secretKey)
    {
        return strlen((string) $secretKey) === self::LEGACY_KEY_LENGTH;
    }

    /**
     * @param string $scope
     * @param int|string|null $scopeCode
     * @return bool
     */
    public function isLegacyKeyConfigured($scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeCode = null)
    {
        return $this->isLegacyKey($this->scopeConfig->getValue(self::CONFIG_PATH, $scope, $scopeCode));
    }

    /**
     * Whether the legacy key is in use in the default scope or in any website or store.
     *
     * @return bool
     */
    public function isLegacyKeyConfiguredAnywhere()
    {
        if ($this->isLegacyKeyConfigured()) {
            return true;
        }

        foreach ($this->storeManager->getWebsites() as $website) {
            if ($this->isLegacyKeyConfigured(ScopeInterface::SCOPE_WEBSITES, $website->getCode())) {
                return true;
            }
        }

        foreach ($this->storeManager->getStores() as $store) {
            if ($this->isLegacyKeyConfigured(ScopeInterface::SCOPE_STORES, $store->getCode())) {
                return true;
            }
        }

        return false;
    }

    /**
     * HTML of the notice. Only <strong>, <br> and <a> are used so it renders
     * correctly both in the admin system messages bar and in the config page.
     *
     * @return string
     */
    public function getNoticeHtml()
    {
        $portalLink = $this->renderLink(self::URL_TPV_PORTAL, __('Portal de Administración del TPV Virtual'));
        $bulletinLink = $this->renderLink(self::URL_SECURITY_BULLETIN, __('Boletín de Seguridad'));
        $manualLink = $this->renderLink(self::URL_PLUGIN_MANUAL, __('Manual plugin'));

        return '<strong>' . __('Acción requerida: actualización de seguridad pendiente') . '</strong><br>'
            . __('Tu comercio está configurado con una clave de 8 caracteres. Para completar la adaptación de seguridad debes:') . '<br>'
            . '&bull; ' . __('Configurar la nueva clave de 32 caracteres que puedes encontrar en el %1, en la configuración de tu comercio.', $portalLink) . '<br>'
            . '&bull; &#128214; ' . __('Más información: %1 &middot; %2', $bulletinLink, $manualLink) . '<br>'
            . __('Si ya has completado estas actualizaciones, puedes ignorar este mensaje.');
    }

    /**
     * @param string $url
     * @param string|\Magento\Framework\Phrase $text
     * @return string
     */
    protected function renderLink($url, $text)
    {
        return '<a href="' . $url . '" target="_blank" rel="noopener noreferrer">' . $text . '</a>';
    }
}
