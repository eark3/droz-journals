<?php

class OJSTest extends ProcessExecutor {
    
    public function execute($parameters = []) {
        $products = [];
        foreach (OJS::entries() as $entry) {
            $product = OJS::product($entry);
            if ($product !== 'noissue') {
                echo $product['reference'].'/'.$product['chapter']."\n";
                $products[] = $product;
            }
        }
        file_put_contents('/tmp/OJS.json', Zord::json_encode($products));
    }
    
}

?>