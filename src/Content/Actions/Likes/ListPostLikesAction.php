<?php
namespace Module\Content\Actions\Likes;

use Module\Content;
use Module\Content\Actions\aAction;
use Module\Content\Interfaces\Model\Entity\iEntityLike;
use Module\Content\Interfaces\Model\Repo\iRepoLikes;
use Module\Content\Model\Entity\EntityLike;
use Module\Content\Model\Entity\MemberObject;
use Module\Foundation\Actions\IOC;
use Poirot\Application\Sapi\Server\Http\ListenerDispatch;
use Poirot\Http\HttpMessage\Request\Plugin\ParseRequestData;
use Poirot\Http\Interfaces\iHttpRequest;


class ListPostLikesAction
    extends aAction
{
    /** @var iRepoLikes */
    protected $repoLikes;


    /**
     * Construct
     *
     * @param iHttpRequest $request   @IoC /
     * @param iRepoLikes   $repoLikes @IoC /module/content/services/repository/Likes
     */
    function __construct(iHttpRequest $request, iRepoLikes $repoLikes)
    {
        parent::__construct($request);

        $this->repoLikes = $repoLikes;
    }

    /**
     * List Users who have liked a Post
     *
     * - Trigger Like.Post Event To Notify Subscribers
     *
     * @param string             $content_id
     *
     * @return array
     */
    function __invoke($content_id = null)
    {
        $q     = ParseRequestData::_($this->request)->parseQueryParams();
        $skip  = (isset($q['skip']))  ? (int) $q['skip']  : null;
        $limit = (isset($q['limit'])) ? (int) $q['limit'] : 30;


        # Retrieve Users Who Liked a Post
        $cursor = $this->repoLikes->findByItemIdentifierOfModel(
            $content_id
            , EntityLike::MODEL_POSTS
            , $skip
            , $limit + 1
        );

        $likes  = [];
        /** @var iEntityLike $like */
        foreach ($cursor as $like) {
            $member = new MemberObject;
            $member->setUid($like->getOwnerIdentifier());

            $likes[] = [
                'user' => $member,
            ];
        }


        # Build Response:

        // Check whether to display fetch more link in response?
        $linkMore = null;
        if (count($likes) > $limit) {
            $linkMore = IOC::url(null, array('content_id' => $content_id));
            $linkMore = (string) $linkMore->uri()->withQuery('skip='.($skip+$limit).'&limit='.$limit);
        }


        return [
            ListenerDispatch::RESULT_DISPATCH => [
                'count' => count($likes),
                'items' => $likes,
                '_link_more' => $linkMore,
                '_self' => [
                    'content_id' => $content_id,
                    'skip'       => $skip,
                    'limit'      => $limit,
                ],
            ],
        ];
    }

    // Helper Action Chains:

}