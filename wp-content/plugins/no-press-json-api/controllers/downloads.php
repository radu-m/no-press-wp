<?php

/**
 * Created by PhpStorm.
 * User: radu
 * Date: 2/22/16
 * Time: 10:08 AM
 */
class JSON_API_Downloads_Controller
{
    private $json = null;
    private $postData = null;

    private $archivesDir = 'no-press_tmp_down';
    private $retainFields = array('id' => 'integer', 'parent' => 'integer', 'mime_type' => 'string');
    private $cleanReq = array();
    private $disqualified = array();
    private $filesQueue = array();
    private $path = '';
    private $fileName = '';
    private $filePrefix = '';
    private $zipLifetime = 0;

    public function get_zipped()
    {
        $this->json = file_get_contents('php://input');
        $this->postData = json_decode($this->json, true);

        if (!is_array($this->postData)) {
            /**
             * TODO: replace with generic err msg in production
             **/
            echo json_last_error_msg();
            // and don't forget to die() immediately after
            die();
        }

        $this->zipLifetime = 3600 * 24; // 24h
        $this->path = ABSPATH . '/' . $this->archivesDir;
        $this->filePrefix = 'NoPress_';
        $this->fileName = $this->filePrefix . time() . '.zip';

        if (count($this->postData['files']) == 0) {
            return array(
                'data' => array(
                    'code' => 9500,
                    'zip_uri' => null,
                    'missing_files' => null
                )
            );
        }

        foreach ($this->postData['files'] as $field) {
            // cleanup, validation, etc.
            $cleanField = array();
            foreach ($this->retainFields as $k => $expectedType) {
                if (gettype($field[$k]) === $expectedType) {
                    // validate
                    switch ($expectedType) {
                        case 'integer':
                            $cleanField[$k] = $field[$k];
                            break;
                        case 'string':
                            $cleanField[$k] = preg_replace('/[^\s\w-_\/]/', '', trim($field[$k]));
                            break;
                    }
                } else {
                    // this file has a malformed or missing attr, so it counts as disqualified
                    $disqualified[] = $field['id'];
                }
            }
            $this->cleanReq[] = $cleanField;
        }

        foreach ($this->cleanReq as $fRef) {
            // should get full attachment data and do more checking here
            $url = get_attached_file($fRef['id'], false);
            if ($url) {
                $this->filesQueue[] = array('url' => $url, 'title' => basename($url));
            } else {
                $disqualified[] = array('url' => null, 'title' => $fRef['title']);
            }
        }

        if (!file_exists($this->path)) {
            mkdir($this->path, 0777, true);
        }

        $zipOk = $this->create_zip($this->filesQueue, $this->path . '/' . $this->fileName);

        if ($zipOk) {
            $code = 9200;
            $this->zipUri = get_site_url() . '/' . $this->archivesDir . '/' . $this->fileName;

            if (count($this->disqualified) > 0) {
                $code = 9301;
            }

        } else {
            $code = 9404;
        }

        return array(
            'data' => array(
                'code' => $code,
                'zip_url' => $this->zipUri,
                'missing_files' => $this->disqualified
            )
        );
    }

    /* creates a compressed zip file */
    protected function create_zip($files = array(), $destination = '', $overwrite = false)
    {
        $valid_files = array();
        $this->fileName = $this->filePrefix . time() . '.zip';

        if (is_array($files)) {
            foreach ($files as $file) {
                if (file_exists($file['url'])) {
                    $valid_files[] = $file;
                } else {
                    $this->disqualified[] = $file;
                }
            }
        }

        if (count($valid_files)) {
            $zip = new ZipArchive();

            if (!$zip->open($destination, $overwrite ? ZIPARCHIVE::OVERWRITE : ZIPARCHIVE::CREATE)) {
                return false;
            }

            foreach ($valid_files as $file) {
                $zip->addFile($file['url'], $file['title']);
            }

            $zip->close();
            return file_exists($destination);
        } else {
            return false;
        }
    }

    protected function delete_old($maxAge)
    {
        // delete archives older than $maxAge

    }


    private function check_if_file_at_url($url)
    {
        $protocol = is_ssl() ? 'https://' : 'http://'; // uses WP function is_ssl()
//        $robots   = $protocol . $_SERVER['SERVER_NAME'] .'/robots.txt';
        $exists = false;

        if (!$exists && in_array('curl', get_loaded_extensions())) {

            $ch = curl_init($url);

            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_exec($ch);

            $response = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            print_r('___________ ', $url);
            print_r($response);
            print_r($url);
            print_r('___________');

            if ($response === 200) $exists = true;
            curl_close($ch);
        }

        if (!$exists && function_exists('get_headers')) {
            $headers = @get_headers($url);
            if ($headers) {
                if (strpos($headers[0], '404') !== false) {
                    $exists = true;
                }
            }
        }
        return $exists;
    }
}