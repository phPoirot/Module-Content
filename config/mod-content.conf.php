<?php
use Module\Content\Events\EventsHeapOfContent;
use Module\Content\Events\RetrieveContent\OnRetrieveContentEmbedProfile;

return [

    \Module\Content\Module::CONF => [

        ## Events
        #
        \Module\Content\Actions\aAction::CONF => [
            // Events Section Of Events Builder
            /** @see \Poirot\Events\Event\BuildEvent */

            EventsHeapOfContent::RETRIEVE_CONTENT => [
                'listeners' => [
                    ['priority' => 1000,  'listener' => OnRetrieveContentEmbedProfile::class ],
                ],
            ],

            EventsHeapOfContent::RETRIEVE_CONTENT_RESULT => [
                'listeners' => [
                    ['priority' => 1000,  'listener' => function($result, $me) {
                        // Implement this
                        /** @var \Module\Content\Model\Entity\EntityPost $entityPost */
                    }],
                    ['priority' => 10000, 'listener' => \Module\Content\Events\OnThatConvertToArray::class ],
                    ['priority' => 1000,  'listener' => \Module\Content\Events\OnThatEmbedProfiles::class ],
                    ['priority' => 1000,  'listener' => \Module\Content\Events\OnThatEmbedMediaLinks::class ],
                ],
            ],
            
        ],
    ],

    # Mongo Driver:

    Module\MongoDriver\Module::CONF_KEY =>
    [
        \Module\MongoDriver\Services\aServiceRepository::CONF_REPOSITORIES =>
        [
            \Module\Content\Model\Driver\Mongo\PostsRepoService::class => [
                'collection' => [
                    // query on which collection
                    'name' => 'content.posts',
                    // which client to connect and query with
                    'client' => 'master',
                    // ensure indexes
                    'indexes' => [
                        ['key' => ['_id' => 1]],
                        ['key' => ['owner_identifier' => 1]],
                        ['key' => ['_id' => -1, 'stat' => 1]],
                        ['key' => ['_id' => -1, 'stat' => 1, 'stat_share' => 1]],
                        ['key' => ['_id' => -1, 'owner_identifier' => 1, 'stat' => 1, 'stat_share' => 1]],
                    ],],],

            \Module\Content\Model\Driver\Mongo\LikesRepoService::class => [
                'collection' => [
                    // query on which collection
                    'name' => 'content.likes',
                    // which client to connect and query with
                    'client' => 'master',
                    // ensure indexes
                    'indexes' => [
                        ['key' => ['_id'  => 1]],
                        ['key' => ['_uid' => 1]],
                        ['key' => ['item_identifier' => 1, 'model' => 1]],
                        ['key' => ['owner_identifier' => 1, 'model' => 1]],
                    ],],],

            \Module\Content\Model\Driver\Mongo\CommentsRepoService::class => [
                'collection' => [
                    // query on which collection
                    'name' => 'content.comments',
                    // which client to connect and query with
                    'client' => 'master',
                    // ensure indexes
                    'indexes' => [
                        ['key' => ['_id'  => 1]],
                        ['key' => ['item_identifier' => 1, 'model' => 1]],
                        ['key' => ['owner_identifier' => 1, 'model' => 1]],
                    ],],],
        ],
    ],
];
