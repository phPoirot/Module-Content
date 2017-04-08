<?php
namespace Module\Content\Actions\Likes;

use Module\Content;
use Module\Content\Actions\aAction;
use Module\Content\Interfaces\Model\Repo\iRepoLikes;
use Module\Content\Interfaces\Model\Repo\iRepoPosts;
use Module\Content\Model\Entity\MemberObject;
use Poirot\Application\Sapi\Server\Http\ListenerDispatch;
use Poirot\Http\Interfaces\iHttpRequest;
use Poirot\OAuth2\Interfaces\Server\Repository\iEntityAccessToken;


class UnLikePostAction
    extends aAction
{
    /** @var iRepoLikes */
    protected $repoLikes;
    /** @var iRepoPosts */
    protected $repoPosts;


    /**
     * Construct
     *
     * @param iHttpRequest $request   @IoC /
     * @param iRepoLikes   $repoLikes @IoC /module/content/services/repository/Likes
     * @param iRepoPosts   $repoPosts @IoC /module/content/services/repository/Posts
     */
    function __construct(iHttpRequest $request, iRepoLikes $repoLikes, iRepoPosts $repoPosts)
    {
        parent::__construct($request);

        $this->repoLikes = $repoLikes;
        $this->repoPosts = $repoPosts;
    }

    /**
     * Remove Like On Post By Authenticated User
     *
     * - Assert Validate Token That Has Bind To ResourceOwner,
     *   Check Scopes
     *
     * - Trigger UnLike.Post Event To Notify Subscribers
     *
     * @param string             $content_id
     * @param iEntityAccessToken $token
     *
     * @return array
     */
    function __invoke($content_id = null, iEntityAccessToken $token = null)
    {
        # Assert Token
        $this->assertTokenByOwnerAndScope($token);


        # Persist Like Entity
        $like = new Content\Model\Entity\EntityLike;
        $like
            ->setOwnerIdentifier($token->getOwnerIdentifier())
            ->setItemIdentifier($content_id)
            ->setModel(Content\Model\Entity\EntityLike::MODEL_POSTS)
        ;


        $objMember = new MemberObject;
        $objMember->setUid($token->getOwnerIdentifier());

        if ( $this->repoLikes->remove($like) ) {
            # Remove Embed Like From Post Document
            $likes = $this->repoPosts->removeLikeEntry($content_id, $objMember);
        }


        # Build Response:

        if ( isset($likes) ) {
            $r = [
                'stat'  => 'del-like',
                'count' => $likes->getCount(),
            ];
        } else {
            $r = [
                'stat' => 'none',
            ];
        }

        return [
            ListenerDispatch::RESULT_DISPATCH => $r + [
                '$user'  => $objMember,
                '_self' => [
                    'content_id' => $content_id
                ],
            ],
        ];
    }

    // Helper Action Chains:

}