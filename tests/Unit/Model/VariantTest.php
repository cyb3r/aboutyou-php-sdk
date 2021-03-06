<?php
/**
 * @auther nils.droege@aboutyou.de
 * (c) ABOUT YOU GmbH
 */

namespace AboutYou\SDK\Test\Unit\Model;

use AboutYou\SDK\Model\Facet;
use AboutYou\SDK\Model\FacetGroupSet;
use AboutYou\SDK\Model\FacetManager\StaticFacetManager;
use AboutYou\SDK\Model\Product;
use AboutYou\SDK\Model\Variant;

class VariantTest extends AbstractModelTest
{
    public function testFromJson()
    {
        $jsonObject = $this->getJsonObject('variant.json');

        $variant = Variant::createFromJson($jsonObject, $this->getModelFactory(), $this->getProduct());

        $this->assertEquals(5145543, $variant->getId());
        $this->assertEquals('ean1', $variant->getEan());
        $this->assertFalse($variant->isDefault());
        $this->assertEquals(3990, $variant->getPrice());
        $this->assertEquals(0, $variant->getOldPrice());
        $this->assertEquals(3995, $variant->getRetailPrice());
        $this->assertEquals(5, $variant->getQuantity());
        $this->assertEquals(new \DateTime('2014-02-14 18:09:38'), $variant->getFirstActiveDate());
        $this->assertEquals(new \DateTime('2014-01-10 10:10:00'), $variant->getFirstSaleDate());
        $this->assertEquals(new \DateTime('2014-01-11 10:11:00'), $variant->getCreatedDate());
        $this->assertEquals(new \DateTime('2014-01-12 10:12:00'), $variant->getUpdatedDate());

        return $variant;
    }

    public function testFromJson2()
    {
        $jsonObject = $this->getJsonObject('variant2.json');

        $variant = Variant::createFromJson($jsonObject, $this->getModelFactory(), $this->getProduct());
        $this->assertNull($variant->getFirstActiveDate());
        $this->assertNull($variant->getFirstSaleDate());
        $this->assertNull($variant->getCreatedDate());
        $this->assertNull($variant->getUpdatedDate());
    }

    /**
     * @depends testFromJson
     */
    public function testVariantWithAttributes(Variant $variant)
    {
        $facetGroupSet = $variant->getFacetGroupSet();
        $this->assertInstanceOf('\\AboutYou\\SDK\\Model\\FacetGroupSet', $facetGroupSet);
        $this->assertCount(8, $facetGroupSet->getLazyGroups());
    }

    public function testFromJsonAdditionalInfo()
    {
        $jsonObject = json_decode('{"additional_info":null}');
        $variant = Variant::createFromJson($jsonObject, $this->getModelFactory(), $this->getProduct());
        $this->assertEquals(null, $variant->getAdditionalInfo());

        $expected = json_decode('{"some":"data"}');
        $jsonObject = json_decode('{"additional_info":{"some":"data"}}');
        $variant = Variant::createFromJson($jsonObject, $this->getModelFactory(), $this->getProduct());
        $this->assertEquals($expected, $variant->getAdditionalInfo());
        $this->assertEquals("data", $variant->getAdditionalInfo()->some);

        return $variant;
    }

    /**
     * @depends testFromJson
     */
    public function testGetSize(Variant $variant)
    {
        $facetManager = $this->getFacetManager('facets-all.json');
        FacetGroupSet::setFacetManager($facetManager);

        $size = $variant->getSize();
        $this->assertInstanceOf('\\AboutYou\\SDK\\Model\\FacetGroup', $size);
        $this->assertEquals('XS', $size->getFacetNames());
    }

