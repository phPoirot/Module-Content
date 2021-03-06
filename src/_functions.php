<?php
namespace Module\Content
{

    use Poirot\Std\Type\StdTravers;
    use Poirot\TenderBinClient;
    use Module\Content\Model\Entity\EntityPost;


    /**
     * Build Array Response From Given Entity Object
     *
     * @param EntityPost  $post
     * @param null|string $me       Current User Identifier
     *
     * @return array
     */
    function toArrayResponseFromPostEntity(EntityPost $post, $me = null)
    {
        # Build Likes Response:
        $likes = ($post->getLikes()) ? [
            'count'   => $post->getLikes()->getCount(),
            'members' => \Poirot\Std\cast( $post->getLikes()->getLatestMembers() )
                ->withWalk(function(&$value, $key) {
                    $value = ['user' => $value];
                })
        ] : null;

        if ($me && $likes) {
            // Check Whether Current User Has Liked Entity?
            $totalMembers = $post->getLikes()->getTotalMembers();
            if ( in_array((string)$me, $totalMembers) )
                $likes = ['by_me' => true] + $likes;
        }



        $post = StdTravers::of($post)->each(function ($val, &$key) use ($post, $likes) {
            switch ($key) {
                case '_id':
                case 'uid': $val = (string) $post->getUid();
                            $key = 'uid';
                    break;
                case 'owner_identifier': $val = [
                        'uid' => $post->getOwnerIdentifier(), ];
                     $key = 'user';
                    break;
                case 'likes': $val = $likes;
                    break;
                case 'location': $val = ($post->getLocation()) ? $post->getLocation() : null;
                    break;
                case 'date_time_created_mongo':
                case 'datetime_created': $val = [
                        'datetime'  => $post->getDateTimeCreated(),
                        'timestamp' => $post->getDateTimeCreated()->getTimestamp(),
                    ];
                    $key = 'datetime_created';
                    break;
                case '__pclass':
                    $val = null;
                    $key = null;

            }

            return $val;
        });


        $post = StdTravers::of($post)->toArray();
        return $post;
    }

    /**
     * Magic Touch Media Contents To Infinite Expiration
     *
     * @param \Traversable|array $content
     *
     * @throws \Exception
     */
    function assertMediaContents($content)
    {
        if (! $content instanceof \Traversable )
            // Do Nothing!!
            return;


        /** @var $cTender */
        $cTender = \Module\TenderBinClient\Services::ClientTender();

        foreach ($content as $c)
        {
            if ($c instanceof TenderBinClient\Model\aMediaObject) {
                try {
                    $cTender->touch( $c->getHash() );
                } catch (TenderBinClient\Exceptions\exResourceNotFound $e) {
                    // Specific Content Client Exception
                } catch (\Exception $e) {
                    // Other Errors Throw To Next Layer!
                    throw $e;
                }
            }

            elseif (is_array($c) || $c instanceof \Traversable)
                assertMediaContents($c);
        }
    }
}


namespace Module\Content\Lib
{
    use Module\Content\Exception\exUnknownContentType;
    use Module\Content\Interfaces\Model\Entity\iEntityPostContentObject;
    use Poirot\Std\Interfaces\Pact\ipFactory;


    class FactoryContentObject
        implements ipFactory
    {
        /**
         * Factory With Valuable Parameter
         *
         * @param mixed $contentName
         * @param null  $contentData
         *
         * @return iEntityPostContentObject
         * @throws \Exception
         */
        static function of($contentName, $contentData = null)
        {
            if (! \Module\Content\Services::ContentPlugins()->has($contentName) )
                throw new exUnknownContentType(sprintf(
                    'Content Of Type (%s) Has No Plugin Registered In System.', $contentName
                ));


            /** @var iEntityPostContentObject $contentObject */
            $contentObject = \Module\Content\Services::ContentPlugins()->fresh($contentName/*, [$contentData]*/);
            $contentObject->with($contentObject::parseWith($contentData));
            return $contentObject;
        }
    }
}
