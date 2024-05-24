<?php

namespace SV\AdsHelper\XF\Pub\Controller;

use SV\AdsHelper\XF\Entity\User;
use XF\Mvc\Entity\AbstractCollection;
use function is_callable;

/**
 * @extends \XF\Pub\Controller\Thread
 */
class Thread extends XFCP_Thread
{
    protected function getNewPostsReplyInternal(\XF\Entity\Thread $thread, AbstractCollection $posts, \XF\Entity\Post $firstUnshownPost = null)
    {
        /** @var User $visitor */
        $visitor = \XF::visitor();
        $adsInfo = $visitor->getAdsInfo();
        $adsInfo['showAdOnce'] = 1;

        return parent::getNewPostsReplyInternal($thread, $posts, $firstUnshownPost);
    }
}