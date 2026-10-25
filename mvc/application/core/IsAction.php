<?php

trait IsAction
{

    public function _remap($method, $params = array()) {
        if(!method_exists($this, $method)) {
            $defaultMethod = method_exists($this, 'run') ? 'run' : 'index';
            if (empty($params)) {
                return $this->$defaultMethod($method);
            }else {
                return call_user_func_array([$this, $defaultMethod], [$method] + $params);
            }
        }

        return call_user_func_array([$this, $method], $params);
    }
}
