<?php
namespace Module\Content\Actions\Comments;

use Module\Content;
use Module\Content\Actions\aAction;
use Module\Content\Interfaces\Model\Repo\iRepoComments;
use Module\HttpFoundation\Events\Listener\ListenerDispatch;
use Poirot\Http\HttpMessage\Request\Plugin\ParseRequestData;
use Poirot\Http\Interfaces\iHttpRequest;
use Poirot\OAuth2Client\Interfaces\iAccessToken;


class ListCommentsOfPostAction
    extends aAction
{
    /** @var Content\Model\Driver\Mongo\CommentsRepo */
    protected $repoComments;


    /**
     * Construct
     *
     * @param iHttpRequest  $httpRequest  @IoC /HttpRequest
     * @param iRepoComments $repoComments @IoC /module/content/services/repository/Comments
     */
    function __construct(iHttpRequest $httpRequest, iRepoComments $repoComments)
    {
        parent::__construct($httpRequest);

        $this->repoComments = $repoComments;
    }

    /**
     * List Recent Comments on a Post
     *
     * @param string       $content_id
     * @param iAccessToken $token
     *
     * @return array
     */
    function __invoke($content_id = null, iAccessToken $token = null)
    {
        $q      = ParseRequestData::_($this->request)->parseQueryParams();
        $offset = (isset($q['offset'])) ? (int) $q['offset'] : null;
        $limit  = (isset($q['limit']))  ? (int) $q['limit']  : 30;


        # Retrieve Comments Of Given Post ID
        $persistComments = $this->repoComments->findAll(
            \Module\MongoDriver\parseExpressionFromArray([
                // We Consider All Item Liked Has _id from Mongo Collection
                'item_identifier' => $this->repoComments->attainNextIdentifier($content_id),
                'model'           => Content\Model\Entity\EntityComment::MODEL_POSTS,
                'stat'            => 'publish', // all comments that has publish stat
            ])
            , $offset
            , $limit + 1
        );


        $comments  = [];
        /** @var Content\Model\Entity\EntityComment $comment */
        foreach ($persistComments as $comment)
        {
/*            if ($comment->getStat() == $comment::STAT_IGNORE)
                // Ignored Comment Displayed For Owner
                if ($comment->getOwnerIdentifier() !== $token->getOwnerIdentifier())
                    // Don't Display this comment
                    continue;*/

            $cid = (string) $comment->getUid();
            $comments[] = [
                'comment' => [
                    'uid'     => $cid,
                    'content' => $comment->getContent(),
                    'user' => new Content\Model\Entity\MemberObject([
                        'uid' => $comment->getOwnerIdentifier(),
                    ])
                ]
            ];
        }


        # Build Response:

        // Check whether to display fetch more link in response?
        $linkMore = null;
        if (count($comments) > $limit) {
            array_pop($comments);                       // skip augmented content to determine has more?
            $nextOffset = $comment[count($comments)-1]; // retrieve the next from this offset (less than this)
            $linkMore   = \Module\HttpFoundation\Actions::url(null, array('content_id' => $content_id));
            $linkMore   = (string) $linkMore->uri()->withQuery('offset='.($nextOffset['comment']['uid']).'&limit='.$limit);
        }

        return [
            ListenerDispatch::RESULT_DISPATCH => [
                'count' => count($comments),
                'items' => array_values($comments),
                '_link_more' => $linkMore,
                '_self' => [
                    'content_id' => $content_id,
                    'skip'       => $offset,
                    'limit'      => $limit,
                ],
            ],
        ];
    }

    // Helper Action Chains:

}
