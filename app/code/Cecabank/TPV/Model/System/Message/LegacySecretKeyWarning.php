<?php

namespace Cecabank\TPV\Model\System\Message;

use Cecabank\TPV\Model\LegacySecretKey;
use Magento\Framework\Notification\MessageInterface;

/**
 * Admin system message (top bar / "System Messages" popup) shown while the
 * merchant is still using the legacy 8-character Cecabank secret key.
 */
class LegacySecretKeyWarning implements MessageInterface
{
    const IDENTITY = 'cecabank_tpv_legacy_secret_key';

    /**
     * @var LegacySecretKey
     */
    protected $legacySecretKey;

    public function __construct(LegacySecretKey $legacySecretKey)
    {
        $this->legacySecretKey = $legacySecretKey;
    }

    /**
     * @return string
     */
    public function getIdentity()
    {
        return self::IDENTITY;
    }

    /**
     * @return bool
     */
    public function isDisplayed()
    {
        return $this->legacySecretKey->isLegacyKeyConfiguredAnywhere();
    }

    /**
     * @return string
     */
    public function getText()
    {
        return $this->legacySecretKey->getNoticeHtml();
    }

    /**
     * @return int
     */
    public function getSeverity()
    {
        return self::SEVERITY_CRITICAL;
    }
}
