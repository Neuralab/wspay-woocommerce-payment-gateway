<?php

/**
 * Woocommerce mock functions.
 */
function is_woocommerce_active() {
   return true;
}

function woothemes_queue_update($file, $file_id, $product_id) {
   return true;
}

function wcwspay_is_woocommerce_active() {
  return true;
}

/**
 * Call protected/private method of a class.
 *
 * @param object &$object    Instantiated object that we will run method on.
 * @param string $methodName Method name to call
 * @param array  $parameters Array of parameters to pass into method.
 * @return mixed
 */
function invokePrivateMethod( &$object, $methodName, array $parameters = array() ) {
  $reflection = new ReflectionClass(get_class($object));
  $method = $reflection->getMethod($methodName);
  $method->setAccessible(true);

  return $method->invokeArgs($object, $parameters);
}
