<?php
//允许跨域
header('Access-Control-Allow-Origin:*');
if(!empty($_POST['image'])){
	$base64_img = trim($_POST['image']);
	//图片位置
	$upload_path = '/uploads/article/' . date('Ym', time()) . '/' . date('d', time());
	if (!file_exists('.' . $upload_path)) {
	    mkdir('.' . $upload_path, 0777, true);
	}
	//筛选图片类型
	if (preg_match('/^(data:\s*image\/(.+);base64,)/', $base64_img, $result)) {
	    $type = $result[2];
	    if (in_array($type, array('pjpeg', 'jpeg', 'jpg', 'gif', 'bmp', 'png'))) {
	    	//TODO: 这儿多图上传会有并发问题，图片名一定要唯一！
	        $img_path = $upload_path . sha1(uniqid(mt_rand(),1)) . '.' . $type;
	        if (file_put_contents('.' . $img_path, base64_decode(str_replace($result[1], '', $base64_img)))) {
	        	//TODO: 这儿可以返回完整的图片地址，自行拼接
	            echo $img_path;
	        } else {
	            echo '图片上传失败';
	        }
	    } else {
	        //文件类型错误
	        echo '图片上传类型错误';
	    }
	} else {
	    echo '文件错误';
	}
}
