<?php
namespace Module\Content\Actions\Posts;

use Module\Content;
use Module\Content\Actions\aAction;
use Module\Content\Interfaces\Model\Repo\iRepoPosts;
use Module\HttpFoundation\Events\Listener\ListenerDispatch;
use Poirot\Http\HttpMessage\Request\Plugin\ParseRequestData;
use Poirot\Http\Interfaces\iHttpRequest;
use Poirot\OAuth2Client\Interfaces\iAccessToken;


class ListPostsOfMeAction
    extends aAction
{
    /** @var iRepoPosts */
    protected $repoPosts;


    /**
     *
     * @param iHttpRequest $httpRequest @IoC /HttpRequest
     * @param iRepoPosts   $repoPosts   @IoC /module/content/services/repository/Posts
     */
    function __construct(iHttpRequest $httpRequest, iRepoPosts $repoPosts)
    {
        parent::__construct($httpRequest);

        $this->repoPosts = $repoPosts;
    }


    /**
     * List and Filter Content Posts Of Current User
     *
     * Search Terms:
     *   Retrieve Specific Post Type
     *   ?$post=content_type:general
     *
     *   Post With Stats
     *   ?$post=stat:publish|share:private
     *
     * - post with draft and private posts also can be retrieved for owner
     * - posts with share:locked is disabled and will not showing in list.
     *
     * @param iAccessToken|null $token
     *
     * @return array
     */
    function __invoke($token = null)
    {
        # Assert Token
        $this->assertTokenByOwnerAndScope($token);


        # Parse Request Query params
        $q      = ParseRequestData::_($this->request)->parseQueryParams();
        // offset is Mongo ObjectID "58e107fa6c6b7a00136318e3"
        $offset = (isset($q['offset'])) ? $q['offset']       : null;
        $limit  = (isset($q['limit']))  ? (int) $q['limit']  : 30;


//        $expression   = \Module\MongoDriver\parseExpressionFromArray($q, ['stat'], 'allow');
        $me = ($token) ? $token->getOwnerIdentifier() : null;
        $posts = \Module\Content\Actions::ListPostsOfUser(
            $me
            , $token->getOwnerIdentifier()
            , \Module\MongoDriver\parseExpressionFromString('stat=publish|draft')
            , $offset
            , $limit + 1
        );


        # Build Response:

        // Check whether to display fetch more link in response?
        $linkMore = null;
        if (count($posts) > $limit) {
            array_pop($posts);                     // skip augmented content to determine has more?
            $nextOffset = $posts[count($posts)-1]; // retrieve the next from this offset (less than this)
            $linkMore   = \Module\HttpFoundation\Actions::url(null);
            $linkMore   = (string) $linkMore->uri()->withQuery('offset='.($nextOffset['uid']).'&limit='.$limit);
        }

        return [
            ListenerDispatch::RESULT_DISPATCH => [
                'count' => count($posts),
                'items' => $posts,
                '_link_more' => $linkMore,
                '_self' => [
                    'offset' => $offset,
                    'limit'  => $limit,
                ],
            ],
        ];
    }
}
