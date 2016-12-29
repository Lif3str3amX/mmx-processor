<?php

class Mmx_Processor_Model_BtTest extends PHPUnit_Framework_TestCase {

    /**
     *
     * @var Mmx_Processor_Model_Bt
     */
    protected $model;
    
    /**
     *
     * @var Mage_Sales_Model_Order
     */
    protected $order;

    public function setUp() {

        $this->order = Mage::getModel('sales/order')->load(1036);
        $this->model = new Mmx_Processor_Model_Bt();
    }

    public function testSetGetOrder() {
        $this->model->setOrder($this->order);
        $this->assertEquals(1036, $this->model->getOrder()->getId());
    }
    
}
