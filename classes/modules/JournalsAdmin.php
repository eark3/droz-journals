<?php

class JournalsAdmin extends Admin {
        
    use JournalsModule;
    
    public function settings() {
        $type = $this->params['type'] ?? null;
        $id = $this->params['id'] ?? null;
        $return = $this->params['return'] ?? 'data';
        if (!isset($type) || !isset($id) || !in_array($return, ['data','form']) || !in_array($type, CACHED_OBJECT_TYPES)) {
            return $this->error(400);
        }
        $class = ucfirst($type).'Entity';
        $object = (new $class())->retrieveOne($id);
        if ($object === false) {
            return $this->error(404);
        }
        switch ($return) {
            case 'data': {
                $update = $this->params['update'] ?? null;
                if (!empty($update)) {
                    $update = Zord::objectToArray(json_decode($update));
                    foreach ($update as $name => $value) {
                        if (is_array($value)) {
                            $value = base64_encode(serialize($value));
                        }
                        (new SettingEntity($type))->updateOne([
                            'object' => $object->id,
                            'name'   => $name,
                            'locale' => $this->lang,
                        ], ['value' => $value]);
                    }
                    return true;
                } else {
                    $name = $this->params['name'] ?? null;
                    return $this->_settings($type, $object, $name);
                }
            }
            case 'form': {
                $form = new View(
                    '/portal/page/admin/settings/form',
                    ['type' => $type, 'id' => $object->id, 'action' => $this->baseURL, 'settings' => $this->_settings($type, $object)],
                    $this->controler, 'admin'
                );
                return $form->render();
            }
        }
    }
    
    public function cache() {
        $process = $this->params['process'] ?? null;
        if (!isset($process)) {
            return $this->error(400);
        }
        $cache = Cache::instance();
        switch ($process) {
            case 'clear': {
                foreach (CACHED_OBJECT_TYPES as $type) {
                    $cache->clear($type);
                }
                return true;
            }
            default: {
                return $this->error(400);
            }
        }
    }
    
}

?>