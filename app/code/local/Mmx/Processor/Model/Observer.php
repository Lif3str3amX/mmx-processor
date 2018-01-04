<?php

class Mmx_Processor_Model_Observer {

    /**
     * Triggered by sales_order_place_after event
     *
     * @param Varien_Event_Observer $observer
     * @return boolean
     */
    public function processOrder(Varien_Event_Observer $observer) {
        $order = $observer->getEvent()->getOrder();

        Mage::log('Mmx_Processor_Model_Observer::processOrder() observed an event with order_id: ' . $order->getId());

        Mage::getModel('mmx_processor/bt')
                ->setOrder($order)
                ->processOrder();

        Mage::getModel('mmx_processor/indigo')
                ->setOrder($order)
                ->processOrder();

        Mage::getModel('mmx_processor/huawei')
            ->setOrder($order)
            ->processOrder();

        return true;
    }

    /**
     * Triggered by order_cancel_after event
     *
     * @param Varien_Event_Observer $observer
     * @return boolean
     */
    public function cancelOrder(Varien_Event_Observer $observer) {
        $order = $observer->getEvent()->getOrder();

        Mage::log('Mmx_Processor_Model_Observer::cancelOrder() observed an event with order_id: ' . $order->getId());

        Mage::getModel('mmx_processor/common')
                ->setOrder($order)
                ->cancelOrder();

        return true;
    }

}
