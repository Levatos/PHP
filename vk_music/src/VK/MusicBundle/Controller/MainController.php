<?php
namespace VK\MusicBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use GetId3\GetId3Core as GetId3;

$request = Request::createFromGlobals();

class MainController extends Controller
{
    public $vk_token = '103ed0dd4bf8b18ee0a66176364b27afbdc15296817bf7dc385b03a1ca367bb766a34f4f44225e13c549c';
    public $vk_api_url = 'https://api.vk.com/method/';

    public function indexAction()
    {
        return $this->render('VKMusicBundle:Main:index.html.twig', array('players_html_array' => 'Default page'));
    }

    public function generateRandomString($length = 10){
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $randomString;
    }

    public function remoteFileSize($url) {
        $fp = fopen($url, "r");
        $inf = stream_get_meta_data($fp);
        fclose($fp);

        foreach($inf["wrapper_data"] as $v)
            if (stristr($v, "content-length")) {
                $v = explode(":", $v);
                return trim($v[1]);
        }
    }

    public function searchAction(Request $request) {
        $song_query = urlencode($request->request->get('query'));
        $query_result = file_get_contents($this->vk_api_url . "audio.search?q=".$song_query."&auto_complete=1&access_token=".$this->vk_token);
        $query_result = json_decode($query_result);

        $players_html_array = array();

        $players_counter = 0;
        foreach ($query_result->response as $song_number => $song_data) {
            if (is_object($song_data)) {
                $players_html_array[$players_counter]['song_aid'] = $song_data->aid;
                $players_html_array[$players_counter]['song_owner_id'] = $song_data->owner_id;
                $players_html_array[$players_counter]['song_url'] = $song_data->url;
                $players_html_array[$players_counter]['song_title'] = $song_data->title;
                $players_html_array[$players_counter]['song_artist'] =  $song_data->artist;
                $players_html_array[$players_counter]['song_duration'] = date('i:s', $song_data->duration);

                $players_counter++;
            }
        }

        return $this->render('VKMusicBundle:Main:search_result.html.twig', array('players_html_array' => $players_html_array));
    }

    public function detectBitrateAction(Request $request) {
        $remote_file = @file_get_contents($request->request->get('song_url'));
        $local_file = '/htdocs/app/cache/prod/'.$this->generateRandomString().'_'.time().'.mp3';

        $fp = fopen($local_file, "w");
        fputs($fp, $remote_file);
        fclose($fp);

        $getID3 = new getID3;
        $song_info = $getID3->analyze($local_file);
        $bitrate = @$song_info['audio']['bitrate'];

        $filesize = @filesize($local_file);

        @unlink($local_file);

        return new Response('<b>'.@round($filesize / 1024 / 1024, 2).' mb, '.@round($bitrate / 1000).' kb/s</b>');
    }

    public function downloadAction($data) {
        $audio_data = file_get_contents($this->vk_api_url . "audio.getById?audios=".$data."&access_token=".$this->vk_token);
        $audio_data = json_decode($audio_data);

        header('Content-Type: audio/mpeg');
        header('Content-Disposition: attachment; filename="'.$audio_data->response[0]->artist.' - '.$audio_data->response[0]->title.'.mp3"');
        header('Content-Length: '.$this->remoteFileSize($audio_data->response[0]->url));
        header("Content-Transfer-Encoding: binary"); 
        header("Content-Encoding: none");

        readfile($audio_data->response[0]->url);

        exit();
    }
}