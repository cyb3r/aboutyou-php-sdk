<?php
namespace Collins\ShopApi\Model;

use Collins\ShopApi\Exception\InvalidParameterException;
use Collins\ShopApi\Factory\ModelFactoryInterface;
use Collins\ShopApi\Model\Basket\BasketItemInterface;
use Collins\ShopApi\Model\Basket\BasketVariantItem;
use Collins\ShopApi\Model\Basket\BasketSet;
use Collins\ShopApi\Model\Basket\BasketItem;

/**
 *
 */
class Basket
{
    /** @var object */
    protected $jsonObject = null;

    /** @var ModelFactoryInterface */
    protected $factory;

    /** @var AbstractBasketItem[] */
    private $items = [];

    private $errors = [];

    /** @var integer */
    protected $uniqueVariantCount;

    /**
     * Constructor.
     *
     * @param object $jsonObject The basket data.
     */
    public function __construct($jsonObject, ModelFactoryInterface $factory)
    {
        $this->jsonObject = $jsonObject;
        $this->factory    = $factory;
    }

    /**
     * Get the total price.
     *
     * @return integer
     */
    public function getTotalPrice()
    {
        return $this->jsonObject->total_price;
    }

    /**
     * Get the total net.
     *
     * @return integer
     */
    public function getTotalNet()
    {
        return $this->jsonObject->total_net;
    }

    /**
     * Get the total vat.
     *
     * @return integer
     */
    public function getTotalVat()
    {
        return $this->jsonObject->total_vat;
    }

    /**
     * Get the total amount of all items.
     *
     * @return integer
     */
    public function getTotalAmount()
    {
        if (!$this->items) {
            $this->parseItems();
        }

        return count($this->items);
    }

    /**
     * Get the number of variants.
     *
     * @return integer
     */
    public function getTotalVariants()
    {
        if (!$this->items) {
            $this->parseItems();
        }

        return $this->uniqueVariantCount;
    }

    public function hasErrors()
    {
        if (!$this->items) {
            $this->parseItems();
        }

        return count($this->errors) > 0;
    }

    /**
     * Get all basket items.
     *
     * @return BasketItem[]|BasketSet[]
     */
    public function getItems()
    {
        if (!$this->items) {
            $this->parseItems();
        }

        return $this->items;
    }

    /**
     * build order line for update query
     * @return array
     */
    public function getOrderLinesArray()
    {
        $orderLines = [];

        foreach ($this->deletedItems as $itemId) {
            $orderLines[] = ['delete' => $itemId];
        }

        foreach ($this->updatedItems as $item) {
            $orderLines[] = $item;
        }

        return $orderLines;
    }

    protected function parseItems()
    {
        $factory = $this->factory;

        $products = [];
        foreach ($this->jsonObject->products as $productId => $jsonProduct) {
            $products[$productId] = $factory->createProduct($jsonProduct);
        }
        unset($this->jsonObject->products);

        $vids = [];
        foreach ($this->jsonObject->order_lines as $index => $jsonItem) {
            if (isset($jsonItem->set_items)) {
                $item = $factory->createBasketSet($jsonItem, $products);
            } else {
                $item = $factory->createBasketItem($jsonItem, $products);
                $vids[] = $jsonItem->variant_id;
            }

            if ($item->hasErrors()) {
                $this->errors[$index] = $item;
            } else {
                $this->items[$index] = $item;
            }
        }
        unset($this->jsonObject->order_lines);

        array_unique($vids);
        $this->uniqueVariantCount = count($vids);
    }

    /*
     * Methods to manipulate basket
     *
     * this api is unstable method names and signatures may be changed in the future
     */

    /** @var array */
    protected $deletedItems = [];
    /** @var array */
    protected $updatedItems = [];

    /**
     * @param $itemId
     */
    public function deleteItem($itemId)
    {
        $this->deletedItems[$itemId] = $itemId;

        return $this;
    }

    /**
     * @param $itemId
     * @param $variantId
     * @param array $additionalData
     *
     * @return $this
     */
    public function updateItem($itemId, $variantId, array $additionalData = null)
    {
        $this->checkAdditionData($additionalData);

        $this->updatedItems[$itemId] = [
            'id' => $itemId,
            'variant_id' => $variantId,
            'additional_data' => $additionalData
        ];

        return $this;
    }

    /**
     * Update an basket item set, for example:
     *  $basket->updateItemSet(
     *      'identifier4',
     *      [
     *          [12312121],
     *          [66666, ['description' => 'engravingssens', 'internal_infos' => ['stuff']]]
     *      ],
     *      ['description' => 'Wudnerschön und s 2o']
     *  );
     *
     * @param $itemId
     * @param $subItems
     * @param array $additionalData
     *
     * @return $this
     */
    public function updateItemSet($itemId, $subItems, array $additionalData = null)
    {
        $this->checkAdditionData($additionalData);

        $itemSet = [];
        foreach ($subItems as $subItem) {
            $item = [
                'variant_id' => $subItem[0]
            ];
            if (isset($subItem[1])) {
                $this->checkAdditionData($subItem[1]);
                $item['additional_data'] = $subItem[1];
            }
            $itemSet[] = $item;
        }

        $this->updatedItems[$itemId] = [
            'id' => $itemId,
            'additional_data' => $additionalData,
            'set_items' => $itemSet,
        ];

        return $this;
    }

    protected function checkAdditionData(array $additionalData = null)
    {
        if ($additionalData && !isset($additionalData['description'])) {
            throw new InvalidParameterException('description is required in additional data');
        }

        if (isset($additionalData['internal_infos']) && !is_array($additionalData['internal_infos'])) {
            throw new InvalidParameterException('internal_infos must be an array');
        }
    }
}
