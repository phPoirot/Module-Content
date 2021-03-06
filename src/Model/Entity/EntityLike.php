<?php
namespace Module\Content\Model\Entity;

use Module\Content\Interfaces\Model\Entity\iEntityLike;
use Poirot\Std\Struct\DataOptionsOpen;


class EntityLike
    extends DataOptionsOpen
    implements iEntityLike
{
    const MODEL_POSTS = 'posts';


    protected $identifier;
    protected $itemIdentifier;
    protected $ownerIdentifier;
    protected $model;
    /** @var \DateTime */
    protected $datetimeCreated;


    /**
     * Set Identifier
     *
     * @param mixed $identifier
     */
    function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
    }

    /**
     * Unique Identifier
     *
     * @return mixed
     */
    function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Set Owner Identifier
     *
     * @param mixed $identifier
     *
     * @return $this
     */
    function setOwnerIdentifier($identifier)
    {
        $this->ownerIdentifier = $identifier;
        return $this;
    }

    /**
     * Get Owner Who Like Something
     *
     * @return mixed
     */
    function getOwnerIdentifier()
    {
        return $this->ownerIdentifier;
    }

    /**
     * Set Item Identifier
     *
     * @param mixed $identifier
     *
     * @return $this
     */
    function setItemIdentifier($identifier)
    {
        $this->itemIdentifier = $identifier;
        return $this;
    }

    /**
     * Item Which You Have Liked From Model X
     *
     * @return mixed
     */
    function getItemIdentifier()
    {
        return $this->itemIdentifier;
    }

    /**
     * Set Model Namespace
     *
     * @param string $model
     *
     * @return $this
     */
    function setModel($model)
    {
        $this->model = (string) $model;
        return $this;
    }

    /**
     * Model Namespace
     * allow to like multiple media items, i.e. wall posts and videos
     *
     * @return string
     */
    function getModel()
    {
        return $this->model;
    }

    /**
     * Set Created Timestamp
     *
     * @param \DateTime|null $dateTime
     *
     * @return $this
     */
    function setDateTimeCreated($dateTime)
    {
        if ( !($dateTime === null || $dateTime instanceof \DateTime) )
            throw new \InvalidArgumentException(sprintf(
                'Datetime must instance of \Datetime or null; given: (%s).'
                , \Poirot\Std\flatten($dateTime)
            ));

        $this->datetimeCreated = $dateTime;
        return $this;
    }

    /**
     * Get Date Time Created
     *
     * @return \DateTime
     */
    function getDateTimeCreated()
    {
        if (!$this->datetimeCreated)
            $this->setDateTimeCreated(new \DateTime);

        return $this->datetimeCreated;
    }
}
