<?php
namespace Module\Content\Actions\Comments;

use Module\Content;
use Module\Content\Actions\aAction;
use Module\Content\Interfaces\Model\Repo\iRepoComments;
use Module\Content\Interfaces\Model\Repo\iRepoLikes;
use Module\Content\Interfaces\Model\Repo\iRepoPosts;
use Module\Content\Model\Entity\EntityLike;
use Module\Content\Model\Entity\MemberObject;
use Poirot\Application\Sapi\Server\Http\ListenerDispatch;
use Poirot\Http\HttpMessage\Request\Plugin\ParseRequestData;
use Poirot\Http\Interfaces\iHttpRequest;
use Poirot\OAuth2\Interfaces\Server\Repository\iEntityAccessToken;


class AddCommentOnPostAction
    extends aAction
{
    /** @var iRepoLikes */
    protected $repoComments;
    /** @var iRepoPosts */
    protected $repoPosts;


    /**
     * Construct
     *
     * @param iHttpRequest  $request      @IoC /
     * @param iRepoComments $repoComments @IoC /module/content/services/repository/Comments
     * @param iRepoPosts    $repoPosts    @IoC /module/content/services/repository/Posts
     */
    function __construct(iHttpRequest $request, iRepoComments $repoComments, iRepoPosts $repoPosts)
    {
        parent::__construct($request);

        $this->repoComments = $repoComments;
        $this->repoPosts = $repoPosts;
    }

    /**
     * Set Like On Post By Authenticated User
     *
     * - Assert Validate Token That Has Bind To ResourceOwner,
     *   Check Scopes
     *
     * - Trigger Like.Post Event To Notify Subscribers
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


        $_posts = ParseRequestData::_($this->request)->parseBody();
        if (!isset($_posts['comment']) || empty($_posts['comment']))
            throw new \InvalidArgumentException('Comment is empty.');


        # Add Comment To Given Post Content With Id
        $comment = new Content\Model\Entity\EntityComment;
        $comment
            ->setItemIdentifier($content_id)
            ->setOwnerIdentifier( $token->getOwnerIdentifier() )
            ->setContent( $_posts['comment'] )
            ->setModel( Content\Model\Entity\EntityComment::MODEL_POSTS )
        ;

        # Persist Comment
        $comment = $this->repoComments->insert($comment);


        return [
            ListenerDispatch::RESULT_DISPATCH => [
                'comment' => [
                    'uid'     => (string) $comment->getUid(),
                    'content' => $comment->getContent(),
                    'user'    => [
                        'uid' => $comment->getOwnerIdentifier(),
                    ],
                ],
                '_self'   => [
                    'content_id' => $content_id,
                ],
            ],
        ];
    }

    // Helper Action Chains:

}
