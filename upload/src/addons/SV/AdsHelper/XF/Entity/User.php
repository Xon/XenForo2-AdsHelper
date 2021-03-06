<?php

namespace SV\AdsHelper\XF\Entity;

use ArrayObject;
use XF\Mvc\Entity\Structure;
use function count;

/**
 * Extends \XF\Entity\User
 */
class User extends XFCP_User
{
    public ?ArrayObject $adsInfo = null;

    public function getAdsInfo(): ArrayObject
    {
        if ($this->adsInfo === null)
        {
            $this->adsInfo = new ArrayObject();
        }

        return $this->adsInfo;
    }

    public function canViewAds(): bool
    {
        if (!$this->user_id)
        {
            return true;
        }

        $groupBypass = (array)(\XF::options()->svAdsGroupBypass ?? []);

        if (count($groupBypass) !== 0 && $this->isMemberOf($groupBypass))
        {
            return false;
        }

        return true;
    }

    /** @noinspection PhpMissingReturnTypeInspection */
    public static function getStructure(Structure $structure)
    {
        $structure = parent::getStructure($structure);

        $structure->getters['adsInfo'] = ['getter' => 'getAdsInfo', 'cache' => false];
        $structure->getters['canViewAds'] = ['getter' => 'canViewAds', 'cache' => true];
        $structure->options['canViewAds'] = true;

        return $structure;
    }
}