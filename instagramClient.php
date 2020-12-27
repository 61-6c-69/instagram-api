<?php
class instagramClient{
    private $instaSession;
    private $instaInfo;
    private $instaXIDAppId='936619743392459';
    
    //construct
    public function __construct($ins){
        $this->instaSession=$ins;
    }
    //instagram request
    private function inRequest($url,$header=[]){
        $ch=curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_COOKIE, $this->instaSession);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)');
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, TRUE);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }
    //get info user key
    private function getInfoUser($key,$info=''){
        if($info=='')
            $info=$this->instaInfo;
        if(!isset($info->graphql->user->$key))
            return false;
        return $info->graphql->user->$key;
    }
    //get info
    public function getInfo($id){
        $info=@json_decode($this->inRequest("https://www.instagram.com/$id/?__a=1"));
        if(!isset($info->logging_page_id))
            return false;
        $this->instaInfo=$info;
        return $info;
    }
    //get feed reel
    public function getFeedReel($user_id=''){
        if($user_id=='')
            $user_id=$this->getId();
        if($user_id==false)
            return false;
        $header=[
            'x-ig-app-id: '.$this->instaXIDAppId
        ];
        $reel=@json_decode($this->inRequest("https://i.instagram.com/api/v1/feed/reels_media/?reel_ids=$user_id",$header));
        if(!isset($reel->status))
            return false;
        return $reel;
    }
    //get info
    public function getUserInfo($user_id=''){
        if($user_id=='')
            $user_id=$this->getId();
        if($user_id==false)
            return false;
        $header=[
            'x-ig-app-id: '.$this->instaXIDAppId
        ];
        $info=@json_decode($this->inRequest("https://i.instagram.com/api/v1/users/$user_id/info/",$header));
        return $info;
    }
    //get post from shortcode
    //https://www.instagram.com/p/{shortcode}/?__a=1
    public function getPost($short_code){
        if($short_code=='')
            return false;
        $post=@json_decode($this->inRequest("https://www.instagram.com/p/$short_code/?__a=1"));
        if(!isset($post->graphql->shortcode_media))
            return false;
        return $post;
    }
    //get id
    public function getId($i=''){
        return $this->getInfoUser('id',$i);
    }
    //get private
    public function isPrivate($i=''){
        return $this->getInfoUser('is_private',$i);
    }
    //get verified
    public function isVerified($i=''){
        return $this->getInfoUser('is_verified',$i);
    }
    //get bussiness acount
    public function isBussiness($i=''){
        return $this->getInfoUser('is_bussiness',$i);
    }
    //get full name
    public function getFullName($i=''){
        return $this->getInfoUser('full_name',$i);
    }
    //get pic profile
    public function getPicProfile($i=''){
        return $this->getInfoUser('profile_pic_url',$i);
    }
    //get pic profile hd
    public function getPicProfileHD($i=''){
        return $this->getInfoUser('profile_pic_url_hd',$i);
    }
    //get following
    public function getFollowing($i=''){
        return @$this->getInfoUser('edge_follow',$i)->count;
    }
    //get following
    public function getFollower($i=''){
        return @$this->getInfoUser('edge_followed_by',$i)->count;
    }
    //get bio
    public function getBio($i=''){
        return @$this->getInfoUser('biography',$i);
    }
    //get stories
    public function getStories($user_id=''){
        $stories=$this->getFeedReel($user_id);
        if($stories==false)
            return false;
        $stories_array=[];
        foreach($stories->reels_media[0]->items as $media){
            $stories_array[]=[
                'id'=>$media->id,
                'pk'=>$media->pk,
                'timestamp'=>$media->device_timestamp,
                'original_width'=>$media->original_width,
                'original_height'=>$media->original_height,
                'type'=>$media->media_type,
                'img'=>@$media->image_versions2->candidates,
                'video'=>@$media->video_versions,
                'video_duration'=>@$media->video_duration
            ];
        }
        return $stories_array;
    }
    //get post picture
    public function getPostMedia($short_code){
        $post=$this->getPost($short_code);
        $media=[
            'type_name'=>$post->graphql->shortcode_media->__typename,
            'id'=>$post->graphql->shortcode_media->id,
            'is_video'=>$post->graphql->shortcode_media->is_video,
            'caption'=>$post->graphql->shortcode_media->edge_media_to_caption->edges[0]->node->text,
            'comment_count'=>$post->graphql->shortcode_media->edge_media_to_parent_comment->count,
            'like'=>$post->graphql->shortcode_media->edge_media_preview_like->count,
            'location'=>$post->graphql->shortcode_media->location,
            'username'=>$post->graphql->shortcode_media->owner->username,
            'media'=>[]
        ];

        if(isset($post->graphql->shortcode_media->edge_sidecar_to_children->edges)){
            foreach($post->graphql->shortcode_media->edge_sidecar_to_children->edges as $m){
                $media['media'][]=[
                    'type_name'=>$m->node->__typename,
                    'id'=>$m->node->id,
                    'shortcode'=>$m->node->shortcode,
                    'size'=>[
                        'width'=>$m->node->dimensions->width,
                        'height'=>$m->node->dimensions->height
                    ],
                    'resource'=>$m->node->display_resources,
                    'is_video'=>$m->node->is_video,
                    'video_url'=>@$m->node->video_url
                ];
            }
        }else{
            $media['media'][]=[
                    'type_name'=>$media['type_name'],
                    'id'=>$media['id'],
                    'shortcode'=>$post->graphql->shortcode_media->shortcode,
                    'size'=>[
                        'width'=>$post->graphql->shortcode_media->dimensions->width,
                        'height'=>$post->graphql->shortcode_media->dimensions->height
                    ],
                    'resource'=>$post->graphql->shortcode_media->display_resources,
                    'is_video'=>$post->graphql->shortcode_media->is_video,
                    'video_url'=>@$post->graphql->shortcode_media->video_url
                ];
        }
        return $media;
    }
    //test login
    public function testLogin(){
        if(!isset(json_decode($this->inRequest('https://www.instagram.com/eminem/?__a=1'))->logging_page_id))
            return false;
        return true;
    }
}














