<?php
namespace Module\Content\Actions;

use Module\Content;
use Module\Content\Events\EventsHeapOfContent;
use Module\Content\Interfaces\Model\Repo\iRepoPosts;
use Module\Content\Model\Entity\EntityPost;


class ListPostsOfUser
    extends aAction
{
    /** @var iRepoPosts */
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

        ## Event
        #
        $posts = $this->event()
            ->trigger(EventsHeapOfContent::LIST_POSTS_RESULTSET, [
                /** @see Content\Events\DataCollector */
                'me' => $me, 'posts' => $persistPosts
            ])
            ->then(function ($collector) {
                /** @var Content\Events\DataCollector $collector */
                return $collector->getPosts();
            });


        return $posts;
    }
}
