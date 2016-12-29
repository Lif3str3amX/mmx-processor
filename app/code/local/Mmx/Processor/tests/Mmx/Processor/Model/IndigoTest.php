<?php

class Mmx_Processor_Model_IndigoTest extends PHPUnit_Framework_TestCase {

    /**
     *
     * @var Mmx_Processor_Model_Indigo
     */
    protected $model;
    
    /**
     *
     * @var Mage_Sales_Model_Order
     */
    protected $order;

    public function setUp() {

        $this->order = Mage::getModel('sales/order')->load(1231);
        $this->model = new Mmx_Processor_Model_Indigo();
    }

    public function testSetGetOrder() {
        $this->model->setOrder($this->order);
        $this->assertEquals(1231, $this->model->getOrder()->getId());
    }
    
}
