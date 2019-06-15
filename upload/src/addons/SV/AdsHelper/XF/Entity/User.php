<?php

namespace SV\AdsHelper\XF\Entity;

use XF\Mvc\Entity\Structure;

/**
 * Extends \XF\Entity\User
 */
class User extends XFCP_User
{
    public $adsInfo = null;

    public function getAdsInfo()
    {
        if ($this->adsInfo === null)
        {
            $this->adsInfo = new \ArrayObject();
        }
        return $this->adsInfo;
    }

    public static function getStructure(Structure $structure)
    {
        $structure = parent::getStructure($structure);

        $structure->getters['adsInfo'] = ['getter' => 'getAdsInfo', 'cache' => false];

        return $structure;
    }
}