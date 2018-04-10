<?php
class Coinmarketcap {
    var $global;
    var $quick_search;
    public function __construct() {
        $this->quick_search = $this->getQuickSearch();
        $this->global = $this->getGlobal();
    }
    public function ticker() {
        $convert = '&convert=' . get_field('coin_convert', 'coin_options');
        $ticker = wp_remote_get('https://api.coinmarketcap.com/v1/ticker/?limit=0' . $convert);
        if (!is_wp_error($ticker)) {
            $this->processing($ticker);
        }
    }
    private function processing($ticker) {
        $data = json_decode($this->getBody($response = $ticker)); // Production
        //$data = json_decode( file_get_contents(__DIR__."/test-response.json") ); // Testing
        if (empty($data)) {
            return false;
        }
        $this->updateOptions($data[0]);
        foreach ($data as $coin) {
            $post = $this->findPost($meta_key = 'coin_id', $meta_value = $coin->id);
            if (empty($post)) {
                $this->createPost($coin);
            } else {
                $this->updatePostMeta($post, $coin);
            }
        }
    }
    private function getGlobal() {
        $this->global = wp_remote_get('https://s2.coinmarketcap.com/generated/stats/global.json');
        $this->global = $this->getBody($response = $this->global);
        return $this->global;
    }
    private function getBody($response) {
        return wp_remote_retrieve_body($response);
    }
    private function findPost($meta_key, $meta_value) {
        $args = array('meta_key' => $meta_key, 'meta_value' => $meta_value, 'post_type' => 'coin', 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids');
        $post = get_posts($args);
        if (!empty($post)) {
            return $post[0];
        } else {
            return false;
        }
    }
    private function updatePostMeta($post_id, $coin) {
        $array = get_object_vars($coin);
        foreach ($array as $meta_key => $meta_value) {
            update_field('coin_' . $meta_key, $meta_value, $post_id);
        }
    }
    private function createPost($coin) {
        foreach ($this->quick_search as $item) {
            if ($item['slug'] == $coin->id) {
                $image_url = "https://s2.coinmarketcap.com/static/img/coins/64x64/" . $item['id'] . ".png";
            }
        }
        $post_data = array('post_type' => 'coin', 'post_title' => $coin->name, 'post_status' => 'draft');
        $post_id = wp_insert_post(wp_slash($post_data));
        if (is_wp_error($post_id)) {
            return false;
        }
        $this->generateFeaturedImage($image_url, $post_id);
        $this->updatePostMeta($post_id, $coin);
    }
    private function getQuickSearch() {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://s2.coinmarketcap.com/generated/search/quick_search.json");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        curl_close($ch);
        // Replace quotes that might break the json decode
        $output = str_replace("'", "", $output);
        $outputdecoded = json_decode($output, true);
        if (empty($outputdecoded)) {
            return false;
        }
        return $outputdecoded;
    }
    private function generateFeaturedImage($image_url, $post_id) {
        $upload_dir = wp_upload_dir();
        $image_data = file_get_contents($image_url);
        $filename = basename($image_url);
        if (wp_mkdir_p($upload_dir['path'])) $file = $upload_dir['path'] . '/' . $filename;
        else $file = $upload_dir['basedir'] . '/' . $filename;
        file_put_contents($file, $image_data);
        $wp_filetype = wp_check_filetype($filename, null);
        $attachment = array('post_mime_type' => $wp_filetype['type'], 'post_title' => sanitize_file_name($filename), 'post_content' => '', 'post_status' => 'inherit');
        $attach_id = wp_insert_attachment($attachment, $file, $post_id);
        require_once (ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attach_id, $file);
        $res1 = wp_update_attachment_metadata($attach_id, $attach_data);
        $res2 = set_post_thumbnail($post_id, $attach_id);
    }
    private function updateOptions($coin) {
        $array = get_object_vars($coin);
        $properties = array_keys($array);
        $prefixed_properties = preg_filter('/^/', 'coin_', $properties);
        update_option('coin_meta_keys', $prefixed_properties);
        update_option('coin_global', $this->getGlobal());
    }
}
