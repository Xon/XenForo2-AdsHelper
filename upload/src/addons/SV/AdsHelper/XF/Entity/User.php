<?php

namespace SV\AdsHelper\XF\Entity;

use ArrayObject;
use XF\Mvc\Entity\AbstractCollection;
use XF\Mvc\Entity\Structure;
use function count;
use function is_array;
use function reset;

/**
 * @extends \XF\Entity\User
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

    public function isPostFirstOrFirstThreadmarked($post, $posts): bool
    {
        if (!$post instanceof \XF\Entity\Post)
        {
            return false;
        }
        $adsInfo = $this->getAdsInfo();

        $firstThreadmarkPosition = $adsInfo['firstThreadmarkPosition'] ?? null;
        if ($firstThreadmarkPosition === null)
        {
            if ($posts instanceof AbstractCollection)
            {
                $posts = $posts->toArray();
            }
            else if (!is_array($posts))
            {
                $posts = [];
            }
            /** @var \XF\Entity\Post[]|\SV\Threadmarks\XF\Entity\Post[] $posts */
            if ($post->hasRelation('Threadmark'))
            {
                foreach ($posts as $p)
                {
                    if ($p->Threadmark !== null)
                    {
                        $firstThreadmarkPosition = $p->position;
                        break;
                    }
                }
            }

            if ($firstThreadmarkPosition === null)
            {
                $p = $posts ? reset($posts) : null;
                $firstThreadmarkPosition = $p->position ?? 0;
            }


            $adsInfo['firstThreadmarkPosition'] = $firstThreadmarkPosition;
        }

        return $firstThreadmarkPosition === $post->position;
    }

    public function canViewAds(): bool
    {
        if (!$this->getOption('canViewAds'))
        {
            return false;
        }

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

    public function isNotSeenThisSession(string $key, ?int $duration = null): bool
    {
        $session = $this->app()->session();
        if (!$session || !$session->isStarted())
        {
            return true;
        }

        $duration = $duration ?? 20;
        $offset = \XF::$time + $duration * 60;

        if ($session->keyExists($key))
        {
            $seenTimestamp = (int)$session->get($key);
            if ($seenTimestamp >= $offset)
            {
                return false;
            }
        }

        $session->offsetSet($key, $offset);

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
