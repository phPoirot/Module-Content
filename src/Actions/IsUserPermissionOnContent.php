<?php
namespace Module\Content\Actions;

use Module\Content\Model\Entity\EntityPost;
use Poirot\OAuth2Client\Interfaces\iAccessToken;


class IsUserPermissionOnContent
{
    /**
     * Check Whether Current User (By Token) Has Permission On Content?
     *
     * @param EntityPost|null   $post
     * @param iAccessToken|null $token
     *
     * @return bool
     */
    function __invoke(EntityPost $post = null, $token = null)
    {
        if (! $token)
            // There is no token given ...
            return false;

        $postOwner  = (string) $post->getOwnerIdentifier();
        $tokenOwner = (string) $token->getOwnerIdentifier();

        return $tokenOwner == $postOwner;
    }
}
