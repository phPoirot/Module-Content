<?php
namespace Module\Content\Events\RetrieveContentResult;

use Module\Content\Actions\UploadMediaAction;
use Module\Content\Events\EventsHeapOfContent;
use Module\Content\Model\Entity\EntityPost;
use Poirot\Http\HttpRequest;
use Poirot\TenderBinClient\Model\aMediaObject;
use Poirot\TenderBinClient\Model\MediaObjectTenderBin;
use Poirot\TenderBinClient\Model\MediaObjectTenderBinVersions;


class OnThatEmbedMediaLinks
{
    const EVENT_PRIORITY = 1500;


    /**
     * Embed Media Links To Content
     *
     * @param \Traversable $posts
     * @param mixed        $me
     *
     * @return array
     */
    function __invoke($posts = null, $me = null, $entity_post = null)
    {
        if ($entity_post !== null)
            // Attached To retrieve_post event
            /** @see EventsHeapOfContent */
            return [
                'entity_post' => $this->_embedMediaLinks($entity_post),
            ];


        // retrieve_post_resultset

        /** @var EntityPost $post */
        foreach ($posts as $post)
            $this->_embedMediaLinks($post);

        return [
            'posts' => $posts,
        ];
    }


    // ..

    /**
     * @param EntityPost $post
     *
     * @return EntityPost
     */
    private function _embedMediaLinks($post)
    {
        $content = $post->getContent();

        if ( method_exists($content, 'getMedias') ) {
            $postMedias = $content->getMedias();

            /** @var aMediaObject $media */
            $medias = [];
            foreach ($postMedias as $media) {
                if ( \Poirot\Std\isMimeMatchInList(['image/*'], $media->getContentType()) )
                    $media = $this->_mediaVersion($media);

                $medias[] = $media;
            }


            $content->setMedias($medias);
        }

        return $post;
    }

    /** @see UploadMediaAction */
    private function _mediaVersion(aMediaObject $media)
    {
        if ( ! $media instanceof MediaObjectTenderBin )
            // We Not Embed Versions To Unknown Media Type!
            return $media;


        if ($media instanceof MediaObjectTenderBinVersions)
            return $media;


        $availableVersions = $media->getVersions();

        if ( empty($availableVersions) || !$this->_isSupportedForMediaMeta() )
            ## Embed Default Versions Into Response
            #
            /** @var MediaObjectTenderBin $media */
            return new MediaObjectTenderBinVersions($media, [
                'thumb', 'low_thumb', 'small', 'low_small', 'large', 'low_large', 'origin'
            ]);

        return $media;
    }


    // ..

    /**
     * Check whether request has contains supported header contracted version
     *
     * - Version Support Is Implementation Of Backward Compatibility for API Clients!
     *
     */
    function _isSupportedForMediaMeta()
    {
        ## Check Request With Specified Header; Considering Client Version
        #
        /** @var HttpRequest $request */
        $request = \IOC::GetIoC()->get('/HttpRequest');

        $v = \Poirot\Http\Header\renderHeaderValue($request, 'X-Apanaj-Ver');
        return version_compare($v, '2.3.0', '>=');
    }
}
