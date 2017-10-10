<?php
namespace Module\Content\Actions;

use Module\Content\Interfaces\Model\Repo\iRepoPosts;
use Module\Content\Model\Entity\EntityPost;


class ListPostsOfUser
{
    protected $repoPosts;


    /**
     * Construct
     *
     * @param iRepoPosts   $repoPosts @IoC /module/content/services/repository/Posts
     */
    function __construct(iRepoPosts $repoPosts)
    {
        $this->repoPosts = $repoPosts;
    }


    /**
     * Retrieve Displayable Posts Of a User
     *
     * @param string   $owner_identifier Owner Identifier
     * @param array    $expression       Filter expression
     * @param int|null $offset
     * @param int|null $limit
     *
     * @return array
     */
    function __invoke($me = null, $owner_identifier = null, $expression = null, $offset = null, $limit = 30)
    {
        if (!$expression)
            $expression = \Module\MongoDriver\parseExpressionFromString('stat=publish|draft&stat_share=public|private');

        $persistPosts = $this->repoPosts->findAllMatchWithOwnerId(
            $owner_identifier
            , $expression
            , $offset
            , $limit
        );

        $profiles = \Module\Profile\Actions::RetrieveProfiles([$owner_identifier]);

        /** @var EntityPost $post */
        $posts = \Poirot\Std\cast($persistPosts)->toArray(function (&$post) use ($me, $profiles) {
            $post = \Module\Content\toArrayResponseFromPostEntity($post, $me, $profiles);
        });

        return $posts;
    }
}
