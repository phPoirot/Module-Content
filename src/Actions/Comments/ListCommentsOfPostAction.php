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
    /** @var iRepoComments */
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
    function __invoke($content_id = null, $token = null)
    {
        $q      = ParseRequestData::_($this->request)->parseQueryParams();
        $offset = (isset($q['offset'])) ? (string) $q['offset'] : null;
        $limit  = (isset($q['limit']))  ? (int)    $q['limit']  : 30;


        ## Retrieve Comments Of Given Post ID
        #
        $persistComments = $this->repoComments->findAllCommentsFor(
            Content\Model\Entity\EntityComment::MODEL_POSTS
            , $content_id
            , null // retrieve all users comment
            , $offset
            , $limit + 1
        );


        ## Build Response
        #
        $userIds   = [];
        $comments  = [];
        /** @var Content\Model\Entity\EntityComment $cm */
        foreach ($persistComments as $cm)
        {
            $commentOwnerId = (string)$cm->getOwnerIdentifier();

            $userIds[$commentOwnerId] = true;

            $cid = (string) $cm->getUid();

            $comments[] = [
                'uid'     => $cid,
                'content' => $cm->getContent(),
                'user' => [
                    'uid' => $commentOwnerId,
                ],
            ];
        }

        // Embed profile to response
        //

        $profiles = \Module\Profile\Actions::RetrieveProfiles(array_keys($userIds));

        foreach ($comments as $i => $cm) {
            $cmOwner = $cm['user']['uid'];
            if ( isset($profiles[$cmOwner]) )
                $cm['user'] = $profiles[$cmOwner];

            $comments[$i] = $cm;
        }

        $comments = array_values($comments);

        ## Build Response:
        #
        // Check whether to display fetch more link in response?
        $linkMore = null;
        if (count($comments) > $limit) {
            array_pop($comments);// skip augmented content to determine has more?
            $nextOffset = $comments[count($comments)-1]; // retrieve the next from this offset (less than this)
            $linkMore   = \Module\HttpFoundation\Actions::url(null);
            $linkMore   = (string) $linkMore->uri()->withQuery('offset='.($nextOffset['uid']).'&limit='.$limit);
        }


        return [
            ListenerDispatch::RESULT_DISPATCH => [
                'count' => count($comments),
                'items' => $comments,
                '_link_more' => $linkMore,
                '_self' => [
                    'content_id' => $content_id,
                    'offset'     => $offset,
                    'limit'      => $limit,
                ],
            ],
        ];
    }
}
