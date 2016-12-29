<?php

class Mmx_Processor_Model_CommonTest extends PHPUnit_Framework_TestCase {

    /**
     *
     * @var Mmx_Processor_Model_Common
     */
    protected $model;
    
    /**
     *
     * @var Mage_Sales_Model_Order
     */
    protected $order;

    public function setUp() {

        $this->order = Mage::getModel('sales/order')->load(1036);
        $this->model = new Mmx_Processor_Model_Common();
    }

    public function testSetGetOrder() {
        $this->model->setOrder($this->order);
        $this->assertEquals(1036, $this->model->getOrder()->getId());
    }
    
}