    /**
     * @depends testFromJson
     */
    public function testGetSeasonCode(Variant $variant)
    {
        $facetManager = $this->getFacetManager('facets-all.json');
        FacetGroupSet::setFacetManager($facetManager);

        $facetGroup = $variant->getSeasonCode();
        $this->assertInstanceOf('\\AboutYou\\SDK\\Model\\FacetGroup', $facetGroup);
        $this->assertEquals('season', $facetGroup->getName());
        $this->assertEquals('HW 14', $facetGroup->getFacetNames());

        $facet = $facetManager->getFacet(289, 4084);
        $this->assertInstanceOf('\\AboutYou\\SDK\\Model\\Facet', $facet);
        $this->assertEquals('hw14', $facet->getValue());
        $this->assertEquals('HW 14', $facet->getName());
        $this->assertEquals('season', $facet->getGroupName());
    }

    /**
     * @depends testFromJson
     */
    public function testGetGender(Variant $variant)
    {
        $facetManager = $this->getFacetManager('facets-all.json');
        FacetGroupSet::setFacetManager($facetManager);

        $facetGroup = $variant->getGender();
        $this->assertInstanceOf('\\AboutYou\\SDK\\Model\\FacetGroup', $facetGroup);
        $this->assertEquals('genderage', $facetGroup->getName());
        $this->assertEquals('Unisex', $facetGroup->getFacetNames());

        $facet = $facetManager->getFacet(3, 64);
        $this->assertInstanceOf('\\AboutYou\\SDK\\Model\\Facet', $facet);
        $this->assertEquals('unisex', $facet->getValue());
        $this->assertEquals('Unisex', $facet->getName());
        $this->assertEquals('genderage', $facet->getGroupName());
    }

    public function testGetMaterials()
    {
        $jsonObject = $this->getJsonObject('variant.json');
        $variant = Variant::createFromJson($jsonObject, $this->getModelFactory(), $this->getProduct());

        $materials = $variant->getMaterials();
        $this->assertNull($materials);


        $jsonObject = $this->getJsonObject('variant-with-materials.json');
        $variant = Variant::createFromJson($jsonObject, $this->getModelFactory(), $this->getProduct());
        $materials = $variant->getMaterials();
        $this->assertInternalType('array', $materials);
        $this->assertCount(4, $materials);

        foreach ($materials as $index => $material) {
            $this->assertInstanceOf('\\AboutYou\\SDK\\Model\\Material', $material);
            if ($index === 3) {
                $this->assertNull($material->getName());
            } else {
                $this->assertInternalType('string', $material->getName());
            }
            $compositions = $material->getCompositions();
            $this->assertInternalType('array', $compositions);
            $this->assertContainsOnlyInstancesOf('\\AboutYou\\SDK\\Model\\Composition', $compositions);
        }

        $this->assertEquals('Obermaterial', $materials[0]->getName());
        $this->assertEquals('shell', $materials[0]->getType());
        $this->assertEquals('Futter 1', $materials[1]->getName());
        $this->assertEquals('lining', $materials[1]->getType());
        $this->assertEquals('Füllung', $materials[2]->getName());
        $this->assertNull($materials[2]->getType());

        $compositions = $materials[0]->getCompositions();
        $this->assertCount(2, $compositions);
        $this->assertEquals('baumwolle', $compositions[0]->getName());
        $this->assertEquals(35.0, $compositions[0]->getPercentage());
        $this->assertInternalType('float', $compositions[0]->getPercentage());
        $this->assertEquals('polyester', $compositions[1]->getName());
        $this->assertEquals(65.0, $compositions[1]->getPercentage());
    }

    protected function getFacetManager($filename)
    {
        $jsonObject = $this->getJsonObject($filename);
        if (isset($jsonObject[0]->facets->facet)) {
            $jsonFacets = $jsonObject[0]->facets->facet;
        } else {
            $jsonFacets = $jsonObject[0]->facet;
        }
        $facets = array();
        foreach ($jsonFacets as $jsonFacet) {
            $facet = Facet::createFromJson($jsonFacet);
            $facets[] = $facet;
        }

        $facetManager = new StaticFacetManager($facets);

        return $facetManager;
    }

    private function getProduct()
    {
        $json = json_decode('{"id":1,"name":"Product"}');
        return Product::createFromJson($json, $this->getModelFactory(), 1);
    }
}
