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

    public function dataAdsSvGet(string $key)
    {
        $adsInfo = $this->getAdsInfo();

        return $adsInfo[$key] ?? null;
    }

    public function dataAdsSvSet(string $key, ?string $value): void
    {
        $adsInfo = $this->getAdsInfo();
        if ($value === null)
        {
            unset($adsInfo[$key]);
        }
        else
        {
            $adsInfo[$key] = $value;
        }
    }

    public function isPostWordCountMet($post, $posts): bool
    {
        if (!$post instanceof \XF\Entity\Post)
        {
            return false;
        }
        if (!$post->hasRelation('Threadmark'))
        {
            return false;
        }

        $adEveryXWords = (int)(\XF::options()->svAdEveryXWords ?? 0);
        if ($adEveryXWords === 0)
        {
            return false;
        }

        $adsInfo = $this->getAdsInfo();
        $adsByWordCountPosition = $adsInfo['adsByWordCountPosition'] ?? [];
        /** @var \SV\WordCountSearch\XF\Entity\Post $post */
        $position = $post->position;

        $showAd = $adsByWordCountPosition[$position] ?? null;

        if ($showAd === null)
        {
            if ($posts instanceof AbstractCollection)
            {
                $posts = $posts->toArray();
            }
            else if (!is_array($posts))
            {
                $posts = [];
            }

            $wordCountTotal = 0;
            /** @var \SV\WordCountSearch\XF\Entity\Post[] $posts */
            foreach ($posts as $p)
            {
                $adsByWordCountPosition[$position] = $wordCountTotal === 0;
                $wordCount = $p->RawWordCount;
                $wordCountTotal += $wordCount;

                if ($wordCountTotal >= $adEveryXWords)
                {
                    $wordCountTotal = 0;
                }
            }

            $adsInfo['adsByWordCountPosition'] = $adsByWordCountPosition;
        }

        return $showAd;
    }

    public function isPostFirstThreadmarkedInCategory($post, $posts): bool
    {
        if (!$post instanceof \XF\Entity\Post)
        {
            return false;
        }
        if (!$post->hasRelation('Threadmark'))
        {
            return false;
        }
        /** @var \SV\Threadmarks\XF\Entity\Post $post */
        $threadmark = $post->Threadmark;
        if ($threadmark === null)
        {
            return false;
        }

        $adsInfo = $this->getAdsInfo();

        $position = $post->position;
        $threadmarkCategoryId = $post->Threadmark->threadmark_category_id;
        $firstThreadmarkByCategory = $adsInfo['firstThreadmarkByCategory'] ?? [];

        $positionByCategory = $firstThreadmarkByCategory[$threadmarkCategoryId] ?? null;
        if ($positionByCategory === null)
        {
            if ($posts instanceof AbstractCollection)
            {
                $posts = $posts->toArray();
            }
            else if (!is_array($posts))
            {
                $posts = [];
            }

            /** @var \SV\Threadmarks\XF\Entity\Post[] $posts */
            foreach ($posts as $p)
            {
                $threadmark = $p->Threadmark;
                if ($threadmark !== null && $threadmark->threadmark_category_id === $threadmarkCategoryId)
                {
                    $firstThreadmarkByCategory[$threadmarkCategoryId] = $positionByCategory = $p->position;
                    break;
                }
            }

            if ($positionByCategory === null)
            {
                $firstThreadmarkByCategory[$threadmarkCategoryId] = $positionByCategory = $position;
            }

            $adsInfo['firstThreadmarkByCategory'] = $firstThreadmarkByCategory;
        }

        return $positionByCategory === $position;
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

            /** @var \SV\Threadmarks\XF\Entity\Post[] $posts */
            if ($post->hasRelation('Threadmark'))
            {
                // ignore sticky post is possible
                $stickyPostPosition = null;
                if (\XF::isAddOnActive('SV/StickyAnyPost'))
                {
                    /** @var \XF\Entity\Post|null $p */
                    $p = $posts ? reset($posts) : null;
                    if ($p !== null)
                    {
                        /** @var \SV\StickyAnyPost\XF\Entity\Thread $thread */
                        $thread = $p->Thread;
                        $stickyPostPosition = $thread->sv_sticky_post_position;
                    }
                }

                $stickyThreadmarkPosition = null;
                foreach ($posts as $p)
                {
                    if ($p->Threadmark === null)
                    {
                        continue;
                    }

                    $postPosition = $p->position;
                    $isSticky = $postPosition === $stickyPostPosition;
                    if ($stickyThreadmarkPosition === null && $isSticky)
                    {
                        $stickyThreadmarkPosition = $postPosition;
                    }
                    else if (!$isSticky)
                    {
                        $firstThreadmarkPosition = $postPosition;
                        break;
                    }
                }

                if ($firstThreadmarkPosition === null)
                {
                    $firstThreadmarkPosition = $stickyThreadmarkPosition;
                }
            }

            if ((\XF::options()->svAdOnFirstNonThreadmarkPost ?? true) && $firstThreadmarkPosition === null)
            {
                /** @var \XF\Entity\Post|null $p */
                $p = $posts ? reset($posts) : null;
                $firstThreadmarkPosition = $p->position ?? 0;
            }

            if ($firstThreadmarkPosition === null)
            {
                $firstThreadmarkPosition = -1;
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

        if (\XF::isAddOnActive('Siropu/AdsManager') && $this->hasPermission('siropuAdsManager', 'viewAds'))
        {
            return true;
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

    protected function getPersonalizedAdsId(): string
    {
        if (!(\XF::options()->adsHelper_personalizeAds ?? ''))
        {
            return '';
        }

        return '';
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
        $structure->getters['personalizedAdsId'] = ['getter' => 'getPersonalizedAdsId', 'cache' => true];
        $structure->options['canViewAds'] = true;

        return $structure;
    }
}
