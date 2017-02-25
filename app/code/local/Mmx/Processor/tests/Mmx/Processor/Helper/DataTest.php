<?php

class Mmx_Processor_Helper_DataTest extends PHPUnit_Framework_TestCase {

    /**
     *
     * @var Mmx_Processor_Helper_Data
     */
    protected $helper;
    
    /**
     *
     * @var Mage_Sales_Model_Order
     */
    protected $order;

    public function setUp() {
        
        $this->order = Mage::getModel('sales/order')->load(1321); // mixed order containing Indigo, Inbtreservation and Inciena

        $this->helper = new Mmx_Processor_Helper_Data();
    }

    public function testSplitOrder() {
        
        $newOrder = $this->helper->splitOrder($this->order);
        $this->assertNotEquals(1321, $newOrder->getId());
    }
    
    public function testGetBtIncrementIdBySchemeref() {
        
    }
    
    
}
