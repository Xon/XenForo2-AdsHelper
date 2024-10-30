<?php

namespace SV\AdsHelper\XF\Pub\Controller;

use SV\AdsHelper\XF\Entity\User as ExtendedUserEntity;
use XF\Mvc\Entity\AbstractCollection;

/**
 * @extends \XF\Pub\Controller\Thread
 */
class Thread extends XFCP_Thread
{
    /** @noinspection PhpMissingReturnTypeInspection */
    protected function getNewPostsReplyInternal(\XF\Entity\Thread $thread, AbstractCollection $posts, \XF\Entity\Post $firstUnshownPost = null)
    {
        /** @var ExtendedUserEntity $visitor */
        $visitor = \XF::visitor();
        $adsInfo = $visitor->getAdsInfo();
        $adsInfo['showAdOnce'] = 1;

        return parent::getNewPostsReplyInternal($thread, $posts, $firstUnshownPost);
    }
}